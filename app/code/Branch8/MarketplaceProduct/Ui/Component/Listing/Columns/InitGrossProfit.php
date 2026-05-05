<?php

namespace Branch8\MarketplaceProduct\Ui\Component\Listing\Columns;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Security\Model\AdminSessionsManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class InitGrossProfit extends Column
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
            $initGrossProfit = $item['init_gross_profit'];
            $item[$fieldName] = !$initGrossProfit ? $initGrossProfit : round($initGrossProfit,2);
        }
        return $dataSource;
    }

}
