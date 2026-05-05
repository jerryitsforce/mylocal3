<?php

namespace Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\File\Pdf\Image;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\RtlTextHandler;

class Pdf extends \Magento\Sales\Model\Order\Pdf\AbstractPdf
{

    /**
     * @var \Webkul\MpRmaSystem\Helper\Data
     */
    protected $rmaHelper;

    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $details;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;

    /**
     * @var  \Webkul\MpRmaSystem\Model\ReasonsRepository
     */
    protected $reasonRepo;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadInterface
     */
    protected $rootDirectory;
    /**
     * @var
     */
    private $rtlTextHandler;
    /**
     * @var
     */
    protected $string;
    /**
     * @var \Branch8\Rma\Helper\Data
     */
    protected $b8RmaHelper;

    /**
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Filesystem $filesystem
     * @param Config $pdfConfig
     * @param \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory
     * @param \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param \Webkul\MpRmaSystem\Helper\Data $rmaHelper
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $details
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Webkul\MpRmaSystem\Model\ReasonsRepository $reasonRepo
     * @param DateTime $date
     * @param FileFactory $fileFactory
     * @param \Branch8\Rma\Helper\Data $b8RmaHelper
     * @param array $data
     * @param Database|null $fileStorageDatabase
     * @param RtlTextHandler|null $rtlTextHandler
     * @param Image|null $image
     */
    public function __construct(
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Webkul\MpRmaSystem\Helper\Data $rmaHelper,
        \Webkul\MpRmaSystem\Model\DetailsFactory $details,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Webkul\MpRmaSystem\Model\ReasonsRepository $reasonRepo,
        DateTime $date,
        FileFactory $fileFactory,
        \Branch8\Rma\Helper\Data $b8RmaHelper,
        array $data = [],
        Database $fileStorageDatabase = null,
        ?RtlTextHandler $rtlTextHandler = null,
        ?Image $image = null
    ) {
        parent::__construct(
            $paymentData, $string, $scopeConfig, $filesystem, $pdfConfig, $pdfTotalFactory,
            $pdfItemsFactory, $localeDate, $inlineTranslation, $addressRenderer, $data,
            $fileStorageDatabase, $rtlTextHandler, $image
        );
        $this->rmaHelper                    = $rmaHelper;
        $this->details                      = $details;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        $this->reasonRepo                   = $reasonRepo;
        $this->date                         = $date;
        $this->fileFactory                  = $fileFactory;
        $this->rootDirectory                = $filesystem->getDirectoryRead(DirectoryList::ROOT);
        $this->b8RmaHelper = $b8RmaHelper;
        $this->rtlTextHandler = $rtlTextHandler ?: ObjectManager::getInstance()->get(RtlTextHandler::class);

    }
    public function generatePdf($rmaId)
    {
        $rmaDetails = $this->details->create()->load($rmaId);
        $productDetails = $this->rmaHelper->getRmaProductDetails($rmaId);
        $customerId = $rmaDetails->getCustomerId();
        $customerData = [];
        if ($customerId) {
            try {
                $customer = $this->_customerRepositoryInterface->getById($customerId);
                $customerData = [
                    'name' => $customer->getFirstname().' '.$customer->getLastname(),
                    'email' => $customer->getCustomAttribute('buyer_email')->getValue()
                ];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $customerData = [];
            }
        }
        if (empty($customerData)) {
            $orderId = $rmaDetails->getOrderId();
            $order = $this->rmaHelper->getOrder($orderId);
            // loggedin customer
            if ($order->getCustomerFirstname()) {
                $customerName = $order->getCustomerName();
            } else {
                // guest customer
                $billingAddress = $order->getBillingAddress();
                $customerName = $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname();
            }
            $customerData = [
                'name' => $customerName,
                'email' => $order->getCustomerEmail()
            ];
        }
        $pdf = new \Zend_Pdf();
        $pdf->pages[] = $pdf->newPage(\Zend_Pdf_Page::SIZE_A4);
        $page = $pdf->pages[0]; // this will get reference to the first page.
        $style = new \Zend_Pdf_Style();
        $style->setLineColor(new \Zend_Pdf_Color_Rgb(0, 0, 0));
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->rootDirectory->getAbsolutePath('extra-font/NotoSans/NotoSansCJKtc-Regular.ttf')
        );
        $style->setFont($font, 15);
        $page->setStyle($style);
        $width = $page->getWidth();
        $hight = $page->getHeight();
        $x = 30;
        $pageTopalign = 850; //default PDF page height
        $this->y = 850 - 100; //print table row from page top – 100px

        // set heading
        $this->insertHeading($page, $style, $font, $x);

        // Customer Details
        $this->insertCustomerDetails($page, $style, $font, $x, $customerData);

        // Rma Details
        $this->insertRmaDetails($page, $style, $font, $x, $rmaDetails);

        // insert rma products
        $this->insertProducts($page, $style, $font, $x, $productDetails);

        $date = $this->date->date('Y-m-d_H-i-s');

        $fileName = 'rma' . $date . '.pdf';
        $this->fileFactory->create(
            $fileName,
            $pdf->render(),
            DirectoryList::VAR_DIR,
            'application/pdf'
        );
        $content['type'] = 'filename';
        $content['value'] = $fileName;
        $content['rm'] = 1;

        return $this->fileFactory->create($fileName, $content, DirectoryList::VAR_DIR);
    }
    protected function _setFontRegular($object, $size = 7)
    {
        $font = \Zend_Pdf_Font::fontWithPath(
            $this->_rootDirectory->getAbsolutePath('extra-font/NotoSans/NotoSansCJKtc-Regular.ttf')
        );
        $object->setFont($font, $size);
        return $font;
    }
    public function insertHeading($page, $style, $font, $x)
    {
        $style->setFont($font, 16);
        $page->setStyle($style);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.7));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(30, $this->y + 30, $page->getWidth()-30, $this->y +70);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 20);
        $page->setStyle($style);
        $titleText = __("RMA Details");

        $titleWidth = $this->widthForStringUsingFontSize($titleText, $this->_setFontRegular($page, 16), 16);
        $titleX = ceil($page->getWidth()/2) - ceil($titleWidth/2);//var_dump(ceil($x/2), ceil($titleWidth/2));die;
        $page->drawText($titleText, $titleX, $this->y+42, 'UTF-8');

        $this->y -= 90;
    }
    public function insertRmaDetails($page, $style, $font, $x, $rmaDetails)
    {
        $status = $rmaDetails->getStatus();
//        $finalStatus = $rmaDetails->getFinalStatus();
        $resolution = $this->rmaHelper->getResolutionTypeTitle($rmaDetails->getResolutionType());
        $rmaStatus = $this->b8RmaHelper->getAdminAndSellerPanelRmaStatusTitle($status);
        $style->setFont($font, 16);
        $page->setStyle($style);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.7));
        $page->drawRectangle(30, $this->y - 20, $page->getWidth()-30, $this->y +70);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 15);
        $page->setStyle($style);
        $page->drawText(__("RMA Details"), $x + 5, $this->y+50, 'UTF-8');
        $style->setFont($font, 11);
        $page->setStyle($style);
        $page->drawText(__("Order Id : %1", $rmaDetails->getOrderRef()), $x + 5, $this->y+32, 'UTF-8');
        $page->drawText(__("Rma Status : %1", $rmaStatus), $x + 5, $this->y+16, 'UTF-8');
        $page->drawText(__("Resolution Type : %1", $resolution), $x + 5, $this->y-1, 'UTF-8');

        $this->y -= 60;
    }
    public function insertCustomerDetails($page, $style, $font, $x, $customerData)
    {
        $style->setFont($font, 16);
        $page->setStyle($style);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.7));
        $page->drawRectangle(30, $this->y + 10, $page->getWidth()-30, $this->y +70);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 15);
        $page->setStyle($style);
        $page->drawText(__("Cutomer Details"), $x + 5, $this->y+50, 'UTF-8');
        $style->setFont($font, 11);
        $page->setStyle($style);
        $page->drawText(__(
            "Name : %1",
            $customerData['name']
        ), $x + 5, $this->y+33, 'UTF-8');
        $page->drawText(__("Email : %1", $customerData['email']), $x + 5, $this->y+16, 'UTF-8');

        $this->y -= 90;
    }

    public function insertProducts($page, $style, $font, $x, $productDetails)
    {
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.45));
        $page->drawRectangle(30, $this->y -20, $page->getWidth()-30, $this->y + 5);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $style->setFont($font, 12);
        $page->setStyle($style);
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0));
        $page->drawText(__("S.NO"), $x + 5, $this->y-10, 'UTF-8');
        $page->drawText(__("Product Name"), $x + 40, $this->y-10, 'UTF-8');
        $page->drawText(__("Sku"), $x + 200, $this->y-10, 'UTF-8');
        $page->drawText(__("Price"), $x + 360, $this->y-10, 'UTF-8');
        $page->drawText(__("Qty"), $x + 400, $this->y-10, 'UTF-8');
        $page->drawText(__("Reason"), $x + 430, $this->y-10, 'UTF-8');
        $totalAmount = 0;
        $this->y -= 20;
        $i = 1;
        foreach ($productDetails as $product) {
            $reasonColl = $this->reasonRepo->getById($product->getReasonId());
            $style->setFont($font, 10);
            $page->setStyle($style);
            $add = 9;

            $productName = $product->getName();
            $productNameArr = $this->string->split(
                $this->prepareText((string)$productName),
                16, false, true
            );

            $sku = $product->getSku();
            $skuArr = $this->string->split(
                $this->prepareText((string)$sku),
                25, false, true
            );

            $reason = $reasonColl->getReason();;
            $resonArr = $this->string->split(
                $this->prepareText((string)$reason),
                10, false, true
            );

            $maxLine = max(count($productNameArr), count($skuArr), count($resonArr));

            $iArr[] = $i;
            for($j = 1; $j <= $maxLine; ++$j){
                $iArr[$j] = '';
            }
            for($j = count($productNameArr); $j <= $maxLine; ++$j){
                $productNameArr[$j] = '';
            }
            for($j = count($skuArr); $j <= $maxLine; ++$j){
                $skuArr[$j] = '';
            }
            $priceArr[0] = $product->getPrice();
            for($j = 1; $j <= $maxLine; ++$j){
                $priceArr[$j] = '';
            }
            $qtyArr[] = $product->getQty();
            for($j = 1; $j <= $maxLine; ++$j){
                $qtyArr[$j] = '';
            }

            for($j = count($resonArr); $j <= $maxLine; ++$j){
                $resonArr[$j] = '';
            }
            for($k = 0; $k <= $maxLine; ++$k){
                $page->drawText($iArr[$k], $x + 5, $this->y-30, 'UTF-8');

                $page->drawText($productNameArr[$k], $x + 40, $this->y-30, 'UTF-8');

                $page->drawText($skuArr[$k], $x + 200, $this->y-30, 'UTF-8');

                $page->drawText($priceArr[$k], $x + 360, $this->y-30, 'UTF-8');

                $page->drawText($qtyArr[$k], $x + 400, $this->y-30, 'UTF-8');

                $page->drawText($resonArr[$k], $x + 430, $this->y-30, 'UTF-8');

                $this->y -= 15;

            }
            $this->y -= 25;
            $i++;
        }
        $this->y -= 150;
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(1));
        $page->drawRectangle(30, $this->y -62, $page->getWidth()-30, $this->y - 100);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));

        // total Amount
        $style->setFont($font, 15);
        $page->setStyle($style);
    }

    private function prepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->rtlTextHandler->reverseRtlText(html_entity_decode($string));
    }

    public function getPdf()
    {
        // TODO: Implement getPdf() method.
    }
}