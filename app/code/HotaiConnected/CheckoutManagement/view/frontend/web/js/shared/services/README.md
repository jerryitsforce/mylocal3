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
- 封裝常用端點：
  - `getReconciliationStatusData()`
  - `getReconciliationSellers()`
  - `getReconciliationBatches()`
  - `createMonthlyBatch()`
  - `deleteCheckout()`
  - `createVendorInvoice()`
  - `sendStatement()`
  - `downloadVendorStatement()`
  - `downloadMonthlySummary()`
  - `getExceptionAuth()` / `getBatchExceptionAuth()` / `uploadExceptionAuth()` / `exportExceptionAuth()`
- 使用 `hotaiRequest` 的 `get` / `post` / `download` 介面。

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

### **步驟 2：在其他模組中使用**
```javascript
define([
    'HotaiConnected_CheckoutManagement/js/shared/services/api/reconciliation'
], function(api) {
    api.getBatchExceptionAuth([1, 2, 3])
        .done(function(response) {
            console.log('例外授權名單:', response.items);
        });

    api.exportExceptionAuth({ ids: [1, 2, 3] }, { filename: 'exception-auth.csv' });
});
```

---

## 🔧 擴充建議

1. **頁面專屬流程**：若需要額外 Toast、資料轉換，可在各頁面的包裝層實作。
2. **新增共用端點**：直接在 `api/reconciliation.js` 增加函式，並撰寫 JSDoc。
3. **文件同步**：若端點新增或行為調整，記得更新 README 與 MIGRATIONS。

---

**版本**: 1.0.0  
**最後更新**: 2025-11-13  
**維護**: HotaiConnected 開發團隊
