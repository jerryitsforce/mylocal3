<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Model\AbstractExtensibleModel;

/**
 * Team HelpDesk
 * @method Ticket setName($value)
 * @method string getName()
 */
class Team extends AbstractExtensibleModel
{
    /**
     *
     */
    const ENABLE = '1';
    /**
     *
     */
    const DISABLE = '0';
    /**
     * @var null
     */
    private $teamMembers = null;
    /**
     * @var \Magento\User\Model\ResourceModel\User\CollectionFactory
     */
    private $userCollectionFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory
     * @param ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                         $context,
        \Magento\Framework\Registry                              $registry,
        ExtensionAttributesFactory                               $extensionFactory,
        AttributeValueFactory                                    $customAttributeFactory,
        \Magento\User\Model\ResourceModel\User\CollectionFactory $userCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource  $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb            $resourceCollection = null,
        array                                                    $data = []
    )
    {

        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Branch8\HelpDesk\Model\ResourceModel\Team');
    }

    /**
     * GetTeamMembers
     * @return array|\Magento\User\Model\ResourceModel\User\Collection|null
     */
    public function getTeamMembers()
    {
        if ($this->teamMembers == null) {
            $this->teamMembers = [];
            $connection = $this->getResource()->getConnection();
            $table = $connection->getTableName('branch8_helpdesk_team_user');
            $select = $connection->select();
            $select->from($table,
                ['user_id']
            );
            $select->where('team_id = ?', (int)$this->getId());
            $memberIds = $connection->fetchCol($select);
            if (!$memberIds) {
                return $this->teamMembers;
            }
            $this->teamMembers = $this->userCollectionFactory->create()->addFieldToFilter(
                'user_id',
                ['in' => $memberIds]
            );
        }
        return $this->teamMembers;
    }
}
