<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\ViewModel;

use Branch8\MarketplaceProduct\Model\ProductRepository;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\ConfigData;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Thread implements ArgumentInterface
{
    private UrlInterface $url;
    private ProductRepository $productRepository;
    private RequestInterface $request;

    private ConfigData $configData;

    private \Magento\Framework\Registry $registry;

    /**
     * @param UrlInterface $url
     * @param ProductRepository $productRepository
     * @param \Magento\Framework\Registry $registry
     * @param RequestInterface $httpRequest
     * @param ConfigData $configData
     */
    public function __construct(
        UrlInterface                $url,
        ProductRepository           $productRepository,
        \Magento\Framework\Registry $registry,
        RequestInterface            $httpRequest,
        ConfigData                  $configData
    )
    {
        $this->configData = $configData;
        $this->request = $httpRequest;
        $this->productRepository = $productRepository;
        $this->url = $url;
        $this->registry = $registry;
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getConfig()
    {
        return [
            'ajaxSubmitNewQuestion' => $this->url->getUrl('product_discussion/ajax/postThread'),
            'maxLength' => $this->configData->getValue('max_length_input') ?: 200,
            'rateLimit' => $this->configData->getValue('submit_rate_limit') ?: 30,
            'pageSize' => $this->configData->getValue('pageSize', ConfigData::PREFIX_PATH_CUSTOMER) ?: 5,
            'productId' => $this->productRepository->get($this->request->getParam('sku'))->getId(),
            'referer' => $this->request->getParam('referer', 'product'),
        ];
    }

    /**
     * @return array
     */
    public function getConfigForProductPage()
    {
        return [
            'productId' => $this->getProduct()->getId(),
            'url' => $this->url->getUrl('product_discussion/ajax/loadthread'),
            'pageType' => 'product',
            'viewFull' => $this->url->getUrl('product_discussion/thread/list', ['sku' => $this->getProduct()->getSku()]),
            'askForQuestionUrl' => $this->url->getUrl('product_discussion/thread/new',
                ['sku' => $this->getProduct()->getSku(), 'referer' => 'product']
            ),
        ];
    }

    /**
     * @return array
     */
    public function getConfigForListPage()
    {
        return [
            'productId' => $this->getProduct()->getId(),
            'url' => $this->url->getUrl('product_discussion/ajax/loadthread'),
            'hideTotalThreads' => true,
            'showPaging' => true,
            'pageType' => 'list',
            'askForQuestionUrl' => $this->url->getUrl('product_discussion/thread/new',
                ['sku' => $this->getProduct()->getSku(), 'referer' => 'list']
            ),
        ];
    }

    /**
     * @return array
     */
    public function getAskForQuestionConfig()
    {
        return [
            'productId' => $this->getProduct()->getId(),
            'askForQuestionUrl' => $this->url->getUrl('product_discussion/thread/new',
                ['sku' => $this->getProduct()->getSku(), 'referer' => 'product']
            ),
        ];
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    private function getProduct()
    {
        return $this->registry->registry('product');
    }
}
