# Mage2 Module Branch8 Hopes

    ``branch8/module-hopes``

 - [Main Functionalities](#markdown-header-main-functionalities)
 - [Installation](#markdown-header-installation)
 - [Configuration](#markdown-header-configuration)
 - [Specifications](#markdown-header-specifications)
 - [Attributes](#markdown-header-attributes)


## Main Functionalities
HopesApiModule

## Installation
\* = in production please use the `--keep-generated` option

### Type 1: Zip file

 - Unzip the zip file in `app/code/Branch8`
 - Enable the module by running `php bin/magento module:enable Branch8_Hopes`
 - Apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`

### Type 2: Composer

 - Make the module available in a composer repository for example:
    - private repository `repo.magento.com`
    - public repository `packagist.org`
    - public github repository as vcs
 - Add the composer repository to the configuration by running `composer config repositories.repo.magento.com composer https://repo.magento.com/`
 - Install the module composer by running `composer require branch8/module-hopes`
 - enable the module by running `php bin/magento module:enable Branch8_Hopes`
 - apply database updates by running `php bin/magento setup:upgrade`\*
 - Flush the cache by running `php bin/magento cache:flush`


## Configuration




## Specifications

 - 取得運送訂單明細
	- POST - /rest/V1/branch8-hopes/orderstackdetails
    - Bearer Token {{token}}

|Request Params|Type|Is Required|
|-|-|-|
|From|datetime|True|
|To|datetime|True|

|Response Params|Name|Value|
|-|-|-|
|key|子訂單訂單編號||
|SHIPBRNCD|倉庫代碼|SDEC:和勁出貨倉|
|B2USORD_SEQ|B2C訂單序號||
|ORDDT|訂貨日期||
|MEMBER_ID|會員ID|Email|
|PAYTERM|預設付款方式|1: 信用卡付款|
|ORDERPTAX|訂價(含稅額)||
|ACT_CASH||實付現金|
|ACT_POINT|實付點數||
|ACT_TAX|實付稅額||
|CURRENCY|幣別|NT:台幣|
|INVSHEET|開立發票聯式|p:Individual c:Company d:Donation|
|SHIPTYPE|B2C運送方式|1:和泰配送 2:黑貓 3:超商|
|STOREID|B2C門市店號||
|STIRENAME|B2C門市店名||
|SHIPFEE|B2C運費||
|SHIPFEEPAY|B2C運費支付方式|1:現金|
|RECVNAME|B2C客戶名稱||
|RECVCITY1|B2C縣市||
|RECVCITY2|B2C鄉鎮||
|RECVADDRESS|B2C客戶地址||
|RECVZIP|B2C客戶郵遞區號||
|RECVTEL|B2C客戶聯絡電話||
|RECVMOBILE|B2C客戶手機電話||
|RECVEMAIL|B2C客戶EMAIL||
|INVOICE_NO|B2C發票號碼||
|CUSTID|B2C客戶統一編號||
|ORDERCOMMENT|訂單備註||
|SENDDT|傳送日期||
|PROMARK|處理註記||
|ITEMS|訂單子項目||
|ITEM_NO|序號||
|ITEM_TYPE|子訂單類型|configurable, simple, bundle, grouped, downloadable|
|FRCD|商品別||
|PARTNO|零件號碼|sku|
|PARTCUSTID|零件客戶代碼|EC00: 電商代碼|
|CARNO|車號||
|ORDQTY|客戶訂購數量||
|PAYCD|點數+現金碼|A: 點數、B: 現金、C: 點數+現金、D:員工價|
|ORDERTAX|訂價(含稅額)||
|ACT_CASH|實付現金||
|ACT_POINT|實付點數||
|EMPRTAX|員工價||
|PROMARK|處理註記||

```
# Request Example
{
    "From": "2024-06-05 00:00:00",
    "To": "2024-06-05 23:00:00"
}

# Response Example
{
    "000001083": {
        "SHIPBRNCD": "SDEC",
        "B2USORD_SEQ": "0",
        "ORDDT": "2024-06-05 12:11:32",
        "MEMBER_ID": "glenn@branch8.com",
        "PAYTERM": 1,
        "ORDERPTAX": 0,
        "ACT_CASH": "227.0000",
        "ACT_POINT": 0,
        "ACT_TAX": 0,
        "CURRENCY": "NT",
        "INVSHEET": "p",
        "SHIPTYPE": 3,
        "STOREID": null,
        "STIRENAME": null,
        "SHIPFEE": 40,
        "SHIPFEEPAY": 1,
        "RECVNAME": "glenn123glenn123",
        "RECVCITY1": "10",
        "RECVCITY2": null,
        "RECVADDRESS": "Level 6, Departures Level, Terminal 1, Hong Kong International Airport (near Gate 1)",
        "RECVZIP": "123456",
        "RECVTEL": "12345678",
        "RECVMOBILE": "12345678",
        "RECVEMAIL": "glenn@branch8.com",
        "INVOICE_NO": null,
        "CUSTID": null,
        "ORDERCOMMENT": "",
        "SENDDT": "2024-08-34",
        "PROMARK": "",
        "ITEMS": [
            {
                "ITEM_NO": "1705",
                "ITEM_TYPE": "configurable",
                "FRCD": "TODO",
                "PARTNO": "24060512_P8AU0037301_1705",
                "PARTCUSTID": "EC00",
                "CARNO": "TODO",
                "ORDQTY": "1.0000",
                "PAYCD": "B",
                "ORDERTAX": 88,
                "ACT_CASH": 88,
                "ACT_POINT": 0,
                "EMPRTAX": "TODO",
                "PROMARK": ""
            },
            {
                "ITEM_NO": "1706",
                "ITEM_TYPE": "configurable",
                "FRCD": "TODO",
                "PARTNO": "24060512_P8AU0037301_1706",
                "PARTCUSTID": "EC00",
                "CARNO": "TODO",
                "ORDQTY": "1.0000",
                "PAYCD": "B",
                "ORDERTAX": 99,
                "ACT_CASH": 99,
                "ACT_POINT": 0,
                "EMPRTAX": "TODO",
                "PROMARK": ""
            }
        ]
    }
}
```



 - 更新 Shipping Number
	- POST - /rest/V1/branch8-hopes/updateshippingtracknumber
    - Bearer Token {{token}}

|Request Params|Type|Is Required|Name|
|-|-|-|-|
|Key|String|True|NWSHIPMENTNO: 寄送單號|
|SHIPTYPE|String|True|B2C運送方式|
|ITEMS||True|寄送單號包含的子項目|
|B2CHDORDNO|String|True|B2C客戶訂單|
|PROMARK|String|True|備註|
|PARTNO|String|True|商品編號|
|CHKORDDT|Datetime|True|查照日期|
|MUCASECD|String|True|是否拆箱|
```
# Request Example
{
    "TEST-GGG-1": {
        "SHIPTYPE": "1",
        "B2CHDORDNO": "000001083",
        "ITEMS": [
            {
                "PROMARK": "RRR",
                "PARTNO": "24060512_P8AU0037301_1705",
                "CHKORDDT": "2021-06-15 14:27:49.603",
                "MUCASECD": ""
            },
            {
                "PROMARK": "RRR",
                "PARTNO": "24060512_P8AU0037301_1706",
                "CHKORDDT": "2021-06-15 14:27:49.603",
                "MUCASECD": ""
            }
        ]
    },
    "TEST-PARKNO-2": {
        "SHIPTYPE": "2",
        "B2CHDORDNO": "000001084",
        "ITEMS": [
            {
                "PROMARK": "RRR",
                "PARTNO": "24060512_P8AU0037302_1709",
                "CHKORDDT": "2021-06-15 14:27:49.603",
                "MUCASECD": "Y"
            }
        ]
    },
    "TEST-PARKNO-3": {
        "SHIPTYPE": "3",
        "B2CHDORDNO": "000001084",
        "ITEMS": [
            {
                "PROMARK": "RRR",
                "PARTNO": "Hao Configurable 02-SS",
                "CHKORDDT": "2021-06-15 14:27:49.603",
                "MUCASECD": "Y",
                "SHIPTYPE": 3
            }
        ]
    }
}

# Response Example
{
    "TEST-GGG-1": "Successful Created",
    "TEST-PARKNO-2": "Preorder is not completed.",
    "TEST-PARKNO-3": "We cannot create an empty shipment."
}
```

 - 取得統一數網 Sin 檔資料
	- POST - /rest/V1/branch8-hopes/orderstackshipmentsin
    - Bearer Token {{token}}

|Request Params|Type|Is Required|
|-|-|-|
|From|datetime|True|
|To|datetime|True|


```
# Request Example
{
    "From": "2024-08-30",
    "To": "2024-09-01"
}

# Response Example
{
    "TEST-PARKNO-1": { //Tracking Number
        "EshopId": "",
        "OPMode": "A",
        "EshopOrderNo": "000001083",
        "EshopOrderDate": "2024-06-05",
        "ServiceType": 3,
        "ShopperName": "glennglenn",
        "ShopperPhone": "",
        "ShopperEmail": "",
        "ShopperMobilPhone": "",
        "ReceiverName": "glennglenn",
        "ReceiverPhone": "",
        "ReceiverMobilPhone": "12345678",
        "ReceiverEmail": "",
        "ReceiverIDNumber": "",
        "OrderAmount": 227,
        "OrderDetail": {
            "ProductId": "",
            "ProductName": "",
            "Quantity": "",
            "Unit": "",
            "UnitPrice": ""
        },
        "ShipmentDetail": {
            "ShipmentNo": "TEST-PARKNO-1",
            "ShipDate": "2024-08-31",
            "ReturnDate": "2024-09-08",
            "LastShipment": "Y",
            "ShipmentAmount": 528,
            "StoreId": "TODO",
            "EshopType": "TODO",
            "AwardAmount": 528
        }
    },
    "TEST-GGG-1": {
        "EshopId": "",
        "OPMode": "A",
        "EshopOrderNo": "000001083",
        "EshopOrderDate": "2024-06-05",
        "ServiceType": 3,
        "ShopperName": "glennglenn",
        "ShopperPhone": "",
        "ShopperEmail": "",
        "ShopperMobilPhone": "",
        "ReceiverName": "glennglenn",
        "ReceiverPhone": "",
        "ReceiverMobilPhone": "12345678",
        "ReceiverEmail": "",
        "ReceiverIDNumber": "",
        "OrderAmount": 227,
        "OrderDetail": {
            "ProductId": "",
            "ProductName": "",
            "Quantity": "",
            "Unit": "",
            "UnitPrice": ""
        },
        "ShipmentDetail": {
            "ShipmentNo": "TEST-GGG-1",
            "ShipDate": "2024-08-31",
            "ReturnDate": "2024-09-08",
            "LastShipment": "Y",
            "ShipmentAmount": 3828,
            "StoreId": "TODO",
            "EshopType": "TODO",
            "AwardAmount": 3828
        }
    }
}
```


 -  更新 SRP 資料
	- POST - /rest/V1/branch8-hopes/updateshipmentsrp
    - Bearer Token {{token}}

|Request Params|Type|Is Required||
|-|-|-|-|
|Key|string|True|Tracking Number|
|ErrorCode|string|True||

```
# Request Example
{
    "TEST-GGG-1": {
        "ErrorCode": "01110"
    },
    "TEST-PARKNO-3": {
        "ErrorCode": "E1.05"
    }
}

# Response Example
{
    "TEST-GGG-1": "Successful Updated.",
    "TEST-PARKNO-3": "Successful Updated."
} 
```

 - 更新 ETA 資料 
	- POST - /rest//V1/branch8-hopes/updateshipmenteta
    - Bearer Token {{token}}

|Request Params|Type|Is Required||
|-|-|-|-|
|Key|string|True|Tracking Number|
|ServiceType|string|True|服務型態代碼|
|PickUpDeadline|string|True| 消費者領貨期限 (ShipDate + 15) |
|StoreId|string|True|門市店代碼|
|StoreName|string|True|門市店名稱|
|Route|String|True|路線路順|
|Area|String|True|區域別|
|ShipmentAmount|String|True|出貨單金額|
|ReplyCode|String|True|回應代碼|
|ReplyDetail|String|True|回應詳細內容|

```
#Request Expmle
{
    "TEST-GGG-1": {
        "ServiceType": "1",
        "PickUpDeadline": "2024-10-11",
        "StoreId": "886750",
        "StoreName": "統一門市",
        "Route": "D13031",
        "Area": "12",
        "ShipmentAmount": 1111,
        "ReplyCode": "02001",
        "ReplyDetail": "結轉物流中心"
    },
    "TEST-PARKNO-3": {
        "ServiceType": "1",
        "PickUpDeadline": "2024-10-11",
        "StoreId": "886749",
        "StoreName": "信義門市",
        "Route": "D13031",
        "Area": "12",
        "ShipmentAmount": 1111,
        "ReplyCode": "02001",
        "ReplyDetail": "結轉物流中心"
    }
}

#Response Example
{
    "TEST-GGG-1": "Successful Updated.",
    "TEST-PARKNO-3": "Successful Updated."
}
```

 - API Endpoint
	- POST - /rest/V1/branch8-hopes/updateshipmentein
    - Bearer Token {{token}}

|Request Params|Type|Is Required||
|-|-|-|-|
|Key|string|True|Tracking Number|
|DCReceiveDate|String|True|DC 進貨驗收日期 |
|DCReceiveStatus|String|True|DC 進貨驗收狀態代碼|
|DCRecName|String|True|DC 進貨驗收狀態名稱|
|DCStoreDate|String|True|門市到店日期|

```
# Request Example
{
    "TEST-GGG-1": {
        "DCReceiveDate": "2009-01-02",
        "DCReceiveStatus": "00",
        "DCRecName": "超才",
        "DCStoreDate": "2029-01-02"
    },
    "TEST-PARKNO-3": {
        "DCReceiveDate": "2009-01-02",
        "DCReceiveStatus": "00",
        "DCRecName": "超才",
        "DCStoreDate": "2029-01-02"
    }
}

#Response Example
{
    "TEST-GGG-1": "Successful Updated.",
    "TEST-PARKNO-3": "Successful Updated."
}
```

 - 更新 PPS 資料，若送達則將 Item 狀態改成已送達
	- POST - /rest/V1/branch8-hopes/updateshipmentpps
    - Bearer Token {{token}}

|Request Params|Type|Is Required||
|-|-|-|-|
|Key|string|True|Tracking Number|
|StoreId|string|True|門市店代碼|
|StoreDate|string|True|到店日期|
|StoreTime|string|True|到店時間|
|StoreType|string|True|到店註記|
|TelNo|string|True|手機號碼|

```
# Request Example
{
    "TEST-GGG-1": {
        "StoreId": "886750",
        "StoreDate": "2024-07-27",
        "StoreTime": "000623",
        "StoreType": "101",
        "TelNo": ""
    },
    "TEST-PARKNO-3": {
        "StoreId": "886750",
        "StoreDate": "2024-07-27",
        "StoreTime": "000523",
        "StoreType": "011",
        "TelNo": ""
    }
}

# Response Example
{
    "TEST-GGG-1": "Successful Updated.",
    "TEST-PARKNO-3": "Successful Updated."
}
```

 - API Endpoint
	- POST - Branch8\Hopes\Api\CreateRmaManagementInterface > Branch8\Hopes\Model\CreateRmaManagement
    - Bearer Token {{token}}

 - API Endpoint
	- POST - Branch8\Hopes\Api\RmaDetailsManagementInterface > Branch8\Hopes\Model\RmaDetailsManagement
    - Bearer Token {{token}}

 - API Endpoint
	- POST - Branch8\Hopes\Api\UpdateReturnStatusManagementInterface > Branch8\Hopes\Model\UpdateReturnStatusManagement
    - Bearer Token {{token}}

 - API Endpoint
	- POST - Branch8\Hopes\Api\RmaVoidInvoiceManagementInterface > Branch8\Hopes\Model\RmaVoidInvoiceManagement
    - Bearer Token {{token}}


## Attributes



