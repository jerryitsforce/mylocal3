<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Model;

use Branch8\HelpDesk\Api\Data\FindYourOrdersDataInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder as CoreOrder;


class ParentOrder extends CoreOrder implements FindYourOrdersDataInterface
{
    /**
     * @return string
     */
    public function getIncrementId()
    {
        return $this->getData('increment_id');
    }

    /**
     * @param string $value
     * @return $this|FindYourOrdersDataInterface
     */
    public function setIncrementId(string $value)
    {
        $this->setData('increment_id', $value);
        return $this;
    }

}
