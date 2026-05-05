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
class Award extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
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
        $segmentType = $row->getType();
        $award = '';
        if (!empty($item['entity_id'])) {
            if($segmentType == SegmentType::LOSE_TYPE){
                $award = '';
            }
        } else if($segmentType == SegmentType::COUPON_TYPE){
            $rule = $this->ruleCollection->addFieldToSelect('name')
                ->addFieldToFilter('rule_id', $row->getRuleId())
                ->getFirstItem();
            $award = '<a target="_blank" href="'.$this->urlBuilder->getUrl(
                    'sales_rule/promo_quote/edit',
                    ['id' => (int)$row->getRuleId()]).'">'.$rule->getName().'</a>';
        }else if($segmentType == SegmentType::VIRTUAL_TYPE){
            $pool = $this->poolCollection->create()->addFieldToSelect('name')
                ->addFieldToFilter('entity_id', $row->getPoolId())
                ->getFirstItem();
            $award = '<a target="_blank" href="'.$this->urlBuilder->getUrl(
                    'eventticket/event/edit',
                    ['id' => (int)$row->getPoolId()]).'">'.$pool->getName().'</a>';
        }else if($segmentType == SegmentType::PHYSICAL_TYPE){
            // $sku = $row->getSku();
            // $product = $this->productRepo->get($sku);
            // $award = '<a target="_blank" href="'.$this->urlBuilder->getUrl(
            //     'catalog/product/edit',
            //     ['id' => (int)$product->getId()]).'">'.$sku.'</a>';
            $award = '';
        }else if($segmentType== SegmentType::REWARD_POINT_TYPE){
            $award = $row->getRewardPoint();
        }
        return $award;
    }
}
