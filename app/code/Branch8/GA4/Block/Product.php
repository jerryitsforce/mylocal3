<?php

namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\Event;
use Branch8\GA4\Model\ProductHelper;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\View\Element\Template;

class Product extends \Branch8\GA4\Block\Core
{
    private $registry;
    private \Branch8\HotaiPoint\Helper\Data $hotaiPointHelper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;


    private GroupRepositoryInterface $groupRepository;
    private \Magento\Framework\App\Request\Http $request;


    public function __construct(
        Template\Context            $context,
        Config                      $config,
        \Branch8\GA4\Model\Storage  $storage,
        ProductHelper               $productHelper,
        \Magento\Framework\Registry $registry,
        \Branch8\HotaiPoint\Helper\Data $hotaiPointHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Framework\App\Request\Http $request,
        array                       $data = []
    )
    {
        $this->registry = $registry;
        $this->hotaiPointHelper = $hotaiPointHelper;
        $this->customerSession = $customerSession;
        $this->groupRepository = $groupRepository;
        $this->request = $request;
        parent::__construct($context, $config, $storage, $productHelper, $data);
    }

    /**
     * @return array|null
     */
    public function getGa4DetailProductPush()
    {
        $data = null;
        $product = $this->getCurrentProduct();
        if ($product && $product->getId()) {

            $position = $this->request->getParam('position') !== null ? (int)$this->request->getParam('position') : 0;
            $data = $this->productHelper->getDetailProductPush($product, $position);
            $data['price'] = $this->formatMoney($product->getFinalPrice());
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
            $data['affiliation'] = $this->productHelper->getSellerByProductId($product->getRowId());
            $data['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
            $data['quantity'] = 1;
        }
        return $data;
    }

    public function getSelectItemEventData()
    {
        $eventData = null;
        if ($this->getCurrentProduct()) {

            $request = $this->getRequest();
            $quickView = filter_var($request->getParam('quickview'), FILTER_VALIDATE_BOOLEAN);
            $product = $this->getCurrentProduct();
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $price = $product->getPriceInfo()->getPrice('final_price')->getValue();


            $item = $this->productHelper->getDetailProductPush($product, $request->getParam('position', 0));
            $item['price'] = $this->productHelper->formatMoney($price);
            $item['affiliation'] = $this->productHelper->getSellerByProductId($product->getRowId());
            $item['discount'] = $this->productHelper->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
            $item['quantity'] = 1;

            $eventData = [
                'event' => Event::SELECT_ITEM,
                'type' => $quickView ? Event::SELECT_ITEM_ADD_TO_CART : Event::SELECT_ITEM_PRODUCT_CARD,
                'ecommerce' => [
                    ...$this->getRefererParams(),
                    'items' => [$item]
                ],
            ];

            if ($request->getParam('section')) {
                switch ($request->getParam('section')) {
                    case 'vip':
                        $eventData['vip'] = $this->getCustomerGroupName();
                        break;
                    case 'point':
                        $eventData['hotaiPoints'] = $this->hotaiPointHelper->getHotaiPoint();
                        break;
                }
            }
        }
        return $eventData;
    }

    public function getRefererParams()
    {
        $params = [];
        $request = $this->getRequest();
        $params['item_list_id'] = $request->getParam('item_list_id');
        $params['item_list_name'] = $request->getParam('item_list_name');
        if($request->getParam('promotion_id') || $request->getParam('promotion_name')) {
            $params['promotion_id'] = $request->getParam('promotion_id');
            $params['promotion_name'] = $request->getParam('promotion_name');
        }
        return $params;
    }

    /**
     * @param $customerId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerGroupName()
    {
        if($this->customerSession->getCustomerId()) {
            try {
                $groupEntity = $this->groupRepository->getById($this->customerSession->getCustomerGroupId());
                return $groupEntity->getCode();
            } catch (\Exception $e) {
                return "Guest";
            }

        }
        return "Guest";
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}
