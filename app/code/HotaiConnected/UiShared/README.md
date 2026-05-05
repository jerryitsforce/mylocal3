# HotaiConnected UiShared

UI 共用資源模組 - 集中管理所有 HotaiConnected 模組使用的 UI 組件、工具和第三方套件。

---

## 🎯 為什麼需要這個模組？

### **問題：**
- ❌ 每個業務模組（CheckoutManagement、ApprovalFunctionality等）都複製相同的 vendor 套件
- ❌ 導致專案肥大（重複的 tabulator.js、sumoselect.js 等）
- ❌ 維護困難（需要在多處更新同一個庫）
- ❌ 版本不一致（不同模組使用不同版本）

### **解決方案：**
- ✅ 集中管理所有共用前端資源（Single Source of Truth）
- ✅ 通過 RequireJS 全域別名提供統一介面
- ✅ 其他模組只需聲明依賴即可使用
- ✅ 版本統一管理，更新一次即可

---

## 📦 包含的資源

### **第三方套件（Vendor）**
- **Tabulator** - 互動式資料表格庫
- **SumoSelect** - jQuery 多選下拉選單插件

### **配置文件（Config）**
- `tabulator-config.js` - Tabulator 全域預設配置
- `sumoselect-config.js` - SumoSelect 全域預設配置

### **服務層（Services）**
- `request.js` - HTTP 請求封裝（支援 loading 和 toast）

### **UI 組件（Components）**
- `loading-mask.js` - 全螢幕載入動畫（使用 DOM API）
- `selected-items-display.js` - 選中項目顯示組件（標準化資料格式）
- `searchable-dialog-multiselect.js` - 可搜尋多選對話框
- `tabulator-column-control.js` - Tabulator 欄位可見性控制組件
- `tri-state-group-multiselect.js` - 三態分組多選組件
- `filters-base.js` - 篩選器基礎組件（共用功能）

### **工具函數（Utils）**
- `toast-message.js` - 訊息提示（使用 DOM API）
- `date-pickers.js` - 日期選擇器封裝
- `sumoselect.js` - SumoSelect 初始化封裝
- `logger.js` - 統一日誌管理工具（支援 DEBUG 開關）

### **樣式（CSS）**
- Vendor 樣式（Tabulator、SumoSelect）
- 基礎變數和 Mixin
- UI 組件樣式

---

## 📁 目錄結構

```
UiShared/
├── composer.json                       # Composer 配置
├── registration.php                    # Magento 模組註冊
├── README.md                           # 本文檔
├── MIGRATIONS.md                       # 技術遷移記錄
├── etc/
│   └── module.xml                      # 模組定義（無 setup_version）
└── view/adminhtml/
    ├── requirejs-config.js             # 全域別名配置
    └── web/
        ├── js/
        │   ├── vendor/                 # 第三方 JS 套件
        │   │   ├── tabulator.min.js
        │   │   ├── sumoselect.min.js
        │   │   └── README.md
        │   ├── config/                 # 配置文件
        │   │   ├── tabulator-config.js
        │   │   ├── sumoselect-config.js
        │   │   └── README.md
        │   ├── services/               # 服務層
        │   │   └── request.js
        │   ├── components/             # UI 組件
        │   │   ├── loading-mask.js
        │   │   ├── selected-items-display.js
        │   │   ├── searchable-dialog-multiselect.js
        │   │   ├── tabulator-column-control.js
        │   │   ├── tri-state-group-multiselect.js
        │   │   └── filters-base.js
│   └── utils/                  # 工具函數
│       ├── toast-message.js
│       ├── date-pickers.js
│       ├── sumoselect.js
│       ├── logger.js
│       └── README.md
        └── css/
            ├── vendor/                 # 第三方 CSS
            │   ├── tabulator/
            │   │   ├── tabulator.min.css
            │   │   ├── _tabulator-admin-override.less
            │   │   └── README.md
            │   └── sumoselect/
            │       ├── sumoselect.min.css
            │       └── _sumoselect-admin-override.less
            ├── base/                   # 基礎樣式
            │   ├── _animations.less    # 動畫效果
            │   ├── _icons.less         # 圖示樣式
            │   └── _utilities.less     # 工具類別
            └── components/             # 組件樣式
                ├── _toast-message.less
                ├── _selected-items-display.less
                ├── _searchable-dialog-multiselect.less
                ├── _tri-state-group-multiselect.less
                └── _ui-datepicker.less
```

---

## 🎯 使用方式

### **步驟 1: 添加模組依賴**

在你的模組的 `etc/module.xml` 中添加依賴：

```xml
<module name="YourModule_Name">
    <sequence>
        <module name="HotaiConnected_UiShared"/>
    </sequence>
</module>
```

### **步驟 2: 在 JavaScript 中使用**

不需要額外配置！直接使用全域別名即可：

```javascript
define([
    // 第三方套件
    'tabulator',
    'sumoselect',
    
    // 配置
    'sumoselectConfig',
    
    // 服務
    'hotaiRequest',
    
    // 組件
    'hotaiLoadingMask',
    'hotaiSelectedItemsDisplay',
    'hotaiSearchableDialogMultiselect',
    'hotaiTriStateGroupMultiselect',
    'hotaiFiltersBase',
    
    // 工具
    'hotaiToastMessage',
    'hotaiDatePickers',
    'hotaiSumoselect',
    'hotaiLogger'
], function (
    Tabulator, sumoselect,
    sumoConfig,
    request,
    loadingMask, selectedItemsDisplay, searchableDialog, triState, filtersBase,
    toast, datePickers, sumo, logger
) {
    // 使用共用資源
    
    // 1. 發送 API 請求（自動顯示 loading 和 toast）
    request.get('rest/V1/api/endpoint')
        .done(function(response) {
            toast.success('操作成功！');
        });
    
    // 2. 顯示載入動畫
    loadingMask.show();
    
    // 3. 初始化日期選擇器
    datePickers.init('#date-from', '#date-to');
    
    // 4. 初始化 SumoSelect
    sumo.init('#my-select');
    
    // 5. 使用 Tabulator 表格
    var table = new Tabulator("#table", {
        // ... 配置
    });
    
    // 6. 使用 Logger（開發調試）
    var log = logger.create('MyModule');
    log.info('模組初始化完成');
    log.error('錯誤訊息');
});
```

### **步驟 3: 在 CSS 中引用**

```less
// 在你的模組的 .less 文件中

// 引用 Vendor 樣式
@import (less) "HotaiConnected_UiShared::css/vendor/tabulator/tabulator.min.css";
@import "HotaiConnected_UiShared::css/vendor/tabulator/_tabulator-admin-override.less";
@import (less) "HotaiConnected_UiShared::css/vendor/sumoselect/sumoselect.min.css";
@import "HotaiConnected_UiShared::css/vendor/sumoselect/_sumoselect-admin-override.less";

// 引用共用組件樣式
@import "HotaiConnected_UiShared::css/components/_toast-message.less";
@import "HotaiConnected_UiShared::css/components/_selected-items-display.less";
@import "HotaiConnected_UiShared::css/components/_searchable-dialog-multiselect.less";
@import "HotaiConnected_UiShared::css/components/_tri-state-group-multiselect.less";
@import "HotaiConnected_UiShared::css/components/_ui-datepicker.less";

// 引用基礎工具樣式
@import "HotaiConnected_UiShared::css/base/_utilities.less";
@import "HotaiConnected_UiShared::css/base/_animations.less";
@import "HotaiConnected_UiShared::css/base/_icons.less";
```

---

## 📝 全域別名列表

### **第三方套件：**
| 別名 | 指向 | 說明 |
|------|------|------|
| `tabulator` | `js/vendor/tabulator.min` | Tabulator 表格庫 |
| `sumoselect` | `js/vendor/sumoselect.min` | SumoSelect 下拉選單 |

### **配置文件：**
| 別名 | 指向 | 說明 |
|------|------|------|
| `tabulatorConfig` | `js/config/tabulator-config` | Tabulator 通用配置 |
| `sumoselectConfig` | `js/config/sumoselect-config` | SumoSelect 全域配置 |

### **服務層：**
| 別名 | 指向 | 說明 |
|------|------|------|
| `hotaiRequest` | `js/services/request` | HTTP 請求服務 |

### **UI 組件：**
| 別名 | 指向 | 說明 |
|------|------|------|
| `hotaiLoadingMask` | `js/components/loading-mask` | 全螢幕載入動畫 |
| `hotaiSelectedItemsDisplay` | `js/components/selected-items-display` | 選中項目顯示（標準化格式） |
| `hotaiSearchableDialogMultiselect` | `js/components/searchable-dialog-multiselect` | 可搜尋多選對話框 |
| `tabulatorColumnControl` | `js/components/tabulator-column-control` | Tabulator 欄位可見性控制 |
| `hotaiTriStateGroupMultiselect` | `js/components/tri-state-group-multiselect` | 三態分組多選 |
| `hotaiFiltersBase` | `js/components/filters-base` | 篩選器基礎組件（共用功能） |

### **工具函數：**
| 別名 | 指向 | 說明 |
|------|------|------|
| `hotaiToastMessage` | `js/utils/toast-message` | 訊息提示（Success/Error/Warning） |
| `hotaiDatePickers` | `js/utils/date-pickers` | 日期選擇器封裝 |
| `hotaiSumoselect` | `js/utils/sumoselect` | SumoSelect 初始化封裝 |
| `hotaiLogger` | `js/utils/logger` | 統一日誌管理（支援 DEBUG 開關） |

---

## 🔧 維護指南

### **添加新套件：**

1. 將套件文件放入對應的 `vendor/` 目錄
2. 在 `requirejs-config.js` 中添加全域別名
3. 如有樣式，放入 `css/vendor/`
4. 更新本 README 文檔

### **使用 Tabulator 配置：**

#### **方式一：直接使用業務配置（推薦）**
```javascript
define([
    'tabulator',
    'YourModule/js/config/your-tabulator-config'  // 業務配置（已包含通用配置）
], function(Tabulator, tabulatorConfig) {
    // 直接使用完整配置
    new Tabulator("#table", tabulatorConfig.getConfig());
});
```

#### **方式二：手動合併配置**
```javascript
define([
    'tabulator',
    'tabulatorConfig',  // UiShared 通用配置
    'YourModule/js/config/your-tabulator-config'  // 業務特定配置
], function(Tabulator, baseConfig, yourConfig) {
    // 手動合併通用配置和業務特定配置
    var config = $.extend(true, {}, baseConfig.getDefaultConfig(), yourConfig.getConfig());
    
    // 初始化 Tabulator
    new Tabulator("#table", config);
});
```

**業務特定配置應包含：**
- `columns`: 欄位定義
- `ajaxURL`: API 端點
- 其他業務特定的覆蓋設定

### **添加新組件：**

1. 在 `js/components/` 創建組件 JS
2. 在 `css/components/` 創建組件樣式（如需要）
3. 在 `requirejs-config.js` 中添加全域別名（`hotai` 前綴）
4. 更新本 README 文檔

### **更新套件版本：**

1. 下載新版本的套件
2. 替換 `vendor/` 中的對應文件
3. 測試所有使用該套件的模組
4. 清除快取並重新部署

### **移除套件：**

1. **重要：** 先檢查是否有模組仍在使用
2. 移除 `vendor/` 目錄中的文件
3. 移除 `requirejs-config.js` 中的配置
4. 更新本 README 文檔

---

## 🔄 部署流程

### **開發環境：**

```bash
# 清除快取
php bin/magento cache:clean

# 清除靜態檔案和預處理檔案
rm -rf pub/static/adminhtml/
rm -rf var/view_preprocessed/

# 快速部署
php bin/magento setup:static-content:deploy -f
```

### **生產環境：**

```bash
# 完整部署
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy
php bin/magento cache:flush
```

---

## 📚 依賴此模組的模組

- ✅ `HotaiConnected_CheckoutManagement`
- 🔜 `HotaiConnected_ApprovalFunctionality`（未來）
- 🔜 其他業務模組...

---

## ✅ 優勢

1. **單一來源（Single Source of Truth）** - 所有共用資源集中管理
2. **易於更新** - 更新套件只需修改一個地方
3. **減少檔案大小** - 避免在每個模組中重複存放相同的套件
4. **版本一致性** - 確保所有模組使用相同版本的套件
5. **清晰的依賴** - 透過 `module.xml` 明確定義模組間的依賴關係
6. **符合 Magento 標準** - 遵循 Magento 2 的模組化設計原則
7. **性能優化** - 簡單組件使用 DOM API（toast、loading），減少 HTTP 請求

---

## 🎯 設計決策

### **為什麼某些組件使用 DOM API？**

| 組件 | 使用方式 | 原因 |
|------|---------|------|
| `toast-message.js` | DOM API | 簡單結構（16 行），高頻使用，性能優先 |
| `loading-mask.js` | DOM API | 簡單結構（14 行），高頻使用，性能優先 |
| 表單組件 | HTML 模板 | 複雜結構（50+ 行），維護性優先 |

---

## 📚 相關文檔

- **[技術遷移記錄](MIGRATIONS.md)** - 記錄系統級的技術遷移決策和過程
  - Select2 → SumoSelect 遷移記錄
  - 未來的技術遷移將持續更新

---

**版本**: 2.3.0  
**維護**: HotaiConnected 開發團隊  
**最後更新**: 2025-12-09
