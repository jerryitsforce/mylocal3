<?php

namespace Branch8\SalesRule\Plugin\Magento\AdvancedSalesRule\Model\Rule\Condition;

use Branch8\FlagshipStore\Helper\Sales;
use Magento\Backend\Helper\Data;
use Magento\Framework\View\Asset\Repository;

class ProductPlugin
{
    private $operatorMapping = [
        'seller' => ['==', '!=','()', '!()'],
        'brand' => ['==', '!=','()', '!()'],
        'flagship_store' => ['==', '!=', '()', '!()'],
        'children::brand' => ['==', '!=','{}', '!{}','()', '!()'],
        'children::children' => ['{}', '!{}','()', '!()'],
    ];
    private $extraAttributes = [
        'seller' => ['elementChooserUrl' => 'sales_rule/promo_widget/chooser/attribute/seller', 'label' => 'Seller Code'],
        'brand' => ['elementChooserUrl' => 'sales_rule/promo_widget/chooser/attribute/brand'],
        'flagship_store' => ['elementChooserUrl' => 'sales_rule/promo_widget/chooser/attribute/flagship_store', 'label' => 'Flagship Store'],

    ];
    private $assetRepo;
    /**
     * @var \Magento\Backend\Helper\Data
     */
    private \Magento\Backend\Helper\Data $backendHelper;

    /**
     * @var Sales
     */
    private Sales $flagshipSalesHelper;

    /**
     * @param Repository $assetRepo
     * @param Data $backendHelper
     * @param Sales $flagshipSalesHelper
     */
    public function __construct(
        \Magento\Framework\View\Asset\Repository $assetRepo,
        \Magento\Backend\Helper\Data             $backendHelper,
        Sales                                    $flagshipSalesHelper
    )
    {
        $this->backendHelper = $backendHelper;
        $this->assetRepo = $assetRepo;
        $this->flagshipSalesHelper = $flagshipSalesHelper;
    }

    /**
     * @param $subject
     * @param $result
     * @return void
     */
    public function afterLoadAttributeOptions(\Magento\SalesRule\Model\Rule\Condition\Product $subject, $result)
    {
        $attributes = $subject->getAttributeOption();
        foreach ($this->extraAttributes as $attribute => $config) {
            if (isset($config['label'])) {
                $attributes[$attribute] = __($config['label']);
            }

        }
        asort($attributes);
        $subject->setAttributeOption($attributes);
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed
     */
    public function afterGetDefaultOperatorInputByType($subject, $defaultOperatorInputByType)
    {
        foreach ($this->operatorMapping as $key => $operators) {
            $defaultOperatorInputByType[$key] = $operators;
        }
        return $defaultOperatorInputByType;
    }

    /**
     * @param $subject
     * @param $result
     * @return string
     */
    public function afterGetInputType($subject, $result)
    {
        $attributesCodes = array_keys($this->extraAttributes);
        if (in_array($subject->getAttributeObject()->getAttributeCode(), $attributesCodes)) {
            return $subject->getAttributeObject()->getAttributeCode();
        }
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return mixed|true
     */
    public function afterGetValueElementChooserUrl($subject, $result)
    {
        $attribute = $subject->getAttribute();
        if (in_array($attribute, array_keys($this->extraAttributes))) {
            $url = $this->extraAttributes[$attribute]['elementChooserUrl'];
            if ($subject->getJsFormObject()) {
                $url .= '/form/' . $subject->getJsFormObject();
            }
            return $this->backendHelper->getUrl($url);
        }
        return $result;
    }

    /**
     * @return string
     */
    public function afterGetValueAfterElementHtml($subject, $result)
    {
        $attribute = $subject->getAttribute();
        if (in_array($attribute, array_keys($this->extraAttributes))) {
            $image = $this->assetRepo->getUrl('images/rule_chooser_trigger.gif');
            $html = '<a href="javascript:void(0)" class="rule-chooser-trigger"><img src="' .
                $image .
                '" alt="" class="v-middle rule-chooser-trigger" title="' .
                __(
                    'Open Chooser'
                ) . '" /></a>';
            return $html;
        }
        return $result;
    }

    /**
     * @param $subject
     * @param $result
     * @return bool
     */
    public function afterGetExplicitApply($subject, $result)
    {
        $attribute = $subject->getAttribute();
        if (in_array($attribute, array_keys($this->extraAttributes))) {
            return true;
        }
        return $result;
    }

    /**
     * @param \Magento\Rule\Model\Condition\Product\AbstractProduct $subject
     * @param $process
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool|mixed
     */
    public function aroundValidate(
        \Magento\Rule\Model\Condition\Product\AbstractProduct $subject,
                                                              $process,
        \Magento\Framework\Model\AbstractModel                $object
    )
    {
        $attrCode = $subject->getAttribute();
        if ($attrCode == 'seller') {
            $quoteItemSellerCode = '';
            if ($object instanceof \Magento\Quote\Api\Data\CartItemInterface
                && $object->getQuote()
            ) {
                $quoteItemSellerCode = $object->getData('seller_code');
            }
            return $subject->validateAttribute($quoteItemSellerCode);
        } elseif ($attrCode == 'flagship_store') {
            $flagshipStoreId = 'flagship';
            if ($object instanceof \Magento\Quote\Api\Data\CartItemInterface
                && $object->getQuote()
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
                    $flagshipStoreId = 'flagship';
                }
            }
            return $subject->validateAttribute($flagshipStoreId);
        } else {
            return $process($object);
        }
    }

    public function afterGetValueElementType($subject, $result)
    {
        $attribute = $subject->getAttribute();
        if (in_array($attribute, array_keys($this->extraAttributes))) {
            return 'text';
        }
        return $result;
    }
}
