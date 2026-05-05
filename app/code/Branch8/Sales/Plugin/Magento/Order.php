<?php

namespace Branch8\Sales\Plugin\Magento;

use Branch8\HotaiCore\Model\Order\Status;
use Branch8\HotaiCore\Model\Order\State;
use Magento\Framework\Filter\Input\PurifierInterface;

class Order
{
    /**
     * @var PurifierInterface
     */
    private PurifierInterface $purifier;

    /**
     * Order constructor.
     *
     * @param PurifierInterface $purifier
     */
    public function __construct(
        PurifierInterface $purifier
    ){
        $this->purifier = $purifier;
    }

    public function afterCanCreditmemo(\Magento\Sales\Model\Order $subject, $result)
	{

        if ($subject->hasForcedCanCreditmemo() && $subject->getData('forced_can_creditmemo') == "true") {
            return true;
        }

        return $result;

	}

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param $result
     * @param $price
     * @param bool $addBrackets
     * @return string
     */
    public function afterFormatPrice(\Magento\Sales\Model\Order $subject, $result, $price, $addBrackets = false)
    {
        return $this->purifier->purify($result);

    }
}
