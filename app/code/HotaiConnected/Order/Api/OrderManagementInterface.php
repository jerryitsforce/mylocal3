<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\Order\Api;

interface OrderManagementInterface
{

    /**
     * POST for order api
     * @return mixed
     */
    public function postOrder();

    /**
     * PUT for order api
     * @param string $orderNo
     * @return mixed
     */
    public function updateOrder($orderNo);

    /**
     * GET for order api
     * @param string $orderNo
     * @return mixed
     */
    public function getOrder($orderNo);

    /**
     * DELETE for order api
     * @param string $orderNo
     * @return mixed
     */
    public function deleteOrder($orderNo);
}

