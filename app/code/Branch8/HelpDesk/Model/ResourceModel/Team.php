<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel;
/**
 * Team Model
 */
class Team extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init('branch8_helpdesk_team', 'team_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this|Team
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        $teamId = 'team_id';
        $old = $this->findAllMembers((int)$object->getId());
        $members = (array)$object->getMembers();
        $table = $this->getTable('branch8_helpdesk_team_user');
        $delete = array_diff($old, $members);
        if ($delete) {
            $where = [
                $teamId . ' = ?' => (int)$object->getData($teamId),
                'user_id IN (?)' => $delete,
            ];
            $this->getConnection()->delete($table, $where);
        }
        $insert = array_diff($members, $old);
        if ($insert) {
            $data = [];
            foreach ($insert as $userId) {
                $data[] = [
                    $teamId => (int)$object->getData($teamId),
                    'user_id' => (int)$userId
                ];
            }
            $this->getConnection()->insertMultiple($table, $data);
        }
        return $this;
    }

    /**
     * Find All Team Member ids
     * @param $teamId
     * @return array
     */
    public function findAllMembers($teamId)
    {
        $connection = $this->getConnection();
        $linkField = 'team_id';
        $select = $connection->select()
            ->from(['bhtu' => $this->getTable('branch8_helpdesk_team_user')], 'user_id')
            ->where('bhtu.' . $linkField . ' = :team_id');

        return $connection->fetchCol($select, ['team_id' => (int)$teamId]);
    }

}
