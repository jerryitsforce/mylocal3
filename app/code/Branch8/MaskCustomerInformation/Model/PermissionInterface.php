<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model;
interface PermissionInterface
{
    /**
     * @return bool
     */
    public function canView();
}
