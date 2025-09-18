#!/bin/bash

# 生产服务器部署脚本
# 在服务器上执行此脚本

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 服务器配置
SERVER_IP="119.45.242.49"
PROJECT_PATH="/var/www/hoho-new"
DOMAIN="hohopark.com"

log_step "🖥️ 开始生产服务器部署"

# 1. 更新系统
log_info "更新系统包..."
sudo apt update && sudo apt upgrade -y

# 2. 检查PHP版本
log_info "检查PHP版本..."
php --version

# 3. 克隆或更新代码
if [ -d "$PROJECT_PATH" ]; then
    log_info "更新现有代码..."
    cd $PROJECT_PATH
    git pull origin main
else
    log_info "克隆代码仓库..."
    sudo git clone https://github.com/dokidwell/hoho-new.git $PROJECT_PATH
    sudo chown -R www-data:www-data $PROJECT_PATH
fi

cd $PROJECT_PATH

# 4. 安装依赖
log_info "安装Composer依赖..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# 5. 环境配置
log_info "配置环境文件..."
if [ ! -f ".env" ]; then
    sudo -u www-data cp .env.production .env
else
    log_info ".env文件已存在，跳过"
fi

# 6. 生成应用密钥
log_info "生成应用密钥..."
sudo -u www-data php artisan key:generate

# 7. 运行数据库迁移
log_info "运行数据库迁移..."
sudo -u www-data php artisan migrate --force

# 8. 运行数据种子
log_info "初始化基础数据..."
sudo -u www-data php artisan db:seed --class=TaskSeeder --force
sudo -u www-data php artisan db:seed --class=ConsumptionScenarioSeeder --force

# 9. 清理和优化
log_info "清理缓存和优化..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 10. 设置权限
log_info "设置文件权限..."
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 775 $PROJECT_PATH/storage
sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache

# 11. 重启服务
log_info "重启Web服务..."
sudo systemctl reload nginx
sudo systemctl reload php8.4-fpm

log_info "✅ 服务器部署完成！"
log_info "🌐 网站地址: http://$SERVER_IP"
log_info "🌐 域名地址: http://$DOMAIN (如已配置DNS)"