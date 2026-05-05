<?php
declare(strict_types=1);

namespace Branch8\Catalog\Model\Attribute\Backend;

use Exception;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend;
use Magento\Framework\Exception\LocalizedException;

class SalableQuantity extends AbstractBackend
{

    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;

    public function __construct(
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku
    ) {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
    }

    /**
     * Get salable quantity data of product by sku
     *
     * @param Product $object
     * @return string
     * @throws LocalizedException
     */
    public function getSalableQty(Product $object): string
    {
        if($object->getSku()){
            try {
                $salableQty = $this->getSalableQuantityDataBySku->execute($object->getSku());
                if(isset($salableQty[0]['qty'])){
                    return (string)$salableQty[0]['qty'];
                }
            } catch (Exception $e) {
                return '';
            }
        }
        return '';
    }

    public function afterLoad($object)
    {
        if ($object instanceof Product) {
            $object->setData('salable_qty', $this->getSalableQty($object));
        }
        return parent::afterLoad($object);
    }
}
