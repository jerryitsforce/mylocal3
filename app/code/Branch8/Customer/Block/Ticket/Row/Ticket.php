<?php
namespace Branch8\Customer\Block\Ticket\Row;

use Branch8\HotaiShipping\Block\Adminhtml\System\Config\DateTime;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\View\Element\Template;

class Ticket extends  \Magento\Framework\View\Element\Template
{
    protected $_template = 'Branch8_Customer::ticket/row/ticket.phtml';

    /**
     * @var ProductInterface[]
     */
    protected $productCacheArray = [];
    private Image $imageHelper;
    private ProductRepositoryInterface $productRepository;

    protected $timezone;

    public function __construct(
        Template\Context $context,
        Image $imageHelper,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->imageHelper = $imageHelper;
        $this->productRepository = $productRepository;
        parent::__construct($context, $data);
        $this->timezone = $timezone;
    }

    /**
     * TODO: update: get Product by Repository
     */
    public function getProduct($productId)
    {
        if(isset($this->productCacheArray[$productId])) {
            return $this->productCacheArray[$productId];
        }
        try {
            $product = $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            $product = null;
        }

        $this->productCacheArray[$productId] = $product;
        return $this->productCacheArray[$productId];
    }

    public function getProductImage($product)
    {
        return $this->imageHelper->init($product, 'product_small_image')->getUrl();
    }

    public function getProductTicketPeriodTimeText($item)
    {
        $period = $item->getData('use_start_time') ? (new \DateTime($item->getData('use_start_time')))->format('Y/m/d') . ' ~ ' : '';
        $period .= $item->getData('use_end_time') ? (new \DateTime($item->getData('use_end_time')))->format('Y/m/d') : '';
        return $period;
    }

    public function getPeriodTimeText($item)
    {
        $startTime = '';
        $endTime = '';
        if(is_array($item)){
            $startTime = $item['use_start_time'];
            $endTime = $item['use_end_time'];
        }
        if(is_object($item)){
            $startTime = $item->getData('use_start_time');
            $endTime = $item->getData('use_end_time');
        }
        $period = $startTime ? (new \DateTime($startTime))->format('Y/m/d') . ' ~ ' : '';
        $period .= $endTime ? (new \DateTime($endTime))->format('Y/m/d') : '';
        return $period;
    }
//    public function getGiftTicketPeriodTimeText($item){
//        $period = __('Now');
//        if(!empty($item['start_date'])){
//            $period = $this->timezone->date($item['start_date'])->format('Y/m/d');
//        }
//        $period .= ' ~ ';
//        if(!empty($item['end_date'])){
//            $period .= $this->timezone->date($item['end_date'])->format('Y/m/d');
//        }else{
//            $period .= __('Unlimited');
//        }
//        return $period;
//    }

    public function getTicketDetailUrl($ticket)
    {
        return $this->getUrl('member/ticket/detail/', ['id' => $ticket->getData('record_id')]);
    }

    public function getRemainDay($dateString)
    {
        if(empty($dateString)) {
            return 0;
        }
        $date = $this->timezone->date($dateString);
        $now = $this->timezone->date();
        $days = $now->diff($date)->format('%r%a');
        return $days;
    }

    public function getGiftTicketRemainDays($endDate){
        if(empty($endDate)) {
            return 0;
        }
        return $this->timezone->date()->diff($this->timezone->date($endDate))->format('%r%a');
    }
}
