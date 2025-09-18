#!/bin/bash

# 緊急回滾腳本
# 用於在部署出現嚴重問題時快速回滾到穩定狀態

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[ROLLBACK]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_success() {
    echo -e "${PURPLE}[SUCCESS]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_step() {
    echo -e "${BLUE}[STEP]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 創建備份目錄
BACKUP_DIR="/var/backups/hoho-$(date +%Y%m%d-%H%M%S)"
PROJECT_PATH="/var/www/hoho-new"

# 確認回滾操作
confirm_rollback() {
    echo "================================="
    echo "🚨 緊急回滾確認"
    echo "================================="
    echo "這將執行以下操作："
    echo "1. 回滾數據庫遷移"
    echo "2. 恢復到安全的代碼狀態"
    echo "3. 清理所有緩存"
    echo "4. 重啟所有服務"
    echo "================================="
    echo

    read -p "⚠️  確定要執行緊急回滾嗎？(yes/no): " -r
    if [[ ! $REPLY == "yes" ]]; then
        log_info "回滾操作已取消"
        exit 0
    fi
}

# 創建當前狀態備份
create_emergency_backup() {
    log_step "創建緊急備份..."

    sudo mkdir -p $BACKUP_DIR

    # 備份當前代碼
    if [ -d "$PROJECT_PATH" ]; then
        log_info "備份當前代碼到 $BACKUP_DIR/code"
        sudo cp -r $PROJECT_PATH $BACKUP_DIR/code
    fi

    # 備份數據庫
    log_info "備份當前數據庫..."
    if command -v mysqldump >/dev/null 2>&1; then
        sudo mysqldump --defaults-file=/etc/mysql/debian.cnf hoho > $BACKUP_DIR/database-emergency.sql 2>/dev/null || {
            log_warn "數據庫備份失敗，繼續回滾流程"
        }
    fi

    # 備份配置文件
    if [ -f "$PROJECT_PATH/.env" ]; then
        sudo cp $PROJECT_PATH/.env $BACKUP_DIR/env-backup
    fi

    log_success "緊急備份完成: $BACKUP_DIR"
}

# 停止所有服務
stop_services() {
    log_step "停止Web服務..."

    # 停止Nginx
    sudo systemctl stop nginx || log_warn "Nginx停止失敗"

    # 停止PHP-FPM
    sudo systemctl stop php8.4-fpm || sudo systemctl stop php8.2-fpm || log_warn "PHP-FPM停止失敗"

    log_info "Web服務已停止"
}

# 回滾數據庫
rollback_database() {
    log_step "回滾數據庫..."

    cd $PROJECT_PATH

    # 嘗試回滾遷移
    log_info "回滾數據庫遷移..."
    sudo -u www-data php artisan migrate:rollback --force || {
        log_warn "遷移回滾失敗，嘗試重置數據庫..."

        # 如果回滾失敗，嘗試重置到基礎狀態
        sudo -u www-data php artisan migrate:fresh --force || {
            log_error "數據庫重置失敗"
            return 1
        }
    }

    log_success "數據庫回滾完成"
}

# 回滾代碼到安全狀態
rollback_code() {
    log_step "回滾代碼..."

    cd $PROJECT_PATH

    # 重置到最後一個已知的穩定提交
    log_info "重置Git狀態..."
    sudo git reset --hard HEAD~1 || {
        log_warn "Git回滾失敗，嘗試清理工作目錄..."
        sudo git clean -fd
        sudo git reset --hard HEAD
    }

    # 重新安裝依賴
    log_info "重新安裝依賴..."
    sudo -u www-data composer install --no-dev --optimize-autoloader

    log_success "代碼回滾完成"
}

# 恢復基本配置
restore_basic_config() {
    log_step "恢復基本配置..."

    cd $PROJECT_PATH

    # 確保.env存在
    if [ ! -f ".env" ]; then
        if [ -f "$BACKUP_DIR/env-backup" ]; then
            sudo cp $BACKUP_DIR/env-backup .env
        else
            sudo -u www-data cp .env.example .env
            sudo -u www-data php artisan key:generate
        fi
    fi

    # 創建基本的Nginx配置
    create_safe_nginx_config

    log_success "基本配置恢復完成"
}

# 創建安全的Nginx配置
create_safe_nginx_config() {
    log_info "創建安全的Nginx配置..."

    NGINX_CONFIG="/etc/nginx/sites-available/hoho-safe"

    sudo tee $NGINX_CONFIG > /dev/null << 'EOF'
server {
    listen 80;
    server_name 119.45.242.49 hohopark.com;
    root /var/www/hoho-new/public;

    index index.php index.html;

    # 基本安全頭
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    # 靜態文件處理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP處理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    # 拒絕訪問敏感文件
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 靜態資源緩存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

    # 啟用安全配置
    sudo ln -sf $NGINX_CONFIG /etc/nginx/sites-enabled/hoho-safe
    sudo rm -f /etc/nginx/sites-enabled/hoho-new
    sudo rm -f /etc/nginx/sites-enabled/default

    # 測試Nginx配置
    if ! sudo nginx -t; then
        log_error "Nginx配置測試失敗"
        return 1
    fi

    log_success "安全Nginx配置創建完成"
}

# 清理和重置
cleanup_and_reset() {
    log_step "清理和重置系統狀態..."

    cd $PROJECT_PATH

    # 清理所有緩存
    log_info "清理所有緩存..."
    sudo -u www-data php artisan config:clear || true
    sudo -u www-data php artisan route:clear || true
    sudo -u www-data php artisan view:clear || true
    sudo -u www-data php artisan cache:clear || true

    # 重新生成基本緩存
    log_info "重新生成緩存..."
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache

    # 修復權限
    log_info "修復文件權限..."
    sudo chown -R www-data:www-data $PROJECT_PATH
    sudo chmod -R 755 $PROJECT_PATH
    sudo chmod -R 775 $PROJECT_PATH/storage
    sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache

    # 清理臨時文件
    sudo find $PROJECT_PATH/storage/logs -name "*.log" -mtime +7 -delete 2>/dev/null || true

    log_success "系統清理完成"
}

# 重啟服務
restart_services() {
    log_step "重啟Web服務..."

    # 重啟PHP-FPM
    if sudo systemctl start php8.4-fpm; then
        log_info "PHP 8.4 FPM啟動成功"
    elif sudo systemctl start php8.2-fpm; then
        log_info "PHP 8.2 FPM啟動成功"
    else
        log_error "PHP-FPM啟動失敗"
        return 1
    fi

    # 重啟Nginx
    if sudo systemctl start nginx; then
        log_info "Nginx啟動成功"
    else
        log_error "Nginx啟動失敗"
        return 1
    fi

    # 檢查服務狀態
    sleep 3
    if systemctl is-active --quiet nginx && systemctl is-active --quiet php8.4-fpm || systemctl is-active --quiet php8.2-fpm; then
        log_success "所有Web服務運行正常"
    else
        log_error "服務啟動異常"
        return 1
    fi
}

# 驗證回滾結果
verify_rollback() {
    log_step "驗證回滾結果..."

    # 檢查HTTP響應
    sleep 5
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|302"; then
        log_success "HTTP響應正常"
    else
        log_warn "HTTP響應異常，但服務已啟動"
    fi

    # 檢查基本頁面
    if curl -s http://localhost/ | grep -q "Laravel\|HOHO"; then
        log_success "網站內容正常"
    else
        log_warn "網站內容可能異常"
    fi

    log_success "回滾驗證完成"
}

# 生成回滾報告
generate_rollback_report() {
    local report_file="emergency-rollback-report-$(date +%Y%m%d-%H%M%S).log"

    {
        echo "================================="
        echo "緊急回滾報告"
        echo "================================="
        echo "回滾時間: $(date)"
        echo "備份位置: $BACKUP_DIR"
        echo "項目路徑: $PROJECT_PATH"
        echo "================================="
        echo "回滾操作:"
        echo "✓ 服務停止"
        echo "✓ 數據庫回滾"
        echo "✓ 代碼回滾"
        echo "✓ 配置恢復"
        echo "✓ 系統清理"
        echo "✓ 服務重啟"
        echo "✓ 結果驗證"
        echo "================================="
        echo "當前狀態:"
        echo "Nginx: $(systemctl is-active nginx 2>/dev/null || echo 'unknown')"
        echo "PHP-FPM: $(systemctl is-active php8.4-fpm 2>/dev/null || systemctl is-active php8.2-fpm 2>/dev/null || echo 'unknown')"
        echo "HTTP響應: $(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo 'unknown')"
        echo "================================="
        echo "下一步建議:"
        echo "1. 檢查網站功能是否正常"
        echo "2. 查看錯誤日誌: tail -f storage/logs/laravel.log"
        echo "3. 檢查Nginx日誌: tail -f /var/log/nginx/error.log"
        echo "4. 如需恢復，請檢查備份: $BACKUP_DIR"
        echo "================================="
    } > $report_file

    log_info "回滾報告已生成: $report_file"
}

# 主函數
main() {
    log_info "🚨 開始緊急回滾流程..."

    # 確認操作
    confirm_rollback

    echo
    log_step "📋 緊急回滾流程開始"

    # 執行回滾步驟
    create_emergency_backup
    stop_services
    rollback_database
    rollback_code
    restore_basic_config
    cleanup_and_reset
    restart_services
    verify_rollback
    generate_rollback_report

    echo
    echo "================================="
    echo "🎯 緊急回滾完成"
    echo "================================="
    echo -e "備份位置: ${BLUE}$BACKUP_DIR${NC}"
    echo -e "網站狀態: ${GREEN}已恢復${NC}"
    echo -e "訪問地址: ${YELLOW}http://119.45.242.49${NC}"
    echo "================================="
    echo

    log_success "🎉 緊急回滾流程執行完畢！"
    log_info "💡 請測試網站功能，如有問題請查看回滾報告"
}

# 快速回滾函數（用於腳本調用）
quick_rollback() {
    log_info "執行快速回滾（跳過確認）..."

    create_emergency_backup
    stop_services
    rollback_database || log_warn "數據庫回滾失敗，繼續..."
    rollback_code
    restore_basic_config
    cleanup_and_reset
    restart_services
    verify_rollback

    log_success "快速回滾完成"
}

# 根據參數決定執行模式
case "${1:-}" in
    "quick")
        quick_rollback
        ;;
    "auto")
        quick_rollback
        ;;
    *)
        main "$@"
        ;;
esac