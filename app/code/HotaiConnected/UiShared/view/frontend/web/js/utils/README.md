# Utils 資料夾

此資料夾專門存放**工具函數**，提供可重用的功能封裝。

## 📋 規範

### ✅ 應該放在這裡的文件
- 可重用的工具函數
- 功能封裝（如日期選擇器、訊息提示）
- 統一的日誌管理
- 簡單的輔助函數

### ❌ 不應該放在這裡的文件
- 第三方套件原始文件（請放在 `../vendor/`）
- 業務邏輯代碼（業務模組中自行管理）
- UI 組件（請放在 `../components/`）
- 配置文件（請放在 `../config/`）

---

## 📦 目前的工具函數

| 檔案名稱 | 用途 | 別名 |
|---------|------|------|
| `toast-message.js` | 訊息提示系統（Success/Error/Warning/Info） | `hotaiToastMessage` |
| `date-pickers.js` | 日期選擇器封裝（支援日期範圍） | `hotaiDatePickers` |
| `sumoselect.js` | SumoSelect 初始化封裝 | `hotaiSumoselect` |
| `logger.js` | 統一日誌管理工具（支援 DEBUG 開關） | `hotaiLogger` |

---

## 🔧 使用方式

### **1. Toast Message**
```javascript
define(['hotaiToastMessage'], function(toast) {
    // 成功訊息
    toast.success('操作成功！');
    
    // 錯誤訊息
    toast.error('操作失敗！');
    
    // 警告訊息
    toast.warning('請注意！');
    
    // 資訊訊息
    toast.info('提示訊息');
});
```

### **2. Date Pickers**
```javascript
define(['hotaiDatePickers'], function(datePickers) {
    // 初始化日期範圍選擇器
    datePickers.initDatePickers({
        fromSelector: '#date-from',
        toSelector: '#date-to',
        minDate: '-12m'
    });
});
```

### **3. SumoSelect**
```javascript
define(['hotaiSumoselect'], function(sumo) {
    // 初始化多選下拉選單
    sumo.init('#my-select', {
        placeholder: '請選擇...',
        search: true
    });
});
```

### **4. Logger（新增）**
```javascript
define(['hotaiLogger'], function(logger) {
    // 創建模組專用的 logger
    var log = logger.create('MyModule');
    
    // 輸出日誌（只在 DEBUG 模式）
    log.info('初始化完成');
    log.warn('警告訊息');
    log.debug('調試訊息');
    
    // 錯誤日誌（永遠輸出）
    log.error('錯誤訊息', errorObject);
});
```

**啟用 DEBUG 模式：**
```javascript
// 方法 1：瀏覽器控制台臨時啟用
require(['hotaiLogger'], function(logger) { 
    logger.enableDebug(); 
});

// 方法 2：全局設置
require(['hotaiLogger'], function(logger) { 
    logger.setGlobalDebug(true); 
});
```

---

## 💡 Logger 使用場景

### ✅ **應該使用 Logger：**
- **核心服務模組**（如 request.js, api.js）
- **複雜業務邏輯**（需要追踪數據流）
- **需要調試的組件**（表格配置、數據轉換等）

```javascript
// ✓ 推薦：核心服務
define(['hotaiLogger'], function(logger) {
    var log = logger.create('Request');
    
    return {
        send: function(url, data) {
            log.info('發送請求:', url);
            // ...
            log.info('請求成功:', response);
        }
    };
});
```

### ❌ **不需要使用 Logger：**
- **簡單工具函數**（如格式化日期、字串處理）
- **高頻調用場景**（如每次渲染都調用的 formatter）
- **一次性腳本**

```javascript
// ✗ 不推薦：簡單函數不需要 logger
define([], function() {
    return {
        formatDate: function(date) {
            return moment(date).format('YYYY-MM-DD');
        }
    };
});
```

---

## 📝 工具函數規範

### **命名規範**
- 檔案名稱：小寫，使用連字符（kebab-case）
- 模組名稱：清楚描述功能用途
- 函數名稱：駝峰式命名（camelCase）

### **內容結構**
```javascript
/**
 * {工具名稱}
 * 
 * 提供 {功能描述}
 * 
 * @module HotaiConnected_UiShared/js/utils/{tool-name}
 * @version 1.0.0
 */
define([
    'jquery',
    'mage/translate'
], function ($) {
    'use strict';

    return {
        // 公開方法
        init: function() {
            // ...
        }
    };
});
```

---

## 🎯 設計原則

1. **單一職責** - 每個工具函數只負責一個功能
2. **可重用性** - 工具應該可以在多個模組中重用
3. **無狀態** - 盡量避免內部狀態，使用參數傳遞
4. **文檔完整** - 每個公開方法都應有清楚的註解說明
5. **零依賴** - 盡量減少依賴（logger.js 完全零依賴）

---

## 📊 Logger vs Toast Message

| 功能 | Logger | Toast Message |
|------|--------|---------------|
| **用途** | 開發調試 | 用戶提示 |
| **顯示位置** | 瀏覽器控制台 | 頁面上方 |
| **生產環境** | 可關閉（DEBUG=false） | 永遠顯示 |
| **適用場景** | 追踪代碼執行流程 | 操作成功/失敗提示 |
| **使用時機** | 開發和排查問題時 | 任何需要提示用戶的時候 |

**示例：**
```javascript
define(['hotaiLogger', 'hotaiToastMessage'], function(logger, toast) {
    var log = logger.create('MyModule');
    
    return {
        save: function(data) {
            log.info('開始保存數據:', data);  // 控制台日誌（開發用）
            
            api.save(data)
                .done(function() {
                    log.info('保存成功');
                    toast.success('保存成功！');  // 用戶提示（生產環境）
                })
                .fail(function(error) {
                    log.error('保存失敗:', error);
                    toast.error('保存失敗！');
                });
        }
    };
});
```

---

## 🔄 新增工具函數

### **步驟 1：創建工具文件**
```bash
# 在 utils/ 目錄創建新文件
touch js/utils/my-tool.js
```

### **步驟 2：實作工具函數**
```javascript
// js/utils/my-tool.js
define(['jquery'], function($) {
    'use strict';
    
    return {
        doSomething: function(param) {
            // 實作功能
        }
    };
});
```

### **步驟 3：在 requirejs-config.js 添加別名**
```javascript
// view/adminhtml/requirejs-config.js
var config = {
    paths: {
        // ... 其他別名 ...
        'hotaiMyTool': 'HotaiConnected_UiShared/js/utils/my-tool'
    }
};
```

### **步驟 4：更新文檔**
- 在本 README.md 的工具列表中添加說明
- 在 UiShared/README.md 中更新全域別名列表

---

## ⚠️ 注意事項

1. **依賴管理** - 工具函數應該盡量減少依賴，提高可重用性
2. **性能考慮** - 避免在高頻調用的場景使用重量級工具
3. **命名衝突** - 使用 `hotai` 前綴避免與其他模組衝突
4. **版本更新** - 更新工具時注意向後兼容性
5. **DEBUG 控制** - Logger 預設關閉，開發時手動啟用

---

## 🔗 相關文件

- [UiShared 主文檔](../../../../README.md)
- [Logger 源碼](logger.js)
- [Toast Message 源碼](toast-message.js)
- [RequireJS 配置](../../requirejs-config.js)

---

**版本**: 1.0.0  
**最後更新**: 2025年10月29日  
**維護**: HotaiConnected 開發團隊

