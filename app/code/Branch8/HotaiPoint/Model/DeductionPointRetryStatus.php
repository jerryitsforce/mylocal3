<?php

namespace Branch8\HotaiPoint\Model;

class DeductionPointRetryStatus
{
    const FIELD_NAME = 'hotai_point_deduction_point_retry_status';

    const STATUS_RETRY_LIMIT_ERROR   = -1; // 異常, 重新請求已達次數上限
    const STATUS_DEFAULT             = 0; // 初始狀態
    const STATUS_NEED_RETRY          = 1; // 需要重新請求
    const STATUS_RETRY_SUCCESS       = 2; // 重新請求成功
}