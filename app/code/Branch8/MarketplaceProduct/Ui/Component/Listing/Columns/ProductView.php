<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ProductView extends Column
{
    /**
     * @var EncoderInterface
     */
    private EncoderInterface $urlEncoder;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var AdminSessionsManager
     */
    private AdminSessionsManager $adminSessionsManager;

    private $cacheBaseUrl = null;

    /**
     * ProductView constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param EncoderInterface $urlEncoder
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param AdminSessionsManager $adminSessionsManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface           $context,
        UiComponentFactory         $uiComponentFactory,
        EncoderInterface           $urlEncoder,
        StoreManagerInterface      $storeManager,
        ProductRepositoryInterface $productRepository,
        AdminSessionsManager       $adminSessionsManager,
        array                      $components = [],
        array                      $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlEncoder = $urlEncoder;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->adminSessionsManager = $adminSessionsManager;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            $productId = $item['mageproduct_id'] ?? null;
            $visibility = $item['visibility'] ?? null;
            if ($productId && $visibility && (int)$visibility !== 1) {
                $url = $this->getWebsiteUrl((int)$productId);
                $sessionId = $this->urlEncoder->encode(
                    $this->adminSessionsManager->getCurrentSession()->getId()
                );
                $requestPath = $this->_data['config']['requestPath'] ?? '';
                $item[$fieldName] = "<a href='" . $url . $requestPath . '/id/' . $productId . '/SID/' . $sessionId .
                    "/' target='blank' title='" . __('View Product') . "'>" . __('View') . '</a>';
            } else {
                $item[$fieldName] = __('N/A');
            }
        }

        return $dataSource;
    }

    /**
     * Get website URL by product ID.
     *
     * @param int $productId
     *
     * @return string
     */
    private function getWebsiteUrl(int $productId): string
    {
        if (!empty($this->cacheBaseUrl)) {
            return $this->cacheBaseUrl;
        }
        try {
            $website = $this->storeManager->getWebsite(1);
            $stores = $website->getStores();

            if (!empty($stores)) {
                $store = reset($stores);
                $this->cacheBaseUrl = $store->getBaseUrl();
            }
            return $this->cacheBaseUrl;
        } catch (Exception $e) {
            return '';
        }
    }
}
