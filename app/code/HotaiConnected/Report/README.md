# HotaiConnected Report Module

每日定時執行 SQL 查詢並將結果發送到 Slack 的報表模組。

## 新增報表

### 1. 建立新的 Report 類別

在 `Model/Report/` 目錄下建立新的報表類別，例如 `CustomerReport.php`：

```php
<?php

namespace HotaiConnected\Report\Model\Report;

class CustomerReport extends AbstractReport
{
    public function getName(): string
    {
        return '客戶報表標題';
    }

    protected function getSql(): string
    {
        return "
            SELECT * FROM customer_entity
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
        ";
    }

    public function formatForSlack(array $data): array
    {
        if (empty($data)) {
            return [
                'channel' => '#sql-monitor',
                'username' => 'webhookbot',
                'attachments' => [
                    [
                        'color' => '#36a64f',
                        'title' => $this->getName(),
                        'text' => '✅ 沒有新客戶',
                        'footer' => 'SQL Monitor Bot'
                    ]
                ]
            ];
        }

        $text = "新客戶數量: " . count($data);

        return [
            'channel' => '#sql-monitor',
            'username' => 'webhookbot',
            'attachments' => [
                [
                    'color' => '#ff0000',
                    'title' => $this->getName(),
                    'text' => $text,
                    'footer' => 'SQL Monitor Bot'
                ]
            ]
        ];
    }
}
```

### 2. 註冊到 DI

在 `etc/di.xml` 中加入新的 report：

```xml
<type name="HotaiConnected\Report\Model\ReportManager">
    <arguments>
        <argument name="reports" xsi:type="array">
            <item name="missing_invoice" xsi:type="object">HotaiConnected\Report\Model\Report\MissingInvoiceReport</item>
            <item name="customer" xsi:type="object">HotaiConnected\Report\Model\Report\CustomerReport</item>
        </argument>
    </arguments>
</type>
```

### 3. 清除快取

```bash
php bin/magento cache:clean
```

## 目前的報表

### 1. MissingInvoiceReport - 沒有發票主檔紀錄的訂單
- **條件**:
  - 排除 4 小時內建立的訂單
  - 排除特定狀態的訂單
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

### 2. MissingCustomerTicketReport - 沒有customer_ticket的相關訂單
- **條件**:
  - 虛擬商品訂單 (`is_virtual = 1`)
  - 不存在對應的 customer_ticket 記錄
  - 排除 4 小時內建立的訂單
- **輸出**: `sales_order.increment_id`

### 3. MissingPointDeductionReport - 有用點數但沒有deduction紀錄
- **條件**:
  - `row_total_point_used != 0`
  - `hotai_point_deduction_point_trans_s_n IS NULL`
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

### 4. CanceledOrderMissingPointReturnReport - 取消訂單,有用點數沒有return紀錄
- **條件**:
  - `status = 'canceled'`
  - 有用點數但沒有 return 紀錄
- **輸出**: `sales_order.increment_id`

### 5. TicketIssuedButProcessingReport - 已給票券但訂單狀態還在processing
- **條件**:
  - `ct.status = 2` (票券已發行)
  - 訂單狀態非 complete/arrived/canceled
- **輸出**: `sales_order.increment_id`

### 6. TicketStatusInconsistentReport - 球池票券與customer_ticket狀態不一致
- **條件**:
  - `ct.status != tet.status`
- **輸出**: `ticket_event_ticket.entity_id`

### 7. DuplicateTicketReport - 重複票券查詢
- **條件**:
  - 查詢多個票券表中重複的序號
  - 包含: customer_ticket, ticket_event_ticket, family_bonus_pin, edenred, yoxi, general_notify, general_non_notify, qware
- **輸出**: `customer_ticket.ticket_unique_content`

### 8. ShippingAddressMismatchReport - 超取宅配地址不一致
- **條件**:
  - 超取訂單但地址非門市 (city 不含「門市」且無 cvs_store_code)
  - 宅配訂單但地址是門市 (city 含「門市」)
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.entity_id`

### 9. NoPointNoPaymentReport - 沒用點數又沒付款資訊的訂單
- **條件**:
  - `point_used_total = 0`
  - `spop.txn IS NULL`
  - 訂單金額不為 0
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

### 10. TicketOrderNonPurePointReport - 票券訂單，非純點交易檢查
- **條件**:
  - `is_virtual = 1`
  - `eihol.include_tax != 0` (非純點交易)
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

### 11. ReturnedOrderMissingReturnTraceNoReport - 訂單returned沒有return單號
- **條件**:
  - `flow_status = 'returned'`
  - 有用點數但 `hotai_point_return_point_trace_no IS NULL`
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order_item.item_id`

### 12. UnpaidAbnormalStatusReport - 沒付錢的訂單，異常訂單狀態
- **條件**:
  - `is_paid = 0`
  - `status NOT IN ('canceled', 'pending_payment')`
  - `rma_status NOT IN ('rma_completed')`
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.entity_id`

### 13. PointOrderRedemptionStatusReport - 點數訂單兌點狀態檢查
- **條件**:
  - `row_total_point_used > 0`
  - `hotai_point_deduction_point_progress_status != 2`
  - `status NOT IN ('canceled', 'pending_payment', 'returned')`
  - `flow_status NOT IN ('returned')`
  - `rma_status IS NULL`
  - 查詢 30 天內且超過 4 小時的訂單
- **輸出**: `sales_order.increment_id`

### 14. GiftTicketNotReceivedReport - 禮物票券訂單登入後沒拿到
- **條件**:
  - `is_gift_order = 1` 且 `is_virtual = 1`
  - `ct.customer_id = 0` (票券未歸戶)
  - 收件人已註冊 (`ce.entity_id IS NOT NULL`)
- **輸出**: `sales_order_item.item_id`

### 15. GiftOrderEmptyRecipientReport - 禮物訂單/票券/收禮人資訊為空
- **條件**:
  - `is_gift_order = 1`
  - `recipient_telephone IS NULL`
  - 訂單狀態非 canceled/gift_info_pending/cancel_pending/pending_payment
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

### 16. GiftTicketWrongRecipientReport - 下單人非收禮人,且票券非收禮人
- **條件**:
  - `is_gift_order = 1` 且 `is_virtual = 1`
  - `ce.entity_id != ct.customer_id` (票券帳號與收禮人帳號不同)
  - 收件人已註冊且無退貨
- **輸出**: `sales_order.increment_id`

### 17. TicketPoolStatusInconsistentReport - 票券狀態不一致
- **條件**:
  - 查詢多個票券池表與 customer_ticket 狀態不一致
  - 包含: general_notify_ticket_record, general_non_notify_ticket_record, yoxi_ticket_record_v2, family_bonus_pin_ticket_record_v2
  - `fb.status != 0` 且 `fb.status <> ct.status`
  - 排除 gift_info_pending 狀態的訂單
- **輸出**: `sales_order_item.item_id`

### 18. QwareTicketInfoMissingReport - Qware 票券資訊缺失報表 (URL 或密碼為空)
- **條件**:
  - `ct.ticket_table_name = 'qware_ticket_record'`
  - `qw.qware_url = ''` 或 `qw.qware_pwd = ''`
- **輸出**: `sales_order.increment_id`

### 19. NegativeCampaignDiscountReport - 活動折扣異常報表
- **條件**:
  - `soi.special_price < soi.price_incl_tax`
- **輸出**: `sales_order.increment_id`

### 20. EmployeeGroupMismatchReport - 員工身份但組別不符報表
- **條件**:
  - `cpee.isEnabled = 1`
  - `ce.entity_id IS NOT NULL`
  - `ce.group_id != 20`
- **輸出**: `customer_entity.entity_id`

### 21. VirtualStatusInconsistentReport - 訂單虛擬狀態不一致報表
- **條件**:
  - `so.is_virtual != soi.is_virtual` (針對單一商品訂單)
- **輸出**: `sales_order.increment_id`

### 22. IncompleteTicketOrderReport - 票券未完成訂單報表
- **條件**:
  - 排除 `complete`, `canceled`, `pending_complete` 狀態
  - 檢查是否存在未成功核發（status != 2）的票券項
- **輸出**: `sales_order.increment_id`

### 23. SmsErrorLogReport - SMS 發送失敗監控
- **條件**:
  - `responseCode != '00000'`
- **輸出**: `smslog_id`

### 24. NonOrderDayRedemptionReport - 非下單當日兌點檢查
- **條件**:
  - `trans_type = 1` (兌點)
  - `hpar.trans_datetime` 的日期與 `soi.created_at` (加 8 小時) 的日期不同
  - 只查詢 30 天內的訂單
- **輸出**: `sales_order.increment_id`

## 報表開關配置

在 `etc/di.xml` 中可以控制每個報表的啟用/停用狀態：

```xml
<item name="missing_invoice" xsi:type="array">
    <item name="class" xsi:type="object">HotaiConnected\Report\Model\Report\MissingInvoiceReport</item>
    <item name="enabled" xsi:type="boolean">true</item>   <!-- true=啟用, false=停用 -->
</item>
```

修改後執行 `bin/magento cache:clean` 即可生效。

### 目前各報表狀態

| # | 報表 Key | 狀態 |
|---|----------|------|
| 1 | missing_invoice | enabled |
| 2 | missing_customer_ticket | enabled |
| 3 | missing_point_deduction | enabled |
| 4 | canceled_order_missing_point_return | enabled |
| 5 | ticket_issued_but_processing | enabled |
| 6 | ticket_status_inconsistent | enabled |
| 7 | duplicate_ticket | enabled |
| 8 | shipping_address_mismatch | enabled |
| 9 | no_point_no_payment | enabled |
| 10 | ticket_order_non_pure_point | enabled |
| 11 | returned_order_missing_return_trace_no | enabled |
| 12 | unpaid_abnormal_status | enabled |
| 13 | point_order_redemption_status_report | enabled |
| 14 | gift_ticket_not_received | enabled |
| 15 | gift_order_empty_recipient | enabled |
| 16 | gift_ticket_wrong_recipient | enabled |
| 17 | ticket_pool_status_inconsistent | enabled |
| 18 | qware_ticket_info_missing | enabled |
| 19 | negative_campaign_discount | enabled |
| 20 | employee_group_mismatch | enabled |
| 21 | virtual_status_inconsistent | enabled |
| 22 | incomplete_ticket_order | enabled |
| 23 | sms_error_log | enabled |
| 24 | non_order_day_redemption | enabled |

## 配置

### 後台設定

在 **Stores → Configuration → General → HotaiConnected Report** 中可配置：

#### Slack Settings
| 設定項 | 說明 |
|-------|------|
| Daily Report Webhook URL | 每日 SQL 報表通知的 Slack Webhook URL |
| Command Webhook URL | CLI 指令執行結果通知的 Slack Webhook URL |

#### Pending Employee Report
| 設定項 | 必填 | 說明 |
|-------|:----:|------|
| Find Member API URL | ✓ | find-member API 端點（用於 `generate` 指令） |
| Search Member API URL | ✓ | search-member API 端點（用於 `sync` 指令） |
| APP_ID | ✓ | API 請求標頭的 APP_ID |
| AppKey | ✓ | API 請求標頭的 AppKey（加密儲存） |
| Export Path | | CSV 匯出目錄路徑（預設：`var/export`） |

### Cron 執行時間
在 `etc/crontab.xml` 中配置：
```xml
<schedule>0 9 * * *</schedule>  <!-- 每天早上 9:00 -->
```

---

## CLI 指令

### Pending Employee Report

產生待處理員工報表，檢查 `customer_pending_employee` 表中的手機號碼是否已註冊為會員。

```bash
bin/magento report:pending-employee:generate [options]
```

#### 選項

| 選項 | 說明 |
|-----|------|
| `--dry-run` | 只執行查詢，不呼叫 API、不匯出 CSV、不發送 Slack |
| `--no-slack` | 不發送 Slack 通知 |
| `--no-csv` | 不匯出 CSV 檔案 |

#### 執行流程

1. **查詢已註冊員工**: 查詢 `customer_pending_employee` 中已有對應 `customer_entity` 記錄的 `member_seq`
2. **查詢未註冊員工**: 查詢 `customer_pending_employee` 中無對應 `customer_entity` 記錄的資料
3. **呼叫 API**: 將未註冊的手機號碼送至 find-member API 查詢
4. **計算差異**: 比較送出的手機數量與 API 回應數量的差異
5. **匯出 CSV**: 將結果匯出為 CSV 檔案
6. **Slack 通知**: 發送統計結果到 Slack

#### Slack 訊息格式

```
數據統計
• 和泰購員工：85xx 筆
• 非和泰購會員查詢：xx 筆
• 回應總數：xx 筆
• 差異數量：xx 筆

差異 One ID 清單
- <oneId>
- <oneId>

匯出檔案路徑
var/export/pending_employee_report_20250205_120000.csv
```

#### CSV 輸出欄位

| 欄位 | 來源 | 說明 |
|-----|------|------|
| cellphone | DB | 手機號碼 |
| status | DB | 狀態 |
| company | DB | 公司 |
| department | DB | 部門 |
| job_title | DB | 職稱 |
| name | DB | 姓名 |
| employee_id | DB | 員工編號 |
| created_at | DB | 建立時間 |
| updated_at | DB | 更新時間 |
| api_member_id | API | One ID (memberId) |
| api_mobile_phone | API | API 回傳手機 |
| api_name | API | API 回傳姓名 |

#### 使用範例

```bash
# 完整執行（查詢 + API + CSV + Slack）
bin/magento report:pending-employee:generate

# 只執行查詢，測試 SQL 是否正確
bin/magento report:pending-employee:generate --dry-run

# 執行但不發送 Slack
bin/magento report:pending-employee:generate --no-slack

# 執行但不匯出 CSV
bin/magento report:pending-employee:generate --no-csv
```

---

### Pending Employee Sync

傳入多組 One ID，同步員工資料到 `customer_pending_employee` 表。

```bash
bin/magento report:pending-employee:sync <one_ids> [options]
```

#### 參數

| 參數 | 說明 |
|-----|------|
| `one_ids` | One ID 清單（逗號分隔） |

#### 選項

| 選項 | 說明 |
|-----|------|
| `--dry-run` | 只查詢，不執行 INSERT/UPDATE、不發送 Slack |
| `--no-slack` | 不發送 Slack 通知 |

#### 執行流程

```
┌─────────────────────────────────────────────────────────┐
│                 輸入: One ID 清單                        │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Step 1: 查詢 customer_entity.member_seq                │
│  分成兩組:                                               │
│  • Group A: 有在 customer_entity 的 One ID              │
│  • Group B: 不在 customer_entity 的 One ID              │
└─────────────────────────────────────────────────────────┘
                          │
        ┌─────────────────┴─────────────────┐
        ▼                                   ▼
┌─────────────────────┐       ┌─────────────────────────┐
│  Group A: 已註冊會員  │       │  Group B: 非會員        │
│  檢查 phone_number   │       │  呼叫 search-member API │
│  是否已在            │       │  取得手機號碼           │
│  pending_employee    │       │                         │
└─────────────────────┘       └─────────────────────────┘
        │                                   │
        ▼                                   ▼
┌─────────────────────┐       ┌─────────────────────────┐
│  不存在 → INSERT     │       │  INSERT / UPDATE        │
│  已存在 → SKIP       │       │  pending_employee       │
└─────────────────────┘       └─────────────────────────┘
        │                                   │
        └─────────────────┬─────────────────┘
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Step 3: 發送 Slack 通知（統計結果 + 差異清單）           │
└─────────────────────────────────────────────────────────┘
```

#### Slack 訊息格式

```
1. 員工資料更新
• 新增：123 筆
• 更新：22 筆
• 略過（已存在）：50 筆

---

2. 非和泰購會員查詢
• 查詢總數：242 筆
• 回應總數：232 筆
• 差異數量：10 筆

差異 One ID 清單：
- 865f4c96-72b1-4749-8f39-a4e04758c8d1
- 865f4c96-72b1-4749-8f39-a4e04758c8d2
```

#### 使用範例

```bash
# 同步兩個 One ID
bin/magento report:pending-employee:sync "64943ECD-01DD-4DCD-9FD4-857E66C180AE,57DE7257-E96A-4B12-99EF-4088B737C784"

# Dry run 模式（只查詢，不修改資料）
bin/magento report:pending-employee:sync "64943ECD-01DD-4DCD-9FD4-857E66C180AE" --dry-run

# 同步但不發送 Slack
bin/magento report:pending-employee:sync "64943ECD-01DD-4DCD-9FD4-857E66C180AE" --no-slack
```

#### 插入資料說明

同步時會自動設定以下欄位值：

| 欄位 | 值 |
|-----|-----|
| `cellphone` | 從 customer_entity.phone_number 或 API 取得 |
| `isEnabled` | `1` (true) |
| `organizationIdentity` | `null` |
| `categoryIdentity` | `'HotaiEMP'` |
| `created_at` / `updated_at` | 當前時間 |

## 模組架構

```
HotaiConnected/Report/
├── Console/
│   └── Command/
│       ├── PendingEmployeeReportCommand.php  # generate 指令
│       └── PendingEmployeeSyncCommand.php    # sync 指令
├── Cron/
│   └── SendDailyReport.php                   # Cron 執行入口
├── Helper/
│   └── Config.php                            # 配置讀取
├── Model/
│   ├── PendingEmployee/
│   │   ├── ApiClient.php                     # find-member API 客戶端
│   │   ├── SearchApiClient.php               # search-member API 客戶端
│   │   ├── CsvExporter.php                   # CSV 匯出
│   │   ├── ReportGenerator.php               # generate 報表產生器
│   │   └── SyncService.php                   # sync 同步服務
│   ├── Report/
│   │   ├── ReportInterface.php               # Report 介面
│   │   ├── AbstractReport.php                # 抽象基礎類別
│   │   └── MissingInvoiceReport.php          # 沒有發票主檔的訂單報表
│   ├── ReportManager.php                     # 管理所有 Report
│   └── SlackNotifier.php                     # Slack 通知服務
├── etc/
│   ├── adminhtml/
│   │   └── system.xml                        # 後台設定介面
│   ├── config.xml                            # 預設配置值
│   ├── module.xml
│   ├── di.xml                                # DI 配置
│   └── crontab.xml                           # Cron 設定
└── registration.php
```

## Log

所有執行記錄會寫入專用的 log 檔案：

| 功能 | Log 檔案 |
|-----|---------|
| 每日 SQL 報表 | `var/log/hotaiconnected_report.log` |
| Pending Employee Report | `var/log/hotaiconnected_report.log` |
| Slack 通知 | `var/log/hotaiconnected_report.log` |

查看 log：
```bash
tail -f var/log/hotaiconnected_report.log
```

即時監控 Pending Employee Report：
```bash
tail -f var/log/hotaiconnected_report.log | grep -i pending
```

## 注意事項

1. 確保 Magento cron 有正常執行
2. Slack Webhook URL 必須有效（後台設定兩個不同的 URL）
   - **Daily Report Webhook**: 用於每日 SQL 報表通知（Cron）
   - **Command Webhook**: 用於 CLI 指令執行結果通知（Pending Employee Report）
3. SQL 查詢要注意效能，避免慢查詢
4. 每個 Slack 訊息間隔 1 秒，避免 rate limit
5. Pending Employee Report 的 AppKey 會加密儲存在資料庫中
6. CSV 匯出使用 UTF-8 BOM 編碼，確保 Excel 開啟時中文正常顯示
