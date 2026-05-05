# Config 資料夾

此資料夾專門存放**配置文件**，用於自定義第三方套件或系統設定。

## 📋 規範

### ✅ 應該放在這裡的文件
- 第三方套件的自定義配置（如 `sumoselect-config.js`）
- 系統級別的配置設定
- 可重用的配置物件

### ❌ 不應該放在這裡的文件
- 第三方套件原始文件（請放在 `../vendor/`）
- 業務邏輯代碼（業務模組中自行管理）
- 工具函數（請放在 `../utils/`）

## 📦 目前的配置文件

| 檔案名稱 | 用途 | 對應套件 |
|---------|------|---------|
| `tabulator-config.js` | Tabulator 通用預設配置（含 `ajaxURL` 與 `columns` 空預設） | Tabulator |
| `sumoselect-config.js` | SumoSelect 多選下拉選單的預設配置 | SumoSelect |

**注意**：
- UiShared 提供 Tabulator 的「通用預設配置」：`ajaxURL` 預設為空字串 `''`，`columns` 預設為空陣列 `[]`，避免未覆蓋時觸發請求或報錯。
- 各業務模組只需覆蓋 `ajaxURL` 與 `columns`，其餘（分頁、語系、布局、rowHeader、ajaxResponse 等）沿用通用設定。

## 🔧 使用方式

在業務邏輯模組中引用配置：

```javascript
define([
    'tabulator',
    'YourModule/js/config/your-tabulator-config' // 業務配置（內部已合併通用配置）
], function(Tabulator, tabulatorConfig) {
    new Tabulator('#table', tabulatorConfig.getConfig());
});
```

或需要更細緻控制時，也可手動合併：

```javascript
define([
    'tabulator',
    'tabulatorConfig',               // UiShared 通用預設
    'YourModule/js/config/your-tabulator-config'  // 業務特定
], function(Tabulator, baseConfig, bizConfig) {
    var config = $.extend(true, {}, baseConfig.getDefaultConfig(), bizConfig.getConfig());
    new Tabulator('#table', config);
});
```

## 📝 配置文件規範

### 命名規範
- 檔案名稱：`{套件名稱}-config.js`
- 模組名稱：清楚描述配置用途

### 內容結構
```javascript
/**
 * {套件名稱} 配置模組
 * 
 * 提供 {套件名稱} 的預設配置
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
        // 配置物件或方法
    };
});
```

## 🎯 設計原則

1. **單一職責** - 每個配置文件只負責一個套件或功能
2. **可重用性** - 配置應該可以在多個地方重用
3. **可維護性** - 配置與業務邏輯分離，便於維護和更新
4. **文檔完整** - 每個配置選項都應有清楚的註解說明

## 📚 相關文件

- 第三方套件位置：`../vendor/`
- RequireJS 配置：`../../requirejs-config.js`
- UiShared 主文檔：`../../../../README.md`
