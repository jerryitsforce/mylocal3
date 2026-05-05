<?php

/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */

 declare(strict_types=1);

namespace HotaiConnected\Order\Api;

interface OrderExistenceManagementInterface
{

    /**
     * POST for order-existence api
     * @return mixed
     */
    public function postOrderExistence();
}

