<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Team;

use Branch8\HelpDesk\Model\ResourceModel\Team\CollectionFactory;
use Branch8\HelpDesk\Model\ResourceModel\User\GridCollection;
use Branch8\HelpDesk\Model\ResourceModel\User\GridCollectionFactory;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\User\Model\User;

/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\ModifierPoolDataProvider
{
    /**
     * @var \Branch8\HelpDesk\Model\ResourceModel\Category\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;
    private GridCollectionFactory $gridUserCollectionFactory;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $teamCollectionFactory
     * @param GridCollectionFactory $gridUserCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        string                 $name,
        string                 $primaryFieldName,
        string                 $requestFieldName,
        CollectionFactory      $teamCollectionFactory,
        GridCollectionFactory  $gridUserCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array                  $meta = [],
        array                  $data = [],
        PoolInterface          $pool = null
    )
    {
        $this->gridUserCollectionFactory = $gridUserCollectionFactory;
        $this->collection = $teamCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var \Branch8\Category\Model\Category $team */
        foreach ($items as $team) {
            $this->loadedData[$team->getId()] = $team->getData();
            $memberInformation = $this->getTeamMembers(
                $team->getData('assigned_members')
            );
            $this->loadedData[$team->getId()]['members']['assigned_members'] = $memberInformation;
        }
        $data = $this->dataPersistor->get('team');
        if (!empty($data)) {
            $team = $this->collection->getNewEmptyItem();
            $team->setData($data);
            $this->loadedData[$team->getId()] = $team->getData();
            $this->dataPersistor->clear('team');
        }
        return $this->loadedData;
    }

    /**
     * Get Member Information
     * @param $memberIds
     * @return array|array[]
     */
    private function getTeamMembers($memberIds)
    {
        $memberData = [];
        if (!$memberIds) {
            return $memberData;
        }
        /**
         * @var $collection GridCollection
         * @var $user User
         */
        $collection = $this->gridUserCollectionFactory->create();
        $collection->getSelect()->reset('columns')->columns(['user_id', 'firstname', 'lastname', 'email', 'username'])->where('main_table.user_id in (?)', $memberIds);
        $collection->setOrder('user_id', 'ASC');
        foreach ($collection as $user) {
            $memberData[] = [
                'user_id' => $user->getUserId(),
                'name' => $user->getName(),
                'username' => $user->getUserName(),
                'email' => $user->getEmail(),
            ];
        }
        return $memberData;
    }
}
