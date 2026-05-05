<?php
/**
 *
 */

namespace Branch8\MarketplaceProduct\Controller\Product;

use Branch8\MarketplaceProduct\Model\Validator;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Webkul Marketplace Product Validate  Controller.
 */
class Validate extends Action implements HttpPostActionInterface, HttpGetActionInterface
{
    private \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory;
    private \Magento\Catalog\Model\ProductFactory $productFactory;
    private HelperData $helper;
    private $storeManager = null;
    private Validator $validator;
    private mixed $initializationHelper=null;

    /**
     * @param Context $context
     * @param HelperData $helper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param Validator $validator
     */
    public function __construct(
        Context                                          $context,
        HelperData                                       $helper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ProductFactory            $productFactory,
        Validator                                        $validator
    )
    {
        parent::__construct($context);
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productFactory = $productFactory;
        $this->helper = $helper;
        $this->validator = $validator;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $response = new \Magento\Framework\DataObject();
        $response->setError(false);
        try {
            /* @var $product \Magento\Catalog\Model\Product */
            $productData = $this->getRequest()->getParam('product', []);
            if ($productData && !isset($productData['stock_data']['use_config_manage_stock'])) {
                $productData['stock_data']['use_config_manage_stock'] = 0;
            }
            $storeId = $this->getRequest()->getParam('store', 0);
            $store = $this->getStoreManager()->getStore($storeId);
            $this->getStoreManager()->setCurrentStore($store->getCode());
            $product = $this->productFactory->create();
            $product->setData('_edit_mode', true);
            if ($storeId) {
                $product->setStoreId($storeId);
            }
            $setId = $this->getRequest()->getPost('set') ?: $this->getRequest()->getParam('set');
            if ($setId) {
                $product->setAttributeSetId($setId);
            }
            $typeId = $this->getRequest()->getParam('type');
            if ($typeId) {
                $product->setTypeId($typeId);
            }
            $productId = $this->getRequest()->getParam('id');
            if ($productId) {
                $product->load($productId);
            }
            $resource = $product->getResource();
            $resource->getAttribute('special_from_date')->setMaxValue($product->getSpecialToDate());
            $resource->getAttribute('news_from_date')->setMaxValue($product->getNewsToDate());
            $resource->getAttribute('custom_design_from')->setMaxValue($product->getCustomDesignTo());
            $this->getInitializationHelper()->initializeFromData($product, $productData);
            $this->validator->validate($product, $this->getRequest(), $response);
        } catch (\Magento\Eav\Model\Entity\Attribute\Exception $e) {
            $this->addMessage($response, $e);
        } catch (UrlAlreadyExistsException $e) {
            $this->addMessage($response, $e);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->addMessage($response, $e);
        } catch (\Exception $e) {
            $this->addMessage($response, $e);
        }
        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * @return StoreManagerInterface
     * @deprecated 101.0.0
     */
    private function getStoreManager()
    {
        if (null === $this->storeManager) {
            $this->storeManager = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\Store\Model\StoreManagerInterface::class);
        }
        return $this->storeManager;
    }

    /**
     * @param DataObject $res
     * @param \Exception $e
     * @return DataObject
     */
    private function addMessage(DataObject $response, \Exception $e)
    {
        $response->setError(true);
        $messages = $response->getMessages();
        if (is_array($messages)) {
            $messages = [];
        }
        $error = ['code' => $e->getCode(), 'msg' => $e->getMessage()];
        if ($e instanceof \Magento\Eav\Model\Entity\Attribute\Exception) {
            $error['attribute'] = $e->getAttributeCode();
        }
        $messages[] = $error;
        $response->setMessages($messages);
        return $response;
    }

    /**
     * @return \Branch8\MarketplaceProduct\Model\Product\Initialization\Helper
     * @deprecated 101.0.0
     */
    protected function getInitializationHelper()
    {
        if (null === $this->initializationHelper) {
            $this->initializationHelper = ObjectManager::getInstance()->get(\Branch8\MarketplaceProduct\Model\Product\Initialization\Helper::class);
        }
        return $this->initializationHelper;
    }
}
