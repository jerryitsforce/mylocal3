<?php

namespace Branch8\Catalog\Plugin;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class UpdateValuePreservationNormal
{
    protected AttributeSetRepositoryInterface $attributeSetRepository;

    /**
     * UpdateValuePreservationNormal constructor.
     *
     * @param AttributeSetRepositoryInterface $attributeSetRepository
     */
    public function __construct(
        AttributeSetRepositoryInterface $attributeSetRepository
    ){
        $this->attributeSetRepository = $attributeSetRepository;
    }

    /**
     * From initial, the value of normal label is '', but client want to push 'normal' to API
     * Temporary set '' if the value is 'normal'
     * In the future, if the value is updated to 'normal', disable this plugin
     * @param $subject
     * @param ProductInterface $product
     * @param $saveOptions
     * @return array
     * @throws CouldNotSaveException
     */
    public function beforeSave($subject, ProductInterface $product, $saveOptions = false)
    {
        $preservationStatusAttribute = $product->getCustomAttribute('preservation_status');
        $attributeSetId = $product->getAttributeSetId();
        $productDataToChange = $product->getData();
        if (!empty($productDataToChange['options'])) {
            $attributeSet = null;
            if ($attributeSetId) {
                try {
                    $attributeSet = $this->attributeSetRepository->get($attributeSetId);
                } catch (\Exception $e) {
                }
            }
            if ($attributeSet && in_array($attributeSet->getAttributeSetName(), ['ticket_yoxi', 'ticket_fami', 'ticket_redeem', 'ticket_non_redeem'])) {
                throw new CouldNotSaveException(
                    __('Imported tickets do not support modifying custom options.')
                );
            }
        }
        if(!$preservationStatusAttribute){
            return [$product, $saveOptions];
        }
        $preservationStatusValue = $preservationStatusAttribute->getValue();
        if($preservationStatusValue == 'normal'){
            $product->setCustomAttribute('preservation_status', '');
        }

        return [$product, $saveOptions];
    }

}
