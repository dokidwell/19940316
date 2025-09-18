# HOHO社区生产环境部署指南

## 概述

本文档提供HOHO社区项目的完整生产环境部署指南，包括服务器配置、应用部署、安全设置和监控配置。

## 系统要求

### 硬件要求
- CPU: 2核心以上
- 内存: 4GB以上
- 存储: 20GB以上可用空间
- 网络: 稳定的互联网连接

### 软件要求
- 操作系统: Ubuntu 20.04 LTS 或 CentOS 8+
- PHP: 8.4或更高版本
- MySQL: 8.0或更高版本
- Nginx: 1.18或更高版本
- Redis: 6.0或更高版本
- Node.js: 18.0或更高版本

### PHP扩展要求
```bash
php-fpm php-mysql php-xml php-gd php-curl php-mbstring
php-zip php-bcmath php-json php-tokenizer php-ctype
php-openssl php-pdo php-redis
```

## 服务器信息

根据提供的生产环境信息：

- **服务器IP**: 149.129.236.244
- **域名**: hoho.community
- **数据库**: MySQL
- **数据库名**: hoho
- **数据库用户**: hoho
- **数据库密码**: Qaw451973@

## 部署步骤

### 1. 服务器初始化

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装基础软件
sudo apt install -y curl wget git unzip software-properties-common

# 添加PHP仓库
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# 安装PHP 8.4
sudo apt install -y php8.4 php8.4-fpm php8.4-mysql php8.4-xml php8.4-gd \
    php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-json \
    php8.4-tokenizer php8.4-ctype php8.4-openssl php8.4-redis

# 安装Nginx
sudo apt install -y nginx

# 安装MySQL
sudo apt install -y mysql-server

# 安装Redis
sudo apt install -y redis-server

# 安装Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# 安装Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. 数据库配置

```bash
# 登录MySQL
sudo mysql -u root -p

# 创建数据库和用户
CREATE DATABASE hoho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hoho'@'localhost' IDENTIFIED BY 'Qaw451973@';
GRANT ALL PRIVILEGES ON hoho.* TO 'hoho'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. 安全配置

```bash
# 配置防火墙
sudo ufw enable
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 配置SSH安全
sudo sed -i 's/#PermitRootLogin yes/PermitRootLogin no/' /etc/ssh/sshd_config
sudo systemctl restart ssh
```

### 4. SSL证书配置

```bash
# 安装Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取SSL证书
sudo certbot --nginx -d hoho.community -d www.hoho.community

# 设置自动续期
sudo crontab -e
# 添加以下行:
0 12 * * * /usr/bin/certbot renew --quiet
```

### 5. 应用部署

使用提供的部署脚本：

```bash
# 下载项目代码
git clone https://github.com/your-repo/hoho-new.git /tmp/hoho-new
cd /tmp/hoho-new

# 运行部署脚本
sudo ./deploy.sh production
```

或手动部署：

```bash
# 创建项目目录
sudo mkdir -p /var/www/hoho
sudo chown -R www-data:www-data /var/www/hoho

# 克隆代码
sudo -u www-data git clone https://github.com/your-repo/hoho-new.git /var/www/hoho
cd /var/www/hoho

# 安装依赖
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci --production

# 构建前端资源
sudo -u www-data npm run build

# 配置环境文件
sudo -u www-data cp .env.production .env
sudo -u www-data php artisan key:generate

# 设置权限
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache

# 创建存储链接
sudo -u www-data php artisan storage:link

# 缓存配置
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
```

### 6. 数据库迁移

```bash
cd /var/www/hoho

# 执行迁移
sudo -u www-data php artisan migrate --force

# 创建管理员账户
sudo -u www-data php artisan tinker
# 在tinker中执行:
$user = new App\Models\User();
$user->hoho_id = 'H00000001';
$user->name = 'Admin';
$user->email = 'admin@hoho.community';
$user->password = bcrypt('your-admin-password');
$user->is_active = true;
$user->points_balance = 10000.00000000;
$user->save();
```

## 配置文件详解

### 环境变量配置

复制 `.env.production` 文件并根据实际情况修改以下配置：

#### 1. 鲸探API配置
```env
WHALE_APP_ID=your-whale-app-id
WHALE_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
your-private-key-content
-----END RSA PRIVATE KEY-----"
WHALE_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
your-public-key-content
-----END PUBLIC KEY-----"
```

#### 2. 腾讯云COS配置
```env
TENCENT_COS_SECRET_ID=your-secret-id
TENCENT_COS_SECRET_KEY=your-secret-key
TENCENT_COS_BUCKET=hoho-storage
```

#### 3. 腾讯云短信配置
```env
TENCENT_SMS_SECRET_ID=your-sms-secret-id
TENCENT_SMS_SECRET_KEY=your-sms-secret-key
TENCENT_SMS_SDK_APP_ID=your-sdk-app-id
```

### Nginx配置

部署脚本会自动创建Nginx配置文件，位于：`/etc/nginx/sites-available/hoho`

关键配置说明：
- 自动HTTPS重定向
- 文件上传限制：100MB
- Gzip压缩
- 安全头设置
- API限流配置

### PHP-FPM配置

优化的PHP-FPM池配置，位于：`/etc/php/8.4/fpm/pool.d/hoho.conf`

关键配置：
- 内存限制：256M
- 文件上传限制：100M
- 执行时间限制：300秒
- 进程管理：动态，最多50个子进程

## 监控和维护

### 1. 日志管理

日志文件位置：
- 应用日志：`/var/www/hoho/storage/logs/`
- PHP-FPM日志：`/var/log/hoho/php-fpm-error.log`
- Nginx日志：`/var/log/nginx/`
- 定时任务日志：`/var/log/hoho/cron.log`

### 2. 性能监控

```bash
# 查看系统资源使用情况
htop

# 查看Nginx状态
sudo systemctl status nginx

# 查看PHP-FPM状态
sudo systemctl status php8.4-fpm

# 查看MySQL状态
sudo systemctl status mysql

# 查看Redis状态
sudo systemctl status redis
```

### 3. 备份策略

```bash
# 数据库备份脚本
#!/bin/bash
BACKUP_DIR="/var/backups/hoho"
mkdir -p $BACKUP_DIR
mysqldump -u hoho -p'Qaw451973@' hoho > $BACKUP_DIR/hoho_$(date +%Y%m%d_%H%M%S).sql

# 文件备份
tar -czf $BACKUP_DIR/files_$(date +%Y%m%d_%H%M%S).tar.gz /var/www/hoho/storage

# 保留30天的备份
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

设置定时备份：
```bash
sudo crontab -e
# 添加每日2点备份
0 2 * * * /path/to/backup.sh
```

### 4. 安全更新

```bash
# 系统更新
sudo apt update && sudo apt upgrade -y

# 应用更新
cd /var/www/hoho
sudo -u www-data git pull origin main
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci --production
sudo -u www-data npm run build
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```

## 故障排除

### 常见问题

1. **500错误**
   - 检查错误日志：`tail -f /var/www/hoho/storage/logs/laravel.log`
   - 检查文件权限：`sudo chown -R www-data:www-data /var/www/hoho`

2. **数据库连接失败**
   - 检查MySQL服务：`sudo systemctl status mysql`
   - 验证数据库凭据：`mysql -u hoho -p'Qaw451973@' hoho`

3. **鲸探API调用失败**
   - 检查API密钥配置
   - 验证网络连接
   - 查看API调用日志

4. **文件上传失败**
   - 检查腾讯云COS配置
   - 验证存储权限
   - 检查文件大小限制

### 日志查看命令

```bash
# 实时查看应用日志
tail -f /var/www/hoho/storage/logs/laravel.log

# 查看Nginx错误日志
tail -f /var/log/nginx/error.log

# 查看PHP-FPM日志
tail -f /var/log/hoho/php-fpm-error.log

# 查看系统日志
journalctl -f -u nginx
journalctl -f -u php8.4-fpm
```

## 性能优化

### 1. 数据库优化

```sql
-- MySQL配置优化 /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
max_connections = 200
query_cache_type = 1
query_cache_size = 128M
```

### 2. Redis配置

```conf
# /etc/redis/redis.conf
maxmemory 512mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
```

### 3. 应用优化

```bash
# 启用OPcache
sudo nano /etc/php/8.4/fpm/php.ini

# 添加或修改以下配置：
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
opcache.fast_shutdown=1
```

## 联系支持

如有部署问题，请提供以下信息：
- 服务器系统版本
- 错误日志内容
- 配置文件内容（隐藏敏感信息）
- 重现步骤

部署完成后，请访问 https://hoho.community 验证部署是否成功。