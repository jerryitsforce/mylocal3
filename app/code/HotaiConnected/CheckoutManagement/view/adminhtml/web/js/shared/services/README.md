# Shared Services

## 📂 目錄用途

此目錄集中放置多個頁面共用的對帳系統 API 服務。目前包含 `api/reconciliation.js`，供 `checkoutarea`、`approvalfunctionality` 等功能使用。

## 🎯 設計原則

1. **統一來源**：所有 `/rest/V1/reconciliation/*` 相關端點僅維護一份，避免頁面分支。
2. **薄包裝策略**：若頁面需要額外流程，可在各自模組內再包裝，不修改共用函式。
3. **依賴 UiShared**：仍透過 `hotaiRequest` 處理 form_key、loading、toast 及錯誤處理。

---

## 📁 目前檔案

### `api/reconciliation.js`
- **端點前綴**: `/rest/V1/reconciliation/*`
- 封裝常用端點：
  - `getReconciliationStatusData()` - 取得對帳狀態資料
  - `getReconciliationSellers()` - 查詢特約商
  - `getReconciliationBatches()` - 查詢對帳單批次
  - `createMonthlyBatch()` - 建立月結帳批次
  - `deleteCheckout()` - 解除結帳
  - `createVendorInvoice()` - 新增廠商發票
  - `sendStatement()` - 寄出對帳單
  - `downloadVendorStatement()` - 下載廠商對帳單（ZIP）
  - `downloadMonthlySummary()` - 下載月結總表（XLSX）
  - `getBatchExceptionAuth()` - 取得對帳批次用的例外授權名單
  - `uploadExceptionAuth()` - 上傳例外授權資料（對帳批次用）
  - `exportExceptionAuth()` - 匯出例外授權名單（對帳批次用）
  - `exportTicketVerificationDetails()` - 下載票券兌換報表
  - `getTicketCheckoutList()` - 票券結帳匯出列表
- 使用 `hotaiRequest` 的 `get` / `post` / `download` 介面。

### `api/exception-auth.js`
- **端點前綴**: `/rest/V1/exception-auth`
- 封裝例外授權專屬端點：
  - `getExceptionAuthList()` - 取得例外授權名單
  - `updateExceptionAuthStatus()` - 更新例外授權狀態
  - `exportExceptionAuth()` - 匯出例外授權名單
- 使用 `hotaiRequest` 的 `get` / `post` / `download` 介面。
- **注意**: 此模組處理獨立的例外授權功能，與對帳系統中的例外授權（`/rest/V1/reconciliation/exception-auth`）不同。

---

## 💡 使用方式

### 基本引用
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function (reconciliationApi) {
    reconciliationApi.getReconciliationStatusData()
        .done(function (response) {
            console.log('對帳狀態:', response);
        });
});
```

### 帶參數請求
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function (api) {
    api.getReconciliationSellers({ status: 'active' })
        .done(function (sellers) {
            console.log('特約商列表:', sellers);
        });
});
```

### POST 範例
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function (api) {
    api.createMonthlyBatch({
        from: '2025-01-01 00:00:00',
        to: '2025-01-31 23:59:59',
        seller_code: 'SELLER001'
    });
});
```

### 例外授權 API 使用範例
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/exception-auth'
], function(apiExceptionAuth) {
    // 取得例外授權名單
    apiExceptionAuth.getExceptionAuthList({ page: 1, page_size: 20 })
        .done(function(response) {
            console.log('例外授權名單:', response.items);
        });

    // 更新例外授權狀態
    apiExceptionAuth.updateExceptionAuthStatus({
        ids: [1, 2, 3],
        status: 'approved'
    });

    // 匯出例外授權名單
    apiExceptionAuth.exportExceptionAuth([1, 2, 3], {
        filename: '例外授權報表.xlsx'
    });
});
```

### 對帳系統例外授權 API 使用範例
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function(api) {
    // 取得對帳批次用的例外授權名單
    api.getBatchExceptionAuth([1, 2, 3])
        .done(function(response) {
            console.log('對帳批次例外授權名單:', response.items);
        });

    // 上傳例外授權資料（對帳批次用）
    api.uploadExceptionAuth(formData);

    // 匯出例外授權名單（對帳批次用）
    api.exportExceptionAuth({ ids: [1, 2, 3] }, { filename: 'exception-auth.csv' });
});
```

---

## 🔧 擴充建議

1. **頁面專屬流程**：若需要額外 Toast、資料轉換，可在各頁面的包裝層實作。
2. **新增共用端點**：直接在 `api/reconciliation.js` 增加函式，並撰寫 JSDoc。
3. **文件同步**：若端點新增或行為調整，記得更新 README 與 MIGRATIONS。

---

**版本**: 1.1.0  
**最後更新**: 2025-12-09  
**維護**: HotaiConnected 開發團隊
