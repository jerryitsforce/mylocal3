# HotaiConnected CheckoutManagement 模組

## 概述

HotaiConnected CheckoutManagement 是一個專為 Magento 2 設計的結帳管理模組，提供完整的結帳流程管理功能。本模組整合了多種結帳相關功能，包括結帳區域管理、月結批次建立、票券對帳、審核功能以及訂單結帳資料匯出等。

**重要**: 本模組依賴 **HotaiConnected_UiShared** 模組提供所有共用 UI 資源（Tabulator、SumoSelect、Toast 等）。

## 模組資訊

- **模組名稱**: `HotaiConnected_CheckoutManagement`
- **版本**: 1.0.0
- **類型**: 後台管理模組 (Adminhtml) - 業務邏輯模組
- **必要依賴**: 
  - `HotaiConnected_UiShared` ⭐（提供所有共用 UI 資源）
  - `Magento_Backend`（後台頁面框架、ACL 權限系統）
  - `Magento_Sales`（訂單、商品等銷售相關資料模型）
  - `Branch8_HifiSalesReport`（後台選單配置和報表功能）

## 主要功能

### 1. 結帳區域管理 (Checkout Area)
- **功能描述**: 提供完整的結帳區域管理介面，支援 Tabulator 表格顯示
- **主要特色**:
  - 結帳資料的檢視和管理（使用假資料展示，共150筆記錄）
  - 支援多種篩選條件（批次代碼、結帳異動日期、館長姓名、特約商名稱等）
  - 批量操作功能（下載廠商對帳單、下載月結總表、解鎖結帳、發送對帳單、例外授權）
  - 即時資料更新和分頁功能
  - 支援多選操作和確認彈窗
  - 廠商發票號碼管理（支援多個發票號碼）

### 2. 月結批次建立 (Create Monthly Batch)
- **功能描述**: 建立和管理月結帳批次
- **主要特色**:
  - 批次建立表單（包含商品物流狀態、發票狀態、票券狀態選擇）
  - **三態 Checkbox 群組**：智能全選功能，支援全選/部分選/未選三種狀態
  - **可搜尋多選對話框**：特約商名稱選擇，支援即時搜尋和篩選
  - 狀態管理（Pending、Shipped、Delivered、Cancelled、Returned、Refunded等）
  - 表單驗證機制（validate-one-required-by-name）
  - 響應式設計
  - 整合日期選擇器（支援 6 個月範圍限制）
  - Toast 訊息提示系統

### 3. 票券對帳區域 (Voucher Reconciliation Area)
- **功能描述**: 處理票券相關的對帳作業
- **主要特色**:
  - 票券狀態追蹤
  - 對帳資料管理
  - 異常處理

### 4. 審核功能 (Approval Functionality)
- **功能描述**: 提供審核相關的功能
- **主要特色**:
  - 審核流程管理
  - 權限控制
  - 狀態追蹤

### 5. 訂單結帳資料匯出 (Export Order Checkout Data)
- **功能描述**: 匯出訂單結帳相關資料
- **主要特色**:
  - 多種匯出格式
  - 篩選條件支援
  - 批量匯出

## 技術架構

### 模組結構
```
app/code/HotaiConnected/CheckoutManagement/
├── Controller/                   # 控制器
│   └── Adminhtml/
│       ├── ApprovalFunctionality/
│       │   └── Index.php
│       ├── CheckoutArea/
│       │   ├── Index.php
│       │   └── Create.php
│       ├── ExportOrderCheckoutData/
│       │   └── Index.php
│       └── VoucherReconciliationArea/
│           └── Index.php
├── view/                         # 視圖檔案
│   └── adminhtml/               # 後台視圖
│       ├── layout/              # 布局檔案
│       ├── templates/           # 模板檔案
│       ├── requirejs-config.js  # RequireJS 配置
│       └── web/                 # 前端資源
│           ├── css/             # 樣式檔案
│           ├── js/              # JavaScript 檔案
│           └── template/        # HTML 模板
├── etc/                         # 配置檔案
│   ├── acl.xml                  # 權限配置
│   ├── adminhtml/
│   │   └── routes.xml           # 後台路由
│   ├── module.xml               # 模組配置
│   └── （其他配置已移除）
├── i18n/                        # 國際化檔案
│   └── zh_Hant_TW.csv
├── MIGRATIONS.md                # 技術遷移記錄
└── registration.php             # 模組註冊
```

### 依賴關係

#### 後端依賴
- **Magento_Backend**: 後台管理功能（提供後台頁面框架、ACL 權限系統）
- **Magento_Sales**: 銷售模組（提供訂單、商品等銷售相關資料模型）
- **Branch8_HifiSalesReport**: 銷售報表模組（提供後台選單配置和報表功能）

#### 前端依賴
- **HotaiConnected_UiShared**: 共用 UI 資源模組（必須）
  - 提供 Tabulator.js 6.3（表格顯示）
  - 提供 SumoSelect（多選下拉選單）
  - 提供 Toast Message（訊息提示）
  - 提供 Loading Mask（載入動畫）
  - 提供 Searchable Dialog Multiselect（可搜尋多選對話框）
  - 提供 Tri-State Group Multiselect（三態 checkbox 群組）
  - 提供 hotaiRequest（統一 HTTP 請求服務）

### 本模組技術棧
- **RequireJS**: 模組載入器
- **jQuery**: DOM 操作和事件處理
- **Magento UI Components**: 表單驗證、日期選擇器
- **LESS**: 樣式預處理器（使用 BEM 命名規範）
- **業務邏輯**: 對帳系統特定的功能實作

## 核心功能詳解

### 1. 資料管理
目前後端僅保留控制器與視圖範例，無實際資料來源；頁面展示使用假資料或分層自訂流程。

### 2. API 介面
- **端點**: `/V1/checkout-management/data`
- **方法**: GET, POST
- **功能**: 獲取結帳相關資料
- **參數**:
  - `page`: 頁碼（可選，預設為 1）
  - `limit`: 每頁筆數（可選，預設為 20，最大100）
  - `filters`: 篩選條件（可選，JSON 格式）

### 3. 批量操作
- **下載廠商對帳單**: 下載選中項目的廠商對帳單
- **下載月結總表**: 彈出日期選擇視窗，下載月結總表
- **解鎖結帳**: 解鎖選中的結帳項目
- **發送對帳單**: 發送選中的對帳單
- **例外授權**: 對選中項目進行例外授權

### 6. 例外授權名單 (Exception Authorization)
- **功能描述**: 查看和管理例外授權記錄，支援兩種顯示模式
- **主要特色**:
  - **HTML 模式**: 卡片式展示，提供三種模板版本（V1/V2/V3）
    - V1: 詳細卡片式布局，資訊分組清晰
    - V2: 緊湊卡片式，兩欄表格佈局
    - V3: 極簡橫向表格式，適合財務快速對帳和主管復查
  - **DataTree 模式**: 樹狀結構，支援展開/收合查看子記錄
  - **智能緩存**: 已載入的例外授權資料會被緩存，避免重複 API 調用
  - **虛擬 DOM 兼容**: HTML 模式禁用虛擬 DOM，確保手動插入的內容不會消失
  - **BEM 樣式架構**: 使用 LESS 樣式和 BEM 命名規範，易於維護
  - **動態載入**: 點擊展開時才載入子資料，提升性能

### 4. 篩選功能
- **批次代碼**: 文字搜尋
- **結帳異動日期**: 日期範圍選擇
- **館長姓名**: 文字搜尋
- **特約商名稱**: 文字搜尋
- **特約商代碼**: 文字搜尋
- **資源來源**: 下拉選擇（online/offline）
- **票券狀態**: 下拉選擇
- **商品物流狀態**: 下拉選擇
- **發票狀態**: 下拉選擇

## 路由配置

### 後台路由
**檔案路徑**: `etc/adminhtml/routes.xml`

- **路由名稱**: `checkout_management`
- **前端名稱**: `checkout_management`
- **類型**: 後台路由 (admin)

### URL 路徑
所有功能都透過後台管理介面存取：

- `admin/checkout_management/checkoutarea` - 結帳區域管理
- `admin/checkout_management/checkoutarea/create` - 建立月結帳批次表單
- `admin/checkout_management/voucherreconciliationarea` - 憑證對帳區域
- `admin/checkout_management/approvalfunctionality` - 審核功能
- `admin/checkout_management/exportordercheckoutdata` - 匯出訂單結帳資料

## 權限設定

### ACL 權限配置
**檔案路徑**: `etc/acl.xml`

**權限結構**:
```xml
<resource id="HotaiConnected_CheckoutManagement::menu" title="Checkout Management">
    <resource id="HotaiConnected_CheckoutManagement::checkout_area" title="Checkout Area"/>
    <resource id="HotaiConnected_CheckoutManagement::export_order_checkout_data" title="Export Order Checkout Data"/>
    <resource id="HotaiConnected_CheckoutManagement::approval_functionality" title="Approval Functionality"/>
    <resource id="HotaiConnected_CheckoutManagement::voucher_reconciliation_area" title="Voucher Reconciliation Area"/>
    <resource id="HotaiConnected_CheckoutManagement::checkout_create_batch" title="Create Checkout Batch"/>
    <resource id="HotaiConnected_CheckoutManagement::checkout_unlock" title="Unlock Checkout"/>
    <resource id="HotaiConnected_CheckoutManagement::checkout_view" title="View Checkout"/>
</resource>
```

**權限設定說明**:
- 所有權限都繼承自 `Magento_Backend::admin`
- 主要權限群組為 `HotaiConnected_CheckoutManagement::menu`
- 各功能頁面都有對應的獨立權限資源
- 管理員可在後台 **系統 > 權限 > 使用者角色** 中設定權限

## 前端架構

### JavaScript 模組結構
```
view/adminhtml/web/js/
├── checkoutarea/
│   ├── index.js                 # 列表頁主控制器 ⭐
│   ├── create.js                # 建立頁主控制器 ⭐
│   ├── components/              # UI 組件目錄
│   │   ├── filters/
│   │   │   └── filters.js      # 篩選器組件
│   │   └── table/              # 表格相關組件
│   │       ├── select-action.js         # 批量操作處理
│   │       ├── vendor-invoice-actions.js # 廠商發票操作
│   │       └── actions/                 # 批量操作實作
│   │           ├── download-monthly-summary.js
│   │           ├── exception-authorization.js
│   │           └── unlock-checkout.js
│   ├── config/                  # 配置目錄
│   │   └── tabulator-config.js  # Tabulator 配置（業務特定：columns + ajaxURL + ajaxResponse）
│   └── 物流狀態整理.txt         # 開發筆記

└── shared/
    └── services/
        ├── api/
        │   ├── reconciliation.js  # 共用對帳 API 服務
        │   └── exception-auth.js   # 例外授權 API 服務
        └── README.md               # 使用說明

注意：共用資源（tabulator, sumoselect, loading-mask, toast-message 等）
已移至 UiShared 模組，透過 RequireJS 全域別名使用
```

**架構設計原則：**
- 根目錄只放頁面主控制器（index.js, create.js）
- UI 組件統一放在 components/ 目錄
- 配置檔案放在 config/ 目錄
- 共用 API 放在 shared/services/ 目錄

### 模組依賴關係
```
checkoutarea/index.js (列表頁主控制器)
├── tabulator (來自 UiShared)
├── hotaiLoadingMask (來自 UiShared)
├── hotaiToastMessage (來自 UiShared)
├── config/tabulator-config.js (業務特定配置)
├── components/filters/filters.js (篩選器組件)
│   ├── hotaiDatePickers (來自 UiShared)
│   ├── hotaiSumoselect (來自 UiShared)
│   └── shared/services/api/reconciliation.js (API 調用)
├── components/table/select-action.js (批量操作處理)
│   ├── components/table/actions/unlock-checkout.js
│   ├── components/table/actions/download-monthly-summary.js
│   └── components/table/actions/exception-authorization.js
└── components/table/vendor-invoice-actions.js (廠商發票操作)

checkoutarea/create.js (建立頁主控制器)
├── hotaiLoadingMask (來自 UiShared)
├── hotaiToastMessage (來自 UiShared)
├── hotaiSearchableDialogMultiselect (來自 UiShared)
├── hotaiTriStateGroupMultiselect (來自 UiShared)
├── hotaiDatePickers (來自 UiShared)
└── shared/services/api/reconciliation.js (API 調用)

shared/services/api/reconciliation.js (共用對帳 API 服務層)
└── hotaiRequest (來自 UiShared)
    ├── hotaiLoadingMask (自動整合)
    └── hotaiToastMessage (自動整合)

shared/services/api/exception-auth.js (例外授權 API 服務層)
└── hotaiRequest (來自 UiShared)
    ├── hotaiLoadingMask (自動整合)
    └── hotaiToastMessage (自動整合)
```

### 樣式架構
```
view/adminhtml/web/css/
└── checkoutarea/                         # 對帳區域樣式
    ├── index.less                        # 列表頁樣式入口
    ├── create.less                       # 新增頁樣式入口
    ├── pages/                            # 頁面特定樣式
    │   ├── _index.less                   # 列表頁樣式
    │   └── _create.less                  # 新增頁樣式
    └── components/                       # 組件樣式
        ├── _filters.less                 # 篩選器樣式
        ├── _checkout-popup.less          # 確認彈窗樣式
        ├── _checkout-popup-actions.less  # 彈窗操作樣式
        └── table/                        # 表格相關樣式
            ├── _exception-authorization.less  # 例外授權名單樣式（BEM 架構）
            └── cells/
                ├── _table-cells-common.less   # 表格單元格通用樣式
                └── _vendor-invoice.less       # 廠商發票樣式

注意：共用樣式（Tabulator、SumoSelect、Toast 等）已移至 UiShared 模組
```

## 安裝與配置

### 前置需求 ⚠️
**必須先安裝 HotaiConnected_UiShared 模組**，本模組才能正常運作。

### 1. 安裝模組
```bash
# 步驟 1: 確認 UiShared 模組已安裝
php bin/magento module:status HotaiConnected_UiShared

# 步驟 2: 啟用本模組
php bin/magento module:enable HotaiConnected_CheckoutManagement

# 步驟 3: 執行安裝
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

### 2. 權限配置
管理員需要設定相應的 ACL 權限才能存取模組功能。

### 3. 後台選單配置
模組的後台選單是透過 `Branch8_HifiSalesReport` 模組配置的，位於：
- **檔案路徑**: `Branch8/HifiSalesReport/etc/adminhtml/menu.xml`

## 使用指南

### 1. 結帳區域管理
1. 登入 Magento 後台
2. 導航至 **Financial Reconciliation > Reconciliation Area > Checkout Area**
3. 使用篩選器搜尋特定的結帳資料
4. 選擇需要操作的項目
5. 從批量操作下拉選單選擇操作類型
6. 點擊執行按鈕完成操作

### 2. 建立月結批次
1. 導航至 **Financial Reconciliation > Reconciliation Area > Checkout Area > Create**
2. 填寫必要的批次資訊
3. 選擇商品物流狀態、發票狀態、票券狀態
4. 使用多選下拉選單選擇相關選項
5. 提交表單建立批次

### 3. 票券對帳
1. 導航至 **Financial Reconciliation > Reconciliation Area > Voucher Reconciliation Area**
2. 檢視票券狀態
3. 執行對帳作業

## 技術特色

### 1. 模組化架構設計
- **功能導向組織**：按功能模組（create、confirm-popup、table、ajax）組織代碼
- **一致性結構**：JavaScript、Template、Controller 使用相同的目錄結構
- **高內聚低耦合**：相關功能集中管理，降低維護成本
- **分層清晰**：業務邏輯、API 通訊、工具函數明確分離

### 2. 使用 UiShared 共用資源
本模組完全依賴 **HotaiConnected_UiShared** 模組提供的共用 UI 資源：

#### 使用的第三方套件（來自 UiShared）
- **Tabulator.js 6.3**：互動式資料表格，用於對帳區域列表
- **SumoSelect**：多選下拉選單，用於篩選和表單選擇

#### 使用的 UI 組件（來自 UiShared）
- **Searchable Dialog Multiselect**：可搜尋的多選對話框（特約商選擇）
- **Tri-State Group Multiselect**：三態 checkbox 群組（狀態多選）
- **Toast Message**：訊息提示系統（成功、錯誤、警告、資訊）
- **Loading Mask**：全螢幕載入動畫

#### 使用的服務和工具（來自 UiShared）
- **hotaiRequest**：統一 HTTP 請求服務，自動處理 form_key、loading、toast
- **hotaiDatePickers**：日期選擇器封裝
- **hotaiSumoselect**：SumoSelect 初始化工具

#### 本模組的實作
- **API 服務層**：
  - `shared/services/api/reconciliation.js`：集中定義對帳系統 API（`/rest/V1/reconciliation/*`）
  - `shared/services/api/exception-auth.js`：例外授權專屬 API 服務（`/rest/V1/exception-auth`）
- **REST 回應格式**：後端 `CheckoutData` 模型直接回傳陣列，包括 `data`、`success`、`pagination`、`timestamp`、`error` 欄位
- **業務邏輯**：Tabulator 配置、篩選器、批量操作等對帳系統特定功能
- **頁面控制器**：`index.js`、`create.js` 等頁面入口

### 3. 前端架構特色
- **清晰的檔案結構**：JavaScript、Template、CSS 完美對應
- **完整的文檔**：每個模組都有詳細的功能說明和使用範例
- **錯誤處理**：完善的驗證和錯誤提示機制
- **可維護性**：統一的命名規範和架構模式
- **BEM 命名規範**：CSS 使用 BEM 規範，提高可讀性

## 授權資訊

本模組為 HotaiConnected 內部使用，版權所有。詳細授權資訊請參考 COPYING.txt 檔案。

---

## 技術遷移記錄

詳細的技術遷移記錄請參考 [MIGRATIONS.md](MIGRATIONS.md)。

---

**文檔版本**: 4.2  
**最後更新**: 2025年12月9日  
**維護團隊**: HotaiConnected / Branch8