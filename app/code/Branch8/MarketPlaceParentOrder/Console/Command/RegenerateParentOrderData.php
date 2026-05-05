<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Console\Command;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\Services\AssignDataForParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\CopyAddressesFromSalesOrderToParentOrder;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\OrderRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webkul\Mpsplitorder\Model\Mpsplitorder;

/**
 * Class ProductAttributesCleanUp
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegenerateParentOrderData extends \Symfony\Component\Console\Command\Command
{
    private AssignDataForParentOrder $assignDataForParentOrder;
    private Log $log;
    private OrderRepository $orderRepository;
    private ParentOrderFactory $parentOrderFactory;
    private mixed $parentOrderRepository;
    private CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrder;
    private ParentOrderManagementInterface $parentOrderManagement;
    private \Magento\Framework\App\State $appState;
    private \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $collectionFactory;

    /**
     * @param \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $splitOrderCollectionFactory
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param OrderRepository $orderRepository
     * @param ParentOrderFactory $parentOrderFactory
     * @param AssignDataForParentOrder $assignDataForParentOrder
     * @param CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrderToParentOrder
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Magento\Framework\App\State $appState
     * @param Log $log
     */
    public function __construct(
        \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $splitOrderCollectionFactory,
        ParentOrderRepositoryInterface                                          $parentOrderRepository,
        OrderRepository                                                         $orderRepository,
        ParentOrderFactory                                                      $parentOrderFactory,
        AssignDataForParentOrder                                                $assignDataForParentOrder,
        CopyAddressesFromSalesOrderToParentOrder                                $copyAddressesFromSalesOrderToParentOrder,
        ParentOrderManagementInterface                                          $parentOrderManagement,
        \Magento\Framework\App\State                                            $appState,
        Log                                                                     $log
    )
    {
        $this->collectionFactory = $splitOrderCollectionFactory;
        $this->assignDataForParentOrder = $assignDataForParentOrder;
        $this->log = $log;
        $this->orderRepository = $orderRepository;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->copyAddressesFromSalesOrder = $copyAddressesFromSalesOrderToParentOrder;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->appState = $appState;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:parent_oder:regeneratedata');
        $this->setDescription('Regenerate for parent order.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output);
        $progress->setFormat('<comment>%message%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed%');
        try {
            $subQuery = 'select parent_id from sales_parent_order_detail';

            /**
             * @var $collection \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\Collection
             */
            $collection = $this->collectionFactory->create();
            $collection->getSelect()->where(
                'index_id not in (' . $subQuery . ')'
            );
            $collection->load();
            /**
             * @var $item \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder
             */
            foreach ($collection as $item) {
                $this->regenerate($item);
            }
            $output->writeln("");
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln("");
            $output->writeln("<error>{$exception->getMessage()}</error>");
            $this->log->logException('RegenerateParentOrderData', $exception);
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    private function regenerate(\Webkul\Mpsplitorder\Model\Mpsplitorder $item)
    {
        $id = $item->getId();
        $shippingAddress = $billingAddress = '';
        try {
            $suborders = explode(',', $item->getData('order_ids'));
        } catch (\Exception $exception) {
            $suborders = [];
            $this->log->logException('RegenerateParentOrderData', $exception, ['parent_order_id' => $id]);
        }
        $lastSubOrder = null;
        foreach ($suborders as $subOrderId) {
            $subOrder = $this->orderRepository->get(
                $subOrderId
            );
            if ($subOrder->getShippingAddress()) {
                $lastSubOrder = $subOrder;
                break;
            }
        }
        if (!$lastSubOrder) {
            $lastSubOrder = $this->orderRepository->get(
                $item->getData('last_order_id')
            );
        }
        $detail = $this->assignDataForParentOrder->copy(
            $lastSubOrder
        );
        $addresses = $this->copyAddressesFromSalesOrder->copy($lastSubOrder);
        $detail->setParentId((int)$id);
        if ($addresses) {
            /**
             * @var $address ParentOrderAddress
             */
            foreach ($addresses as $address) {
                $address->setParentOrderId((int)$id)->save();
                if ($address->getAddressType() === ParentOrderAddress::TYPE_SHIPPING) {
                    $shippingAddress = $address;
                }
                if ($address->getAddressType() === ParentOrderAddress::TYPE_BILLING) {
                    $billingAddress = $address;
                }
            }
        }
        if ($shippingAddress) {
            $detail->setShippingAddress($shippingAddress->getId());
        }
        if ($billingAddress) {
            $detail->setBillingAddress($billingAddress->getId());
        }
        $detail->setPaymentMethod(
            $lastSubOrder->getPayment() ?
                $lastSubOrder->getPayment()->getMethod() : ''
        );
        if ($suborders) {
            $this->parentOrderManagement->assignSubordersToParentOrder(
                $id,
                $suborders
            );
        }
        $detail->save();
    }
}
