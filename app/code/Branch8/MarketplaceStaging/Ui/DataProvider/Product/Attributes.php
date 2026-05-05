<?php
/**
 * Webkul Software
 *
 * @category Webkul
 * @package Webkul_Marketplace
 * @author Webkul
 * @copyright Copyright (c)  Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceStaging\Ui\DataProvider\Product;

use Magento\Framework\App\RequestInterface;
use Webkul\Marketplace\Model\ResourceModel\VendorAttributeMapping\CollectionFactory as VendorMappingCollection;
use Webkul\Marketplace\Helper\Data as HelperData;

class Attributes extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $collection;

    /**
     * @var \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler
     */
    private $configurableAttributeHandler;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler
     * @param HelperData $helperData
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeHandler $configurableAttributeHandler,
        HelperData $helperData,
        RequestInterface $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->configurableAttributeHandler = $configurableAttributeHandler;
        $this->helperData = $helperData;
        $this->collection = $configurableAttributeHandler->getApplicableAttributes();
        $this->request = $request;
    }

    /**
     * Returns attribute collection
     *
     * @return void
     */
    public function getCollection()
    {
        return $this->collection;
    }

   /**
    * Return allowed items
    *
    * @return array
    */
    public function getData()
    {
        $items = [];
        /*$selectedAttributes = $this->request->getParam('attribute_selected');
        if ($selectedAttributes) {*/
            $skippedItems = 0;
            foreach ($this->getCollection()->getItems() as $attribute) {
                if ($this->configurableAttributeHandler->isAttributeApplicable($attribute)) {
                   // if (in_array($attribute->getAttributeCode(), $selectedAttributes)) {
                        $items[] = $attribute->toArray();
                    /*} else {
                        $skippedItems++;
                    }*/
                } else {
                    $skippedItems++;
                }

            }

            return [
                'totalRecords' => $this->collection->getSize() - $skippedItems,
                'items' => $items
            ];
        /*}
        return [
            'totalRecords' => 0,
            'items' => $items
        ];*/
    }
}
