<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Framework\UrlInterface;
use Branch8\ProductCertification\Helper\Config;
use Branch8\ProductCertification\Model\CertificationTypeRepository;
use Branch8\ProductCertification\Model\ResourceModel\ProductCertification\CollectionFactory as PcCollectionFactory;
use Branch8\ProductCertification\Helper\ImageUploader;

/**
 * Injects the "Product Certification" accordion panel into the product edit form.
 * The panel is rendered by a custom UI component (KnockoutJS) that calls the
 * GetByCategoryAction AJAX endpoint when the product's main category changes.
 */
class ProductCertificationModifier extends AbstractModifier
{
    public const FIELDSET_CODE  = 'branch8_product_certification';
    public const CONTAINER_NAME = 'branch8_certification_container';

    /**
     * @var LocatorInterface
     */
    private LocatorInterface $locator;

    /**
     * @var ArrayManager
     */
    private ArrayManager $arrayManager;

    /**
     * @var UrlInterface
     */
    private UrlInterface $urlBuilder;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var CertificationTypeRepository
     */
    private CertificationTypeRepository $repository;

    /**
     * @var PcCollectionFactory
     */
    private PcCollectionFactory $pcCollectionFactory;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param CertificationTypeRepository $repository
     * @param PcCollectionFactory $pcCollectionFactory
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        UrlInterface $urlBuilder,
        Config $config,
        CertificationTypeRepository $repository,
        PcCollectionFactory $pcCollectionFactory,
        ImageUploader $imageUploader
    ) {
        $this->locator             = $locator;
        $this->arrayManager        = $arrayManager;
        $this->urlBuilder          = $urlBuilder;
        $this->config              = $config;
        $this->repository          = $repository;
        $this->pcCollectionFactory = $pcCollectionFactory;
        $this->imageUploader       = $imageUploader;
    }

    /**
     * @param array $data
     * @return array
     */
    public function modifyData(array $data): array
    {
        if (!$this->config->isEnabled()) {
            return $data;
        }

        $product   = $this->locator->getProduct();
        $productId = (int)$product->getId();
        $rowId     = (int)($product->getData('row_id') ?: $productId);

        // Load certification data
        $existingData = [];
        
        // Priority 1: Check if we have staged data on the product object (for scheduled updates)
        $certPost = $product->getData('branch8_certifications_post');
        if ($certPost) {
            $existingData = is_string($certPost) ? json_decode($certPost, true) : $certPost;
        }

        // Priority 2: Load from database if no staged data and we have a product row_id
        if (empty($existingData) && $rowId) {
            $savedCerts = $this->pcCollectionFactory->create()
                ->addProductFilter($rowId);

            foreach ($savedCerts as $cert) {
                $existingData[$cert->getData('certification_type_id')] = $cert->getData('certification_value');
            }
        }

        $data[$productId]['branch8_certifications'] = $existingData;

        return $data;
    }

    /**
     * @param array $meta
     * @return array
     */
    public function modifyMeta(array $meta): array
    {
        if (!$this->config->isEnabled()) {
            return $meta;
        }

        $meta = array_merge($meta, $this->buildFieldset());

        return $meta;
    }

    /**
     * Build the accordion panel meta definition
     * @return array
     */
    private function buildFieldset(): array
    {
        return [
            self::FIELDSET_CODE => [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label'         => __('Product Certification'),
                            'componentType' => 'fieldset',
                            'dataScope'     => 'data',
                            'collapsible'   => true,
                            'opened'        => false,
                            'sortOrder'     => 900,
                        ],
                    ],
                ],
                'children' => [
                    self::CONTAINER_NAME => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'componentType' => 'container',
                                    'component'     => 'Branch8_ProductCertification/js/components/certification-panel',
                                    'template'      => 'Branch8_ProductCertification/certification-panel',
                                    'dataScope'     => '',
                                    'ajaxUrl'       => $this->urlBuilder->getUrl(
                                        'branch8_certification/ajax/getByCategoryAction'
                                    ),
                                    'hintText'      => __('Please fill in the number or description, for example: energy level can be a number from 1~5; for NCC, fill in: CCAM003G0035T0. If there is no number, it can be left blank, and it will display as "Complies with standards" on the frontend.'),
                                    'noNumberText'  => __('Complies with standards'),
                                ],
                            ],
                        ],
                        'children' => [],
                    ],
                ],
            ],
        ];
    }
}
