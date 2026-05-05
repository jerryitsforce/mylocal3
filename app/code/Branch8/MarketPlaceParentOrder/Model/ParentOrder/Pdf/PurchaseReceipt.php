<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\Services\SubOrderFinder;
use Branch8\MaskInformation\Api\MaskRulesCompositeInterface;
use Branch8\Sales\Model\KanJiLocale;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\File\Pdf\Image;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\Sales\Model\Order\Pdf\Config;
use Magento\Sales\Model\RtlTextHandler;
use Magento\Store\Model\ScopeInterface;

/**
 * Print Pdf for Parent Order
 */
class PurchaseReceipt extends AbstractPdf
{
    const XML_PATH_SALES_PDF_ORDER_USE_SUBSET_FONT = 'sales_pdf/order/use_subset_font';
    const XML_PATH_SALES_PDF_ORDER_NOTE = 'sales_pdf/order/note';

    const XML_PATH_SALES_PDF_ORDER_FOOTER_NOTE = 'sales_pdf/order/footer_note';

    const XML_PATH_COPY_RIGHT_TEXT = 'design/footer/copyright';
    const TEXT_RED = '#c9211e';

    const TEXT_GRAY = "#666666";
    const TEXT_BLACK = '#36333d';

    const TOP_BOTTOM_GREY = '#5a5a5a';

    const LINE_GRAY_COLOR = "#e3e3e3";

    const TEXT_SUPPER_BLACK = '#000000';
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    private $fileStorageDatabase;
    /**
     * @var
     */
    private $subOrderFinder;
    /**
     * @var ParentOrderManagementInterface
     */
    private $parentOrderManagement;
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress
     */
    private $parentFormatRenderer;

    private $maskRuleComposite;

    private $grayColor;

    private $blackColor;

    private $redColor;

    private $lineGrayColor;
    /**
     * @var array $pageSettings
     */
    private $pageSettings;

    protected $topBottomGreyLine;

    protected $supperBlackColor;

    protected $resourceConnection;

    protected $shopTitle = [];
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipStoreSaleHelper;

    private $useFontSubSet = false;
    /**
     * @var SubsetFontGenerator
     */
    private $subsetFontGenerator;

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
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param SubOrderFinder $subOrderFinder
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $formatAddress
     * @param MaskRulesCompositeInterface $maskRulesComposite
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSaleHelper
     * @param array $data
     * @param Database|null $fileStorageDatabase
     * @param RtlTextHandler|null $rtlTextHandler
     * @param Image|null $image
     */
    public function __construct(
        \Magento\Payment\Helper\Data                                 $paymentData,
        \Magento\Framework\Stdlib\StringUtils                        $string,
        \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig,
        \Magento\Framework\Filesystem                                $filesystem,
        Config                                                       $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory                 $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory                  $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface           $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer                  $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface                   $storeManager,
        \Magento\Store\Model\App\Emulation                           $appEmulation,
        SubOrderFinder                                               $subOrderFinder,
        ParentOrderManagementInterface                               $parentOrderManagement,
        \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $formatAddress,
        MaskRulesCompositeInterface                                  $maskRulesComposite,
        ResourceConnection                                           $resourceConnection,
        \Branch8\FlagshipStore\Helper\Sales                          $flagshipStoreSaleHelper,
        array                                                        $data = [],
        Database                                                     $fileStorageDatabase = null,
        ?RtlTextHandler                                              $rtlTextHandler = null,
        ?Image                                                       $image = null
    )
    {
        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $data
        );
        $this->resourceConnection = $resourceConnection;
        $this->maskRuleComposite = $maskRulesComposite;
        $this->_storeManager = $storeManager;
        $this->appEmulation = $appEmulation;
        $this->subOrderFinder = $subOrderFinder;
        $this->parentFormatRenderer = $formatAddress;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->fileStorageDatabase = $fileStorageDatabase ?: ObjectManager::getInstance()->get(Database::class);
        $this->rtlTextHandler = $rtlTextHandler ?: ObjectManager::getInstance()->get(RtlTextHandler::class);
        $this->image = $image ?: ObjectManager::getInstance()->get(Image::class);
        $this->grayColor = new \Zend_Pdf_Color_Html(self::TEXT_GRAY);
        $this->blackColor = new \Zend_Pdf_Color_Html(self::TEXT_BLACK);
        $this->redColor = new \Zend_Pdf_Color_Html(self::TEXT_RED);
        $this->lineGrayColor = new \Zend_Pdf_Color_Html(self::LINE_GRAY_COLOR);
        $this->topBottomGreyLine = new \Zend_Pdf_Color_Html(self::TOP_BOTTOM_GREY);
        $this->supperBlackColor = new \Zend_Pdf_Color_Html(self::TEXT_SUPPER_BLACK);
        $this->flagshipStoreSaleHelper = $flagshipStoreSaleHelper;
        $this->subsetFontGenerator = ObjectManager::getInstance()->create(SubsetFontGenerator::class);
        $this->useFontSubSet = (bool)$this->_scopeConfig->getValue(self::XML_PATH_SALES_PDF_ORDER_USE_SUBSET_FONT)
            && $this->subsetFontGenerator->commandExists();
    }
    /**
     * @param ParentOrder $parentOrder
     * @return array|mixed|string|null
     */
    private function getCustomerId(ParentOrder $parentOrder)
    {
        $id = '';
        $customer = $parentOrder->getDetail()->getCustomer();
        if ($customer && $customer->getId()
        ) {
            $id = str_pad((string)$customer->getId(), 8, '0', STR_PAD_LEFT);
        }
        return $id;
    }

    /**
     * @param $parentOrders
     * @return \Zend_Pdf
     * @throws \Zend_Pdf_Exception
     */
    public function getPdf($parentOrders = [])
    {
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        $this->_beforeGetPdf();
        $this->_initRenderer('parent_order');
        $pdf = new \Zend_Pdf();
        $this->_setPdf($pdf);
        $footerNote = trim((string)$this->_scopeConfig->getValue(self::XML_PATH_SALES_PDF_ORDER_FOOTER_NOTE));
        $copyRight = trim((string)$this->_scopeConfig->getValue(self::XML_PATH_COPY_RIGHT_TEXT));
        $defaultStoreId = $this->_storeManager->getStore()->getId();
        /**
         * @var $parentOrder ParentOrder
         */
        foreach ($parentOrders as $parentOrder) {
            $detail = $parentOrder->getDetail();
            if ($parentOrder->getDetail()->getStoreId()) {
                $this->appEmulation->startEnvironmentEmulation(
                    $detail->getStoreId(),
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    true
                );
                $this->_storeManager->setCurrentStore($detail->getStoreId());
            }
            if ($this->useFontSubSet) {
                $this->subsetFontGenerator->reset()
                    ->setParentOrder($parentOrder)->generate();
            }
            $page = $this->newPage();
            $this->insertLogo($page, $parentOrder->getStore());
            $style = new \Zend_Pdf_Style();
            $this->_setFontBold($style, 10);
            $page = $this->drawParentOrderSection($page, $parentOrder);
            $page = $this->drawSubOrders($pdf, $page, $parentOrder);
            $page = $this->drawOrderSummarizeBlock($page, $parentOrder);
            $page = $this->drawOrderNote($page, $parentOrder);
            if ($detail->getStoreId()) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }
        $this->appEmulation->startEnvironmentEmulation(
            $defaultStoreId,
            \Magento\Framework\App\Area::AREA_FRONTEND,
            true
        );
        $this->_storeManager->setCurrentStore($defaultStoreId);
        // draw page footer;
        $number_pages = sizeof($pdf->pages);
        foreach ($pdf->pages as $key => $page) {
            $page->setFillColor($this->supperBlackColor);
            $page->setLineColor($this->supperBlackColor);
            if ($footerNote) {
                $this->_setFontRegular($page, 10);
                $page->drawText($footerNote, 28, 28, 'UTF-8');
            }
            $page->setLineWidth(0.2);
            $page->drawLine(28, 20, $page->getWidth() - 28, 20);
            $this->_setFontRegular($page, 10);
            $page->setFillColor($this->supperBlackColor);
            $page->drawText($copyRight,
                28, 10,
                'UTF-8'
            );
            $numOfPage = ($key + 1) . " of $number_pages";
            $this->_setFontRegular($page, 10);
            $numOfPageLength = $this->widthForStringUsingFontSize($numOfPage,
                $this->_setFontRegular($page, 10), 10);
            $page->drawText($numOfPage,
                $page->getWidth() - 28 - $numOfPageLength, 10,
                'UTF-8'
            );
        }
        $this->appEmulation->stopEnvironmentEmulation();
        $this->_afterGetPdf();
        if ($this->useFontSubSet) {
            $this->subsetFontGenerator->reset();
        }
        return $pdf;
    }

    /**
     * @param $page
     * @param ParentOrder $parentOrder
     * @return void
     */
    private function drawOrderNote($page, ParentOrder $parentOrder)
    {
        if ($this->y < 160) {
            $page = $this->newPage();
        }
        $bulletChar = '•';
        $this->y = $this->y - 20;
        $orderNote = (string)$this->_scopeConfig->getValue(self::XML_PATH_SALES_PDF_ORDER_NOTE);
        $notes = preg_split('/\r\n|[\r\n]/', $orderNote);
        if (count($notes) && $orderNote) {
            $page->setFillColor($this->blackColor);
            $this->_setFontBold($page, 10);
            $page->drawText(__('Note'), 42, $this->y, 'UTF8');
            $page->setFillColor($this->blackColor);
            $this->y -= 20;
            foreach ($notes as $note) {
                $lines = [];
                $lines[] = [
                    'text' => $bulletChar,
                    'feed' => 32,
                    'align' => 'left',
                    'font_size' => 10,
                    'font' => 'bold',
                    'height' => 15
                ];
                $lines[] = [
                    'text' => $this->string->split($note, 85),
                    'feed' => 47,
                    'align' => 'left',
                    'font_size' => 8,
                    'font' => 'regular',
                    'height' => 15
                ];
                $lineBlock = ['lines' => [$lines], 'height' => 15];
                $this->drawLineBlocks($page, [$lineBlock]);
            }
            return $page;
        }
        return $page;
    }

    /**
     * @param $page
     * @param ParentOrder $parentOrder
     * @return mixed|\Zend_Pdf_Page
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Pdf_Exception
     */
    private function drawOrderSummarizeBlock($page, ParentOrder $parentOrder)
    {
        /**
         * @var $billingAddress ParentOrderAddress
         */
        $billingAddress = $parentOrder->getBillingAddress();
        $totalInformation = $this->parentOrderManagement->getTotals($parentOrder);

//        $flagshipProcessOrderValue = $this->flagshipStoreSaleHelper->getProcessOrderValue($parentOrder->getDetail()->getParentId());


        $discount = (int)$this->parentOrderManagement->getTotalByKey($parentOrder, 'discount')->getValue();
        $subTotal = (int)$this->parentOrderManagement->getTotalByKey($parentOrder, 'subtotal_include_tax')->getValue()
            + $discount;
//        $subTotal -= $flagshipProcessOrderValue;
        $subTotal = number_format($subTotal, 0, '.', ',');


        $shipping = (int)$this->parentOrderManagement->getTotalByKey($parentOrder, 'shipping_include_tax')->getValue();
//        $shipping += $flagshipProcessOrderValue;
        $shipping = number_format($shipping, 0, '.', ',');

        $grandTotal = (int)$this->parentOrderManagement->getTotalByKey($parentOrder, 'grand_total')->getValue();
        $grandTotal = number_format($grandTotal, 0, '.', ',');

        $pointUsedTotal = (int)$this->parentOrderManagement->getTotalByKey($parentOrder, 'point_used_total')->getValue();
        //$pointUsedTotal = number_format($pointUsedTotal, 0, '.', ',');
        $paymentMethod = $parentOrder->getDetail()->getPaymentMethod();
        $paymentMethodTitle = $this->getMethodStoreTitle((string)$paymentMethod, (int)$parentOrder->getDetail()->getStoreId());
        $orderInformationValues = [
            ['label' => __('商品總額')->render(),
                'value' => $subTotal, '' => ''],
            [
                'label' => __('運費')->render(),
                'value' => $shipping,
                'hasIcon' => true,
                'icon' => __('元')
            ],
            [
                'label' => __('點數折抵')->render(),
                'value' => $pointUsedTotal > 0 ? '-' . number_format($pointUsedTotal, 0, '.', ',') : 0,
                'hasIcon' => true,
                'icon' => __('點')
            ],
//            [
//                'label' => __('Event Discount')->render(),
//                'value' => $discount > 0 ? -$discount : 0,
//                'hasIcon' => true,
//                'icon' => __('點')
//            ],
            [
                'label' => __('實付⾦額')->render(),
                'value' => $grandTotal,
                'color' => $this->redColor,
                'hasIcon' => true,
                'icon' => __('元')
            ],
            ['label' => __('⽀付⽅式')->render(), 'value' => $paymentMethodTitle]
        ];
        $values = array_map(function ($item) {
            return $item['value'];
        }, $orderInformationValues);
        $orderSectionHeight = $this->_calcAddressHeight($values);
        $shippingMethod = '';
        if ($billingAddress) {
            $shippingMethod = $parentOrder->getDetail()->getShippingMethod();
        }
        $page = $this->offsetY(10, $page);
        $this->y = $this->y ?: 815;
        $top = $this->y + 7;
        $page = $this->offsetY(10, $page);
        $this->_setFontRegular($page, 9);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $page->drawText(__('收件者/物流資訊'),
            42, $this->y, 'UTF-8'
        );
        $page->drawText(
            __('結帳資訊'),
            385, $this->y + 8, 'UTF-8'
        );
        //
        $addressLine = '';
        if ($billingAddress) {
            $addressLine = implode('  /  ', [
                'name' => $this->maskRuleComposite->mask('name', $billingAddress->getName()),
                'phone' => $this->maskRuleComposite->mask('phone', $billingAddress->getTelephone()),
                'address' => $this->maskRuleComposite->mask('address', implode('\n', $billingAddress->getStreet()))
            ]);
        }

        $page = $this->offsetY(-11, $page);
        $lines = [];

        if ($shippingMethod) {
            $shippingMethodTxt = '';
            if ($shippingMethod == 'hotai_delivery_hotai_delivery') {
                $shippingMethodTxt = '宅配資訊';
            }
            if ($shippingMethod == 'hotai_711_hotai_711') {
                $shippingMethodTxt = '超商取貨';
            }
            $lines[] = [
                'text' => $this->string->split($shippingMethodTxt, 45),
                'feed' => 42,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
            ];
        }

        $lines[] = [
            'text' => $this->string->split($addressLine, 30),
            'feed' => 355,
            'align' => 'right',
            'font_size' => 8,
            'font' => 'regular',
        ];

        $lineBlock = ['lines' => [$lines], 'height' => 15];
        $this->drawLineBlocks($page, [$lineBlock]);
        // draw horizontal line
        $page->setFillColor($this->lineGrayColor);
        $page->drawRectangle(
            370,
            $top,
            372,
            $top - 10 - $orderSectionHeight
        );
        // draw total block
        $page->setFillColor($this->blackColor);
        $this->y = $top - 25;
        //  $top -= 25;
        $lines = $lineLabels = $lineValues = [];
        foreach ($orderInformationValues as $key => $info) {
            $page->setFillColor($this->blackColor);
            $lines = [];
            $lines[] = [
                'text' => $this->string->split($info['label'], 30),
                'feed' => 385,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
            ];
            $lines[] = [
                'text' => $this->string->split($info['value'], 30),
                'feed' => isset($info['hasIcon']) ? $page->getWidth() - 50 : $page->getWidth() - 40,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'regular',
                'width' => 20,
                'color' => $info['color'] ?? $this->blackColor
            ];
            if (isset($info['hasIcon'])) {
                $lines[] = [
                    'text' => $info['icon'],
                    'feed' => $page->getWidth() - 40,
                    'align' => 'right',
                    'font_size' => 8,
                    'font' => 'regular',
                    'width' => 20
                ];
            }
            $lineBlock = ['lines' => [$lines], 'height' => 15];
            $this->drawLineBlocks($page, [$lineBlock]);
        }
        return $page;
    }

    /**
     * Insert logo to pdf page
     *
     * @param \Zend_Pdf_Page $page
     * @param string|null $store
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @throws \Zend_Pdf_Exception
     */
    protected function insertLogo(&$page, $store = null)
    {
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        $this->y = $this->y ?: 815;
        $image = $this->_scopeConfig->getValue(
            'sales/identity/logo',
            ScopeInterface::SCOPE_STORE,
            $store
        );
        $x1 = 28;
        if ($image) {
            $imagePath = '/sales/store/logo/' . $image;
            if ($this->fileStorageDatabase->checkDbUsage() &&
                !$this->_mediaDirectory->isFile($imagePath)
            ) {
                $this->fileStorageDatabase->saveFileToFilesystem($imagePath);
            }

            if ($this->_mediaDirectory->isFile($imagePath)) {
                $image = $this->image->imageWithPathAdvanced($this->_mediaDirectory->getAbsolutePath($imagePath));
                $width = 114;
                $height = 23;
                $top = 836;
                //preserving aspect ratio (proportions)
                $y1 = $top - $height;
                $y2 = $top;
                $x2 = $x1 + $width;
                //coordinates after transformation are rounded by Zend
                $page->drawImage($image, $x1, $y1, $x2, $y2);
                $this->y = $y1 - 10;
            }
            $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0.4));
            $this->_setFontBold($page, 14);
            $page->drawText(
                __('Proof Purchase Receipt')->render(),
                $x2 + 4, $y1 + 7, 'UTF-8'
            );
        }
    }

    /**
     * @param $page
     * @param $seller
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function drawSubOrderHeaders($page, $seller, \Magento\Sales\Model\Order $order)
    {
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        if (isset($this->shopTitle[$seller->getId()])) {
            $shopTitle = $this->shopTitle[$seller->getId()];
        } else {
            $shopTitle = $seller->getFirstname() . ' ' . $seller->getLastname();
        }
        $incrementId = str_replace('hotai_order_', '', $order->getIncrementId());
        $shopTitles = [];
        $limit = 12;
        if (($length = mb_strlen($shopTitle, 'UTF-8')) > $limit) {
            $shopTitles[] = mb_substr($shopTitle, 0, $limit);
            $shopTitles[] = mb_substr($shopTitle, $limit + 1, $length);
        } else {
            $shopTitles[] = $shopTitle;
        }
        $breakLine = count($shopTitles) > 1;
        if ($breakLine) {
            foreach (($shopTitles) as $sub) {
                $lines[] = [
                    'text' => $sub,
                    'feed' => 45,
                    'align' => 'left',
                    'font_size' => 8,
                    'font' => 'regular',
                    'color' => $this->redColor
                ];
                $lineBlock = ['lines' => [$lines], 'height' => 10];
                $this->drawLineBlocks($page, [$lineBlock]);
                $this->y -= 3;
                $lines = [];
            }
            $this->y = $this->y + (2 * 10) + 3;
        }
        $lines = [
            [
                'text' => $incrementId,
                'feed' => 150,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => __('Item Specification'),
                'feed' => 290,
                'align' => 'center',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => __('Unit Price'),
                'feed' => 393,
                'align' => 'center',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => __('Quantity'),
                'feed' => 462,
                'align' => 'center',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => __('合計'),
                'feed' => $page->getWidth() - 36,
                'align' => 'right',
                'font_size' => 8,
                'color' => $this->blackColor
            ]
        ];
        if (!$breakLine) {
            array_unshift($lines, [
                'text' => $shopTitles[0],
                'feed' => 42,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->redColor
            ]);
        }
        $lineBlock = ['lines' => [$lines], 'height' => 15];
        $this->drawLineBlocks($page, [$lineBlock]);

        return $page;
    }

    /**
     * @param $page
     * @param $seller
     * @param \Magento\Sales\Model\Order $order
     * @return mixed
     */
    private function drawSuborderFooter($page, $seller, \Magento\Sales\Model\Order $order)
    {
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        // draw from right to left;
        $x = $page->getWidth() - 36;
        $totalPointUsed = (int)$order->getPointUsedTotal();
        $discount = (int)$order->getDiscountAmount();
        $freight = $order->formatPriceTxt($order->getShippingInclTax());
        $freightLength = $this->widthForStringUsingFontSize($freight, $this->_setFontRegular($page), 8);
        $feedFreight = $x - $freightLength - 1;

        $freightTxtLength = $this->widthForStringUsingFontSize(__('Freight'), $this->_setFontRegular($page), 8);
        $feedFreightTxt = $feedFreight - $freightTxtLength - 5;

        $subTotal = $order->formatPriceTxt($order->getSubtotalInclTax() - $totalPointUsed - $discount);
        $subTotalLength = $this->widthForStringUsingFontSize($subTotal, $this->_setFontRegular($page), 8);
        $feedSubtotal = $feedFreightTxt - 10;
        $totalQuantityOrdered = (int)$order->getTotalQtyOrdered();
        $lines = [
            [
                'text' => $freight,
                'feed' => $x,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->redColor
            ],
            [
                'text' => __('Freight'),
                'feed' => $feedFreight,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => '+',
                'feed' => $feedFreightTxt,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => $subTotal,
                'feed' => $feedSubtotal,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->redColor
            ],
        ];
        $pointTotalLength = 0;
        if ($totalPointUsed) {
            $plusPointLength = $this->widthForStringUsingFontSize('+', $this->_setFontRegular($page), 8);
            $feedPointPlus = $feedSubtotal - $subTotalLength - 5;
            $lines[] = [
                'text' => '+',
                'feed' => $feedPointPlus,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ];
            $pointText = __('P%1', $totalPointUsed);
            $pointTextLength = $this->widthForStringUsingFontSize($pointText, $this->_setFontRegular($page), 8);
            $feedPointValue = $feedPointPlus - $plusPointLength - 5;
            $lines[] = [
                'text' => $pointText,
                'feed' => $feedPointValue,
                'align' => 'right',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->redColor
            ];
            $pointTotalLength = $pointTextLength + $plusPointLength + 10;
        }
        $qtyX = $feedSubtotal - $subTotalLength - $pointTotalLength - 30;
        $widthQty = $this->widthForStringUsingFontSize($totalQuantityOrdered, $this->_setFontRegular($page), 8);

        $lines[] = [
            'text' => __(')'),
            'feed' => $qtyX,
            'align' => 'right',
            'font_size' => 8,
            'font' => 'bold',
            'color' => $this->blackColor
        ];

        $lines[] = [
            'text' => $totalQuantityOrdered,
            'feed' => $qtyX - 9,
            'align' => 'right',
            'font_size' => 8,
            'font' => 'bold',
            'color' => $this->redColor,
        ];

        $lines[] = [
            'text' => __('('),
            'feed' => $qtyX - 9 - $widthQty - 2,
            'align' => 'right',
            'font_size' => 8,
            'font' => 'bold',
            'color' => $this->blackColor
        ];
        $lines[] = [
            'text' => __('Small Plan'),
            'feed' => $qtyX - 9 - $widthQty - 3 - 4,
            'align' => 'right',
            'font_size' => 8,
            'color' => $this->blackColor
        ];

        $lineBlock = ['lines' => [$lines], 'height' => 15];
        $this->drawLineBlocks($page, [$lineBlock]);
        return $page;
    }

    /**
     * @param $sellerIds
     * @return array
     */
    private function getShopTitle($sellerIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from('marketplace_userdata',
            ['seller_id', 'company_name'])->where('seller_id IN (?)', $sellerIds);
        return $connection->fetchPairs($select);
    }

    /**
     * @param \Zend_Pdf $pdf
     * @param $page
     * @param ParentOrder $parentOrder
     * @return false|\Zend_Pdf_Canvas_Abstract|\Zend_Pdf_Page
     * @throws \Zend_Pdf_Exception
     */
    private function drawSubOrders(\Zend_Pdf $pdf, &$page, ParentOrder $parentOrder)
    {
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        $lineX1 = 28;
        $lineX2 = $page->getWidth() - 28;
        $sellerOrders = $this->subOrderFinder->find($parentOrder);
        if ($sellerOrders) {
            $sellerIds = array_keys($sellerOrders);
            $this->shopTitle = $this->getShopTitle($sellerIds);
        }
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        foreach ($sellerOrders as $sellerId => $sellerOrderInfo) {
            /**
             * @var $seller \Magento\Customer\Model\Data\Customer
             */
            $seller = $sellerOrderInfo['seller'];
            $orders = $sellerOrderInfo['orders'];
            /* $last = count($orders - 1);*/
            foreach ($orders as $key => $order) {
                if ($order->getData('is_flagship_store_process_order')) {
                    continue;
                }
                $this->y = $this->y - 20;
                $page = $this->drawSubOrderHeaders($page, $seller, $order);
                ///draw Items
                $page = $this->offsetY(20, $page);
                /**
                 * @var $item \Magento\Sales\Model\Order\Item
                 */
                foreach ($order->getAllItems() as $item) {
                    if ($item->getParentItem()) {
                        continue;
                    }
                    /* Draw item */
                    $this->_drawItem($item, $page, $order);
                    $page = end($pdf->pages);
                }
                $page = $this->offsetY(10, $page);
                $page = end($pdf->pages);
                /** begin draw separate line */
                $page->setFillColor($this->lineGrayColor);
                $page->setLineColor($this->lineGrayColor);

                $page->setLineWidth(0.2);
                $page->setLineColor($this->lineGrayColor);
                $page->drawLine($lineX1, $this->y - 4, $page->getWidth() - 28, $this->y - 4);

                $page = $this->offsetY(25, $page);
                $page = end($pdf->pages);
                /** end draw seperate line **/
                // draw suborder footer
                $page = $this->drawSuborderFooter($page, $seller, $order);
                $page = end($pdf->pages);
                // separate line
                $this->y += 3;
                $page->setLineColor(new \Zend_Pdf_Color_GrayScale(1));
                $page->setFillColor($this->lineGrayColor);
                $page->setFillColor($this->lineGrayColor);
                $page->drawRectangle($lineX1, $this->y, $lineX2, $this->y -= 5);
                $this->y -= 10;
            }
        }

        return $page;
    }

    /**
     * @param $amount
     * @param $page
     * @return mixed|\Zend_Pdf_Page
     */
    private function offsetY($amount, $page)
    {
        $predict = $this->y - $amount;
        $limit = 15;
        if ($predict < $limit) {
            $page = $this->newPage();
            $this->y = 800;
        } else {
            $this->y = $predict;
        }
        return $page;
    }

    /**
     * Draw Item process
     *
     * @param \Magento\Framework\DataObject $item
     * @param \Zend_Pdf_Page $page
     * @param \Magento\Sales\Model\Order $order
     * @return \Zend_Pdf_Page
     */
    protected function _drawItem(
        \Magento\Framework\DataObject $item,
        \Zend_Pdf_Page                $page,
        \Magento\Sales\Model\Order    $order
    )
    {
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        $type = $item->getProductType();
        $renderer = $this->_getRenderer($type);
        $renderer->setOrder($order);
        $renderer->setItem($item);
        $renderer->setPdf($this);
        $renderer->setPage($page);
        $renderer->setRenderedModel($this);
        $renderer->draw();
        return $renderer->getPage();
    }

    /**
     * @param $page
     * @param ParentOrder $parentOrder
     * @return \Zend_Pdf_Canvas_Abstract
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Pdf_Exception
     */
    private function drawParentOrderSection($page, ParentOrder $parentOrder)
    {
        $this->y = $this->y - 4;
        $twId = $this->getCustomerId($parentOrder);
        $name = $this->maskRuleComposite->mask('name',
            (string)$parentOrder->getDetail()->getCustomerName()
        );
        /**
         * @var $page \Zend_Pdf_Canvas_Abstract
         */
        /**************
         * BEGIN DRAW PARENT ORDER NUMBER SECTION
         *************/
        $x = 30;
        $orderCreated = $parentOrder->getDetail()->getCreatedAt();
        $orderCreatedFormated = $this->_localeDate->date($orderCreated)->format('Y/m/d H:i:s');
        $lines = [
            [
                'text' => '訂單主編號',
                'feed' => 42,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => $parentOrder->getDetail()->getIncrementId(),
                'feed' => 90,//150
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => '|',
                'feed' => 180,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => __('訂購完成⽇'),
                'feed' => 190,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ],
            [
                'text' => $orderCreatedFormated,
                'feed' => 237,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => '|',
                'feed' => 322,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ],
            [
                'text' => __('Buyer'),
                'feed' => 334,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'bold',
                'color' => $this->blackColor
            ]
        ];
        if ($twId) {
            $lines[] = [
                'text' => __('%1 (Member ID : %2)', $name, $twId),
                'feed' => 365,
                'align' => 'left',
                'font_size' => 8,
                'font' => 'regular',
                'color' => $this->blackColor
            ];
        }
        $lineBlock = ['lines' => [$lines], 'height' => 11];
        $this->drawLineBlocks($page, [$lineBlock]);
        $this->_setFontRegular($page, 5);
        //draw separate line;
        $page->setLineWidth(0.2);
        $page->setLineColor($this->topBottomGreyLine);
        $page->drawLine(28, $this->y - 4, $page->getWidth() - 28, $this->y - 4);
        /**************
         * END DRAW PARENT ORDER NUMBER
         *************/
        return $page;
    }

    /**
     * Create new page and assign to PDF object
     *
     * @param array $settings
     * @return \Zend_Pdf_Page
     */
    public function newPage(array $settings = [])
    {
        /* Add new table head */
        $page = $this->_getPdf()->newPage(\Zend_Pdf_Page::SIZE_A4);
        $this->_getPdf()->pages[] = $page;
        $this->y = 800;
        if (!empty($settings['table_header'])) {
            $this->_drawHeader($page);
        }
        if (!$page->getFont()) {
            $this->_setFontBold($page, 10);
        }
        return $page;
    }

    /**
     * Draw lines
     *
     * Draw items array format:
     * lines        array;array of line blocks (required)
     * shift        int; full line height (optional)
     * height       int;line spacing (default 10)
     *
     * line block has line columns array
     *
     * column array format
     * text         string|array; draw text (required)
     * feed         int; x position (required)
     * font         string; font style, optional: bold, italic, regular
     * font_file    string; path to font file (optional for use your custom font)
     * font_size    int; font size (default 7)
     * align        string; text align (also see feed parameter), optional left, right
     * height       int;line spacing (default 10)
     *
     * @param \Zend_Pdf_Page $page
     * @param array $draw
     * @param array $pageSettings
     * @return \Zend_Pdf_Page
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function drawLineBlocks(\Zend_Pdf_Page $page, array $draw, array $pageSettings = [])
    {
        $this->pageSettings = $pageSettings;
        foreach ($draw as $itemsProp) {
            if (!isset($itemsProp['lines']) || !is_array($itemsProp['lines'])) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('We don\'t recognize the draw line data. Please define the "lines" array.')
                );
            }
            $lines = $itemsProp['lines'];
            $height = isset($itemsProp['height']) ? $itemsProp['height'] : 10;
            if (empty($itemsProp['shift'])) {
                $shift = 0;
                foreach ($lines as $line) {
                    $maxHeight = 0;
                    foreach ($line as $column) {
                        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
                        if (!is_array($column['text'])) {
                            $column['text'] = [$column['text']];
                        }
                        $top = 0;
                        //
                        foreach ($column['text'] as $part) {
                            $top += $lineSpacing;
                        }

                        $maxHeight = $top > $maxHeight ? $top : $maxHeight;
                    }
                    $shift += $maxHeight;
                }
                $itemsProp['shift'] = $shift;
            }

            if ($this->y - $itemsProp['shift'] < 40) {
                $page = $this->newPage($pageSettings);
            }
            $this->correctLines($lines, $page, $height);
        }

        return $page;
    }

    /**
     * Correct lines.
     *
     * @param array $lines
     * @param \Zend_Pdf_Page $page
     * @param int $height
     * @throws \Zend_Pdf_Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function correctLines($lines, $page, $height): void
    {
        foreach ($lines as $line) {
            $maxHeight = 0;
            foreach ($line as $column) {
                $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
                if (!empty($column['font_file'])) {
                    $font = \Zend_Pdf_Font::fontWithPath($column['font_file']);
                    $page->setFont($font, $fontSize);
                } else {
                    $fontStyle = empty($column['font']) ? 'regular' : $column['font'];
                    switch ($fontStyle) {
                        case 'bold':
                            $font = $this->_setFontBold($page, $fontSize);
                            break;
                        case 'italic':
                            $font = $this->_setFontItalic($page, $fontSize);
                            break;
                        default:
                            $font = $this->_setFontRegular($page, $fontSize);
                            break;
                    }
                }

                if (!is_array($column['text'])) {
                    $column['text'] = [$column['text']];
                }
                $top = $this->correctText($column, $height, $font, $page);

                $maxHeight = $top > $maxHeight ? $top : $maxHeight;
            }
            $this->y -= $maxHeight;
        }
    }

    /**
     * @param $column
     * @param $height
     * @param $font
     * @param $page
     * @return int
     * @throws \Zend_Pdf_Exception
     */
    private function correctText($column, $height, $font, $page): int
    {
        $top = 0;
        $lineSpacing = !empty($column['height']) ? $column['height'] : $height;
        $fontSize = empty($column['font_size']) ? 10 : $column['font_size'];
        foreach ($column['text'] as $part) {
            if ($this->y - $top < 15) {
                $page = $this->newPage($this->pageSettings);
                $top = 0;
            }

            $feed = $column['feed'];
            $textAlign = empty($column['align']) ? 'left' : $column['align'];
            $width = empty($column['width']) ? 0 : $column['width'];
            switch ($textAlign) {
                case 'right':
                    if ($width) {
                        $feed = $this->getAlignRight($part, $feed, $width, $font, $fontSize);
                    } else {
                        $feed = $feed - $this->widthForStringUsingFontSize($part, $font, $fontSize);
                    }
                    break;
                case 'center':
                    if ($width) {
                        $feed = $this->getAlignCenter($part, $feed, $width, $font, $fontSize);
                    }
                    break;
                default:
                    break;
            }
            if (isset($column['color'])) {
                $page->setFillColor($column['color']);
            } else {
                $page->setFillColor($this->blackColor);
            }
            $page->drawText($part, $feed, $this->y - $top, 'UTF-8');
            $top += $lineSpacing;
        }
        return $top;
    }

    /**
     * @param string $code
     * @param int|null $storeId
     * @return string
     */
    private function getMethodStoreTitle(string $code, ?int $storeId = null): string
    {
        $configPath = sprintf('%s/%s/title', 'payment', $code);
        return (string)$this->_scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param $object
     * @param $size
     * @return \Zend_Pdf_Resource_Font|\Zend_Pdf_Resource_Font_OpenType_TrueType|\Zend_Pdf_Resource_Font_Simple_Parsed_TrueType|\Zend_Pdf_Resource_Font_Simple_Standard_Courier|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBold|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_CourierOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Helvetica|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBold|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Symbol|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBold|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBoldItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesRoman|\Zend_Pdf_Resource_Font_Simple_Standard_ZapfDingbats|\Zend_Pdf_Resource_Font_Type0
     * @throws \Zend_Pdf_Exception
     */
    protected function _setFontRegular($object, $size = 7)
    {
        $locales = KanJiLocale::getKanJiLocale();
        $localCheck = $this->getLocaleResolver()->getLocale();
        if (in_array($localCheck, $locales)) {
            $font = \Zend_Pdf_Font::fontWithPath(
                $this->useFontSubSet ?
                    $this->subsetFontGenerator->getSubsetFontPath(SubsetFontGenerator::REGULAR) :
                    $this->_rootDirectory->getAbsolutePath('extra-font/NotoSans/NotoSansCJKtc-Regular.ttf')
            );
            $object->setFont($font, $size);
            return $font;
        }
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * @param $object
     * @param $size
     * @return \Zend_Pdf_Resource_Font|\Zend_Pdf_Resource_Font_OpenType_TrueType|\Zend_Pdf_Resource_Font_Simple_Parsed_TrueType|\Zend_Pdf_Resource_Font_Simple_Standard_Courier|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBold|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_CourierOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Helvetica|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBold|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Symbol|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBold|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBoldItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesRoman|\Zend_Pdf_Resource_Font_Simple_Standard_ZapfDingbats|\Zend_Pdf_Resource_Font_Type0
     * @throws \Zend_Pdf_Exception
     */
    protected function _setFontBold($object, $size = 7)
    {
        $locales = KanJiLocale::getKanJiLocale();
        $localCheck = $this->getLocaleResolver()->getLocale();
        if (in_array($localCheck, $locales)) {
            $font = \Zend_Pdf_Font::fontWithPath(
                $this->useFontSubSet ?
                    $this->subsetFontGenerator->getSubsetFontPath(SubsetFontGenerator::BOLD) :
                    $this->_rootDirectory->getAbsolutePath('extra-font/NotoSans/NotoSansHK-Medium.ttf')
            );
            $object->setFont($font, $size);
            return $font;
        }
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES_BOLD);
        $object->setFont($font, $size);
        return $font;
    }

    /**
     * @param $object
     * @param $size
     * @return \Zend_Pdf_Resource_Font|\Zend_Pdf_Resource_Font_OpenType_TrueType|\Zend_Pdf_Resource_Font_Simple_Parsed_TrueType|\Zend_Pdf_Resource_Font_Simple_Standard_Courier|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBold|\Zend_Pdf_Resource_Font_Simple_Standard_CourierBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_CourierOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Helvetica|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBold|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaBoldOblique|\Zend_Pdf_Resource_Font_Simple_Standard_HelveticaOblique|\Zend_Pdf_Resource_Font_Simple_Standard_Symbol|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBold|\Zend_Pdf_Resource_Font_Simple_Standard_TimesBoldItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesItalic|\Zend_Pdf_Resource_Font_Simple_Standard_TimesRoman|\Zend_Pdf_Resource_Font_Simple_Standard_ZapfDingbats|\Zend_Pdf_Resource_Font_Type0
     * @throws \Zend_Pdf_Exception
     */
    protected function _setFontItalic($object, $size = 7)
    {
        $locales = KanJiLocale::getKanJiLocale();
        $localCheck = $this->getLocaleResolver()->getLocale();
        if (in_array($localCheck, $locales)) {
            $font = \Zend_Pdf_Font::fontWithPath(
                $this->useFontSubSet ?
                    $this->subsetFontGenerator->getSubsetFontPath(SubsetFontGenerator::ITALIC) :
                    $this->_rootDirectory->getAbsolutePath('extra-font/NotoSans/SourceHanSerifCN-Regular.ttf')
            );
            $object->setFont($font, $size);
            return $font;
        }
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_TIMES_ITALIC);
        $object->setFont($font, $size);
        return $font;
    }
}
