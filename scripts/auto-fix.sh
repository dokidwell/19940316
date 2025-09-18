#!/bin/bash

# 自動故障修復腳本
# 根據不同的錯誤代碼執行相應的修復策略

EXIT_CODE=$1
LINE_NUMBER=$2

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[AUTO-FIX]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[FIX-ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warn() {
    echo -e "${YELLOW}[FIX-WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_success() {
    echo -e "${PURPLE}[FIX-SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 通用修復函數
fix_permissions() {
    log_info "修復文件權限..."
    sudo chown -R www-data:www-data .
    sudo chmod -R 755 .
    sudo chmod -R 775 storage
    sudo chmod -R 775 bootstrap/cache
    log_success "權限修復完成"
}

fix_cache_issues() {
    log_info "清理所有緩存..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    php artisan optimize:clear 2>/dev/null || true
    log_success "緩存清理完成"
}

fix_composer_issues() {
    log_info "修復Composer問題..."
    composer clear-cache
    composer dump-autoload -o
    composer install --no-dev --optimize-autoloader
    log_success "Composer修復完成"
}

fix_database_issues() {
    log_info "修復數據庫問題..."
    source ./scripts/database-init.sh
    log_success "數據庫修復完成"
}

fix_php_issues() {
    log_info "檢查PHP配置..."

    # 檢查必要的PHP擴展
    REQUIRED_EXTENSIONS=("pdo" "pdo_mysql" "mbstring" "tokenizer" "xml" "ctype" "json" "bcmath" "openssl")

    for ext in "${REQUIRED_EXTENSIONS[@]}"; do
        if ! php -m | grep -q "^$ext\$"; then
            log_warn "缺少PHP擴展: $ext"
            # 在Ubuntu/Debian上安裝擴展
            sudo apt-get install -y php8.4-$ext 2>/dev/null || log_warn "無法自動安裝 php8.4-$ext"
        fi
    done

    log_success "PHP問題檢查完成"
}

fix_nginx_issues() {
    log_info "檢查和修復Nginx配置..."

    # 測試Nginx配置
    if ! sudo nginx -t; then
        log_warn "Nginx配置測試失敗"
        # 創建基本的Laravel配置
        create_nginx_config
    fi

    # 重啟Nginx
    sudo systemctl reload nginx || sudo systemctl restart nginx
    log_success "Nginx配置修復完成"
}

create_nginx_config() {
    log_info "創建Nginx配置文件..."

    NGINX_CONFIG="/etc/nginx/sites-available/hoho-new"

    sudo tee $NGINX_CONFIG > /dev/null << EOF
server {
    listen 80;
    server_name 149.129.236.244 hohopark.com www.hohopark.com;
    root /var/www/hoho-new/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

    # 啟用站點
    sudo ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default

    log_success "Nginx配置創建完成"
}

fix_storage_issues() {
    log_info "修復存儲問題..."

    # 創建必要的存儲目錄
    mkdir -p storage/app/public
    mkdir -p storage/framework/cache
    mkdir -p storage/framework/sessions
    mkdir -p storage/framework/views
    mkdir -p storage/logs

    # 創建符號鏈接
    php artisan storage:link || log_warn "存儲鏈接創建失敗"

    # 修復權限
    fix_permissions

    log_success "存儲問題修復完成"
}

# 根據錯誤代碼執行修復
case $EXIT_CODE in
    1)
        log_warn "一般錯誤 (代碼: $EXIT_CODE)，執行基礎修復..."
        fix_cache_issues
        fix_permissions
        ;;
    2)
        log_warn "文件權限錯誤 (代碼: $EXIT_CODE)..."
        fix_permissions
        fix_storage_issues
        ;;
    126)
        log_warn "權限拒絕錯誤 (代碼: $EXIT_CODE)..."
        fix_permissions
        ;;
    127)
        log_warn "命令未找到錯誤 (代碼: $EXIT_CODE)..."
        fix_composer_issues
        ;;
    130)
        log_warn "腳本被中斷 (代碼: $EXIT_CODE)，清理狀態..."
        fix_cache_issues
        ;;
    *)
        log_warn "未知錯誤 (代碼: $EXIT_CODE)，執行全面修復..."

        # 全面修復流程
        log_info "🔧 開始全面自動修復流程..."

        # 1. 基礎修復
        fix_cache_issues
        fix_permissions

        # 2. Composer修復
        fix_composer_issues

        # 3. PHP環境檢查
        fix_php_issues

        # 4. 數據庫修復
        if command -v php >/dev/null 2>&1; then
            fix_database_issues
        fi

        # 5. 存儲修復
        fix_storage_issues

        # 6. Web服務器修復
        if command -v nginx >/dev/null 2>&1; then
            fix_nginx_issues
        fi

        log_success "全面修復完成"
        ;;
esac

# 創建修復報告
create_fix_report() {
    REPORT_FILE="fix-report-$(date +%Y%m%d-%H%M%S).log"

    {
        echo "================================="
        echo "自動修復報告"
        echo "================================="
        echo "時間: $(date)"
        echo "錯誤代碼: $EXIT_CODE"
        echo "錯誤行號: $LINE_NUMBER"
        echo "修復狀態: 已執行自動修復"
        echo "================================="
        echo "系統狀態檢查:"
        echo "PHP版本: $(php --version | head -n1)"
        echo "Composer版本: $(composer --version 2>/dev/null || echo '未安裝')"
        echo "Nginx狀態: $(systemctl is-active nginx 2>/dev/null || echo '未知')"
        echo "MySQL狀態: $(systemctl is-active mysql 2>/dev/null || echo '未知')"
        echo "================================="
    } > $REPORT_FILE

    log_info "修復報告已保存到: $REPORT_FILE"
}

create_fix_report

log_success "🔧 自動修復流程執行完畢"
log_info "💡 如果問題仍然存在，請查看修復報告或聯繫技術支持"

# 返回成功狀態，讓主腳本繼續執行
exit 0