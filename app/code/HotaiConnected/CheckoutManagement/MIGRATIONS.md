# HotaiConnected CheckoutManagement - 技術遷移記錄

本文件記錄 CheckoutManagement 模組的重要技術遷移決策和過程。

---

## 例外授權名單功能實作 (2025-10-31)

### 📋 決策摘要
實作例外授權名單查看功能，支援兩種顯示模式（HTML 卡片式 / DataTree 樹狀結構），並採用 LESS BEM 架構管理樣式。

### 🎯 實作原因
1. **業務需求**: 需要查看和管理每筆對帳記錄的例外授權明細
2. **雙模式支援**: 不同場景需要不同的呈現方式（詳細查看 vs 快速對帳）
3. **性能優化**: 動態載入和緩存機制，避免不必要的 API 調用
4. **樣式可維護性**: 使用 LESS 和 BEM 規範，取代 inline styles

### 🔧 主要變更

#### 功能實作
- **API 整合**: 新增 `getExceptionAuth()` API 調用
  - 端點: `GET /rest/V1/reconciliation/exception-auth`
  - 參數: `ids` (comma-separated string)
  - 檔案: `services/api.js`（2025-11 之後移至 `shared/services/api/reconciliation.js`）
  
- **雙顯示模式**:
  - **HTML 模式**: 手動插入 DOM，三種模板版本（V1/V2/V3）
    - V1: 詳細卡片式布局，資訊分組清晰（適合全面查看）
    - V2: 緊湊卡片式，兩欄表格佈局（提高資訊密度）
    - V3: 極簡橫向表格式（財務快速對帳、主管復查專用）
  - **DataTree 模式**: 使用 Tabulator 內建樹狀結構
  
- **緩存機制**: 
  - 全局緩存對象 `_exceptionDataCache`
  - 按父記錄 ID 緩存子資料
  - 主表單資料更新時（換頁、篩選）自動清空緩存
  - 欄位可見性變更時保留緩存但收合所有展開項目

- **虛擬 DOM 處理**:
  - HTML 模式: 設定 `height: false` 和 `renderVertical: "basic"` 禁用虛擬 DOM
  - DataTree 模式: 使用 Tabulator 預設虛擬 DOM
  - 解決手動插入 DOM 元素在滾動時消失的問題

#### 樣式架構
- **LESS 文件**: `css/checkoutarea/components/table/_exception-authorization.less`
- **BEM 命名**: `.exception-auth__xxx` 格式，清晰的元素和修飾器結構
- **三種模板支援**: 統一樣式管理，支援 V1/V2/V3 模板
- **消除 inline styles**: 完全使用 CSS class，移除所有 inline styles（包括動態 margin-top）
- **CSS 相鄰選擇器**: 使用 `& + &` 處理卡片間距，取代 JavaScript 計算

#### 配置管理
- **exceptionConfig**: 集中管理功能開關和顯示模式
  - `enabled`: 功能總開關
  - `displayMode`: 'html' 或 'dataTree'
  - `isMode()`: 判斷當前模式的輔助方法
  - `getDataTreeConfig()`: 取得 DataTree 專用配置
  - `getLayoutConfig()`: 取得布局配置（HTML 模式禁用虛擬 DOM）

#### DataTree 配置
- **selectableRows**: 設為 "highlight"
- **selectableCheck**: 只允許選擇 level 0（父記錄）
- **CSS 隱藏**: 子記錄的 checkbox 使用 `visibility: hidden` 隱藏

#### 模板系統
- **使用 mage/template**: Magento 內建的 Underscore.js 模板引擎
- **三個模板文件**:
  - `exception-authorization-card.html` (V1)
  - `exception-authorization-card-v2.html` (V2)
  - `exception-authorization-card-v3.html` (V3)
- **動態模板切換**: 在 `createNestedHTML()` 中通過 `templateVersion` 變量控制

### ✅ 實作結果
- ✅ 雙模式展示功能完整且穩定
- ✅ 緩存機制有效減少 API 調用（首次載入後使用緩存）
- ✅ HTML 模式內容在滾動時不會消失（虛擬 DOM 已禁用）
- ✅ DataTree 子行正確隱藏 checkbox（功能 + 視覺雙重處理）
- ✅ LESS 樣式架構清晰，易於維護和擴展
- ✅ 三種模板版本滿足不同使用場景
- ✅ 欄位可見性變更時自動收合展開項目，保持介面整潔

### 📝 注意事項
1. **測試資料**: 目前使用 `_fakeExceptionData` 假資料，上線前需移除
2. **模板切換**: 在 `index.js` 的 `createNestedHTML()` 中修改 `templateVersion` 變量
3. **虛擬 DOM**: HTML 模式必須保持 `height: false` 設定，否則插入的 DOM 會消失
4. **緩存清理**: 換頁或篩選時緩存會自動清空，確保資料一致性
5. **API 端點**: 確認後端 API `/rest/V1/reconciliation/exception-auth` 已實作

### 🔗 相關文件
- **模板文件**: `view/adminhtml/web/template/checkoutarea/table/exception-authorization-card*.html`
- **樣式文件**: `view/adminhtml/web/css/checkoutarea/components/table/_exception-authorization.less`
- **API 文件**: `view/adminhtml/web/js/shared/services/api/reconciliation.js`
- **主控制器**: `view/adminhtml/web/js/checkoutarea/index.js`
- **Tabulator 配置**: `view/adminhtml/web/js/checkoutarea/config/tabulator-config.js`

---

## 組件化目錄重構 (2025-10-21)

### 📋 決策摘要
將 JavaScript 模組重新組織為組件化架構，根目錄只保留頁面主控制器，其他功能模組按職責分類到子目錄。

### 🎯 重構原因
1. **清晰的檔案結構**: 根目錄只放頁面主控制器（index.js, create.js），一目了然
2. **職責分離**: UI 組件、配置、服務各自獨立目錄
3. **易於擴展**: 新增組件只需加入 components/ 目錄
4. **符合現代前端規範**: 專業的目錄組織架構

### 🔧 主要變更

#### 目錄結構重組
```
舊結構：
checkoutarea/
├── index.js
├── create.js
├── filters.js               ❌ 與頁面控制器混在一起
├── table/                   ❌ 與其他目錄層級不一致
│   ├── select-action.js
│   ├── vendor-invoice-actions.js
│   └── actions/

新結構：
checkoutarea/
├── index.js                 ⭐ 列表頁主控制器
├── create.js                ⭐ 建立頁主控制器
├── components/              ✅ UI 組件目錄
│   ├── filters/
│   │   └── filters.js      ✅ 篩選器組件
│   └── table/              ✅ 表格相關組件
│       ├── select-action.js
│       ├── vendor-invoice-actions.js
│       └── actions/
├── config/                  ✅ 配置目錄
└── services/                ✅ API 服務目錄
```

#### 檔案移動
- `filters.js` → `components/filters/filters.js`
- `table/select-action.js` → `components/table/select-action.js`
- `table/vendor-invoice-actions.js` → `components/table/vendor-invoice-actions.js`
- `table/actions/*` → `components/table/actions/*`

#### 路徑更新
- `index.js`: 更新所有 require 引用路徑
- `requirejs-config.js`: 更新 mixin 路徑
- 各組件檔案: 更新 @module 註解

### ✅ 重構結果
- ✅ 根目錄清爽，只有頁面主控制器
- ✅ 組件分類明確（filters、table）
- ✅ 目錄職責清晰（components、config、services）
- ✅ 符合現代前端專案架構規範
- ✅ 功能測試通過，無已知問題

### 📝 注意事項
1. **清除快取**: 路徑變更後需執行快取清除和重新編譯
2. **引用路徑**: 新的路徑格式為 `components/filters/filters`, `components/table/select-action` 等
3. **向後兼容**: 舊路徑已失效，需更新所有引用

---

## Tabulator 配置架構重構 (2025-10-20)

### 📋 決策摘要
將 Tabulator 配置分離為通用配置和業務特定配置，通用配置移至 UiShared，業務特定配置保留在本模組。

### 🎯 重構原因
1. **配置分離**: 通用配置（分頁、語系、布局等）與業務配置（columns、ajaxURL）分離
2. **重複利用**: 其他模組可以重用通用配置，只需定義業務特定的部分
3. **維護性**: 通用配置的更新只需在 UiShared 進行一次
4. **架構清晰**: 明確區分通用功能和業務邏輯

### 🔧 主要變更

#### 新增 UiShared 通用配置
- `HotaiConnected_UiShared/js/config/tabulator-config.js`
- 提供 `getDefaultConfig()` 方法
- 包含分頁、語系、布局、AJAX 處理等通用配置

#### 重構業務特定配置
- `HotaiConnected_CheckoutManagement/js/checkoutarea/config/tabulator-config.js`
- 只保留 `columns` 和 `ajaxURL` 等業務特定配置
- 提供 `getConfig()` 方法

#### 更新使用方式
```javascript
// 舊方式
tabulatorConfig.config

// 新方式（推薦）：業務配置自動合併通用配置
tabulatorConfig.getConfig()

// 新方式（手動合併）：如果需要更細緻的控制
$.extend(true, {}, baseTabulatorConfig.getDefaultConfig(), checkoutTabulatorConfig.getConfig())
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

## 共用 UI 資源遷移至 UiShared (2025-10-XX)

### 📋 決策摘要
將所有共用的 UI 組件、第三方套件（Tabulator、SumoSelect）和工具函數遷移至 **HotaiConnected_UiShared** 模組，本模組只保留業務邏輯代碼。

### 🎯 遷移原因
1. **避免重複**: 多個模組不再需要各自維護相同的 UI 資源
2. **統一管理**: 所有共用資源在 UiShared 集中管理和更新
3. **版本一致**: 確保所有模組使用相同版本的第三方套件
4. **模組職責清晰**: CheckoutManagement 專注於對帳業務邏輯

### 🔧 主要變更

#### 遷移至 UiShared 的資源
- **第三方套件**:
  - Tabulator.js 6.3
  - SumoSelect
  
- **UI 組件**:
  - Toast Message（訊息提示）
  - Loading Mask（載入動畫）
  - Searchable Dialog Multiselect（可搜尋多選對話框）
  - Tri-State Group Multiselect（三態 checkbox 群組）
  - Selected Items Display（選中項目顯示）
  
- **服務和工具**:
  - hotaiRequest（統一 HTTP 請求服務）
  - hotaiDatePickers（日期選擇器封裝）
  - hotaiSumoselect（SumoSelect 初始化工具）

#### 本模組保留的內容
- **業務邏輯**:
  - `services/api.js`（後續遷移為 `shared/services/api/reconciliation.js`）
  - `config/tabulator-config.js`（對帳區域特定的 Tabulator 配置）
  - `filters.js`（篩選器業務邏輯）
  - `table/actions/`（批量操作：解鎖、授權、下載）
  
- **頁面控制器**:
  - `index.js`（列表頁）
  - `create.js`（新增頁）

### ✅ 遷移結果
- ✅ 所有共用資源已移至 UiShared
- ✅ 本模組代碼大幅精簡
- ✅ RequireJS 配置已更新使用 UiShared 的全域別名
- ✅ 功能測試通過，無已知問題

### 📝 注意事項
1. **依賴性**: 本模組現在必須依賴 HotaiConnected_UiShared 才能運作
2. **安裝順序**: 必須先安裝 UiShared 模組
3. **更新**: 更新 UI 資源時只需更新 UiShared 模組

---

## 模組架構標準化 (2024-01-XX)

### 📋 決策摘要
採用標準 Magento 2 後台模組架構，使用 `Controller/Adminhtml/` 命名空間和 `checkout_management` 路由。

### 🎯 遷移原因
1. **遵循標準**: 符合 Magento 2 官方最佳實踐
2. **清晰的結構**: 標準化的目錄結構易於理解和維護
3. **避免衝突**: 使用獨立的路由前綴避免與其他模組衝突

### 🔧 主要變更

#### 實際模組結構
```
CheckoutManagement/
├── Controller/Adminhtml/          # 後台控制器
│   ├── CheckoutArea/
│   │   ├── Index.php             # 對帳區域列表
│   │   └── Create.php            # 建立月結批次
│   ├── VoucherReconciliationArea/
│   │   └── Index.php             # 票券對帳
│   ├── ApprovalFunctionality/
│   │   └── Index.php             # 審核功能
│   └── ExportOrderCheckoutData/
│       └── Index.php             # 匯出資料
│
├── Model/                         # 資料模型
│   ├── CheckoutData.php
│   └── ResourceModel/
│       └── CheckoutArea/Grid/
│           └── Collection.php
│
├── view/adminhtml/
│   ├── layout/                    # Layout 檔案
│   │   ├── checkout_management_checkoutarea_index.xml
│   │   ├── checkout_management_checkoutarea_create.xml
│   │   ├── checkout_management_voucherreconciliationarea_index.xml
│   │   ├── checkout_management_approvalfunctionality_index.xml
│   │   └── checkout_management_exportordercheckoutdata_index.xml
│   │
│   └── web/
│       ├── js/checkoutarea/       # 業務邏輯 JavaScript
│       └── css/checkoutarea/      # 業務邏輯樣式
│
└── etc/
    └── adminhtml/
        └── routes.xml              # 路由配置
```

#### 路由配置
  ```xml
<route id="checkout_management" frontName="checkout_management">
      <module name="HotaiConnected_CheckoutManagement"/>
  </route>
  ```

#### URL 格式
```
admin/checkout_management/checkoutarea/index
admin/checkout_management/checkoutarea/create
admin/checkout_management/voucherreconciliationarea/index
admin/checkout_management/approvalfunctionality/index
admin/checkout_management/exportordercheckoutdata/index
```

### ✅ 遷移結果
- ✅ 採用標準 Magento 2 架構
- ✅ 路由配置清晰明確
- ✅ 目錄結構符合最佳實踐
- ✅ 功能測試通過，無已知問題

### 📝 注意事項
1. **純後台模組**: 本模組只有 adminhtml 視圖，沒有 frontend
2. **ACL 權限**: 權限配置在 `etc/acl.xml`
3. **選單配置**: 後台選單由 `Branch8_HifiSalesReport` 模組提供

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

### 🔧 主要變更
- 變更內容列表

### ✅ 遷移結果
- 結果和成效

### 📝 注意事項
- 需要注意的事項
```

---

## 共用對帳 API 抽離至 shared/services (2025-11-13)

### 📋 決策摘要
抽離原本分散於 `checkoutarea`、`approvalfunctionality` 的 `services/api.js`，統一至 `shared/services/api/reconciliation.js`，由各頁面透過簡化包裝或直接引用，避免重覆維護端點。

### 🎯 實作原因
1. **減少重複程式碼**：兩頁面使用同一組 `/rest/V1/reconciliation/*` 端點，分散維護易造成落差。
2. **提高一致性**：共用服務集中於一處，未來調整 API 只需改一份。
3. **保留擴充彈性**：頁面若有專屬流程，可額外在本地包裝層處理。

### 🔧 主要變更
- 新增 `view/adminhtml/web/js/shared/services/api/reconciliation.js`，整合所有對帳 API，並補齊 JSDoc 文件。
- `checkoutarea`、`approvalfunctionality` 的 `services/api.js` 改為引用 shared 服務，並在本次刪除檔案。
- 更新所有引用（filters、actions、index.js 等）改指向 shared 服務。
- README、服務 README、MIGRATIONS 補上路徑調整說明。

### ✅ 成果
- ✅ 對帳 API 僅維護一份，減少未來修改風險。
- ✅ 頁面程式碼更精簡，引用語意清楚。
- ✅ 文檔同步更新，開發者易於了解新結構。

---

## 例外授權 API 獨立抽離 (2025-12-09)

### 📋 決策摘要
將例外授權相關的 API 從對帳系統 API 中獨立出來，建立專屬的 `exception-auth.js` 服務模組，處理 `/rest/V1/exception-auth` 端點。

### 🎯 實作原因
1. **API 端點分離**：例外授權功能有獨立的 API 端點（`/rest/V1/exception-auth`），與對帳系統的 `/rest/V1/reconciliation/exception-auth` 不同
2. **職責清晰**：例外授權作為獨立功能模組，應有專屬的 API 服務層
3. **易於維護**：獨立模組便於未來擴展和維護
4. **避免混淆**：區分對帳系統的例外授權和獨立的例外授權功能

### 🔧 主要變更

#### 新增獨立 API 服務
- **檔案**: `view/adminhtml/web/js/shared/services/api/exception-auth.js`
- **端點**: `/rest/V1/exception-auth`
- **功能**:
  - `getExceptionAuthList()`: 取得例外授權名單
  - `updateExceptionAuthStatus()`: 更新例外授權狀態
  - `exportExceptionAuth()`: 匯出例外授權名單

#### 對帳系統 API 保留
- **檔案**: `view/adminhtml/web/js/shared/services/api/reconciliation.js`
- **端點**: `/rest/V1/reconciliation/exception-auth`
- **功能**:
  - `getBatchExceptionAuth()`: 取得對帳批次用的例外授權名單
  - `uploadExceptionAuth()`: 上傳例外授權資料（對帳批次用）
  - `exportExceptionAuth()`: 匯出例外授權名單（對帳批次用）

#### 使用方式
- **獨立例外授權功能**: 使用 `api/exception-auth.js`
- **對帳系統例外授權**: 使用 `api/reconciliation.js` 中的相關方法

### ✅ 實作結果
- ✅ 例外授權 API 獨立為專屬服務模組
- ✅ 對帳系統的例外授權功能保留在 reconciliation.js
- ✅ 職責分離清晰，避免混淆
- ✅ 功能測試通過，無已知問題

### 📝 注意事項
1. **端點區分**: `/rest/V1/exception-auth` 與 `/rest/V1/reconciliation/exception-auth` 是不同的端點
2. **使用場景**: 
   - 獨立例外授權功能頁面使用 `exception-auth.js`
   - 對帳系統中的例外授權功能使用 `reconciliation.js` 中的方法
3. **向後兼容**: reconciliation.js 中的例外授權方法保留，不影響現有功能

---

**維護說明**：
- 本文件記錄**系統級**的技術遷移，不記錄小型改動或功能新增
- 新增記錄時請按時間倒序排列（最新的在最上面）
- 每筆記錄應簡潔明瞭，詳細內容可連結到其他文檔或 Git commit

**最後更新**：2025-12-09  
**維護者**：HotaiConnected 開發團隊
