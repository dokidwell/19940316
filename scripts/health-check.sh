#!/bin/bash

# 系統健康檢查和驗證腳本
# 用於部署後驗證所有功能是否正常工作

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[HEALTH]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
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

log_check() {
    echo -e "${CYAN}[CHECK]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 全局結果統計
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0

# 結果記錄函數
record_check() {
    local result=$1
    local description=$2

    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))

    if [ "$result" -eq 0 ]; then
        PASSED_CHECKS=$((PASSED_CHECKS + 1))
        log_success "✅ $description"
    else
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        log_error "❌ $description"
    fi
}

# 1. 基礎環境檢查
check_basic_environment() {
    log_check "🔍 基礎環境檢查"

    # PHP版本檢查
    if php -v >/dev/null 2>&1; then
        PHP_VERSION=$(php -v | head -n1 | cut -d' ' -f2 | cut -d'.' -f1,2)
        if (( $(echo "$PHP_VERSION >= 8.4" | bc -l) )); then
            record_check 0 "PHP版本檢查 (當前: $PHP_VERSION)"
        else
            record_check 1 "PHP版本檢查 (當前: $PHP_VERSION, 建議: 8.4+)"
        fi
    else
        record_check 1 "PHP安裝檢查"
    fi

    # Composer檢查
    if composer --version >/dev/null 2>&1; then
        record_check 0 "Composer安裝檢查"
    else
        record_check 1 "Composer安裝檢查"
    fi

    # 必要目錄檢查
    local directories=("storage" "bootstrap/cache" "vendor" "public")
    for dir in "${directories[@]}"; do
        if [ -d "$dir" ]; then
            record_check 0 "目錄存在: $dir"
        else
            record_check 1 "目錄存在: $dir"
        fi
    done

    # 權限檢查
    if [ -w "storage" ] && [ -w "bootstrap/cache" ]; then
        record_check 0 "目錄權限檢查"
    else
        record_check 1 "目錄權限檢查"
    fi
}

# 2. Laravel應用檢查
check_laravel_application() {
    log_check "🔍 Laravel應用檢查"

    # .env文件檢查
    if [ -f ".env" ]; then
        record_check 0 ".env文件存在"

        # APP_KEY檢查
        if grep -q "APP_KEY=base64:" .env; then
            record_check 0 "APP_KEY已生成"
        else
            record_check 1 "APP_KEY未生成"
        fi
    else
        record_check 1 ".env文件存在"
    fi

    # Artisan命令檢查
    if php artisan --version >/dev/null 2>&1; then
        record_check 0 "Artisan命令可用"
    else
        record_check 1 "Artisan命令可用"
    fi

    # 自動加載檢查
    if php artisan tinker --execute="echo 'Autoload works';" 2>/dev/null | grep -q "Autoload works"; then
        record_check 0 "自動加載功能"
    else
        record_check 1 "自動加載功能"
    fi
}

# 3. 數據庫檢查
check_database() {
    log_check "🔍 數據庫檢查"

    # 數據庫連接檢查
    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'DB_CONNECTED';" 2>/dev/null | grep -q "DB_CONNECTED"; then
        record_check 0 "數據庫連接"

        # 遷移狀態檢查
        if php artisan migrate:status >/dev/null 2>&1; then
            record_check 0 "數據庫遷移狀態"

            # 檢查核心表是否存在
            local tables=("users" "tasks" "user_tasks" "economic_configs" "consumption_scenarios" "user_consumptions")
            for table in "${tables[@]}"; do
                if php artisan tinker --execute="echo Schema::hasTable('$table') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
                    record_check 0 "數據表存在: $table"
                else
                    record_check 1 "數據表存在: $table"
                fi
            done

        else
            record_check 1 "數據庫遷移狀態"
        fi
    else
        record_check 1 "數據庫連接"
    fi
}

# 4. 任務中心功能檢查
check_task_center() {
    log_check "🔍 任務中心功能檢查"

    # 任務模型檢查
    if php artisan tinker --execute="echo class_exists('App\Models\Task') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "Task模型存在"

        # 基礎任務數據檢查
        TASK_COUNT=$(php artisan tinker --execute="echo App\Models\Task::count();" 2>/dev/null || echo "0")
        if [ "$TASK_COUNT" -gt 0 ]; then
            record_check 0 "基礎任務數據 ($TASK_COUNT 個任務)"
        else
            record_check 1 "基礎任務數據 (0個任務)"
        fi
    else
        record_check 1 "Task模型存在"
    fi

    # TaskService檢查
    if php artisan tinker --execute="echo class_exists('App\Services\TaskService') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "TaskService服務類"
    else
        record_check 1 "TaskService服務類"
    fi

    # 任務控制器檢查
    if php artisan tinker --execute="echo class_exists('App\Http\Controllers\TaskCenterController') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "TaskCenterController控制器"
    else
        record_check 1 "TaskCenterController控制器"
    fi
}

# 5. 經濟系統檢查
check_economic_system() {
    log_check "🔍 經濟系統檢查"

    # 消費場景數據檢查
    if php artisan tinker --execute="echo class_exists('App\Models\ConsumptionScenario') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "ConsumptionScenario模型存在"

        SCENARIO_COUNT=$(php artisan tinker --execute="echo App\Models\ConsumptionScenario::count();" 2>/dev/null || echo "0")
        if [ "$SCENARIO_COUNT" -gt 0 ]; then
            record_check 0 "消費場景數據 ($SCENARIO_COUNT 個場景)"
        else
            record_check 1 "消費場景數據 (0個場景)"
        fi
    else
        record_check 1 "ConsumptionScenario模型存在"
    fi

    # 管理員控制器檢查
    if php artisan tinker --execute="echo class_exists('App\Http\Controllers\Admin\EconomicController') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "EconomicController管理控制器"
    else
        record_check 1 "EconomicController管理控制器"
    fi

    # 管理員中間件檢查
    if php artisan tinker --execute="echo class_exists('App\Http\Middleware\AdminMiddleware') ? 'EXISTS' : 'MISSING';" 2>/dev/null | grep -q "EXISTS"; then
        record_check 0 "AdminMiddleware中間件"
    else
        record_check 1 "AdminMiddleware中間件"
    fi
}

# 6. Web服務器檢查
check_web_server() {
    log_check "🔍 Web服務器檢查"

    # Nginx狀態檢查
    if systemctl is-active --quiet nginx; then
        record_check 0 "Nginx服務運行狀態"
    else
        record_check 1 "Nginx服務運行狀態"
    fi

    # PHP-FPM檢查
    if systemctl is-active --quiet php8.4-fpm; then
        record_check 0 "PHP-FPM服務運行狀態"
    elif systemctl is-active --quiet php8.2-fpm; then
        record_check 0 "PHP-FPM服務運行狀態 (8.2)"
    else
        record_check 1 "PHP-FPM服務運行狀態"
    fi

    # 端口監聽檢查
    if netstat -tlnp 2>/dev/null | grep -q ":80 "; then
        record_check 0 "HTTP端口80監聽"
    else
        record_check 1 "HTTP端口80監聽"
    fi
}

# 7. 路由檢查
check_routes() {
    log_check "🔍 路由檢查"

    # 獲取路由列表
    if php artisan route:list >/dev/null 2>&1; then
        record_check 0 "路由列表可用"

        # 檢查關鍵路由
        local key_routes=("tasks.index" "admin.economic.index" "tasks.complete" "admin.economic.airdrop")
        for route in "${key_routes[@]}"; do
            if php artisan route:list --name="$route" 2>/dev/null | grep -q "$route"; then
                record_check 0 "關鍵路由: $route"
            else
                record_check 1 "關鍵路由: $route"
            fi
        done
    else
        record_check 1 "路由列表可用"
    fi
}

# 8. HTTP響應檢查
check_http_responses() {
    log_check "🔍 HTTP響應檢查"

    # 檢查主頁響應
    if curl -s -o /dev/null -w "%{http_code}" http://localhost/ | grep -q "200\|302"; then
        record_check 0 "主頁HTTP響應"
    else
        record_check 1 "主頁HTTP響應"
    fi

    # 如果有外部IP，也檢查一下
    if curl -s -o /dev/null -w "%{http_code}" http://119.45.242.49/ 2>/dev/null | grep -q "200\|302"; then
        record_check 0 "外部IP HTTP響應"
    else
        record_check 1 "外部IP HTTP響應"
    fi
}

# 9. 性能檢查
check_performance() {
    log_check "🔍 性能檢查"

    # 緩存狀態檢查
    if [ -f "bootstrap/cache/config.php" ]; then
        record_check 0 "配置緩存已生成"
    else
        record_check 1 "配置緩存已生成"
    fi

    if [ -f "bootstrap/cache/routes-v7.php" ]; then
        record_check 0 "路由緩存已生成"
    else
        record_check 1 "路由緩存已生成"
    fi

    # Composer優化檢查
    if [ -f "vendor/composer/autoload_classmap.php" ] && [ -s "vendor/composer/autoload_classmap.php" ]; then
        record_check 0 "Composer自動加載優化"
    else
        record_check 1 "Composer自動加載優化"
    fi

    # 存儲鏈接檢查
    if [ -L "public/storage" ]; then
        record_check 0 "存儲符號鏈接"
    else
        record_check 1 "存儲符號鏈接"
    fi
}

# 10. 安全檢查
check_security() {
    log_check "🔍 安全檢查"

    # .env文件權限檢查
    if [ -f ".env" ]; then
        ENV_PERMS=$(stat -c "%a" .env)
        if [ "$ENV_PERMS" = "600" ] || [ "$ENV_PERMS" = "644" ]; then
            record_check 0 ".env文件權限 ($ENV_PERMS)"
        else
            record_check 1 ".env文件權限 ($ENV_PERMS)"
        fi
    fi

    # 敏感目錄訪問檢查
    for dir in "storage" "vendor" ".env"; do
        if curl -s "http://localhost/$dir" | grep -q "Forbidden\|403\|Not Found\|404"; then
            record_check 0 "敏感目錄保護: $dir"
        else
            record_check 1 "敏感目錄保護: $dir"
        fi
    done
}

# 生成健康檢查報告
generate_health_report() {
    local timestamp=$(date +%Y%m%d-%H%M%S)
    local report_file="health-check-report-$timestamp.txt"

    {
        echo "================================="
        echo "HOHO社區系統健康檢查報告"
        echo "================================="
        echo "檢查時間: $(date)"
        echo "服務器IP: 119.45.242.49"
        echo "域名: hohopark.com"
        echo "================================="
        echo "檢查結果統計:"
        echo "總檢查項目: $TOTAL_CHECKS"
        echo "通過檢查: $PASSED_CHECKS"
        echo "失敗檢查: $FAILED_CHECKS"
        echo "通過率: $(echo "scale=1; $PASSED_CHECKS * 100 / $TOTAL_CHECKS" | bc)%"
        echo "================================="

        if [ $FAILED_CHECKS -eq 0 ]; then
            echo "🎉 所有檢查項目都通過了！系統運行正常。"
        else
            echo "⚠️  發現 $FAILED_CHECKS 個問題，建議查看詳細日誌。"
        fi

        echo "================================="
        echo "系統信息:"
        echo "PHP版本: $(php --version | head -n1)"
        echo "Laravel版本: $(php artisan --version)"
        echo "數據庫: MySQL"
        echo "Web服務器: Nginx"
        echo "================================="
    } > $report_file

    log_info "健康檢查報告已生成: $report_file"
}

# 主函數
main() {
    log_info "🏥 開始系統健康檢查..."
    echo

    # 執行所有檢查
    check_basic_environment
    echo
    check_laravel_application
    echo
    check_database
    echo
    check_task_center
    echo
    check_economic_system
    echo
    check_web_server
    echo
    check_routes
    echo
    check_http_responses
    echo
    check_performance
    echo
    check_security
    echo

    # 生成報告
    generate_health_report

    # 顯示總結
    echo "================================="
    echo "🏥 健康檢查完成"
    echo "================================="
    echo -e "總檢查項目: ${BLUE}$TOTAL_CHECKS${NC}"
    echo -e "通過檢查: ${GREEN}$PASSED_CHECKS${NC}"
    echo -e "失敗檢查: ${RED}$FAILED_CHECKS${NC}"

    if [ $FAILED_CHECKS -eq 0 ]; then
        echo -e "結果: ${GREEN}🎉 系統完全正常！${NC}"
        exit 0
    else
        echo -e "結果: ${YELLOW}⚠️  發現問題，建議修復${NC}"

        # 提供修復建議
        echo
        echo "🔧 修復建議:"
        echo "1. 運行自動修復腳本: ./scripts/auto-fix.sh"
        echo "2. 檢查詳細錯誤日誌"
        echo "3. 手動解決特定問題"

        exit 1
    fi
}

# 如果腳本被直接調用，執行主函數
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi