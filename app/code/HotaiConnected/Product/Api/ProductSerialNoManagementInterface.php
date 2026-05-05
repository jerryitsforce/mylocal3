<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace HotaiConnected\Product\Api;

interface ProductSerialNoManagementInterface
{

    /**
     * POST for productSerialNo api
     * @return string
     */
    public function postProductSerialNo();

    /**
     * PUT for productSerialNo api
     * @return string
     */
    public function updateProductSerialNo();
}

