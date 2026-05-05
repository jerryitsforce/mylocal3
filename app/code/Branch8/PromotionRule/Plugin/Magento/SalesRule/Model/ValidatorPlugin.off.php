<?php

namespace Branch8\PromotionRule\Plugin\Magento\SalesRule\Model;

use Branch8\PromotionRule\Model\Actions\GetSellerByProductId;
use Laminas\Validator\ValidatorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Helper\CartFixedDiscount;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RulesApplier;
use Magento\SalesRule\Model\Utility;
use Magento\SalesRule\Model\ValidateCouponCode;
use Magento\Store\Model\StoreManagerInterface;

class ValidatorPlugin extends \Magento\SalesRule\Model\Validator
{
    private GetSellerByProductId $getSellerByProductId;

    private $ruleToSellersBorne = [];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param Utility $utility
     * @param RulesApplier $rulesApplier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\SalesRule\Model\Validator\Pool $validators
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param GetSellerByProductId $getSellerByProductId
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     * @param CartFixedDiscount|null $cartFixedDiscount
     * @param StoreManagerInterface|null $storeManager
     * @param ValidateCouponCode|null $validateCouponCode
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        CollectionFactory                                       $collectionFactory,
        \Magento\Catalog\Helper\Data                            $catalogData,
        \Magento\SalesRule\Model\Utility                        $utility,
        \Magento\SalesRule\Model\RulesApplier                   $rulesApplier,
        \Magento\Framework\Pricing\PriceCurrencyInterface       $priceCurrency,
        \Magento\SalesRule\Model\Validator\Pool                 $validators,
        \Magento\Framework\Message\ManagerInterface             $messageManager,
        GetSellerByProductId                                    $getSellerByProductId,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        ?CartFixedDiscount                                      $cartFixedDiscount = null,
        StoreManagerInterface                                   $storeManager = null,
        ValidateCouponCode                                      $validateCouponCode = null
    )
    {
        $this->_collectionFactory = $collectionFactory;
        $this->_catalogData = $catalogData;
        $this->validatorUtility = $utility;
        $this->rulesApplier = $rulesApplier;
        $this->priceCurrency = $priceCurrency;
        $this->validators = $validators;
        $this->messageManager = $messageManager;
        $this->cartFixedDiscountHelper = $cartFixedDiscount ?:
            ObjectManager::getInstance()->get(CartFixedDiscount::class);
        $this->storeManager = $storeManager ?:
            ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->validateCouponCode = $validateCouponCode ?:
            ObjectManager::getInstance()->get(ValidateCouponCode::class);
        parent::__construct(
            $context,
            $registry,
            $collectionFactory,
            $catalogData,
            $utility,
            $rulesApplier,
            $priceCurrency,
            $validators,
            $messageManager,
            $resource,
            $resourceCollection,
            $data,
            $cartFixedDiscount,
            $storeManager,
            $validateCouponCode
        );
        $this->getSellerByProductId = $getSellerByProductId;
    }


    /**
     * @param Rule $rule
     * @return mixed|string[]|null
     */
    private function getSellerRuleBornesByRule(Rule $rule)
    {
        if (isset($this->ruleToSellersBorne[$rule->getId()])) {
            return $this->ruleToSellersBorne[$rule->getId()];
        }
        $sellerIds = $rule->getData('seller_ids');
        if (is_string($sellerIds)) {
            $sellerIds = explode(',', trim($sellerIds));
        }
        $this->ruleToSellersBorne[$rule->getId()] = $sellerIds;
        return $this->ruleToSellersBorne[$rule->getId()];
    }

    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param callable $process
     * @param AbstractItem $item
     * @param Rule $rule
     * @return \Magento\SalesRule\Model\Validator
     */
    public function aroundProcess(\Magento\SalesRule\Model\Validator $subject, callable $process, AbstractItem $item, Rule $rule)
    {
        $isSellerBoneRule = (bool)$rule->getData('seller_borne_discount');
        $sellerIds = $this->getSellerRuleBornesByRule($rule);
        $productSellerId = $this->getSellerByProductId->get((int)$item->getProductId());
        if (!$isSellerBoneRule) {
            return $process($item, $rule);
        }
        if (empty($sellerIds)) {
            return $process($item, $rule);
        }
        // if product seller not belong to the seller list ,discount not apply for this item
        if (!in_array($productSellerId, $sellerIds)) {
            return $subject;
        }

        return $process($item, $rule);
    }

    /**
     * @param \Magento\SalesRule\Model\Validator $subject
     * @param callable $process
     * @param $items
     * @param Address $address
     * @return \Magento\SalesRule\Model\Validator
     * @throws \Zend_Db_Select_Exception
     */
    public function aroundInitTotals(\Magento\SalesRule\Model\Validator $subject, callable $process, $items, Address $address)
    {
        if (!$items) {
            return $subject;
        }

        /** @var Rule $rule */
        foreach ($subject->getRules($address) as $rule) {
            if (Rule::CART_FIXED_ACTION !== $rule->getSimpleAction()
                || !$this->validatorUtility->canProcessRule($rule, $address)
            ) {
                continue;
            }
            $ruleTotalItemsPrice = 0;
            $ruleTotalBaseItemsPrice = 0;
            $ruleTotalItemsDiscountAmount = 0;
            $ruleTotalBaseItemsDiscountAmount = 0;
            $validItemsCount = 0;

            /** @var Quote\Item $item */
            foreach ($items as $item) {
                if (!$this->isValidItemForRule($item, $rule)) {
                    continue;
                }
                $qty = $subject->validatorUtility->getItemQty($item, $rule);
                $ruleTotalItemsPrice += $subject->getItemPrice($item) * $qty;
                $ruleTotalBaseItemsPrice += $subject->getItemBasePrice($item) * $qty;
                $ruleTotalItemsDiscountAmount += $item->getDiscountAmount();
                $ruleTotalBaseItemsDiscountAmount += $item->getBaseDiscountAmount();
                $validItemsCount++;
            }

            $this->_rulesItemTotals[$rule->getId()] = [
                'items_price' => $ruleTotalItemsPrice,
                'items_discount_amount' => $ruleTotalItemsDiscountAmount,
                'base_items_price' => $ruleTotalBaseItemsPrice,
                'base_items_discount_amount' => $ruleTotalBaseItemsDiscountAmount,
                'items_count' => $validItemsCount,
            ];
        }
        $subject->setRulesItemTotals($this->_rulesItemTotals);
        return $subject;
    }

    /**
     * @param AbstractItem $item
     * @param Rule $rule
     * @return bool
     */
    private function isValidItemForRule(AbstractItem $item, Rule $rule)
    {
        $isSellerBoneRule = (bool)$rule->getData('seller_borne_discount');
        $sellerIds = $this->getSellerRuleBornesByRule($rule);
        $productSellerId = $this->getSellerByProductId->get((int)$item->getProductId());
        /**
         *
         */
        if ($isSellerBoneRule &&
            $sellerIds &&
            !in_array($productSellerId, $sellerIds)
        ) {// Ignore items have seller not in Seller-Born list Config
            return false;
        }

        if ($item->getParentItem() && $item->getParentItem()->getProductType() === 'configurable'
            || (($item->getHasChildren() || $item->getChildren()) && $item->isChildrenCalculated())
            || $item->getNoDiscount()
        ) {
            return false;
        }

        if (!$rule->getActions()->validate($item)) {
            return false;
        }
        if (!$this->canApplyDiscount($item)) {
            return false;
        }
        return true;
    }
}
