<?php
declare(strict_types=1);

namespace Branch8\Spin2Win\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\OrderRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\Spin2Win\Model\Config\Source\SegmentType;

/**
 * Class Edit
 */
class Gravity extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\SalesRule\Model\ResourceModel\Rule\Collection
     */
    protected $ruleCollection;
    /**
     * @var \Branch8\TicketEvent\Model\ResourceModel\TicketEvent\Collection
     */
    protected $poolCollection;

    protected $productRepo;

    /**
     * @param UrlInterface $urlBuilder
     * @param \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection
     * @param \Branch8\EventTicket\Model\ResourceModel\TicketEvent\Collection $poolCollection
     */
    public function __construct(
        UrlInterface                              $urlBuilder,
        \Magento\SalesRule\Model\ResourceModel\Rule\Collection $ruleCollection,
        \Branch8\EventTicket\Model\ResourceModel\TicketEvent\CollectionFactory $poolCollection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepo
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->ruleCollection = $ruleCollection;
        $this->poolCollection = $poolCollection;
        $this->productRepo = $productRepo;
    }

    /**
     * @param \Magento\Framework\DataObject $row
     * @return mixed
     */
    public function render(\Magento\Framework\DataObject $row){
        $gravity = (float)$row->getGravity();
        return $gravity;
    }
}
