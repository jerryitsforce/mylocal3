<?php
namespace Branch8\Sales\Block\Order\Email\Shipment;

use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Items extends \Magento\Sales\Block\Order\Email\Shipment\Items
{

    /**
     * @var \Branch8\Sales\Helper\Config
     */
    protected $config;

    public function __construct(
        Context $context,
        \Branch8\Sales\Helper\Config $config,
        array $data = [],
        ?OrderRepositoryInterface $orderRepository = null,
        ?ShipmentRepositoryInterface $creditmemoRepository = null
    ) {
        $this->config = $config;
        parent::__construct($context, $data, $orderRepository, $creditmemoRepository);
    }


    public function getLogisticsCompanies()
    {
        return $this->config->getLogisticsCompanies();
    }
}
