# Tabulator 使用說明

## 套件資訊

- **套件名稱**: Tabulator
- **版本**: v6.3
- **官方網站**: [https://tabulator.info/](https://tabulator.info/)
- **功能**: JavaScript 表格和資料網格庫，支援互動式表格功能

## 資料夾結構說明

```
css/tabulator/
├── tabulator.min.css                    # Tabulator 原始樣式檔案
├── _tabulator-admin-override.less       # Magento Admin 通用樣式覆寫
└── README.md                           # 此說明檔
```

### 檔案用途說明

#### 1. `tabulator.min.css`
- **用途**：Tabulator 官方提供的原始樣式檔案
- **內容**：包含所有基礎的表格樣式
- **注意**：不要直接修改此檔案

#### 2. `_tabulator-admin-override.less`
- **用途**：覆寫 Tabulator 原始樣式，使其符合 Magento Admin 設計規範
- **目的**：為符合目前 Magento 後台 UI 一致性做出的調整
- **內容**：表頭樣式（Magento 黑色主題）、表格邊框、間距、Hover 效果
- **適用範圍**：整個後台的所有 Tabulator 表格


## 注意事項

1. **不要修改原始檔案**：`tabulator.min.css` 是官方檔案，不要直接修改
2. **通用樣式**：`_tabulator-admin-override.less` 適用於所有目前 magento 專案下的 Tabulator 表格
3. **頁面特定樣式**：欄位寬度設定需要在各自的頁面 LESS 檔案中設定
4. **欄位名稱**：`tabulator-field` 的值必須與 Tabulator 配置中定義的欄位名稱完全一致
   - 詳情可查看各業務模組的 `js/config/tabulator-config.js` 中的 `columns` 陣列中的 `field`
   - 範例：`columns: [{...}, { title: '顯示名稱', field: '資料欄位名稱'}, {...}]`
   - 通用配置位於：`HotaiConnected_UiShared/js/config/tabulator-config.js`
5. **響應式設計**：建議使用 `min-width` 而非固定 `width`

## 如何使用

### 步驟 1：引入樣式檔案
在主 LESS 檔案中加入：
```less
@import 'tabulator/tabulator.min.css';
@import 'tabulator/_tabulator-admin-override.less';
```

### 步驟 2：了解各區塊的用法
```less
#tabulator-grid {
    .tabulator {
        // 同時影響標題和內容區塊
        &-col, &-cell {
            &[tabulator-field="欄位名稱"] {
                // 樣式設定
            }
        }
        
        // 只影響標題區塊
        &-col {
            &[tabulator-field="欄位名稱"] {
                // 標題樣式設定
            }
        }
        
        // 只影響內容區塊
        &-cell {
            &[tabulator-field="欄位名稱"] {
                // 內容樣式設定
            }
        }
    }
}
```

### 實際使用範例

#### 1. 調整欄位寬度
```less
#tabulator-grid {
    .tabulator {
        // ⭐ 注意事項 ⭐
        // 寬度調整時需注意使用 &-col, &-cell 避免標題與下方欄位不對齊
        &-col, &-cell {
            &[tabulator-field="checkout_batch"] {
                min-width: 180px;
            }
            &[tabulator-field="vendor_invoice_number"] {
                min-width: 320px;
            }
        }
    }
}
```

**中文標題寬度參考**：
為避免在不同瀏覽器中出現顯示問題，寬度不得小於以下設定，以免標題無法完整呈現：
- **2個字**：不得小於 `80px`
- **4個字**：不得小於 `100px`
- **5個字**：不得小於 `115px`
- **6個字**：不得小於 `130px`

#### 2. 設定欄位對齊
```less
#tabulator-grid {
    .tabulator {
        // ⭐ 注意事項 ⭐
        // 對齊設定只影響內容區塊，使用 &-cell 即可
        &-cell {
            &[tabulator-field="total_payment_amount"] {
                justify-content: flex-end;  // 右對齊
            }
            &[tabulator-field="price"] {
                justify-content: flex-end;  // 右對齊
            }
            &[tabulator-field="vendor_invoice_number"] {
                justify-content: flex-start;  // 左對齊
            }
        }
    }
}
```

## 常見數值設定建議

### 對齊方式建議
根據資料類型和一般表格設計最佳實踐：

#### 靠右對齊 (`justify-content: flex-end`)
- **數值資料**：價格、金額、數量、百分比
- **原因**：便於比較數值大小，符合閱讀習慣
- **範例**：`$1,234.56`、`99.5%`、`1,000件`

#### 靠左對齊 (`justify-content: flex-start`)
- **文字識別碼**：訂單編號、產品ID、會員編號
- **操作按鈕**：編輯、刪除、查看等動作
- **原因**：便於快速識別和點擊操作
- **範例**：`ORD-2024-001`、`BTN-EDIT`、`MEM-12345`

#### 置中對齊 (`justify-content: center`) - 預設值
- **狀態標籤**：已處理、進行中、已完成
- **分類標籤**：VIP、一般、特殊
- **原因**：視覺平衡，突出狀態重要性
- **範例**：`已完成`、`VIP會員`、`進行中`



