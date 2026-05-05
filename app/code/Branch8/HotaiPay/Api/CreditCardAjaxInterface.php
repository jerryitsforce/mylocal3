<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Api;

interface CreditCardAjaxInterface
{
    /**
     * Edit credit card from ajax request.
     * @return mixed
     */
    public function editFromAjax();

    /**
     * Delete credit card from ajax request.
     * @return mixed
     */
    public function deleteFromAjax();
}

