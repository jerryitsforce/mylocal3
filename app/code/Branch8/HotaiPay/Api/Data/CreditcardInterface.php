<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\HotaiPay\Api\Data;

interface CreditcardInterface
{

    const IS_MAIN = 'is_main';
    const CREDITCARD_ID = 'creditcard_id';
    const MEMBER_ONE_ID = 'member_one_id';
    const CREDITCARD_TOKEN_ID = 'creditcard_toke_id';
    const TYPE = 'type';
    const BANK_DESC = 'bank_desc';
    const NUMBER_MASK = 'number_mask';
    const AFFINITY_CODE = 'affinity_code';
    const CUSTOMER_ID = 'customer_id';
    const ALIAS_NAME = 'alias_name';
    const IS_DEFAULT = 'is_default';
    const IS_DEBIT = 'is_debit';
    const IS_CTBC = 'is_ctbc';
    const IS_HT = 'is_ht';
    const BIN_INFO_CODE = 'bin_info_code';

    /**
     * Get creditcard_id
     * @return string|null
     */
    public function getCreditcardId();

    /**
     * Set creditcard_id
     * @param string $creditcardId
     * @return \Branch8\HotaiPay\Creditcard\Api\Data\CreditcardInterface
     */
    public function setCreditcardId($creditcardId);

    /**
     * Get is_main
     * @return string|null
     */
    public function getIsMain();

    /**
     * Set is_main
     * @param string $isMain
     * @return \Branch8\HotaiPay\Creditcard\Api\Data\CreditcardInterface
     */
    public function setIsMain($isMain);
}
