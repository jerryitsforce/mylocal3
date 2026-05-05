<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MaskCustomerInformation\Ui\Listing\Columns;

use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

/**
 * Class Options
 */
class CustomerGroupOptions implements OptionSourceInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;
    private \Branch8\MaskCustomerInformation\Model\AdminPermission $permission;
    private MaskRulesComposite $ruleManagement;

    /**
     * @param CollectionFactory $collectionFactory
     * @param \Branch8\MaskCustomerInformation\Model\AdminPermission $permission
     * @param MaskRulesComposite $maskRulesComposite
     */
    public function __construct(
        CollectionFactory                                      $collectionFactory,
        \Branch8\MaskCustomerInformation\Model\AdminPermission $permission,
        MaskRulesComposite                                     $maskRulesComposite
    )
    {
        $this->ruleManagement = $maskRulesComposite;
        $this->permission = $permission;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options === null) {
            $this->options = $this->collectionFactory->create()->toOptionArray();
        }
        $canView = $this->permission->canView();
        array_walk(
            $this->options,
            function (&$item, $key, $canView) {
                $item['__disableTmpl'] = true;
                if (!$canView) {
                    $item['label'] = $this->ruleManagement->mask('customer_group', $item['label']);
                }
            }, $canView);
        return $this->options;
    }
}
