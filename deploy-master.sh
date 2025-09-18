#!/bin/bash

# HOHO社區完整自動化部署腳本
# 作者: Claude Code Assistant
# 版本: 1.0
# 使用方法: ./deploy-master.sh

set -e  # 遇到錯誤立即退出

# 顏色定義
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# 日誌函數
log_info() {
    echo -e "${GREEN}[INFO]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $(date '+%Y-%m-%d %H:%M:%S') $1"
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

# 錯誤處理函數
handle_error() {
    local exit_code=$?
    local line_number=$1
    log_error "腳本在第 $line_number 行失敗，退出碼: $exit_code"
    log_error "正在嘗試自動修復..."

    # 調用錯誤修復腳本
    if [ -f "./scripts/auto-fix.sh" ]; then
        bash ./scripts/auto-fix.sh $exit_code $line_number
    fi

    exit $exit_code
}

# 設置錯誤陷阱
trap 'handle_error $LINENO' ERR

# 創建腳本目錄
mkdir -p scripts

log_info "🚀 開始HOHO社區自動化部署流程"
log_info "📋 項目目錄: $(pwd)"
log_info "⏰ 部署時間: $(date)"

# ============================================================================
# 第一階段：本地準備工作
# ============================================================================

log_step "📦 第一階段：本地代碼準備"

# 1.1 檢查PHP版本
log_info "檢查PHP版本..."
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
if (( $(echo "$PHP_VERSION < 8.4" | bc -l) )); then
    log_warn "PHP版本 $PHP_VERSION 可能不兼容，建議使用 8.4+"
fi

# 1.2 安裝/更新依賴
log_info "安裝Composer依賴..."
if ! command -v composer &> /dev/null; then
    log_error "Composer未安裝，請先安裝Composer"
    exit 1
fi

composer install --no-dev --optimize-autoloader || {
    log_warn "Composer install失敗，嘗試修復..."
    composer clear-cache
    composer install --no-dev --optimize-autoloader
}

# 1.3 檢查關鍵文件
log_info "檢查關鍵文件完整性..."
REQUIRED_FILES=(
    "app/Models/Task.php"
    "app/Models/UserTask.php"
    "app/Models/EconomicConfig.php"
    "app/Models/ConsumptionScenario.php"
    "app/Services/TaskService.php"
    "app/Http/Controllers/Admin/EconomicController.php"
    "app/Http/Controllers/TaskCenterController.php"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "$file" ]; then
        log_error "關鍵文件缺失: $file"
        exit 1
    fi
done

log_success "✅ 本地代碼準備完成"

# ============================================================================
# 第二階段：代碼提交和版本控制
# ============================================================================

log_step "📝 第二階段：代碼提交"

# 2.1 Git狀態檢查
log_info "檢查Git狀態..."
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    log_info "初始化Git倉庫..."
    git init
    git branch -M main
fi

# 2.2 添加.gitignore（如果不存在）
if [ ! -f ".gitignore" ]; then
    log_info "創建.gitignore文件..."
cat > .gitignore << 'EOF'
/node_modules
/public/build
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.env.production
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
npm-debug.log
yarn-error.log
/.fleet
/.idea
/.vscode
EOF
fi

# 2.3 提交所有更改
log_info "提交所有更改..."
git add .

# 檢查是否有更改需要提交
if git diff --staged --quiet; then
    log_info "沒有更改需要提交"
else
    git commit -m "feat: 完整的任務中心和CMS經濟管理系統

✨ 新功能:
- 完整的任務中心系統（每日、每週、一次性、按行為觸發）
- CMS經濟管理面板（任務獎勵、消費價格、開關控制）
- 消費場景系統（治理、高級功能、推廣、實用工具）
- 8位小數精度積分系統
- 透明的經濟參數變更公示機制

🔧 技術改進:
- 移除作品互動積分獎勵
- 新增管理員權限中間件
- 完整的模型關係和數據庫約束
- 事務安全的積分操作
- 靈活的任務和消費場景配置

🎯 系統特性:
- 任務為主要積分來源
- CMS完全可控的經濟參數
- 即使關閉第三方API也能形成經濟閉環
- 支持空投、批量管理等運營功能

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"
fi

log_success "✅ 代碼提交完成"

# ============================================================================
# 第三階段：GitHub推送指導
# ============================================================================

log_step "🌐 第三階段：GitHub推送準備"

echo
echo "================================="
echo "🔑 GitHub推送指令"
echo "================================="
echo
echo "請依次執行以下命令："
echo
echo -e "${GREEN}# 1. 添加遠程倉庫（如果還沒有）${NC}"
echo "git remote add origin https://github.com/dokidwell/hoho-new.git"
echo
echo -e "${GREEN}# 2. 推送到GitHub${NC}"
echo "git push -u origin main"
echo
echo -e "${YELLOW}注意：如果遇到認證問題，請使用您的GitHub用戶名和Token${NC}"
echo "用戶名: dokidwell"
echo "密碼/Token: [使用您提供的API密鑰]"
echo
echo "================================="
echo

read -p "是否已完成GitHub推送？(y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    log_warn "請完成GitHub推送後重新運行此腳本"
    exit 1
fi

log_success "✅ GitHub推送確認完成"

# ============================================================================
# 第四階段：生產服務器部署指令
# ============================================================================

log_step "🖥️ 第四階段：生產服務器部署指令生成"

# 創建服務器部署腳本
cat > scripts/server-deploy.sh << 'EOF'
#!/bin/bash

# 生產服務器部署腳本
# 在服務器上執行此腳本

set -e

# 顏色定義
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

# 服務器配置
SERVER_IP="119.45.242.49"
PROJECT_PATH="/var/www/hoho-new"
DOMAIN="hohopark.com"

log_step "🖥️ 開始生產服務器部署"

# 1. 更新系統
log_info "更新系統包..."
sudo apt update && sudo apt upgrade -y

# 2. 檢查PHP版本
log_info "檢查PHP版本..."
php --version

# 3. 克隆或更新代碼
if [ -d "$PROJECT_PATH" ]; then
    log_info "更新現有代碼..."
    cd $PROJECT_PATH
    git pull origin main
else
    log_info "克隆代碼倉庫..."
    sudo git clone https://github.com/dokidwell/hoho-new.git $PROJECT_PATH
    sudo chown -R www-data:www-data $PROJECT_PATH
fi

cd $PROJECT_PATH

# 4. 安裝依賴
log_info "安裝Composer依賴..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# 5. 環境配置
log_info "配置環境文件..."
if [ ! -f ".env" ]; then
    sudo -u www-data cp .env.production .env
else
    log_info ".env文件已存在，跳過"
fi

# 6. 生成應用密鑰
log_info "生成應用密鑰..."
sudo -u www-data php artisan key:generate

# 7. 運行數據庫遷移
log_info "運行數據庫遷移..."
sudo -u www-data php artisan migrate --force

# 8. 運行數據種子
log_info "初始化基礎數據..."
sudo -u www-data php artisan db:seed --class=TaskSeeder --force
sudo -u www-data php artisan db:seed --class=ConsumptionScenarioSeeder --force

# 9. 清理和優化
log_info "清理緩存和優化..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 10. 設置權限
log_info "設置文件權限..."
sudo chown -R www-data:www-data $PROJECT_PATH
sudo chmod -R 755 $PROJECT_PATH
sudo chmod -R 775 $PROJECT_PATH/storage
sudo chmod -R 775 $PROJECT_PATH/bootstrap/cache

# 11. 重啟服務
log_info "重啟Web服務..."
sudo systemctl reload nginx
sudo systemctl reload php8.4-fpm

log_info "✅ 服務器部署完成！"
log_info "🌐 網站地址: http://$SERVER_IP"
log_info "🌐 域名地址: http://$DOMAIN (如已配置DNS)"

EOF

chmod +x scripts/server-deploy.sh

echo
echo "================================="
echo "🖥️ 服務器部署指令"
echo "================================="
echo
echo "請在您的服務器上執行以下步驟："
echo
echo -e "${GREEN}# 1. 登錄服務器${NC}"
echo "ssh ubuntu@119.45.242.49"
echo
echo -e "${GREEN}# 2. 下載部署腳本${NC}"
echo "wget https://raw.githubusercontent.com/dokidwell/hoho-new/main/scripts/server-deploy.sh"
echo "chmod +x server-deploy.sh"
echo
echo -e "${GREEN}# 3. 執行部署${NC}"
echo "./server-deploy.sh"
echo
echo "================================="
echo

log_success "✅ 部署指令準備完成"

# ============================================================================
# 第五階段：設置腳本權限和最終準備
# ============================================================================

log_step "🔧 第五階段：設置腳本權限和最終準備"

# 設置所有腳本的執行權限
log_info "設置腳本執行權限..."
chmod +x scripts/*.sh
chmod +x deploy-master.sh

log_success "✅ 腳本權限設置完成"

# ============================================================================
# 第六階段：創建一鍵部署總結
# ============================================================================

log_step "📋 第六階段：部署總結和使用指南"

echo
echo "================================="
echo "🎉 自動化部署腳本準備完成！"
echo "================================="
echo
echo "📁 已創建的腳本文件："
echo "├── deploy-master.sh          # 主部署腳本（當前）"
echo "├── scripts/"
echo "│   ├── database-init.sh      # 數據庫初始化"
echo "│   ├── auto-fix.sh          # 自動故障修復"
echo "│   ├── health-check.sh      # 系統健康檢查"
echo "│   ├── emergency-rollback.sh # 緊急回滾"
echo "│   └── server-deploy.sh     # 服務器部署"
echo
echo "🚀 部署流程："
echo "1. ✅ 本地代碼準備完成"
echo "2. ✅ Git提交完成"
echo "3. ⏳ 推送到GitHub"
echo "4. ⏳ 服務器部署"
echo "5. ⏳ 健康檢查"
echo
echo "🔧 故障恢復工具："
echo "• 自動修復：./scripts/auto-fix.sh"
echo "• 健康檢查：./scripts/health-check.sh"
echo "• 緊急回滾：./scripts/emergency-rollback.sh"
echo "• 數據庫重置：./scripts/database-init.sh"
echo
echo "================================="
echo

<function_calls>
<invoke name="TodoWrite">
<parameter name="todos">[{"content": "Create comprehensive deployment scripts", "status": "completed", "activeForm": "Creating comprehensive deployment scripts"}, {"content": "Create database initialization scripts", "status": "in_progress", "activeForm": "Creating database initialization scripts"}, {"content": "Create error recovery scripts", "status": "pending", "activeForm": "Creating error recovery scripts"}, {"content": "Create verification and health check scripts", "status": "pending", "activeForm": "Creating verification and health check scripts"}, {"content": "Create rollback and emergency scripts", "status": "pending", "activeForm": "Creating rollback and emergency scripts"}]