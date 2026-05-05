<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       30/03/2026
 */

namespace Branch8\MarketPlaceParentOrderEcPayLogisticIntegration\Model;

use Branch8\HotaiCore\Model\LogModuleDeclarationInterface;

class LogDeclaration implements LogModuleDeclarationInterface
{
    /**
     * @return string[]
     */
    public function getInfo(): array
    {
       return [
           'value' => 'Branch8_MarketPlaceParentOrderEcPayLogisticIntegration',
           'label' => 'Branch8_MarketPlaceParentOrderEcPayLogisticIntegration',
       ];
    }
}
