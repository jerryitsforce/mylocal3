<?php

declare(strict_types=1);

namespace Branch8\Report\Helper;

use Branch8\Report\Model\ResourceModel\GetOptionVariations;
use Branch8\Report\Model\Source\UserType;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\SellerSubAccount\Helper\Data as SellerSubAccountHelper;

class Data extends AbstractHelper
{
    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var MarketplaceHelper
     */
    private MarketplaceHelper $marketplaceHelper;

    /**
     * @var SellerSubAccountHelper
     */
    private SellerSubAccountHelper $sellerSubAccountHelper;

    /**
     * @var GetOptionVariations
     */
    private GetOptionVariations $getOptionVariations;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * @var ProductLinkRepositoryInterface
     */
    private ProductLinkRepositoryInterface $productLinkRepository;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param SerializerInterface $serializer
     * @param UserContextInterface $userContext
     * @param AuthSession $authSession
     * @param MarketplaceHelper $marketplaceHelper
     * @param SellerSubAccountHelper $sellerSubAccountHelper
     * @param GetOptionVariations $getOptionVariations
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     * @param ProductLinkRepositoryInterface $productLinkRepository
     */
    public function __construct(
        Context                        $context,
        SerializerInterface            $serializer,
        UserContextInterface           $userContext,
        AuthSession                    $authSession,
        MarketplaceHelper              $marketplaceHelper,
        SellerSubAccountHelper         $sellerSubAccountHelper,
        GetOptionVariations            $getOptionVariations,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        AttributeRepositoryInterface   $attributeRepository,
        ProductLinkRepositoryInterface $productLinkRepository
    ) {
        parent::__construct($context);
        $this->serializer = $serializer;
        $this->userContext = $userContext;
        $this->authSession = $authSession;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->sellerSubAccountHelper = $sellerSubAccountHelper;
        $this->getOptionVariations = $getOptionVariations;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository = $attributeRepository;
        $this->productLinkRepository = $productLinkRepository;
    }

    /**
     * Retrieve request object.
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return parent::_getRequest();
    }

    /**
     * Adjust product data to string.
     *
     * @param array $productData
     *
     * @return string
     */
    public function adjustProductData(array $productData): string
    {
        $optionsData = [];
        if (isset($productData['quantity_and_stock_status']) && is_array($productData['quantity_and_stock_status'])) {
            if (isset($productData['quantity_and_stock_status']['is_in_stock'])) {
                $productData['quantity_and_stock_status']['is_in_stock'] = (bool)$productData['quantity_and_stock_status']['is_in_stock'];
            }
            if (!isset($productData['quantity_and_stock_status']['qty'])) {
                $productData['quantity_and_stock_status']['qty'] = 0;
            }
        }
        if (isset($productData['options'])) {
            $optionVariations = $productData['option_variations'] ?? false;
            $loadVariations = false;
            if (false === $optionVariations) {
                $loadVariations = true;
                $optionVariations = [];
            } elseif (in_array('__EMPTY__', $optionVariations, true)) {
                $optionVariations = [];
            }
            foreach ($productData['options'] as $option) {
                $optionData = [
                    'is_delete' => '',
                    'previous_type' => $option->getType(),
                    'previous_group' => $option->getGroupByType(),
                    'sort_order' => $option->getSortOrder(),
                    'title' => $option->getTitle(),
                    'type' => $option->getType(),
                    'is_require' => $option->getIsRequire(),
                    'values' => [],
                ];

                if ($loadVariations) {
                    $rowId = (int)$option->getProductId();
                    $variations = $this->getOptionVariations->execute($rowId);
                    $optionVariations = array_merge($optionVariations, $variations);
                }

                $values = $option->getValues() ?: $option->getData('values');

                if ($values) {
                    foreach ($values as $value) {
                        $isObject = is_object($value);
                        $optionData['values'][] = [
                            'sort_order' => $isObject ? $value->getSortOrder() : ($value['sort_order'] ?? ''),
                            'title' => $isObject ? $value->getTitle() : ($value['title'] ?? ''),
                            'price' => $isObject ? $value->getPrice() : ($value['price'] ?? '0'),
                            'price_type' => $isObject ? $value->getPriceType() : ($value['price_type'] ?? ''),
                            'sku' => $isObject ? $value->getSku() : ($value['sku'] ?? ''),
                            'is_bought' => $isObject ? ($value->getData('is_bought') ?? '0') : ($value['is_bought'] ?? '0'),
                            'is_visible' => $isObject ? ($value->getData('is_visible') ?? '1') : ($value['is_visible'] ?? '1'),
                        ];
                    }
                }

                $optionsData[] = $optionData;
            }

            $productData['options'] = $optionsData;
            $productData['wk_manage_variation'] = $optionVariations;
        }
        unset($productData['option_variations']);

        if (isset($productData['media_gallery']['images'])) {
            foreach ($productData['media_gallery']['images'] as &$image) {
                unset($image['types'], $image['content']);
            }
            unset($productData['media_gallery']['values']);
        }

        $this->processImageBase64($productData, ['note', 'specification']);

        foreach ($this->getExcludedProductKeys() as $key) {
            unset($productData[$key]);
        }

        return (string)$this->getSerializer()->serialize($productData);
    }

    /**
     * Filters and returns the common key-value pairs from two input arrays.
     *
     * @param array $arrayA
     * @param array $arrayB
     *
     * @return array
     */
    public function getCommonOrderedSubset(array $arrayA, array $arrayB): array
    {
        if (empty($arrayA) || empty($arrayB)) {
            return [$arrayA, $arrayB];
        }
        $commonKeys = array_values(array_intersect(array_keys($arrayA), array_keys($arrayB)));

        $filteredA = [];
        $filteredB = [];

        foreach ($commonKeys as $key) {
            $filteredA[$key] = $arrayA[$key];
            $filteredB[$key] = $arrayB[$key];
        }

        $requiredKeys = $this->getProductAttributes();
        foreach ($requiredKeys as $requiredKey) {
            if (!in_array($requiredKey, $commonKeys, true)) {
                $filteredA[$requiredKey] = array_key_exists($requiredKey, $arrayA) ? $arrayA[$requiredKey] : null;
                $filteredB[$requiredKey] = array_key_exists($requiredKey, $arrayB) ? $arrayB[$requiredKey] : null;
            }
        }

        return [$filteredA, $filteredB];
    }

    /**
     * Process HTML content to extract base64-encoded images.
     *
     * @param array $data
     * @param array $targetKeys
     *
     * @return array
     */
    public function processImageBase64(array &$data, array $targetKeys = []): array
    {
        foreach ($data as $key => $value) {
            if (!in_array($key, $targetKeys) || empty($value)) {
                continue;
            }
            preg_match_all('/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"/i', $value, $matches);
            foreach ($matches[0] as $index => $imgTag) {
                $extension = $matches[1][$index];
                $filename = "{$key}_base64_decoded_image_" . $index . '.' . $extension;
                $newTag = preg_replace(
                    '/src="[^"]+"/',
                    'src="/path/to/' . $filename . '"',
                    $imgTag
                );

                $value = str_replace($imgTag, $newTag, $value);
            }

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Returns an array of keys to be excluded product key.
     *
     * @return string[]
     */
    private function getExcludedProductKeys(): array
    {
        return [
            '_edit_mode',
            'extension_attributes',
            'related_products',
            'up_sell_products',
            'cross_sell_products',
            '_cache_instance_product_set_attributes',
        ];
    }

    /**
     * Convert product object to array.
     *
     * @param ProductInterface $product
     * @param array $productLinks
     *
     * @return array
     */
    public function prepareProductLinks(ProductInterface $product, array $productLinks = []): array
    {
        $related = $crosssell = $upsell = [];
        if (empty($productLinks)) {
            $productLinks = $this->productLinkRepository->getList($product);
        }
        foreach ($productLinks as $productLink) {
            switch ($productLink->getLinkType()) {
                case 'related':
                    $related[] = $productLink->getLinkedProductSku();
                    break;
                case 'upsell':
                    $upsell[] = $productLink->getLinkedProductSku();
                    break;
                case 'crosssell':
                    $crosssell[] = $productLink->getLinkedProductSku();
                    break;
                default:
                    break;
            }
        }
        return ['related_skus' => $related, 'upsell_skus' => $upsell, 'crosssell_skus' => $crosssell];
    }

    /**
     * Retrieve all attributes for product entity.
     *
     * @return array
     */
    public function getProductAttributes(): array
    {
        $attributes = [];
        $exclAttributes = ['gallery', 'custom_design', 'custom_design_from', 'custom_design_to', 'page_layout',
            'custom_layout', 'custom_layout_update_file', 'allow_open_amount', 'open_amount_min', 'open_amount_max'];
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('is_visible', true)->create();
        $list = $this->attributeRepository->getList(Product::ENTITY, $searchCriteria)->getItems();
        /** @var AbstractAttribute $attribute */
        foreach ($list as $attribute) {
            $code = $attribute->getAttributeCode();
            if (in_array($code, $exclAttributes)) {
                continue;
            }
            $attributes[] = $code;
        }
        return $attributes;
    }

    /**
     * Retrieve serializer object.
     *
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    /**
     * Retrieve GetOptionVariations object.
     *
     * @return GetOptionVariations
     */
    public function getOptionVariations(): GetOptionVariations
    {
        return $this->getOptionVariations;
    }

    /**
     * Get the ID of the user who updated the data.
     *
     * @return array
     */
    public function getUpdatedByUser(): array
    {
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_ADMIN) {
            $userId = (int)$this->userContext->getUserId();
            if (!empty($userId)) {
                return [UserType::TYPE_ADMIN => $userId];
            }
        }
        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER) {
            $userId = (int)$this->userContext->getUserId();
            if (!empty($userId)) {
                return [UserType::TYPE_SELLER => $userId];
            }
        }

        $user = $this->authSession->getUser();
        if ($user) {
            return [UserType::TYPE_ADMIN => (int)$user->getId()];
        }

        $isPartner = $this->marketplaceHelper->isSeller();
        if ($isPartner == 1) {
            $sellerId = $this->sellerSubAccountHelper->getCustomerId();
            if (!$sellerId) {
                $sellerId = $this->marketplaceHelper->getCustomerId();
            }
            return [UserType::TYPE_SELLER => (int)$sellerId];
        }

        return [UserType::TYPE_SYSTEM => null];
    }

    /**
     * Prepare post data and convert to string.
     *
     * @param array $data
     *
     * @return string
     */
    public function preparePostData(array $data): string
    {
        if (isset($data['custom_layout_update_file'])) {
            unset($data['custom_layout_update_file']);
        }
        if (isset($data['quantity_and_stock_status']['is_in_stock'])) {
            $data['quantity_and_stock_status']['is_in_stock'] = (bool)$data['quantity_and_stock_status']['is_in_stock'];
        }
        if (isset($data['stock_data']) && is_array($data['stock_data'])) {
            $stockData = [];
            $keepKeys = ['qty', 'is_in_stock', 'manage_stock', 'min_qty', 'min_sale_qty',
                'max_sale_qty', 'notify_stock_qty', 'backorders'];
            foreach ($keepKeys as $keepKey) {
                if (!isset($data['stock_data'][$keepKey])) {
                    continue;
                }
                if ($keepKey === 'is_in_stock') {
                    $stockData[$keepKey] = (bool)$data['stock_data'][$keepKey];
                } else {
                    $stockData[$keepKey] = $data['stock_data'][$keepKey];
                }
            }
            $data['stock_data'] = $stockData;
        }
        $this->processImageBase64($data, ['note', 'specification']);
        if (isset($data['allow_customer_groups']) && is_array($data['allow_customer_groups'])) {
            $data['allow_customer_groups'] = implode(',', $data['allow_customer_groups']);
        }

        $linkKeys = ['related', 'upsell', 'crosssell'];
        foreach ($linkKeys as $linkKey) {
            if (isset($data[$linkKey]) && is_array($data[$linkKey])) {
                $linkData = $data[$linkKey];
                $linkSkus = [];
                foreach ($linkData as $link) {
                    if (!isset($link['sku'])) {
                        continue;
                    }
                    $linkSkus[] = $link['sku'];
                }
                unset($data[$linkKey]);
                $data["{$linkKey}_skus"] = $linkSkus;
            }
        }

        return (string)$this->getSerializer()->serialize($data);
    }
}
