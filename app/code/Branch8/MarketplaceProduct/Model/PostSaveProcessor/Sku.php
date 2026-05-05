<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/02/2026
 */

namespace Branch8\MarketplaceProduct\Model\PostSaveProcessor;

use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Math\Random;
use Webkul\Marketplace\Helper\Data as HelperData;

class Sku implements PostSaveProcessorInterface
{
    /**
     * @var ProductResourceModel
     */
    protected $_productResourceModel;
    /**
     * @var mixed|HelperData
     */
    private mixed $helper;

    /**
     * @param ProductResourceModel $_productResourceModel
     * @param HelperData|null $helper
     */
    public function __construct(
        ProductResourceModel $_productResourceModel,
        HelperData           $helper = null,
    )
    {
        $this->helper = $helper ?: ObjectManager::getInstance()->create(HelperData::class);
        $this->_productResourceModel = $_productResourceModel;
    }

    /**
     * @param RequestInterface $request
     * @param $productId
     * @param $wholeData
     * @return array|mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(RequestInterface $request, $productId, &$wholeData = [])
    {
        $skuPrefix = $this->helper->getSkuPrefix();
        if ($this->helper->getSkuType() == 'dynamic' && !$productId) {
            $sku = $skuPrefix . $wholeData['product']['name'];
            $wholeData['product']['sku'] = $this->checkSkuExist($sku);
        }
        return $wholeData;
    }


    /**
     * @param $sku
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function checkSkuExist($sku)
    {
        try {
            $id = $this->_productResourceModel->getIdBySku($sku);
            $availability = $id ? 0 : 1;
        } catch (\Exception $e) {
            $this->helper->logDataInLogger('Controller_Product_Save checkSkuExist : ' . $e->getMessage());
            $availability = 0;
        }
        if ($availability == 0) {
            $sku = $sku . Random::getRandomNumber();
            $sku = $this->checkSkuExist($sku);
        }
        return $sku;
    }
}
