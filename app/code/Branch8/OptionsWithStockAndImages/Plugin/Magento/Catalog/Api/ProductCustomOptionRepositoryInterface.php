<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Api;

use Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterfaceFactory;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\CouldNotSaveException;

class ProductCustomOptionRepositoryInterface
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterfaceFactory
     */
    protected $B8VisibleObject;

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $scopeConfig;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * @var State
     */
    protected State $appState;

    /**
     * Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param B8VisibleInterfaceFactory $b8Visible
     * @param ScopeConfigInterface $scopeConfig
     * @param Salable $salable
     * @param State $appState
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Branch8\OptionsWithStockAndImages\Api\Data\B8VisibleInterfaceFactory $b8Visible,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable,
        State $appState
    ) {
        $this->productRepository = $productRepository;
        $this->B8VisibleObject = $b8Visible;
        $this->scopeConfig = $scopeConfig;
        $this->salable = $salable;
        $this->appState = $appState;
    }

    public function afterGetProductOptions(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        $result,
        $product,
        $requiredOnly = false
    ) {
        foreach($result as $option){
            if($option->getValues()){
                $visibles = [];
                foreach($option->getValues() as $value){
                    $visible = $this->B8VisibleObject->create();
                    $visible->setTitle($value->getTitle());
                    $visible->setIsVisible($value->getIsVisible());
                    $visible->setProductItemId($value->getProductItemId());
                    $visibles[] = $visible;
                }
                $extensionAttributes = $option->getExtensionAttributes();
                $extensionAttributes->setVisible($visibles);
            }
        }
        return $result;
    }

    public function beforeSave(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        \Magento\Catalog\Api\Data\ProductCustomOptionInterface $option
    ) {
        if($this->scopeConfig->getValue('optionsWithStockAndImages/setting/only_enable_dropdown')
            && $option->getType() != 'drop_down'){
            if($option->getType() == 'radio'){
                $option->setType('drop_down');
            } else {
                throw new CouldNotSaveException(__('The Product Custom Option only allow drop_down type.'));
            }
        }
        if(empty(trim($option->getTitle()))){
            throw new CouldNotSaveException(__('The Product Custom Option title is required.'));
        }
        $extensionAttributes = $option->getExtensionAttributes();
        $visibles = $extensionAttributes->getVisible();
        if($visibles && count($visibles)){
            $backedOptions = $option->getValues();
            if($backedOptions){
                $newValues = [];
                $areaCode = $this->appState->getAreaCode();
                foreach($backedOptions as $value){
                    if(empty($value->getTitle()) || empty(trim($value->getTitle()))){
                        throw new CouldNotSaveException(__('The Product Custom Option value title is required.'));
                    }
                    if ($areaCode == Area::AREA_WEBAPI_REST) {
                    if(!$value->getIsBought() && (empty($value->getSku()) || empty(trim($value->getSku())))){
                        throw new CouldNotSaveException(__('Custom Options SKU can not be empty.'));
                    }
                    }
                    foreach($visibles as $visible){
                        if($visible->getTitle() == $value->getTitle()){
                            $value->setIsVisible($visible->getIsVisible());
                            $value->setProductItemId($visible->getProductItemId());
                        }
                    }
                    $newValues[] = $value;
                }
                $option->setValues($newValues);
            }
        }
        return [$option];
    }

    public function afterDuplicate(
        \Magento\Catalog\Api\ProductCustomOptionRepositoryInterface $subject,
        $result,
        $product,
        $duplicate
    ) {
        //Your plugin code
        $this->salable->saveVariationAndSwatchesForDuplicateProduct($product, $duplicate);
        return $result;
    }

}
