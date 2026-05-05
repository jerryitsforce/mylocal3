<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Api;

use Branch8\TicketApi\Api\Data\CheckResponseInterface;

interface CheckInterface
{
    /**
     * @return void
     */
    public function handle();
}
