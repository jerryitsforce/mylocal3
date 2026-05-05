# HotaiConnected_Cart Module

統一購物車 API 模組，整合原本的 3 個 API 為單一端點。

## 功能

將以下 3 個 API 整合為一個：
1. `/checkout/cart/reloadItems` - 購物車商品列表
2. `/customer/section/load/?sections=cart` - 購物車摘要資訊
3. `/graphql` (customerCart) - 優惠券和折扣資訊

## API 端點

```
GET /hotaicart/data/index
```

### 特性
- ✅ GET 方法（無需 CSRF token）
- ✅ 需要登入 Session
- ✅ 返回 JSON 格式

## 回應格式

```json
{
  "sellerItems": [
    {
      "sellerId": "flagship_11",
      "sellerInfo": {
          "link": "https://example.com/seller.html",
          "title": "賣家名稱"
      },
      "sellerType": "常溫",
      "freeShippingStatus": {
        "sellerSubtotal": "NT$3,600",
        "shippingMethods": [
          {
            "message": "已達免運門檻",
            "methodName": "超商取貨",
            "reached": true,
            "shippingFee": "NT$0"
          }
        ],
        "statusList": [
          {
            "message": "已達免運門檻",
            "reached": true
          }
        ]
      },
      "isAllChecked": false,
      "items": [
          {
              "itemId": "361216",
              "productId": "1284228",
              "productType": "simple",
              "isChecked": false,
              "productUrl": "https://example.com/product.html?from_cart=1",
              "productName": "商品名稱",
              "imageUrl": "https://example.com/media/catalog/product/image.jpg",
              "imageAlt": "商品名稱",
              "qty": 3,
              "qtyInputName": "cart[361216][qty]",
              "totalPrice": "NT$1,350",
              "price": "NT$450",
              "shippingMethod": "",
              "hasError": false,
              "options": [
                {
                  "custom_view": false,
                  "label": "單位",
                  "optionId": "741437",
                  "optionType": "drop_down",
                  "printValue": "台",
                  "value": "台"
                }
              ],
              "productImage": {
                "alt": "商品名稱",
                "src": "https://example.com/media/catalog/product/image.jpg",
                "width": 150,
                "height": 150
              }
          }
      ]
    }
  ],
  "applied_coupons": "COUPON_CODE",
  "prices_coupons": {
    "discounts": [
      {
        "label": "折扣說明",
        "amount": {"value": 100}
      }
    ]
  },
  "subtotal": "<span class=\"price\">NT$11,779</span>",
  "subtotalAmount": 11779,
  "subtotalExclTax": "<span class=\"price\">NT$11,218</span>",
  "subtotalInclTax": "<span class=\"price\">NT$11,779</span>",
  "summaryCount": 10
}
```

## 架構

```
HotaiConnected/Cart/
├── Controller/
│   └── Data/
│       └── Index.php                    # API Controller
├── Service/
│   ├── UnifiedCartDataService.php       # 主服務
│   └── DataProvider/
│       ├── SellerItemsProvider.php      # 賣家商品資料
│       ├── ItemDetailsProvider.php      # 商品詳細資訊
│       ├── FreeShippingProvider.php     # 免運門檻計算
│       ├── TotalsProvider.php           # 價格總計
│       └── CouponProvider.php           # 優惠券資訊
```

## 測試

### 方法 1: cURL

```bash
curl -X GET 'http://your-domain.com/hotaicart/data/index' \
  -H 'Cookie: PHPSESSID=your_session_id' \
  | jq '.'
```

### 方法 2: 瀏覽器

1. 登入前台帳號
2. 加入商品到購物車
3. 訪問：`http://your-domain.com/hotaicart/data/index`

### 方法 3: JavaScript

```javascript
fetch('/hotaicart/data/index', {
    credentials: 'include'
})
.then(response => response.json())
.then(data => console.log(data));
```

## 依賴模組

- `Magento_Checkout`
- `Magento_Customer`
- `Magento_Quote`
- `Branch8_SplitCart`
- `Branch8_HotaiShipping`
- `Branch8_FlagshipStore`

## 啟用模組

```bash
bin/magento module:enable HotaiConnected_Cart
bin/magento setup:upgrade
bin/magento cache:flush
```

## 效能優化

- 建議在前端加入快取機制
- 可在 Nginx/Varnish 層設定短時間快取（10-30秒）
- Session 資料自動處理，無需手動管理

## 注意事項

1. **需要登入** - 必須有有效的 customer session
2. **無 CSRF 驗證** - GET 方法，唯讀操作
3. **商品圖片** - 自動調整為 150x150
4. **價格格式** - 包含 HTML `<span class="price">` 標籤
5. **免運門檻** - 依賣家和配送方式動態計算
