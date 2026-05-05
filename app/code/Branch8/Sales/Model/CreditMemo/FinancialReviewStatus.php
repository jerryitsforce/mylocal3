<?php

namespace Branch8\Sales\Model\CreditMemo;

class FinancialReviewStatus
{
    const DEFAULT = 0; //No need to be reviewed
    const FINANCIAL_REVIEWING = 1; //reviewing 審核中
    const FINANCIAL_REVIEW_SUCCESS = 2; //審核成功
    const FINANCIAL_REVIEW_FAIL = 3; //審核失敗

    const PASS_FINANIAL_REVIEW = [
        self::DEFAULT,
        self::FINANCIAL_REVIEW_SUCCESS
    ];
}
