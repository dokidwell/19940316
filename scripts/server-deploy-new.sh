#!/bin/bash

# HOHO社区最新完整生产服务器部署脚本
# 版本: 2.0
# 支持: Ubuntu 22.04 LTS
# 包含智能依赖检测和完整部署流程

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# 日志函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_success() {
    echo -e "${PURPLE}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_skip() {
    echo -e "${YELLOW}[SKIP]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_check() {
    echo -e "${CYAN}[CHECK]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 错误处理
handle_error() {
    local exit_code=$?
    local line_number=$1
    log_error "脚本在第 $line_number 行失败，退出码: $exit_code"
    log_error "尝试运行自动修复..."

    # 基础修复
    log_info "执行基础修复..."
    sudo apt update || true
    sudo systemctl restart nginx || true
    sudo systemctl restart php8.4-fpm || true

    exit $exit_code
}

trap 'handle_error $LINENO' ERR

# 服务器配置
SERVER_IP="119.45.242.49"
PROJECT_PATH="/var/www/hoho-new"
DOMAIN="hohopark.com"
DB_NAME="hoho"
DB_USER="hoho"
DB_PASS="Qaw451973@"

echo "================================="
echo "🚀 HOHO社区自动化部署系统 v2.0"
echo "================================="
echo "服务器: $SERVER_IP"
echo "项目路径: $PROJECT_PATH"
echo "域名: $DOMAIN"
echo "开始时间: $(date)"
echo "================================="

log_step "🔍 系统环境检查"

# 检查操作系统
if [ -f /etc/os-release ]; then
    . /etc/os-release
    log_info "操作系统: $PRETTY_NAME"
else
    log_error "无法检测操作系统"
    exit 1
fi

# 检查sudo权限
if ! sudo -n true 2>/dev/null; then
    log_error "需要sudo权限，请确保当前用户有sudo权限"
    exit 1
fi

log_step "📦 系统更新"
log_info "更新软件包列表..."
sudo apt update

log_info "升级系统软件包..."
sudo apt upgrade -y

log_step "🛠️ 基础工具安装"
BASIC_TOOLS="curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release bc"

for tool in $BASIC_TOOLS; do
    if ! command -v $tool &> /dev/null && ! dpkg -l | grep -q "^ii  $tool "; then
        log_info "安装 $tool..."
        sudo apt install -y $tool
    else
        log_skip "$tool 已安装"
    fi
done

log_step "🐘 PHP 8.4 安装配置"

# 检查PHP安装
if command -v php &> /dev/null; then
    PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    log_skip "PHP $PHP_VERSION 已安装"

    # 检查是否是PHP 8.4
    if [[ "$PHP_VERSION" != "8.4" ]]; then
        log_info "当前PHP版本为 $PHP_VERSION，推荐使用PHP 8.4"
    fi

    # 检查并安装PHP扩展
    log_info "检查PHP扩展..."
    PHP_EXTENSIONS="php8.4-fpm php8.4-mysql php8.4-xml php8.4-gd php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-json php8.4-tokenizer php8.4-ctype php8.4-openssl php8.4-redis php8.4-intl php8.4-soap php8.4-xsl php8.4-sqlite3 php8.4-pdo"

    for ext in $PHP_EXTENSIONS; do
        if ! dpkg -l | grep -q "^ii  $ext "; then
            log_info "安装PHP扩展: $ext"
            sudo apt install -y $ext 2>/dev/null || log_skip "$ext 安装失败，继续..."
        else
            log_skip "$ext 已安装"
        fi
    done
else
    log_info "安装PHP 8.4..."
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-gd \
        php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-json \
        php8.4-tokenizer php8.4-ctype php8.4-openssl php8.4-redis php8.4-intl \
        php8.4-soap php8.4-xsl php8.4-sqlite3 php8.4-pdo
fi

log_step "🎵 Composer安装"

if command -v composer &> /dev/null; then
    COMPOSER_VERSION=$(composer --version | head -n1)
    log_skip "Composer已安装: $COMPOSER_VERSION"
else
    log_info "安装Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
    log_success "Composer安装完成"
fi

log_step "🟢 Node.js安装"

if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    log_skip "Node.js已安装: $NODE_VERSION"
else
    log_info "安装Node.js 18..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
    sudo apt install -y nodejs
    log_success "Node.js安装完成"
fi

log_step "🌐 Nginx安装配置"

if command -v nginx &> /dev/null; then
    NGINX_VERSION=$(nginx -v 2>&1)
    log_skip "Nginx已安装: $NGINX_VERSION"
else
    log_info "安装Nginx..."
    sudo apt install -y nginx
    log_success "Nginx安装完成"
fi

log_step "🗄️ MySQL安装配置"

if command -v mysql &> /dev/null; then
    log_skip "MySQL已安装"

    # 检查数据库是否存在
    if mysql -u root -e "USE $DB_NAME;" 2>/dev/null; then
        log_skip "数据库 $DB_NAME 已存在"
    else
        log_info "创建数据库和用户..."
        sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || true
        sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" || true
        sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';" || true
        sudo mysql -e "FLUSH PRIVILEGES;" || true
        log_success "数据库配置完成"
    fi
else
    log_info "安装MySQL..."
    sudo apt install -y mysql-server

    # 配置MySQL
    log_info "配置MySQL数据库..."
    sudo mysql -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    sudo mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    sudo mysql -e "GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
    sudo mysql -e "FLUSH PRIVILEGES;"
    log_success "MySQL安装配置完成"
fi

log_step "🔴 Redis安装"

if command -v redis-server &> /dev/null; then
    log_skip "Redis已安装"
else
    log_info "安装Redis..."
    sudo apt install -y redis-server
    log_success "Redis安装完成"
fi

log_step "📁 项目代码处理"

# 备份现有项目（如果存在）
if [ -d "$PROJECT_PATH" ]; then
    log_info "备份现有项目..."
    sudo cp -r $PROJECT_PATH ${PROJECT_PATH}_backup_$(date +%Y%m%d_%H%M%S) || true

    log_info "更新现有代码..."
    cd $PROJECT_PATH
    sudo git pull origin main || {
        log_error "Git拉取失败，重新克隆..."
        cd /var/www
        sudo rm -rf hoho-new
        sudo git clone https://github.com/dokidwell/hoho-new.git
    }
else
    log_info "克隆代码仓库..."
    sudo mkdir -p /var/www
    cd /var/www
    sudo git clone https://github.com/dokidwell/hoho-new.git
fi

cd $PROJECT_PATH

log_step "🔐 权限设置"
log_info "设置项目权限..."
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH

log_step "📋 依赖安装"
log_info "安装Composer依赖..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

log_step "📁 目录结构创建"
log_info "创建必要目录..."
sudo -u www-data mkdir -p storage/app/public
sudo -u www-data mkdir -p storage/framework/cache
sudo -u www-data mkdir -p storage/framework/sessions
sudo -u www-data mkdir -p storage/framework/views
sudo -u www-data mkdir -p storage/logs
sudo -u www-data mkdir -p bootstrap/cache

log_step "⚙️ 环境配置"
log_info "配置.env文件..."

if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        sudo -u www-data cp .env.production .env
        log_info "使用.env.production模板"
    else
        sudo -u www-data cp .env.example .env
        log_info "使用.env.example模板"
    fi

    # 更新数据库配置
    sudo sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sudo sed -i "s/DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
    sudo sed -i "s/DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
    sudo sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASS/" .env
    sudo sed -i "s/APP_URL=.*/APP_URL=http:\/\/$SERVER_IP/" .env

    log_success "环境文件配置完成"
else
    log_skip ".env文件已存在"
fi

log_step "🔑 应用密钥生成"
if ! grep -q "APP_KEY=base64:" .env; then
    log_info "生成应用密钥..."
    sudo -u www-data php artisan key:generate --force
else
    log_skip "应用密钥已存在"
fi

log_step "🔗 存储链接创建"
if [ ! -L "public/storage" ]; then
    log_info "创建存储链接..."
    sudo -u www-data php artisan storage:link
else
    log_skip "存储链接已存在"
fi

log_step "🗃️ 数据库迁移"
log_info "执行数据库迁移..."
sudo -u www-data php artisan migrate --force

log_step "🌱 数据种子运行"
log_info "运行数据种子..."

# 检查并运行种子文件
if [ -f "database/seeders/TaskSeeder.php" ]; then
    sudo -u www-data php artisan db:seed --class=TaskSeeder --force
    log_success "TaskSeeder执行完成"
fi

if [ -f "database/seeders/ConsumptionScenarioSeeder.php" ]; then
    sudo -u www-data php artisan db:seed --class=ConsumptionScenarioSeeder --force
    log_success "ConsumptionScenarioSeeder执行完成"
fi

log_step "🧹 缓存优化"
log_info "清理和优化缓存..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear

log_info "生成优化缓存..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

log_step "🔧 Nginx配置"

NGINX_CONFIG="/etc/nginx/sites-available/hoho-new"

if [ ! -f "$NGINX_CONFIG" ]; then
    log_info "创建Nginx配置..."

    sudo tee $NGINX_CONFIG > /dev/null << 'NGINX_EOF'
server {
    listen 80;
    server_name 119.45.242.49 hohopark.com www.hohopark.com;
    root /var/www/hoho-new/public;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php index.html;
    charset utf-8;

    # 文件上传限制
    client_max_body_size 100M;

    # 主要位置块
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 静态文件
    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # 安全：隐藏敏感文件
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # 错误页面
    error_page 404 /index.php;
    error_page 500 502 503 504 /50x.html;

    location = /50x.html {
        root /usr/share/nginx/html;
    }
}
NGINX_EOF

    # 启用站点
    sudo ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default

    log_success "Nginx配置创建完成"
else
    log_skip "Nginx配置已存在"
fi

# 测试Nginx配置
log_info "测试Nginx配置..."
if sudo nginx -t; then
    log_success "Nginx配置测试通过"
else
    log_error "Nginx配置测试失败"
    exit 1
fi

log_step "🔧 PHP-FPM配置优化"
log_info "优化PHP-FPM设置..."

# PHP配置优化
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/max_input_vars = .*/max_input_vars = 3000/' /etc/php/8.4/fpm/php.ini

log_step "🔒 最终权限设置"
log_info "设置最终权限..."
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 775 $PROJECT_PATH/storage
sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache

log_step "🚀 服务启动"
log_info "启动和启用服务..."

# 启用服务
sudo systemctl enable nginx
sudo systemctl enable php8.4-fpm
sudo systemctl enable mysql
sudo systemctl enable redis-server

# 启动服务
sudo systemctl start nginx
sudo systemctl start php8.4-fpm
sudo systemctl start mysql
sudo systemctl start redis-server

# 重新加载服务
sudo systemctl reload nginx
sudo systemctl reload php8.4-fpm

log_step "🏥 健康检查"
log_info "执行系统健康检查..."

# 检查服务状态
SERVICES=("nginx" "php8.4-fpm" "mysql" "redis-server")
for service in "${SERVICES[@]}"; do
    if systemctl is-active --quiet $service; then
        log_success "$service 运行正常"
    else
        log_error "$service 运行异常"
    fi
done

# 检查HTTP响应
sleep 3
if curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|302"; then
    log_success "HTTP响应正常"
else
    log_error "HTTP响应异常"
fi

# 检查数据库连接
if sudo -u www-data php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_OK';" 2>/dev/null | grep -q "DB_OK"; then
    log_success "数据库连接正常"
else
    log_error "数据库连接异常"
fi

log_step "📊 部署总结"

echo "================================="
echo "🎉 HOHO社区部署完成！"
echo "================================="
echo "📅 完成时间: $(date)"
echo "🌐 访问地址:"
echo "  - 网站首页: http://$SERVER_IP"
echo "  - 管理面板: http://$SERVER_IP/admin/economic"
echo "  - 任务中心: http://$SERVER_IP/tasks"
echo "  - 消费功能: http://$SERVER_IP/tasks/consumptions"
echo "================================="
echo "👤 默认管理员账户:"
echo "  - 邮箱: admin@hohopark.com"
echo "  - 密码: HohoAdmin@2024"
echo "  ⚠️  请立即登录并修改默认密码！"
echo "================================="
echo "📁 重要路径:"
echo "  - 项目目录: $PROJECT_PATH"
echo "  - 日志文件: $PROJECT_PATH/storage/logs/"
echo "  - Nginx配置: /etc/nginx/sites-available/hoho-new"
echo "================================="
echo "🔧 管理命令:"
echo "  - 查看日志: tail -f $PROJECT_PATH/storage/logs/laravel.log"
echo "  - 重启Nginx: sudo systemctl restart nginx"
echo "  - 重启PHP: sudo systemctl restart php8.4-fpm"
echo "  - 清理缓存: cd $PROJECT_PATH && php artisan cache:clear"
echo "================================="

log_success "🎊 部署流程全部完成！"
log_info "💡 如遇问题，请查看日志文件或运行健康检查脚本"