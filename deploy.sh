#!/bin/bash

# HOHO社区生产环境部署脚本
# 使用方法: ./deploy.sh [环境]
# 环境选项: production, staging

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 打印带颜色的消息
print_message() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')] $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

print_error() {
    echo -e "${RED}[ERROR] $1${NC}"
}

# 检查命令是否存在
check_command() {
    if ! command -v $1 &> /dev/null; then
        print_error "$1 未安装，请先安装 $1"
        exit 1
    fi
}

# 设置环境
ENVIRONMENT=${1:-production}
print_message "开始部署到 $ENVIRONMENT 环境"

# 检查必要的命令
print_message "检查系统依赖..."
check_command "php"
check_command "composer"
check_command "npm"
check_command "mysql"

# 检查PHP版本
PHP_VERSION=$(php -r "echo PHP_VERSION;" | cut -d. -f1,2)
if (( $(echo "$PHP_VERSION < 8.1" | bc -l) )); then
    print_error "PHP版本需要 >= 8.1，当前版本: $PHP_VERSION"
    exit 1
fi

print_message "PHP版本检查通过: $PHP_VERSION"

# 检查PHP扩展
print_message "检查PHP扩展..."
required_extensions=("mbstring" "openssl" "pdo" "tokenizer" "xml" "ctype" "json" "bcmath" "curl" "gd" "zip")

for ext in "${required_extensions[@]}"; do
    if ! php -m | grep -q "$ext"; then
        print_error "缺少PHP扩展: $ext"
        exit 1
    fi
done

print_message "PHP扩展检查通过"

# 设置项目目录
PROJECT_DIR="/var/www/hoho"
BACKUP_DIR="/var/backups/hoho"

# 创建必要的目录
print_message "创建项目目录..."
sudo mkdir -p $PROJECT_DIR
sudo mkdir -p $BACKUP_DIR
sudo mkdir -p /var/log/hoho

# 设置权限
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chown -R www-data:www-data /var/log/hoho

# 检查是否已有部署
if [ -d "$PROJECT_DIR/.git" ]; then
    print_message "检测到现有部署，创建备份..."

    # 创建备份
    BACKUP_NAME="hoho_backup_$(date +%Y%m%d_%H%M%S)"
    sudo cp -r $PROJECT_DIR $BACKUP_DIR/$BACKUP_NAME
    print_message "备份已创建: $BACKUP_DIR/$BACKUP_NAME"

    # 进入项目目录
    cd $PROJECT_DIR

    print_message "拉取最新代码..."
    sudo -u www-data git pull origin main
else
    print_message "首次部署，克隆代码库..."
    sudo -u www-data git clone https://github.com/your-repo/hoho-new.git $PROJECT_DIR
    cd $PROJECT_DIR
fi

# 安装/更新Composer依赖
print_message "安装Composer依赖..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# 安装/更新NPM依赖
print_message "安装NPM依赖..."
sudo -u www-data npm ci --production

# 构建前端资源
print_message "构建前端资源..."
sudo -u www-data npm run build

# 设置环境文件
print_message "配置环境文件..."
if [ ! -f ".env" ]; then
    if [ -f ".env.$ENVIRONMENT" ]; then
        sudo -u www-data cp .env.$ENVIRONMENT .env
        print_message "使用 .env.$ENVIRONMENT 作为环境配置"
    else
        print_warning "未找到 .env.$ENVIRONMENT 文件，请手动配置 .env"
    fi
fi

# 生成应用密钥
if ! grep -q "APP_KEY=base64:" .env; then
    print_message "生成应用密钥..."
    sudo -u www-data php artisan key:generate --force
fi

# 清理和优化缓存
print_message "清理缓存..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear

# 数据库迁移
print_message "执行数据库迁移..."
read -p "是否执行数据库迁移? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    sudo -u www-data php artisan migrate --force
    print_message "数据库迁移完成"
else
    print_warning "跳过数据库迁移"
fi

# 优化配置
print_message "优化应用配置..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 创建存储链接
print_message "创建存储链接..."
sudo -u www-data php artisan storage:link

# 设置文件权限
print_message "设置文件权限..."
sudo find $PROJECT_DIR -type f -exec chmod 644 {} \;
sudo find $PROJECT_DIR -type d -exec chmod 755 {} \;
sudo chmod -R 775 $PROJECT_DIR/storage
sudo chmod -R 775 $PROJECT_DIR/bootstrap/cache
sudo chmod 755 $PROJECT_DIR/artisan

# 设置定时任务
print_message "设置定时任务..."
CRON_JOB="* * * * * cd $PROJECT_DIR && php artisan schedule:run >> /var/log/hoho/cron.log 2>&1"

if ! sudo crontab -u www-data -l 2>/dev/null | grep -q "$PROJECT_DIR"; then
    (sudo crontab -u www-data -l 2>/dev/null; echo "$CRON_JOB") | sudo crontab -u www-data -
    print_message "定时任务已设置"
fi

# 配置Web服务器
print_message "配置Web服务器..."

# Nginx配置
NGINX_CONFIG="/etc/nginx/sites-available/hoho"
if [ ! -f "$NGINX_CONFIG" ]; then
    print_message "创建Nginx配置..."

    sudo tee $NGINX_CONFIG > /dev/null <<EOF
server {
    listen 80;
    listen [::]:80;
    server_name hoho.community www.hoho.community;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;

    server_name hoho.community www.hoho.community;
    root $PROJECT_DIR/public;
    index index.php index.html;

    # SSL配置
    ssl_certificate /etc/ssl/certs/hoho.community.crt;
    ssl_certificate_key /etc/ssl/private/hoho.community.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # 文件上传大小限制
    client_max_body_size 100M;

    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # 超时设置
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静态文件缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # API限流
    location /api/ {
        limit_req zone=api burst=20 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

    sudo ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/
    print_message "Nginx配置已创建"
fi

# 测试Nginx配置
print_message "测试Nginx配置..."
if sudo nginx -t; then
    print_message "Nginx配置测试通过"
else
    print_error "Nginx配置测试失败"
    exit 1
fi

# PHP-FPM配置优化
print_message "优化PHP-FPM配置..."
PHP_FPM_POOL="/etc/php/8.2/fpm/pool.d/hoho.conf"

if [ ! -f "$PHP_FPM_POOL" ]; then
    sudo tee $PHP_FPM_POOL > /dev/null <<EOF
[hoho]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm-hoho.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000

php_admin_value[error_log] = /var/log/hoho/php-fpm-error.log
php_admin_flag[log_errors] = on
php_admin_value[memory_limit] = 256M
php_admin_value[upload_max_filesize] = 100M
php_admin_value[post_max_size] = 100M
php_admin_value[max_execution_time] = 300
EOF

    print_message "PHP-FPM池配置已创建"
fi

# 重启服务
print_message "重启服务..."
sudo systemctl reload php8.2-fpm
sudo systemctl reload nginx

# 健康检查
print_message "执行健康检查..."
sleep 5

if curl -f -s -o /dev/null https://hoho.community/health || curl -f -s -o /dev/null http://localhost; then
    print_message "健康检查通过"
else
    print_warning "健康检查失败，请检查应用状态"
fi

# 设置监控
print_message "设置监控和日志轮转..."

# 日志轮转配置
sudo tee /etc/logrotate.d/hoho > /dev/null <<EOF
/var/log/hoho/*.log {
    daily
    missingok
    rotate 52
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload php8.2-fpm
    endscript
}
EOF

# 完成部署
print_message "部署完成!"
echo
echo -e "${BLUE}=== 部署摘要 ===${NC}"
echo -e "环境: ${GREEN}$ENVIRONMENT${NC}"
echo -e "项目目录: ${GREEN}$PROJECT_DIR${NC}"
echo -e "备份目录: ${GREEN}$BACKUP_DIR${NC}"
echo -e "日志目录: ${GREEN}/var/log/hoho${NC}"
echo

print_message "请执行以下步骤完成配置:"
echo "1. 检查并更新 .env 文件中的配置"
echo "2. 配置SSL证书"
echo "3. 配置数据库连接"
echo "4. 配置鲸探API密钥"
echo "5. 配置腾讯云服务密钥"
echo "6. 运行数据库迁移: php artisan migrate"
echo "7. 创建管理员账户: php artisan make:admin"

print_warning "请确保防火墙允许80和443端口访问"
print_message "部署脚本执行完成!"