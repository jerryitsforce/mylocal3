<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\CustomerSync\Api;

interface CustomerSyncManagementInterface
{

    /**
     * POST CustomerSync status api
     *
     * @return mixed
     */
    public function getCustomerSync();

    /**
     * POST for CustomerSync api
     *
     * @return mixed
     */
    public function deleteCustomerSync();
}

