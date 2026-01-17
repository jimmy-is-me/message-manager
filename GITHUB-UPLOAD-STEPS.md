# 上傳到GitHub步驟

## ✅ 已完成的步驟

- [x] Git 初始化
- [x] 配置用戶信息
- [x] 添加所有文件
- [x] 創建初始提交

## 📝 接下來的步驟

### 步驟 1：在GitHub上創建新倉庫

1. 前往 https://github.com/new
2. 填寫以下信息：
   - **Repository name**: `line-message-manager`
   - **Description**: `WordPress插件 - LINE官方帳號訊息管理系統，提供前台對話框、後台管理和Discord通知功能`
   - **Visibility**: 選擇 Public（公開）或 Private（私有）
   - **不要**勾選以下選項：
     - ❌ Add a README file
     - ❌ Add .gitignore
     - ❌ Choose a license
3. 點擊 **Create repository** 按鈕

### 步驟 2：推送到GitHub

創建倉庫後，在您當前的PowerShell視窗中執行以下命令：

```powershell
# 添加遠程倉庫
git remote add origin https://github.com/jimmy-is-me/line-message-manager.git

# 設置主分支為 main（GitHub 新標準）
git branch -M main

# 推送到GitHub
git push -u origin main
```

### 步驟 3：驗證上傳

1. 前往 https://github.com/jimmy-is-me/line-message-manager
2. 確認所有文件都已上傳
3. 查看 README.md 是否正確顯示

## 🔐 如果需要認證

如果推送時要求輸入憑證：

### 方法一：使用Personal Access Token（推薦）

1. 前往 https://github.com/settings/tokens
2. 點擊 **Generate new token** → **Generate new token (classic)**
3. 設置：
   - **Note**: `line-message-manager upload`
   - **Expiration**: 選擇期限
   - **Scopes**: 勾選 `repo`（完整控制私有倉庫）
4. 點擊 **Generate token**
5. **複製token**（只會顯示一次！）
6. 推送時：
   - Username: `jimmy-is-me`
   - Password: 貼上剛才複製的 token

### 方法二：使用GitHub CLI（進階）

```powershell
# 安裝 GitHub CLI
winget install GitHub.cli

# 登入
gh auth login

# 推送
git push -u origin main
```

## 📊 倉庫信息

- **GitHub用戶名**: jimmy-is-me
- **倉庫名稱**: line-message-manager
- **倉庫URL**: https://github.com/jimmy-is-me/line-message-manager
- **Clone URL**: https://github.com/jimmy-is-me/line-message-manager.git

## 🎉 完成後

上傳成功後，您可以：

1. 在倉庫頁面添加標籤（Topics）：
   - `wordpress`
   - `wordpress-plugin`
   - `chat`
   - `customer-service`
   - `discord`
   - `line`

2. 考慮添加 License（建議使用 GPL-2.0）

3. 在 About 設定中添加：
   - Website: 您的網站
   - Topics: 相關標籤

4. 可以在 README 中添加徽章：
   ```markdown
   ![Version](https://img.shields.io/badge/version-1.0.0-blue)
   ![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue)
   ![PHP](https://img.shields.io/badge/PHP-7.2%2B-blue)
   ![License](https://img.shields.io/badge/license-GPL--2.0-green)
   ```

## ❓ 常見問題

**Q: 推送時顯示 "failed to push some refs"**
A: 這通常是因為遠程倉庫有您本地沒有的內容。如果您剛創建空倉庫，這不應該發生。

**Q: 忘記複製 Personal Access Token**
A: 回到 https://github.com/settings/tokens 刪除舊的並創建新的。

**Q: 想要修改提交信息**
A: 如果還沒推送，可以使用：
```powershell
git commit --amend -m "新的提交信息"
```

## 💡 後續更新

當您修改代碼後，使用以下命令更新：

```powershell
git add .
git commit -m "描述您的更改"
git push
```

---

*最後更新：2026-01-17*
