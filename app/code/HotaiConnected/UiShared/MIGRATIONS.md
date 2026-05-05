# HotaiConnected UiShared - 技術遷移記錄

本文件記錄 UiShared 共用模組的重要技術遷移決策和過程。

---

## Toast Message 關閉按鈕樣式修正 (2025-10-20)

### 📋 決策摘要
修正 `toast-message` 組件中關閉按鈕的 HTML 結構，使其與 CSS 樣式匹配，並優化按鈕樣式以避免與 Magento 預設樣式衝突。

### 🎯 修正原因
1. **HTML 結構不匹配**: JavaScript 生成的結構與 CSS 期望的結構不一致
2. **樣式失效**: 關閉按鈕的樣式無法正確顯示
3. **按鈕樣式衝突**: 與 Magento 預設按鈕樣式產生衝突

### 🔧 主要變更

#### JavaScript 結構修正
```javascript
// 修正前
var closeBtn = document.createElement('button');
closeBtn.textContent = '×';

// 修正後
var closeBtn = document.createElement('button');
var closeSpan = document.createElement('span');
closeSpan.textContent = '×';
closeBtn.appendChild(closeSpan);
```

#### CSS 樣式優化
```less
&__close {
    // 新增按鈕重置樣式
    background: none;
    border: none;
    padding: 0;
    margin: 0;
    cursor: pointer;
    
    > span {
        // 新增 line-height 修正
        line-height: 1;
    }
}
```

### ✅ 修正結果
- ✅ 關閉按鈕 HTML 結構與 CSS 樣式完全匹配
- ✅ 關閉按鈕樣式正確顯示
- ✅ 避免與 Magento 預設按鈕樣式衝突
- ✅ 保持所有互動效果（hover、active）

### 📝 注意事項
1. **結構一致性**: 確保 JavaScript 生成的 HTML 結構與 CSS 選擇器匹配
2. **樣式重置**: 按鈕元素需要重置預設樣式以避免衝突
3. **向後相容**: 不影響現有的 toast 訊息功能

---

## hotaiRequest GET 請求參數處理修正 (2025-10-20)

### 📋 決策摘要
修正 `hotaiRequest` 服務中 GET 請求的參數處理方式，將參數正確放在 URL 查詢字串中，而不是請求體中。

### 🎯 修正原因
1. **HTTP 標準**: GET 請求的參數應該放在 URL 查詢字串中，不應該有請求體
2. **400 錯誤**: 原本的實作導致 GET 請求參數被序列化為 JSON 放在請求體中，造成 400 錯誤
3. **URL 格式錯誤**: 原本會產生 `?{}&_=timestamp` 這樣的無效 URL

### 🔧 主要變更

#### 修正前
```javascript
// 所有請求都使用 JSON 格式
var jsonData = JSON.stringify(requestData);
ajaxConfig.data = jsonData;
ajaxConfig.processData = false;
// 結果：GET /rest/V1/reconciliation/status?{}&_=timestamp (400 錯誤)
```

#### 修正後
```javascript
if (httpMethod === 'GET') {
    // GET 請求：參數放在 URL 查詢字串中
    ajaxConfig.data = requestData;
    ajaxConfig.processData = true;  // 讓 jQuery 處理查詢參數
} else {
    // POST/PUT/DELETE 請求：參數放在請求體中（JSON 格式）
    var jsonData = JSON.stringify(requestData);
    ajaxConfig.data = jsonData;
    ajaxConfig.processData = false;
}
// 結果：GET /rest/V1/reconciliation/status?page=1&limit=20 (正常)
```

### ✅ 修正結果
- ✅ GET 請求參數正確放在 URL 查詢字串中
- ✅ POST/PUT/DELETE 請求仍使用 JSON 格式
- ✅ 解決 400 錯誤問題
- ✅ 符合 HTTP 標準和 REST API 最佳實踐

### 📝 注意事項
1. **GET 請求**: 參數會自動轉換為 URL 查詢字串
2. **POST/PUT/DELETE 請求**: 參數仍使用 JSON 格式放在請求體中
3. **向後相容**: 不影響現有的 API 調用方式

---

## Tabulator 配置架構重構 (2025-10-20)

### 📋 決策摘要
將 Tabulator 配置分離為通用配置和業務特定配置，通用配置由 UiShared 提供，業務特定配置由各模組自行定義。

### 🎯 重構原因
1. **配置分離**: 通用配置（分頁、語系、布局等）與業務配置（columns、ajaxURL）分離
2. **重複利用**: 其他模組可以重用通用配置，只需定義業務特定的部分
3. **維護性**: 通用配置的更新只需在 UiShared 進行一次
4. **架構清晰**: 明確區分通用功能和業務邏輯

### 🔧 主要變更

#### 新增通用配置
- `HotaiConnected_UiShared/js/config/tabulator-config.js`
- 提供 `getDefaultConfig()` 方法
- 包含分頁、語系、布局、AJAX 處理等通用配置
- 預設 `ajaxURL` 為空字串 `''`，`columns` 為空陣列 `[]`

#### 更新 RequireJS 配置
- 添加 `tabulatorConfig` 全域別名
- 指向 `js/config/tabulator-config`

#### 業務模組使用方式
```javascript
// 方式一：業務配置內部自動合併（推薦）
define(['tabulatorConfig', 'YourModule/js/config/your-tabulator-config'], 
function(baseConfig, yourConfig) {
    return $.extend(true, {}, baseConfig.getDefaultConfig(), yourConfig.getConfig());
});

// 方式二：手動合併
var config = $.extend(true, {}, baseConfig.getDefaultConfig(), yourConfig.getConfig());
```

### ✅ 重構結果
- ✅ 配置架構更清晰，通用配置可重用
- ✅ 業務特定配置更簡潔，只包含必要內容
- ✅ 其他模組可以輕鬆使用相同的通用配置
- ✅ 功能測試通過，無已知問題

### 📝 注意事項
1. **使用規範**: 業務模組應只定義 `columns` 和 `ajaxURL`，其他配置由 UiShared 提供
2. **配置合併**: 業務配置檔案內部自動合併通用配置，使用者只需調用 `getConfig()`
3. **架構優勢**: 業務配置檔案可以獨立使用，無需外部合併邏輯

---

## Select2 → SumoSelect (2025-10-15)

### 📋 決策摘要
將整個 HotaiConnected 系統從 Select2 遷移到 SumoSelect 作為統一的多選下拉選單解決方案。

### 🎯 遷移原因
1. **統一共用組件管理**：將所有 UI 組件集中到 UiShared 模組
2. **減少重複依賴**：避免每個模組重複引入相同的第三方套件
3. **版本統一管理**：確保所有模組使用相同版本，易於更新和維護
4. **功能對等性**：SumoSelect 提供與 Select2 相同或更好的功能

### 📦 影響範圍
- ✅ CheckoutManagement 模組（已完成遷移）
- 🔜 其他使用多選下拉選單的模組（待遷移）

### 🔧 主要變更
1. **新增至 UiShared**：
   - `js/vendor/sumoselect.min.js` - SumoSelect 核心庫
   - `js/config/sumoselect-config.js` - 預設配置
   - `js/utils/sumoselect.js` - 初始化封裝工具
   - `css/vendor/sumoselect/` - 樣式文件

2. **RequireJS 全域別名**：
   - `sumoselect` - SumoSelect 核心庫
   - `sumoselectConfig` - 預設配置
   - `hotaiSumoselect` - 初始化工具

3. **從 CheckoutManagement 移除**：
   - 所有 Select2 相關的 JS 和 CSS 文件
   - Select2 的 RequireJS 配置

### ✅ 遷移結果
- ✅ Select2 已完全從系統中移除
- ✅ 所有功能已成功遷移到 SumoSelect
- ✅ 功能測試通過，無已知問題
- ✅ 代碼庫更加簡潔和統一

### 🔗 相關資源
- **SumoSelect 官方網站**：https://hemalatha.github.io/jquery.sumoselect/
- **使用文檔**：參考 UiShared/README.md
- **詳細的遷移分析**：可從 Git 歷史中查看（原 SELECT2_REMOVAL_ANALYSIS.md）

### 📝 經驗教訓
1. **共用組件應該集中管理**：避免在各業務模組中重複實作
2. **遷移前做好完整分析**：確保新方案能滿足所有需求
3. **保持功能對等**：遷移過程中確保不影響用戶體驗
4. **文檔同步更新**：遷移完成後及時更新相關文檔

---

## 未來遷移記錄

（待補充新的技術遷移記錄）

### 遷移記錄格式範例：

```
## 技術 A → 技術 B (日期)

### 📋 決策摘要
簡述遷移的內容和目標

### 🎯 遷移原因
1. 原因一
2. 原因二

### 📦 影響範圍
- 影響的模組或功能

### 🔧 主要變更
- 變更內容列表

### ✅ 遷移結果
- 結果和成效

### 🔗 相關資源
- 相關連結或文檔

### 📝 經驗教訓
- 學到的經驗
```

---

**維護說明**：
- 本文件記錄**系統級**的技術遷移，不記錄小型改動或功能新增
- 新增記錄時請按時間倒序排列（最新的在最上面）
- 每筆記錄應簡潔明瞭，詳細內容可連結到其他文檔或 Git commit

**最後更新**：2025-12-09  
**維護者**：HotaiConnected 開發團隊

