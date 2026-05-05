<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Plugin\Branch8\HelpDesk\ViewModel;

use Branch8\HelpDesk\Model\Ticket;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Magento\Framework\UrlInterface;

class TicketDataPlugin
{
    const XML_PATH_DISABLE_SUB_ORDERS_ACCESS = 'parent_order/sub_order_configuration/access';


    private CollectionFactory $collectionFactory;
    private UrlInterface $url;
    private \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config;

    /**
     * @param \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config
     * @param CollectionFactory $collectionFactory
     * @param UrlInterface $url
     */
    public function __construct(
        \Branch8\MarketPlaceParentOrderHelpDesk\Model\Config $config,
        CollectionFactory                                    $collectionFactory,
        UrlInterface                                         $url
    )
    {
        $this->url = $url;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
    }

    /**
     * Get array Order Links
     * @return array
     */
    public function aroundGetOrderLinks($subject, callable $process, Ticket $ticket)
    {
        $disable = $this->config->isDisableNativeOrderUi();
        if (!$disable) {
            return $process($ticket);
        }
        $orderIds = explode(',', (string)$ticket->getParentOrder());
        $urls = [];
        if ($orderIds) {
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('index_id', ['in' => $orderIds]);
            foreach ($collection as $item) {
                $detail = $item->getDetail();
                $urls[] = [
                    'title' => '#' . ($detail ? $detail->getIncrementId() : ''),
                    'link' => $this->url->getUrl('sales/ParentOrder/view',
                        ['id' => $item->getId()])
                ];

            }
        }
        return $urls;
    }
}
