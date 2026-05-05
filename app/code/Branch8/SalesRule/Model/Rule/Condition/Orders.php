<?php
declare(strict_types=1);

namespace Branch8\SalesRule\Model\Rule\Condition;

use Branch8\SalesRule\Model\Actions\GetOrderCount;
use Magento\Rule\Model\Condition as Condition;

/**
 * Product rule condition data model
 */
class Orders extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @var GetOrderCount
     */
    private $orderCount;

    /**
     * @param Condition\Context $context
     * @param GetOrderCount $getOrderCount
     * @param array $data
     */
    public function __construct(
        Condition\Context $context,
        GetOrderCount     $getOrderCount,
        array             $data = []
    )
    {
        $this->orderCount = $getOrderCount;
        parent::__construct($context, $data);
    }

    public function loadAttributeOptions()
    {
        $attributes = [
            'first_purchase_history' => __('First Purchase Order'),
        ];
        $this->setAttributeOption($attributes);
        return $this;
    }

    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    public function getInputType()
    {
        return 'select';
    }

    public function getValueElementType()
    {
        return 'select';
    }

    public function getValueSelectOptions()
    {
        $options = [[
            'label' => __('Yes'),
            'value' => 1]
        ];
        $key = 'value_select_options';
        if (!$this->hasData($key)) {
            $this->setData($key, $options);
        }

        return $this->getData($key);
    }

    /**
     * Validate Address Rule Condition
     *
     * @param \Magento\Framework\Model\AbstractModel $model
     *
     * @return bool
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $quote = $model;
        // @phpstan-ignore class.notFound
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            $quote = $model->getQuote();
        }
        if (empty($quote) || !$quote->getCustomerId()) {
            return false;
        }
        $customerId = $quote->getCustomerId();
        $totalOrders = (int)$this->orderCount->get($customerId, $this->getAttributeName());
        return $totalOrders <= 0;
    }
}
