<?php
/**
 * Copyright © Hotai Connected Co.,Ltd All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Api;

interface FamilyNotifyInterface
{
    /**
     * Handle the ticket notification process
     *
     * @return void
     */
    public function handle();
}
