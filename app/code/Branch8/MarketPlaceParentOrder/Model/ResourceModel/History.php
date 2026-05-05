<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Status\History\Validator;

class History extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    private Validator $validator;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Validator $validator
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Validator                                         $validator,
        string                                            $connectionName = null
    )
    {
        $this->validator = $validator;
        parent::__construct($context, $connectionName);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_parent_order_status_history', 'entity_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this|History
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_beforeSave($object);
        $warnings = $this->validator->validate($object);
        if (!empty($warnings)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __("Cannot save comment:\n%1", implode("\n", $warnings))
            );
        }
        return $this;
    }
}
