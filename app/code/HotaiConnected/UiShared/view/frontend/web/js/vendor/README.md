# Vendor 資料夾

此資料夾專門存放**第三方套件**的原始文件（通常是 .min.js）。

## 📋 規範

### ✅ 應該放在這裡的文件
- 第三方庫的原始 .min.js 文件
- 未經修改的外部套件
- 從 CDN 或 npm 下載的原始文件

### ❌ 不應該放在這裡的文件
- 自定義配置文件（請放在 `../config/`）
- 業務邏輯代碼（業務模組中自行管理）
- 工具函數（請放在 `../utils/`）

## 📦 目前包含的套件

| 套件名稱 | 版本 | 用途 | 官方網站 |
|---------|------|------|---------|
| Tabulator | - | 互動式資料表格庫 | https://tabulator.info/ |
| SumoSelect | - | jQuery 多選下拉選單插件 | https://hemalatha.github.io/jquery.sumoselect/ |

## 🔧 使用方式

這些套件已在 `requirejs-config.js` 中配置路徑映射：

```javascript
// 在其他模組中引用
define([
    'tabulator',    // 自動映射到 vendor/tabulator.min.js
    'sumoselect'    // 自動映射到 vendor/sumoselect.min.js
], function(Tabulator, sumoselect) {
    // 使用套件
});
```

## 📝 維護注意事項

1. **不要修改此資料夾中的文件** - 這些是第三方原始文件
2. **更新套件時** - 直接替換對應的 .min.js 文件
3. **新增套件時** - 記得在 `requirejs-config.js` 中添加路徑映射
4. **配置文件** - 所有自定義配置應放在 `../config/` 資料夾

## 📚 相關文件

- 配置文件位置：`../config/`
- RequireJS 配置：`../../requirejs-config.js`
- UiShared 主文檔：`../../../../README.md`
- 使用文檔：請參考各套件的官方文檔

