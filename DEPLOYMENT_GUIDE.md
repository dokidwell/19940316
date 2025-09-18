# 🚀 HOHO社區一鍵自動化部署指南

## 📋 概述

這是一套完整的自動化部署腳本系統，包含自動故障修復、健康檢查和緊急回滾功能。

## 🎯 快速開始

### 第一步：執行主部署腳本

```bash
./deploy-master.sh
```

這個腳本會：
- ✅ 檢查本地環境
- ✅ 安裝/更新依賴
- ✅ 提交所有代碼更改
- ✅ 生成服務器部署指令
- ✅ 創建所有故障修復腳本

### 第二步：推送到GitHub

按照腳本提示執行：

```bash
# 添加遠程倉庫（如果還沒有）
git remote add origin https://github.com/dokidwell/hoho-new.git

# 推送代碼
git push -u origin main
```

### 第三步：服務器部署

在您的服務器 (149.129.236.244) 上執行：

```bash
# 下載部署腳本
wget https://raw.githubusercontent.com/dokidwell/hoho-new/main/scripts/server-deploy.sh
chmod +x server-deploy.sh

# 執行部署
./server-deploy.sh
```

### 第四步：健康檢查

部署完成後，運行健康檢查：

```bash
./scripts/health-check.sh
```

## 🔧 故障修復工具

### 自動修復腳本

如果遇到任何問題，運行：

```bash
./scripts/auto-fix.sh
```

這個腳本會自動：
- 🔧 修復文件權限
- 🔧 清理緩存問題
- 🔧 修復Composer依賴
- 🔧 檢查PHP環境
- 🔧 修復數據庫連接
- 🔧 重建Nginx配置

### 數據庫初始化

重新初始化數據庫：

```bash
./scripts/database-init.sh
```

功能包括：
- 📊 測試數據庫連接
- 📊 運行/修復遷移
- 📊 初始化基礎數據
- 📊 創建管理員用戶

### 緊急回滾

如果部署出現嚴重問題：

```bash
./scripts/emergency-rollback.sh
```

這會：
- ⏪ 創建當前狀態備份
- ⏪ 回滾數據庫遷移
- ⏪ 回滾代碼到穩定版本
- ⏪ 恢復基本配置
- ⏪ 重啟所有服務

## 📊 健康檢查

運行全面的系統檢查：

```bash
./scripts/health-check.sh
```

檢查項目包括：
- 🏥 基礎環境（PHP、Composer、權限）
- 🏥 Laravel應用（.env、Artisan、自動加載）
- 🏥 數據庫（連接、遷移、數據表）
- 🏥 任務中心功能
- 🏥 經濟系統功能
- 🏥 Web服務器狀態
- 🏥 路由檢查
- 🏥 HTTP響應
- 🏥 性能優化
- 🏥 安全配置

## 🎛️ 管理員訪問

部署完成後，您可以使用以下默認管理員賬戶：

- **登錄郵箱**: `admin@hohopark.com`
- **登錄密碼**: `HohoAdmin@2024`
- **管理面板**: `http://149.129.236.244/admin/economic`

⚠️ **請立即修改默認密碼！**

## 🌐 訪問地址

- **服務器IP**: http://149.129.236.244
- **域名** (如已配置DNS): http://hohopark.com

## 📁 重要路徑

### 用戶功能
- 任務中心: `/tasks`
- 消費功能: `/tasks/consumptions`
- 我的消費記錄: `/tasks/my-consumptions`

### 管理員功能
- 經濟管理首頁: `/admin/economic`
- 任務管理: `/admin/economic/tasks`
- 消費場景管理: `/admin/economic/consumptions`
- 經濟統計: `/admin/economic/stats`
- 積分空投: `/admin/economic/airdrop`

## 🔍 故障排除

### 常見問題

#### 1. 數據庫連接失敗
```bash
# 檢查.env配置
cat .env | grep DB_

# 重新初始化數據庫
./scripts/database-init.sh
```

#### 2. 權限問題
```bash
# 修復權限
sudo chown -R www-data:www-data /var/www/hoho-new
sudo chmod -R 755 /var/www/hoho-new
sudo chmod -R 775 /var/www/hoho-new/storage
```

#### 3. Nginx配置問題
```bash
# 測試配置
sudo nginx -t

# 重啟服務
sudo systemctl reload nginx
```

#### 4. PHP擴展缺失
```bash
# 檢查PHP擴展
php -m | grep -E "(pdo|mysql|mbstring)"

# 安裝缺失擴展
sudo apt install php8.4-mysql php8.4-mbstring
```

### 日誌位置

- **Laravel日誌**: `/var/www/hoho-new/storage/logs/laravel.log`
- **Nginx錯誤日誌**: `/var/log/nginx/error.log`
- **PHP-FPM日誌**: `/var/log/php8.4-fpm.log`

## 🔄 更新部署

如果需要更新代碼：

1. 在本地修改代碼
2. 運行 `./deploy-master.sh`
3. 推送到GitHub
4. 在服務器上運行：
   ```bash
   cd /var/www/hoho-new
   git pull origin main
   ./scripts/database-init.sh
   ./scripts/health-check.sh
   ```

## 🆘 緊急聯繫

如果所有自動修復都失敗，您可以：

1. **完全回滾**: `./scripts/emergency-rollback.sh`
2. **查看備份**: 備份位置在 `/var/backups/hoho-*`
3. **檢查日誌**: 查看上述日誌文件
4. **重新部署**: 重新執行整個部署流程

## 📈 監控建議

建議設置以下監控：

1. **定期健康檢查**:
   ```bash
   # 添加到crontab
   0 */6 * * * /var/www/hoho-new/scripts/health-check.sh
   ```

2. **日誌監控**:
   ```bash
   # 監控錯誤日誌
   tail -f /var/www/hoho-new/storage/logs/laravel.log
   ```

3. **系統資源監控**:
   ```bash
   # 檢查系統資源
   htop
   df -h
   ```

---

## 🎉 部署完成檢查清單

- [ ] 主部署腳本執行成功
- [ ] 代碼推送到GitHub
- [ ] 服務器部署完成
- [ ] 健康檢查全部通過
- [ ] 管理員賬戶可以登錄
- [ ] 任務中心功能正常
- [ ] CMS經濟管理面板正常
- [ ] 網站可以正常訪問

**恭喜！您的HOHO社區平台已成功部署！** 🎊