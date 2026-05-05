<?php
namespace Branch8\RoleDelegate\Plugin\Amasty\Rolepermissions\Helper;

use Amasty\Rolepermissions\Helper\Data as AmastyRoleHelper;
use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Products;
use Amasty\Rolepermissions\Model\Authorization\GetCurrentUserInterface;
use Amasty\Rolepermissions\Model\RuleFactory;
use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Magento\User\Model\UserFactory;
use Magento\Framework\Registry;
use Psr\Log\LoggerInterface;

class Data
{
    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;

    /**
     * @var RuleFactory
     */
    protected $ruleFactory;

    /**
     * @var DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /**
     * @var UserFactory
     */
    protected $userFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param GetCurrentUserInterface $getCurrentUser
     * @param RuleFactory $ruleFactory
     * @param DelegateRepositoryInterface $delegateRepository
     * @param UserFactory $userFactory
     * @param Registry $registry
     * @param LoggerInterface $logger
     */
    public function __construct(
        GetCurrentUserInterface $getCurrentUser,
        RuleFactory $ruleFactory,
        DelegateRepositoryInterface $delegateRepository,
        UserFactory $userFactory,
        Registry $registry,
        LoggerInterface $logger
    ) {
        $this->getCurrentUser = $getCurrentUser;
        $this->ruleFactory = $ruleFactory;
        $this->delegateRepository = $delegateRepository;
        $this->userFactory = $userFactory;
        $this->registry = $registry;
        $this->logger = $logger;
    }

    /**
     * Combine delegate and current user's role rules
     *
     * @param AmastyRoleHelper $subject
     * @param callable $proceed
     * @return \Amasty\Rolepermissions\Model\Rule
     */
    public function aroundCurrentRule(AmastyRoleHelper $subject, callable $proceed)
    {
        // If already cached, skip recomputing
        $cachedRule = $this->registry->registry('current_amrolepermissions_rule');
        if ($cachedRule) {
            return $cachedRule;
        }

        $rule = $proceed(); // Load the original rule (current user)
        try {
            $currentUser = $this->getCurrentUser->execute();
            if (!$currentUser || !$currentUser->getId()) {
                return $rule;
            }

            // Check if this user is currently a delegate
            $delegation = $this->delegateRepository->getActiveForUser($currentUser->getId());
            if (!$delegation || !$delegation->getId()) {
                return $rule;
            }

            // Load the delegator (original user)
            $originalUser = $this->userFactory->create()->load($delegation->getUserId());
            if (!$originalUser->getId() || !$originalUser->getRole()->getId()) {
                return $rule;
            }

            // Load delegator's Amasty rule
            $delegateRule = $this->ruleFactory->create()->loadByRole($originalUser->getRole()->getId());

            // Combine key rule fields
            $this->mergeRules($rule, $delegateRule);

            // Cache in registry for later reuse
            $this->registry->register('current_amrolepermissions_rule', $rule, true);
        } catch (\Exception $e) {
            $this->logger->error('RoleDelegate: error combining rules - ' . $e->getMessage());
        }

        return $rule;
    }

    /**
     * Merge delegate rule data into current user's rule
     *
     * @param \Amasty\Rolepermissions\Model\Rule $baseRule
     * @param \Amasty\Rolepermissions\Model\Rule $delegateRule
     * @return void
     */
    protected function mergeRules($baseRule, $delegateRule)
    {
        if (!$delegateRule || !$delegateRule->getId()) {
            $baseRule->setCategoryAccessMode(Products::MODE_ANY);
            $baseRule->setProductAccessMode(Products::MODE_ANY);
            $baseRule->setAttributeAccessMode(Products::MODE_ANY);
            $baseRule->setBlockAccessMode(Products::MODE_ANY);
            $baseRule->setSellerAccessMode(Products::MODE_ANY);
            return;
        }

        // Merge category access
        if ($baseRule->getCategoryAccessMode() == Products::MODE_ANY ||
            $delegateRule->getCategoryAccessMode() == Products::MODE_ANY) {
            $baseRule->setCategoryAccessMode(Products::MODE_ANY);
            $mergedCategories = '';
        } else {
            $mergedCategories = array_unique(array_merge(
                (array)$baseRule->getCategories(),
                (array)$delegateRule->getCategories()
            ));
        }
        $baseRule->setCategories($mergedCategories);

        // Merge products
        if ($baseRule->getProductAccessMode() == Products::MODE_ANY ||
            $delegateRule->getProductAccessMode() == Products::MODE_ANY) {
            $baseRule->setProductAccessMode(Products::MODE_ANY);
        }
        $mergedProducts = array_unique(array_merge(
            (array)$baseRule->getProducts(),
            (array)$delegateRule->getProducts()
        ));
        $baseRule->setProducts($mergedProducts);

        // Merge attributes
        if ($baseRule->getAttributeAccessMode() == Products::MODE_ANY ||
            $delegateRule->getAttributeAccessMode() == Products::MODE_ANY) {
            $baseRule->setAttributeAccessMode(Products::MODE_ANY);
        }
        $mergedAttributes = array_unique(array_merge(
            (array)$baseRule->getAttributes(),
            (array)$delegateRule->getAttributes()
        ));
        $baseRule->setAttributes($mergedAttributes);

        // Merge blocks
        if ($baseRule->getBlockAccessMode() == Products::MODE_ANY ||
            $delegateRule->getBlockAccessMode() == Products::MODE_ANY) {
            $baseRule->setBlockAccessMode(Products::MODE_ANY);
        }
        $mergedBlocks = array_unique(array_merge(
            (array)$baseRule->getBlocks(),
            (array)$delegateRule->getBlocks()
        ));
        $baseRule->setBlocks($mergedBlocks);

        // Merge sellers
        if ($baseRule->getSellerAccessMode() == Products::MODE_ANY ||
            $delegateRule->getSellerAccessMode() == Products::MODE_ANY) {
            $baseRule->setSellerAccessMode(Products::MODE_ANY);
        }
        $mergedSellers = array_unique(array_merge(
            (array)$baseRule->getSellers(),
            (array)$delegateRule->getSellers()
        ));
        $baseRule->setSellers($mergedSellers);

        // Merge any other relevant flags (example)
        $baseRule->setLimitOrders($baseRule->getLimitOrders() && $delegateRule->getLimitOrders());
        $baseRule->setLimitInvoices($baseRule->getLimitInvoices() && $delegateRule->getLimitInvoices());
    }
}
