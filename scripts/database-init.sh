#!/bin/bash

# 數據庫初始化和修復腳本
# 用於處理數據庫相關的部署問題

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() {
    echo -e "${GREEN}[DB-INIT]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_error() {
    echo -e "${RED}[DB-ERROR]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warn() {
    echo -e "${YELLOW}[DB-WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_step() {
    echo -e "${BLUE}[DB-STEP]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

# 數據庫連接測試
test_database_connection() {
    log_step "測試數據庫連接..."

    if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected successfully';" 2>/dev/null; then
        log_info "✅ 數據庫連接成功"
        return 0
    else
        log_error "❌ 數據庫連接失敗"
        return 1
    fi
}

# 修復數據庫連接問題
fix_database_connection() {
    log_step "嘗試修復數據庫連接問題..."

    # 1. 檢查.env文件
    if [ ! -f ".env" ]; then
        log_warn ".env文件不存在，從.env.example複製..."
        cp .env.example .env
        php artisan key:generate
    fi

    # 2. 清理配置緩存
    log_info "清理配置緩存..."
    php artisan config:clear
    php artisan cache:clear

    # 3. 重新測試連接
    if test_database_connection; then
        log_info "✅ 數據庫連接已修復"
        return 0
    else
        log_error "❌ 無法自動修復數據庫連接，請檢查.env配置"
        return 1
    fi
}

# 運行遷移
run_migrations() {
    log_step "運行數據庫遷移..."

    # 檢查是否有待遷移的文件
    if php artisan migrate:status | grep -q "Pending"; then
        log_info "發現待遷移的文件，開始遷移..."
        php artisan migrate --force
        log_info "✅ 數據庫遷移完成"
    else
        log_info "ℹ️ 所有遷移已是最新狀態"
    fi
}

# 修復遷移問題
fix_migration_issues() {
    log_step "嘗試修復遷移問題..."

    # 1. 回滾最後一批遷移
    log_warn "回滾最後一批遷移..."
    php artisan migrate:rollback --force || true

    # 2. 重新運行遷移
    log_info "重新運行遷移..."
    php artisan migrate --force

    log_info "✅ 遷移問題修復完成"
}

# 初始化基礎數據
seed_initial_data() {
    log_step "初始化基礎數據..."

    # 檢查任務表是否有數據
    TASK_COUNT=$(php artisan tinker --execute="echo App\Models\Task::count();" 2>/dev/null || echo "0")

    if [ "$TASK_COUNT" -eq "0" ]; then
        log_info "初始化任務數據..."
        php artisan db:seed --class=TaskSeeder --force
    else
        log_info "任務數據已存在 ($TASK_COUNT 條記錄)"
    fi

    # 檢查消費場景表是否有數據
    SCENARIO_COUNT=$(php artisan tinker --execute="echo App\Models\ConsumptionScenario::count();" 2>/dev/null || echo "0")

    if [ "$SCENARIO_COUNT" -eq "0" ]; then
        log_info "初始化消費場景數據..."
        php artisan db:seed --class=ConsumptionScenarioSeeder --force
    else
        log_info "消費場景數據已存在 ($SCENARIO_COUNT 條記錄)"
    fi

    log_info "✅ 基礎數據初始化完成"
}

# 創建管理員用戶
create_admin_user() {
    log_step "檢查管理員用戶..."

    ADMIN_COUNT=$(php artisan tinker --execute="echo App\Models\User::where('role', 'admin')->count();" 2>/dev/null || echo "0")

    if [ "$ADMIN_COUNT" -eq "0" ]; then
        log_info "創建默認管理員用戶..."

        # 創建管理員用戶的PHP腳本
        cat > /tmp/create_admin.php << 'EOL'
<?php
use App\Models\User;
use Illuminate\Support\Facades\Hash;

$admin = User::create([
    'hoho_id' => 'admin_001',
    'nickname' => 'HOHO管理員',
    'email' => 'admin@hohopark.com',
    'password' => Hash::make('HohoAdmin@2024'),
    'role' => 'admin',
    'status' => 'active',
    'is_verified' => true,
    'email_verified_at' => now(),
    'points_balance' => 10000.00000000,
]);

echo "管理員用戶創建成功，ID: " . $admin->id . "\n";
echo "登錄郵箱: admin@hohopark.com\n";
echo "登錄密碼: HohoAdmin@2024\n";
EOL

        php artisan tinker < /tmp/create_admin.php
        rm /tmp/create_admin.php

        log_info "✅ 管理員用戶創建完成"
        log_warn "📧 登錄郵箱: admin@hohopark.com"
        log_warn "🔐 登錄密碼: HohoAdmin@2024"
        log_warn "⚠️  請立即修改默認密碼！"
    else
        log_info "管理員用戶已存在 ($ADMIN_COUNT 個)"
    fi
}

# 主函數
main() {
    log_info "🗄️ 開始數據庫初始化流程..."

    # 1. 測試數據庫連接
    if ! test_database_connection; then
        fix_database_connection || {
            log_error "無法修復數據庫連接，請手動檢查.env配置"
            exit 1
        }
    fi

    # 2. 運行遷移
    if ! run_migrations; then
        log_warn "遷移失敗，嘗試修復..."
        fix_migration_issues
    fi

    # 3. 初始化數據
    seed_initial_data

    # 4. 創建管理員用戶
    create_admin_user

    # 5. 最終驗證
    log_step "最終驗證..."
    TOTAL_TASKS=$(php artisan tinker --execute="echo App\Models\Task::count();" 2>/dev/null || echo "0")
    TOTAL_SCENARIOS=$(php artisan tinker --execute="echo App\Models\ConsumptionScenario::count();" 2>/dev/null || echo "0")
    TOTAL_USERS=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null || echo "0")

    log_info "📊 數據庫狀態:"
    log_info "   - 任務數量: $TOTAL_TASKS"
    log_info "   - 消費場景: $TOTAL_SCENARIOS"
    log_info "   - 用戶數量: $TOTAL_USERS"

    log_info "✅ 數據庫初始化完成！"
}

# 如果腳本被直接調用，執行主函數
if [ "${BASH_SOURCE[0]}" == "${0}" ]; then
    main "$@"
fi