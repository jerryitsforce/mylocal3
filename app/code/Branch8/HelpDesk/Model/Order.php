<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\FindYourOrdersDataInterface;
use Magento\Sales\Model\Order as CoreOrder;

/**
 * Find Your Order Model
 * @method FindYourOrdersDataInterface setId(string $value)
 * @method getId() getId()
 * @method getIncrementId() getIncrementId()
 * @method FindYourOrdersDataInterface setIncrementId(string $value)
 */
class Order extends CoreOrder implements FindYourOrdersDataInterface
{

}
