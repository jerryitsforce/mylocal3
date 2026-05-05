<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Observer\Admin\Rule;

use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Attributes;
use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Categories;
use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Products;
use Amasty\Rolepermissions\Exception\AccessModeValidationException;
use Amasty\Rolepermissions\Helper\Data as Helper;
use Amasty\Rolepermissions\Model\RuleFactory;
use Amasty\Rolepermissions\Model\Validation\AccessMode\ValidatorInterface as AccessModeValidatorInterface;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Amasty\Rolepermissions\Api\Data\RuleInterface;

class SaveObserver implements ObserverInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AccessModeValidatorInterface
     */
    private $accessModeValidator;

    /**
     * @var MessageManagerInterface
     */
    private $messageManager;

    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        RuleFactory $ruleFactory,
        Registry $registry,
        StoreManagerInterface $storeManager,
        AccessModeValidatorInterface $accessModeValidator,
        MessageManagerInterface $messageManager,
        Helper $helper
    ) {
        $this->coreRegistry = $registry;
        $this->ruleFactory = $ruleFactory;
        $this->storeManager = $storeManager;
        $this->accessModeValidator = $accessModeValidator;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * @throws Exception
     */
    public function execute(Observer $observer): void
    {
        $role = $this->coreRegistry->registry('current_role');

        if (!$role->getId()) {
            return;
        }
        $request = $observer->getRequest();
        $data = $request->getParam('amrolepermissions');

        if (!$data) {
            return;
        }
        /** @var  \Amasty\Rolepermissions\Model\Rule $rule */
        $rule = $this->ruleFactory->create();
        $rule = $rule->load($role->getId(), 'role_id');

        $rule->setScopeWebsites([])
            ->setScopeStoreviews([]);

        $data['role_id'] = $role->getId();

        try {
            if ($currentRule = $this->helper->currentRule()) {
                $this->accessModeValidator->validate($data, $currentRule);
            }
        } catch (AccessModeValidationException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());

            return;
        }

        if (isset($data[RuleInterface::PRODUCT_ACCESS_MODE])) {
            switch ($data[RuleInterface::PRODUCT_ACCESS_MODE]) {
                case Products::MODE_ANY:
                case Products::MODE_MY:
                case Products::MODE_SCOPE:
                    $data['products'] = [];
                    break;
                case Products::MODE_SELECTED:
                    $data['products'] = explode('&', $data['products']);
                    break;
            }
        }

        if (isset($data[RuleInterface::ATTRIBUTE_ACCESS_MODE])) {
            switch ($data[RuleInterface::ATTRIBUTE_ACCESS_MODE]) {
                case Attributes::MODE_ANY:
                    $data['attributes'] = [];
                    break;
                case Attributes::MODE_SELECTED:
                    $data['attributes'] = explode('&', $data['attributes']);
                    break;
            }
        }

        if (isset($data[RuleInterface::CATEGORY_ACCESS_MODE])) {
            switch ($data[RuleInterface::CATEGORY_ACCESS_MODE]) {
                case Categories::MODE_ALL:
                    $data['categories'] = [];
                    break;
                case Categories::MODE_SELECTED:
                    $data['categories'] = $this->getFormattedCategories($data);
                    break;
            }
        }

        if (isset($data[RuleInterface::ROLE_ACCESS_MODE])) {
            switch ($data[RuleInterface::ROLE_ACCESS_MODE]) {
                case Attributes::MODE_ANY:
                    $data['roles'] = [];
                    break;
                case Attributes::MODE_SELECTED:
                    $data['roles'] = explode('&', $data['roles']);
                    break;
            }
        }

        if (isset($data['seller_access_mode'])) {
            switch ($data['seller_access_mode']) {
                case Attributes::MODE_ANY:
                    $data['sellers'] = [];
                    break;
                case Attributes::MODE_SELECTED:
                    $data['sellers'] = explode('&', $data['sellers']);
                    break;
            }
        }

        if (isset($data['block_access_mode'])) {
            switch ($data['block_access_mode']) {
                case Attributes::MODE_ANY:
                    $data['blocks'] = [];
                    break;
                case Attributes::MODE_SELECTED:
                    $data['blocks'] = explode('&', $data['blocks']);
                    break;
            }
        }

        $rule->addData($data);

        $rule->save();
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getFormattedCategories($data)
    {
        $categories = explode(',', str_replace(' ', '', $data['categories']));
        //$rootCategories = $this->getRootCategories($data);
        $categories = array_values(
            array_unique(
                $categories
            )
        );

        return array_filter($categories);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getRootCategories($data)
    {
        $rootCategories = [];
        $allStores = $this->storeManager->getStores();

        switch (true) {
            case isset($data['scope_storeviews']):
                foreach ($data['scope_storeviews'] as $storeId) {
                    array_push($rootCategories, $allStores[$storeId]->getRootCategoryId());
                }
                break;
            case isset($data['scope_websites']):
                foreach ($allStores as $store) {
                    if (in_array($store->getWebsiteId(), $data['scope_websites'])) {
                        array_push($rootCategories, $store->getRootCategoryId());
                    }
                }
                break;
            default:
                foreach ($allStores as $store) {
                    array_push($rootCategories, $store->getRootCategoryId());
                }
        }
        $rootCategories = array_unique($rootCategories);

        return $rootCategories;
    }
}
