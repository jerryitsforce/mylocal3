<?php

namespace Branch8\FlagshipStore\Plugin\SalesRule\Model\Condition;

class FlagshipStore
{
    const FLAGSHIP_ATTR = 'flagship_store';

    protected $flagshipCollectionFactory;

    protected $flagshipSalesHelper;

    private $operatorMapping = ['==', '!=','{}', '!{}','()', '!()'];

    public function __construct(
        \Branch8\FlagshipStore\Model\ResourceModel\FlagshipStore\CollectionFactory $flagshipCollectionFactory,
        \Branch8\FlagshipStore\Helper\Sales $flagshipSalesHelper
    ){
        $this->flagshipCollectionFactory = $flagshipCollectionFactory;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
    }


    public function afterLoadAttributeOptions($subject, $result)
    {
        $attributes = $subject->getAttributeOption();
        $attributes[self::FLAGSHIP_ATTR] = 'Flagship Store';
        $result->setAttributeOption($attributes);
        return $result;
    }

    public function afterGetInputType(\Magento\Rule\Model\Condition\Product\AbstractProduct $subject, $result)
    {
        if ($subject->getAttribute() === self::FLAGSHIP_ATTR) {
            return 'multiselect';
        }

        return $result;
    }

    public function afterGetValueSelectOptions(\Magento\Rule\Model\Condition\Product\AbstractProduct $subject, $result)
    {
        if ($subject->getAttribute() !== self::FLAGSHIP_ATTR) {
            return $result;
        }
        $result = [];
        $flagshipColl = $this->flagshipCollectionFactory->create();
        foreach ($flagshipColl as $item) {
            $result[] = ['value' => $item->getId(), 'label' => $item->getStoreName()];
        }
        return $result;
    }

    public function afterGetValueElementType(\Magento\Rule\Model\Condition\Product\AbstractProduct $subject, $result)
    {
        if ($subject->getAttribute() === self::FLAGSHIP_ATTR) {
            return 'multiselect';
        }

        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetDefaultOperatorInputByType($subject, $defaultOperatorInputByType)
    {
        $defaultOperatorInputByType['multiselect'] = $this->operatorMapping;
        return $defaultOperatorInputByType;
    }

    public function afterGetOperatorSelectOptions(
        \Magento\Rule\Model\Condition\Product\AbstractProduct $subject,
                                                              $result
    ) {
        if ($subject->getAttribute() === self::FLAGSHIP_ATTR) {
//            foreach ($result as $key => $item) {
//                if ($item['value'] === '<=>') {
//                    unset($result[$key]);
//                }
//            }
        }

        return $result;
    }

    public function aroundValidate(
        \Magento\Rule\Model\Condition\Product\AbstractProduct $subject,
        $process,
        \Magento\Framework\Model\AbstractModel $object
    ) {
        $attrCode = $subject->getAttribute();

        if($attrCode == self::FLAGSHIP_ATTR){
            if ($object instanceof \Magento\Quote\Api\Data\CartItemInterface
                && $object->getQuote()
                && $object->getQuote()->getItems()
            ) {
                $product = $object->getProduct();
                /**
                 * Get Seller of product
                 */
                $productId = $product->getId();
                $sellerId = $this->flagshipSalesHelper->getSellerIdFromProductId($productId);
                /**
                 * Get Flagship store ID
                 */
                $flagshipStoreId = $this->flagshipSalesHelper->getFlagshipStoreFromSellerId($sellerId);
                if((string)$flagshipStoreId == ''){
                    return false;
                }

            }
            return $subject->validateAttribute($flagshipStoreId);
        }else{
            return $process($object);
        }

        return false;
    }

}
