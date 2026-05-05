<?php

namespace Branch8\Sales\Model\CreditMemo;

class VoidInvoiceType
{
    const DEFAULT = 0;
    const VOID = 1; //作廢發票
    const ALLOWENCE = 2; //開立折讓發票
    const VOID_AND_REISSUE = 3; //作廢發票且重開發票
    const VOID_AND_ISSUE_OFF_INVOICE_ALLOWENCE = 4; //作廢發票且開立折讓發票
}
