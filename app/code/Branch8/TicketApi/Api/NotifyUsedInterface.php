<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Api;

use Branch8\TicketApi\Api\Data\NotifyResponseInterface;

interface NotifyUsedInterface
{
    /**
     * @return void
     */
    public function handle();
}
