#!/bin/bash

# HOHO社区Ubuntu服务器部署脚本
# 直接复制到服务器使用

set -e

echo "================================="
echo "🚀 HOHO社区部署开始"
echo "================================="

# 基础工具安装
echo "📦 更新系统..."
sudo apt update && sudo apt upgrade -y

echo "🛠️ 安装基础工具..."
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https ca-certificates gnupg lsb-release bc

# PHP检查和扩展安装
echo "🐘 检查PHP..."
if command -v php; then
    echo "PHP已安装: $(php --version | head -n1)"
    echo "安装PHP扩展..."
    sudo apt install -y php8.4-fpm php8.4-mysql php8.4-xml php8.4-gd php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-json php8.4-tokenizer php8.4-ctype php8.4-openssl php8.4-redis php8.4-intl
else
    echo "安装PHP 8.4..."
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml php8.4-gd php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-json php8.4-tokenizer php8.4-ctype php8.4-openssl php8.4-redis php8.4-intl
fi

# Composer安装
echo "🎵 检查Composer..."
if ! command -v composer; then
    echo "安装Composer..."
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# Nginx安装
echo "🌐 检查Nginx..."
if ! command -v nginx; then
    echo "安装Nginx..."
    sudo apt install -y nginx
fi

# MySQL安装
echo "🗄️ 检查MySQL..."
if ! command -v mysql; then
    echo "安装MySQL..."
    sudo apt install -y mysql-server
fi

# 配置MySQL数据库
echo "📊 配置数据库..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS hoho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'hoho'@'localhost' IDENTIFIED BY 'Qaw451973@';"
sudo mysql -e "GRANT ALL PRIVILEGES ON hoho.* TO 'hoho'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# 项目代码
echo "📁 处理项目代码..."
if [ -d "/var/www/hoho-new" ]; then
    echo "更新现有代码..."
    cd /var/www/hoho-new
    sudo git pull origin main
else
    echo "克隆代码仓库..."
    sudo mkdir -p /var/www
    cd /var/www
    sudo git clone https://github.com/dokidwell/hoho-new.git
fi

cd /var/www/hoho-new

# 权限设置
echo "🔐 设置权限..."
sudo chown -R www-data:www-data /var/www/hoho-new
sudo chmod -R 755 /var/www/hoho-new

# 安装依赖
echo "📋 安装依赖..."
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction

# 创建目录
echo "📁 创建目录..."
sudo -u www-data mkdir -p storage/app/public
sudo -u www-data mkdir -p storage/framework/cache
sudo -u www-data mkdir -p storage/framework/sessions
sudo -u www-data mkdir -p storage/framework/views
sudo -u www-data mkdir -p storage/logs
sudo -u www-data mkdir -p bootstrap/cache

# 环境配置
echo "⚙️ 配置环境..."
if [ ! -f ".env" ]; then
    if [ -f ".env.production" ]; then
        sudo -u www-data cp .env.production .env
    else
        sudo -u www-data cp .env.example .env
    fi

    sudo sed -i "s/DB_HOST=.*/DB_HOST=127.0.0.1/" .env
    sudo sed -i "s/DB_DATABASE=.*/DB_DATABASE=hoho/" .env
    sudo sed -i "s/DB_USERNAME=.*/DB_USERNAME=hoho/" .env
    sudo sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=Qaw451973@/" .env
    sudo sed -i "s/APP_URL=.*/APP_URL=http:\/\/119.45.242.49/" .env
fi

# 生成密钥
echo "🔑 生成应用密钥..."
sudo -u www-data php artisan key:generate --force

# 存储链接
echo "🔗 创建存储链接..."
sudo -u www-data php artisan storage:link

# 数据库迁移
echo "🗃️ 数据库迁移..."
sudo -u www-data php artisan migrate --force

# 缓存优化
echo "🧹 缓存优化..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Nginx配置
echo "🔧 配置Nginx..."
sudo cat > /etc/nginx/sites-available/hoho-new << 'NGINXEOF'
server {
    listen 80;
    server_name 119.45.242.49 hohopark.com www.hohopark.com;
    root /var/www/hoho-new/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;
    charset utf-8;
    client_max_body_size 100M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico {
        access_log off;
        log_not_found off;
    }

    location = /robots.txt {
        access_log off;
        log_not_found off;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    error_page 404 /index.php;
}
NGINXEOF

sudo ln -sf /etc/nginx/sites-available/hoho-new /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# 测试Nginx配置
echo "🧪 测试Nginx配置..."
sudo nginx -t

# PHP配置优化
echo "🔧 优化PHP配置..."
sudo sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/post_max_size = .*/post_max_size = 100M/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/max_execution_time = .*/max_execution_time = 300/' /etc/php/8.4/fpm/php.ini
sudo sed -i 's/memory_limit = .*/memory_limit = 256M/' /etc/php/8.4/fpm/php.ini

# 最终权限
echo "🔒 设置最终权限..."
sudo chown -R www-data:www-data /var/www/hoho-new
sudo chmod -R 755 /var/www/hoho-new
sudo chmod -R 775 /var/www/hoho-new/storage
sudo chmod -R 775 /var/www/hoho-new/bootstrap/cache

# 启动服务
echo "🚀 启动服务..."
sudo systemctl enable nginx
sudo systemctl enable php8.4-fpm
sudo systemctl enable mysql

sudo systemctl start nginx
sudo systemctl start php8.4-fpm
sudo systemctl start mysql

sudo systemctl reload nginx
sudo systemctl reload php8.4-fpm

echo "================================="
echo "🎉 HOHO社区部署完成！"
echo "================================="
echo "🌐 网站地址: http://119.45.242.49"
echo "📋 管理面板: http://119.45.242.49/admin/economic"
echo "👤 管理员邮箱: admin@hohopark.com"
echo "🔑 管理员密码: HohoAdmin@2024"
echo "================================="
echo "⚠️  请立即登录并修改默认密码！"
echo "================================="