<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel;
/**
 * Attachment Model
 */
class Attachment extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_serializableFields = ['meta' => [null, []]];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

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
        $this->_init('branch8_helpdesk_attachment', 'attachment_id');
    }
}
