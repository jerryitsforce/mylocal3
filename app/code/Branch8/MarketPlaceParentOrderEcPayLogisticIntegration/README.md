Branch8_MarketPlaceParentOrderEcPayLogisticIntegration
This module take responsibility for processing redirect to ECPay Logistic if We used parent order information checkout,
if the config "marketplace/mpsplitorder/mpsplitorder_enable" enable , we change flow to steps
1. Push order information to ECPay platform
2. Process callback 

Require modules

Ecpay_General

