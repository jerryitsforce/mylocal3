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

| 檔案名稱 | 用途 | 對應套件 | 版本 |
|---------|------|---------|------|
| `tabulator-config.js` | Tabulator 通用預設配置（支援本地/遠端分頁） | Tabulator | 2.2.1 |
| `sumoselect-config.js` | SumoSelect 多選下拉選單的預設配置 | SumoSelect | 2.0.0 |

**注意**：
- UiShared 提供 Tabulator 的「通用預設配置」：`ajaxURL` 預設為空字串 `''`，`columns` 預設為空陣列 `[]`，避免未覆蓋時觸發請求或報錯。
- 各業務模組只需覆蓋 `ajaxURL` 與 `columns`，並實作 `ajaxResponse`，其餘（分頁、語系、布局、rowHeader 等）沿用通用設定。
- Tabulator 配置支援兩種分頁模式：本地分頁（`getLocalConfig()`）和遠端分頁（`getRemoteConfig()`）。

## 🔧 使用方式

### **1. Tabulator 配置**

#### **遠端分頁（推薦用於大數據 >1000 筆）**
```javascript
define([
    'tabulator',
    'tabulatorConfig',
    'jquery'
], function(Tabulator, baseConfig, $) {
    // 取得遠端分頁基礎配置
    var config = baseConfig.getRemoteConfig();
    
    // 業務模組覆蓋配置
    $.extend(true, config, {
        ajaxURL: 'rest/V1/your-api',
        ajaxResponse: function(url, params, response) {
            // 從 API 回應中取得資料
            var items = response.items || [];
            var totalRecord = response.total_record || 0;
            var pageSize = response.page_size || 20;
            var lastPage = Math.ceil(totalRecord / pageSize);
            
            // 返回 Tabulator 遠端分頁格式
            return {
                last_page: lastPage,
                data: items
            };
        },
        columns: [
            {title: 'ID', field: 'id'},
            {title: 'Name', field: 'name'}
        ],
        // 儲存當前篩選參數（用於換頁時保留篩選條件）
        currentFilterParams: {}
    });
    
    new Tabulator('#table', config);
});
```

#### **本地分頁（適用於小數據 <1000 筆）**
```javascript
define([
    'tabulator',
    'tabulatorConfig',
    'jquery'
], function(Tabulator, baseConfig, $) {
    // 取得本地分頁基礎配置
    var config = baseConfig.getLocalConfig();
    
    // 業務模組覆蓋配置
    $.extend(true, config, {
        ajaxURL: 'rest/V1/your-api',
        ajaxResponse: function(url, params, response) {
            // 返回完整資料陣列，Tabulator 會在前端自動分頁
            return response.items || [];
        },
        columns: [
            {title: 'ID', field: 'id'},
            {title: 'Name', field: 'name'}
        ]
    });
    
    new Tabulator('#table', config);
});
```

### **2. SumoSelect 配置**

```javascript
define([
    'hotaiSumoselect',
    'sumoselectConfig'
], function(sumoUtil, sumoConfig) {
    // 使用預設配置初始化
    sumoUtil.init({
        selector: '#my-select',
        data: [
            {id: '1', text: '選項 1'},
            {id: '2', text: '選項 2'}
        ],
        placeholder: '請選擇...',
        // 可覆蓋 sumoConfig.multipleDefaults 的任何設定
        customConfig: {
            selectAll: true,  // 啟用全選按鈕
            search: true      // 啟用搜尋
        }
    });
    
    // 或直接使用配置物件
    var sumoOptions = $.extend({}, 
        sumoConfig.multipleDefaults,
        {
            placeholder: '自訂佔位文字'
        }
    );
});
```

## 📝 配置文件規範

### 命名規範
- 檔案名稱：`{套件名稱}-config.js`
- 模組名稱：清楚描述配置用途
- 別名：在 `requirejs-config.js` 中定義（如 `tabulatorConfig`, `sumoselectConfig`）

### 內容結構

#### **簡單配置（如 SumoSelect）**
```javascript
/**
 * {套件名稱} 配置模組
 * 
 * @module HotaiConnected_UiShared/js/config/{package-name}-config
 * @version 1.0.0
 * @author HotaiConnected
 * 
 * 提供 {套件名稱} 的預設配置
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
        // 配置物件
        defaults: {
            option1: 'value1',
            option2: 'value2'
        }
    };
});
```

#### **複雜配置（如 Tabulator）**
```javascript
/**
 * {套件名稱} 配置模組
 * 
 * @module HotaiConnected_UiShared/js/config/{package-name}-config
 * @version 1.0.0
 * @author HotaiConnected
 * 
 * 提供 {套件名稱} 的預設配置
 * 支援多種模式（如本地/遠端分頁）
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
        /**
         * 取得基礎配置（私有方法）
         * @private
         * @returns {Object}
         */
        _getBaseConfig: function() {
            return {
                // 共用配置
            };
        },
        
        /**
         * 取得模式 A 配置（公開方法）
         * @returns {Object}
         */
        getModeAConfig: function() {
            var config = this._getBaseConfig();
            // 添加模式 A 特定配置
            return config;
        },
        
        /**
         * 取得模式 B 配置（公開方法）
         * @returns {Object}
         */
        getModeBConfig: function() {
            var config = this._getBaseConfig();
            // 添加模式 B 特定配置
            return config;
        }
    };
});
```

## 🎯 設計原則

1. **單一職責** - 每個配置文件只負責一個套件或功能
2. **可重用性** - 配置應該可以在多個地方重用
3. **可維護性** - 配置與業務邏輯分離，便於維護和更新
4. **文檔完整** - 每個配置選項都應有清楚的註解說明
5. **預設安全** - 預設值應避免觸發不必要的請求或錯誤（如 `ajaxURL: ''`, `columns: []`）
6. **模式分離** - 複雜套件應提供多種配置模式（如本地/遠端分頁）

## 📚 Tabulator 配置詳細說明

### **分頁模式選擇**

| 模式 | 方法 | 適用場景 | 資料量 | ajaxResponse 返回格式 |
|------|------|---------|--------|---------------------|
| **本地分頁** | `getLocalConfig()` | 小數據 | <1000 筆 | `[{}, {}, ...]` 陣列 |
| **遠端分頁** | `getRemoteConfig()` | 大數據 | >1000 筆 | `{last_page: N, data: [...]}` 物件 |

### **通用配置包含**

- **AJAX 配置**：`ajaxMethod`, `ajaxContentType`, `ajaxURL`, `ajaxParams`, `ajaxConfig`
- **布局配置**：`layout`, `responsiveLayout`, `autoResize`
- **互動功能**：`movableColumns`（允許拖曳欄位排序）
- **分頁配置**：`pagination`, `paginationSize`, `paginationSizeSelector`
- **語系配置**：`locale`, `langs`（繁體中文）
- **選擇行標頭**：`rowHeader`（checkbox 選擇功能）
- **欄位預設**：`columnDefaults`, `columns`

### **業務模組必須實作**

1. **`ajaxURL`** - API 端點 URL
2. **`ajaxResponse`** - 資料轉換函數（根據分頁模式返回不同格式）
3. **`columns`** - 欄位定義陣列

### **SumoSelect 配置說明**

`sumoselect-config.js` 提供 `multipleDefaults` 配置物件，包含：

- **搜尋功能**：`search: true`, `searchText`, `noMatch`
- **選擇功能**：`selectAll: true`, `clearAll: false`
- **互動設定**：`isClickAwayOk: true`, `okCancelInMulti: false`
- **語言包**：`locale` 陣列（使用 Magento 翻譯）

## 📚 相關文件

- 第三方套件位置：`../vendor/`
- RequireJS 配置：`../../requirejs-config.js`
- UiShared 主文檔：`../../../../README.md`
