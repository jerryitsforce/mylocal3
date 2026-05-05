<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Plugin\Branch8\HelpDesk\Block\Adminhtml\Ticket\Edit\Tab;

use Branch8\HelpDesk\Model\Ticket;
use Branch8\MarketPlaceParentOrderHelpDesk\Model\Config;
use Branch8\MarketPlaceParentOrderHelpDesk\Model\ResourceModel\ParentOrder\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\UrlInterface;

class GeneralPlugin
{

    private OrderCollectionFactory $orderCollectionFactory;
    private Config $config;
    private UrlInterface $url;

    /**
     * @param Config $config
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param UrlInterface $url
     */
    public function __construct(
        Config                 $config,
        OrderCollectionFactory $orderCollectionFactory,
        UrlInterface           $url
    )
    {
        $this->url = $url;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->config = $config;
    }

    /**
     * @param $subject
     * @param callable $process
     * @param Ticket $ticket
     * @param \Magento\Framework\Data\Form\Element\FieldSet $fieldset
     * @param $isElementDisabled
     * @return void
     */
    public function aroundGetOrderField(
        $subject,
        callable $process,
        Ticket $ticket,
        \Magento\Framework\Data\Form\Element\FieldSet $fieldset,
        $isElementDisabled
    )
    {
        $isDisableNativeUi = $this->config->isDisableNativeOrderUi();
        if (!$isDisableNativeUi) {
            return $process(
                $ticket,
                $fieldset,
                $isElementDisabled
            );
        }
        $orderIds = explode(',', (string)$ticket->getParentOrder());
        $order = '';
        if ($orderIds) {
            $urls = [];
            $collection = $this->orderCollectionFactory->create()->addFieldToFilter('entity_id', ['in' => $orderIds]);
            foreach ($collection as $item) {
                $html = "<a href='" . $this->url->getUrl(
                        'sales/parent_order/view',
                        ['id' => $item->getData('entity_id')]
                    ) . "' target='blank' title='" . $item->getIncrementId() . "'>";
                $html .= '#' . $item->getIncrementId() . '</a>';
                $urls[] = $html;

            }
            $order = join(' ', $urls);
        }

        $fieldset->addField(
            'order',
            'note',
            [
                'name' => 'last_order',
                'label' => __('Order'),
                'title' => __('Order'),
                'required' => false,
                'disabled' => $isElementDisabled == false,
                'text' => $order
            ]
        );
    }
}
