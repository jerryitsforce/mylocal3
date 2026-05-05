<?php
namespace Branch8\AppSettings\Api;

use Branch8\AppSettings\Api\Data\AppVersionInterface;

interface AppVersionControlInterface
{
    /**
     * @return AppVersionInterface
     */
    public function get(): AppVersionInterface;
}
