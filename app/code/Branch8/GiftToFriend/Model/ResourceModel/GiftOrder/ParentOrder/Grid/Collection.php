<?php
declare(strict_types=1);

namespace Branch8\GiftToFriend\Model\ResourceModel\GiftOrder\ParentOrder\Grid;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail;
use Psr\Log\LoggerInterface as Logger;

/**
 * Order grid collection
 */
class Collection extends \Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid\Collection
{
    /**
     * @var TimezoneInterface
     */
    private $timeZone;

    /**
     * Initialize dependencies.
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     * @param TimezoneInterface|null $timeZone
     */
    public function __construct(
        EntityFactory     $entityFactory,
        Logger            $logger,
        FetchStrategy     $fetchStrategy,
        EventManager      $eventManager,
        string            $mainTable = 'sales_parent_order_grid',
        string            $resourceModel = ParentOrderDetail::class,
        TimezoneInterface $timeZone = null
    )
    {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
        $this->timeZone = $timeZone ?: ObjectManager::getInstance()
            ->get(TimezoneInterface::class);
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->where('main_table.is_gift_order = 1');
        return $this;
    }

    protected function _renderFiltersBefore()
    {
        $select = $this->getSelect();
        $fields = $this->_filtered;
        if (in_array('buyer_phone_number', $fields)) {
            $select->joinLeft(
                ['cgf_g' => 'customer_grid_flat'],
                'main_table.customer_id = cgf_g.entity_id',
                [
                    'buyer_phone_number' => 'phone_number'
                ]
            );
        }
        parent::_renderFiltersBefore();
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {

        return parent::addFieldToFilter($field, $condition);
    }
}
