<?php

declare(strict_types=1);

namespace Branch8\Customer\Block\Ticket;

use Branch8\CustomerTicketTable\Api\Data\CustomerTicketInterface;
use Branch8\CustomerTicketTable\Model\CustomerTicket;
use Branch8\HotaiCore\Helper\Barcode;
use Branch8\HotaiCore\Model\Product\BarcodeType;
use Branch8\HotaiCore\Model\Product\DisplayBarcode;
use Branch8\HotaiCore\Model\Product\DisplaySerialNumber;
use Branch8\HotaiCore\Model\Product\ExchangeHint;
use Branch8\HotaiCore\Model\Product\ExchangeUrl;
use Branch8\HotaiCore\Model\Product\IsOfflineOperation;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Ticket\Status;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Registry;
use Branch8\CustomerTicketTable\Model\CustomerTicketRepository;
use Branch8\CustomerTicketTable\Model\CustomerTicketOverDueRepository;
use Branch8\Edenred\Model\EdenredTicketRecordRepository;

class Detail extends \Magento\Framework\View\Element\Template
{

    protected $ticket = null;

    /**
     * @var CustomerTicketRepository
     */
    protected $customerTicketRepository;

    /**
     * @var CustomerTicketOverDueRepository
     */
    protected $customerTicketOverDueRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var EdenredTicketRecordRepository
     */
    protected $edenredTicketRecordRepository;

    private Image $imageHelper;
    private Barcode $barcodeHelper;

    protected $timezone;

    protected $customerSession;


    /**
     * @param Context $context
     * @param Registry $registry
     * @param CustomerTicketRepository $customerTicketRepository
     * @param CustomerTicketOverDueRepository $customerTicketOverDueRepository
     * @param Image $imageHelper
     * @param Barcode $barcodeHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        CustomerTicketRepository $customerTicketRepository,
        CustomerTicketOverDueRepository $customerTicketOverDueRepository,
        Image $imageHelper,
        Barcode $barcodeHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        EdenredTicketRecordRepository $edenredTicketRecordRepository,
        \Magento\Customer\Model\Session $customerSession,
        array $data = []
    ) {
        $this->customerTicketRepository = $customerTicketRepository;
        $this->customerTicketOverDueRepository = $customerTicketOverDueRepository;
        $this->registry = $registry;
        $this->imageHelper = $imageHelper;
        $this->barcodeHelper = $barcodeHelper;
        $this->timezone = $timezone;
        $this->edenredTicketRecordRepository = $edenredTicketRecordRepository;
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
    }

    /**
     * @return CustomerTicketInterface | CustomerTicket
     */
    public function getTicket()
    {
        return $this->registry->registry('current_ticket');
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->getTicket()->getProduct();
    }

    public function getProductImage($product)
    {
        return $this->imageHelper->init($product, 'product_base_image')->getUrl();
    }


    public function shouldShowSerialNumber()
    {
        $product = $this->getProduct();
        $displaySerialNumber = $product->getCustomAttribute(DisplaySerialNumber::ATTRIBUTE_CODE)?->getValue();

        return $this->boolean($displaySerialNumber) && $this->getTicket()->getTicketUniqueContent();
    }

    public function shouldGiftTicketShowSerialNumber($displayType)
    {
        if(in_array($displayType, [
            \Branch8\EventTicket\Model\Config\Source\DisplayType::TYPE_NUMBER,
            \Branch8\EventTicket\Model\Config\Source\DisplayType::TYPE_BOTH
        ])){
            return true;
        }
        return false;
    }

    public function shouldShowPassword()
    {
        $ticket = $this->getTicket();
        $isEdenredTicket = $ticket->getType() == VirtualProductType::TYPE_EDENRED_TICKET;
        $isQwareTicket = $ticket->getType() == VirtualProductType::TYPE_QWARE_TICKET;
        
        $inAllowedType = $isEdenredTicket || $isQwareTicket;

        return $inAllowedType && $ticket->getPassword();
    }

    public function shouldGiftTicketShowPassword($record)
    {
        if(isset($record['ticket_type']) && $record['ticket_type'] == VirtualProductType::TYPE_EDENRED_TICKET){

        }
        return false;
    }


    public function shouldShowCancelTicketButton()
    {
        $inAllowedType = $this->getTicket()->getType() == VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET;

        $product = $this->getProduct();
        $isOfflineOperation = $product->getCustomAttribute(IsOfflineOperation::ATTRIBUTE_CODE)?->getValue();

        return $inAllowedType && $this->boolean($isOfflineOperation);
    }

    public function shouldGiftTicketShowCancelTicketButton($poolTicketData)
    {
        $inAllowedType = $poolTicketData['ticket_type'] == \Branch8\TicketApi\Model\TicketApiBrand\Source\Brand::BRAND_CODE_GENERAL_NON_NOTIFY;
        $isOfflineOperation = $poolTicketData['is_offline_operation'];

        return $inAllowedType && (int)$isOfflineOperation;
    }

    public function shouldShowBarCode()
    {
        $product = $this->getProduct();
        $displayBarCode = $product->getCustomAttribute(DisplayBarcode::ATTRIBUTE_CODE)?->getValue() ?? 0;
        return $displayBarCode != 0;
    }

    public function shouldGiftTicketShowBarCode($displayType)
    {
        if(in_array($displayType, [
            \Branch8\HotaiCore\Model\Product\DisplayBarcode::TYPE_DISPLAY,
            \Branch8\HotaiCore\Model\Product\DisplayBarcode::TYPE_DISPLAY_BOTH
        ])){
            return true;
        }
        return false;
    }

    public function getBarCodeType()
    {
        $displayBarcode = $this->getProduct()->getCustomAttribute(DisplayBarcode::ATTRIBUTE_CODE)?->getValue() ?? 0;
        if ($displayBarcode == DisplayBarcode::TYPE_DISPLAY_BOTH) {
            return BarcodeType::TYPE_QRCODE_AND_CODE_128;
        }

        $product = $this->getProduct();
        return $product->getCustomAttribute(BarcodeType::ATTRIBUTE_CODE)?->getValue() ?? 0;
    }

    public function getBarCodeUrl($barcodeType, $content)
    {
        return $this->_urlBuilder->getUrl('hotai_core/barcode/display', [
            "barcode_content" => $content,
            "barcode_type"    => $barcodeType,
        ]);
    }

    public function getProductTicketPeriodTimeText($item)
    {
        $startTime = $item->getData('use_start_time');
        $endTime = $item->getData('use_end_time');
        
        if (!$startTime) {
            return '';
        }

        $period = (new \DateTime($startTime))->format('Y/m/d H:i:s') . ' ~ ';
        $period .= $endTime ? (new \DateTime($endTime))->format('Y/m/d H:i:s') : '';

        return $period;
    }

    public function getPeriodTimeText($item)
    {
        $startTime = '';
        $endTime = '';
        if(is_array($item)){
            $startTime = $item['use_start_time'] ?? '';
            $endTime = $item['use_end_time'] ?? '';
        }
        if(is_object($item)){
            $startTime = $item->getData('use_start_time');
            $endTime = $item->getData('use_end_time');
        }
        $period = $startTime ? $this->timezone->date($startTime)->format('Y/m/d H:i:s') . ' ~ ' : '';
        $period .= $endTime ? $this->timezone->date($endTime)->format('Y/m/d H:i:s') : '';
        return $period;
    }

//    public function getGiftTicketPeriodTimeText($item){
//        $period = $item['start_date'] ? $this->timezone->date($item['start_date'])->format('Y/m/d H:i:s') : '';
//        $period .= $item['end_date'] ? ($period ? ' ~ ' : '') . $this->timezone->date($item['end_date'])->format('Y/m/d H:i:s') : '';
//        return $period;
//    }


    public function getRemainDay($dateString)
    {
        if(!$dateString) {
            return 0;
        }
        $date = $this->timezone->date($dateString);
        $now = $this->timezone->date();
        $days = $now->diff($date)->format('%r%a');
        return $days;
    }

    public function getGiftTicketRemainDays($endDate){
        if(!$endDate) {
            return 0;
        }
        return $this->timezone->date()->diff($this->timezone->date($endDate))->format('%r%a');
    }

    public function getTicketDetailNoteHtml()
    {
        $blockId = $this->_scopeConfig->getValue(
            'hotai_account_page/member_ticket/ticket_detail_note',
            ScopeInterface::SCOPE_STORE,
        );

        return $this->getLayout()
            ->createBlock('Magento\Cms\Block\Block')
            ->setBlockId($blockId)
            ->toHtml();
    }


    public function shouldShowDownloadButton()
    {
        $inAllowedType = $this->getTicket()->getType() == VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET;

        $product = $this->getProduct();
        $isOfflineOperation = $product->getCustomAttribute(IsOfflineOperation::ATTRIBUTE_CODE)?->getValue();

        return $inAllowedType && !$this->boolean($isOfflineOperation);
    }

    public function shouldGiftTicketShowDownloadButton($poolTicketData){
        $inAllowedType = $poolTicketData['ticket_type'] == \Branch8\TicketApi\Model\TicketApiBrand\Source\Brand::BRAND_CODE_GENERAL_NON_NOTIFY;
        $isOfflineOperation = $poolTicketData['is_offline_operation'];

        return $inAllowedType && !(int)$isOfflineOperation;
    }
    public function shouldShowRedeemButton()
    {
        $ticket = $this->getTicket();
        
        // 安源票券檢查
        if($ticket->getType() == VirtualProductType::TYPE_EDENRED_TICKET && $ticket->getData('edenred_short_url')){
            return true;
        }
        
        // 宜睿票券檢查
        if($ticket->getType() == VirtualProductType::TYPE_QWARE_TICKET && $ticket->getData('qware_url')){
            return true;
        }
        
        $isAllowType = $ticket->getType() !== VirtualProductType::TYPE_GENERAL_NON_NOTIFY_TICKET;

        $url = $this->getProduct()->getCustomAttribute(ExchangeUrl::ATTRIBUTE_CODE)?->getValue();
        return $isAllowType && $url;
    }

    public function shouldGiftTicketShowRedeemButton($poolTicketData)
    {
        $isAllowType = $poolTicketData['ticket_type'] !== \Branch8\TicketApi\Model\TicketApiBrand\Source\Brand::BRAND_CODE_GENERAL_NON_NOTIFY;

        $exchangeUrl = $poolTicketData['exchange_url'] ?? null;
        $qwareUrl = $poolTicketData['qware_url'] ?? null;
        
        return $isAllowType && (!empty($exchangeUrl) || !empty($qwareUrl));
    }

    public function getRedeemUrl()
    {
        $ticket = $this->getTicket();
        
        if($ticket->getStatus() !== Status::STATUS_UNUSED) {
            return '#';
        }

        $url = $this->getProduct()->getCustomAttribute(ExchangeUrl::ATTRIBUTE_CODE)?->getValue();

        // 安源票券使用專屬 URL
        if($ticket->getType() == VirtualProductType::TYPE_EDENRED_TICKET){
            return $ticket->getData('edenred_short_url') ?? $url ?? '#';
        }
        
        // 宜睿票券使用專屬 URL
        if($ticket->getType() == VirtualProductType::TYPE_QWARE_TICKET){
            return $ticket->getData('qware_url') ?? $url ?? '#';
        }

        return $url ?? '#';
    }

    public function getGiftTicketRedeemUrl($poolTicketData)
    {
        if((int)$poolTicketData['ticket_status'] !== Status::STATUS_UNUSED) {
            return '#';
        }

        if (!empty($poolTicketData['exchange_url'])) {
            return $poolTicketData['exchange_url'];
        }
        
        if (!empty($poolTicketData['qware_url'])) {
            return $poolTicketData['qware_url'];
        }

        return '#';
    }

    public function getExchangeHint()
    {
        return $this->getProduct()->getCustomAttribute(ExchangeHint::ATTRIBUTE_CODE)?->getValue();
    }

    public function getGiftTicketExchangeHint($poolTicketData)
    {
        return $poolTicketData['exchange_hint'];
    }

    public function boolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function getGiftTicketImage($imageUrl)
    {
        $imageData = json_decode($imageUrl, true);
        if(isset($imageData[0]) && isset($imageData[0]['url'])){
            $ticketImage = $imageData[0]['url'];
        }else{
            $imageHelper = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Catalog\Helper\Image::class);
            $ticketImage = $imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
        return $ticketImage;
    }

    public function isEdenredTicket($customerTicket): bool
    {
        if (is_array($customerTicket)) {
            return false;
        }

        if ($customerTicket->getType() === null) {
            return false;
        }
      
        return $customerTicket->getType() == VirtualProductType::TYPE_EDENRED_TICKET;
    }

    public function getEdenredClientOrderNumber(CustomerTicket $customerTicket): string
    {
        $clientOrderNumber = "";

        if (!$this->isEdenredTicket($customerTicket)) {
            return $clientOrderNumber;
        }

        $edenredRecordId   = $customerTicket->getTicketTableRecordId();
        $edenredRecord     = $this->edenredTicketRecordRepository->getById($edenredRecordId);
        $clientOrderNumber = $edenredRecord->getEdenredClientOrderNumber();

        return $clientOrderNumber;
    }

    public function isLoggedIn(){
        return $this->customerSession->isLoggedIn();
    }
}

