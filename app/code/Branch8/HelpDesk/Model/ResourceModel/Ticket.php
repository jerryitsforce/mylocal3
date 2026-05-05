<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel;

use Branch8\HelpDesk\Model\Ticket\Status;
use Magento\Store\Model\Store;

/**
 * Ticket Model
 */
class Ticket extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;


    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Store\Model\StoreManagerInterface        $storeManager,
        string                                            $connectionName = null
    )
    {
        parent::__construct($context, $connectionName);
        $this->_storeManager = $storeManager;
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_helpdesk_ticket', 'ticket_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this|Ticket
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $object->setData('frontend_status', Status::frontendStatusResolve(
            $object->getStatus()
        ));
        if (is_array($object->getData('order'))) {
            $object->setData('order', implode(',', $object->getData('order')));
        }
        parent::_beforeSave($object);
        return $this;
    }
}
