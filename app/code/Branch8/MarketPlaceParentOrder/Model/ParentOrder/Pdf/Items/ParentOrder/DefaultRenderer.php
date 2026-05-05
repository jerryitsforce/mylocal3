<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\Items\ParentOrder;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\PurchaseReceipt;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\RtlTextHandler;

/**
 * Sales Parent Order Item Pdf default items renderer
 */
class DefaultRenderer extends \Magento\Sales\Model\Order\Pdf\Items\AbstractItems
{
    const SKU_COLOR = '#adaaad';
    /**
     * Core string
     *
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    protected $string;

    /**
     * @var RtlTextHandler
     */
    private $rtlTextHandler;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filter\FilterManager $filterManager
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @param RtlTextHandler|null $rtlTextHandler
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        \Magento\Tax\Helper\Data                                $taxData,
        \Magento\Framework\Filesystem                           $filesystem,
        \Magento\Framework\Filter\FilterManager                 $filterManager,
        \Magento\Framework\Stdlib\StringUtils                   $string,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = [],
        ?RtlTextHandler                                         $rtlTextHandler = null
    )
    {
        $this->string = $string;
        parent::__construct(
            $context,
            $registry,
            $taxData,
            $filesystem,
            $filterManager,
            $resource,
            $resourceCollection,
            $data
        );
        $this->rtlTextHandler = $rtlTextHandler ?: ObjectManager::getInstance()->get(RtlTextHandler::class);
    }

    /**
     * Draw item line
     *
     * @return void
     */
    public function draw()
    {
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         * @var $rendererModel \Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\PurchaseReceipt
         */
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $price = $item->getPriceInclTax();
        $rendererModel = $this->getRenderedModel();
        $totalItemPointUsed = (int)$item->getRowTotalPointUsed();
        $rowTotalIncludeTax = $item->getRowTotalInclTax();
        $weight = (float)$item->getRowWeight();
        //$weight=0;
        $pointUsed = number_format((float)$item->getRowTotalPointUsed(), 0, '.', ',');
        /**
         * $rowSubTotal = (int)$row['row_total_incl_tax'] - (int)$row['row_total_point_used'] - (int)$row['discount_amount'];
         */
        $rowSubTotal = (int)$item->getRowTotalInclTax() - (int)$item->getRowTotalPointUsed() - (int)$item->getDiscountAmount();
        $options = $this->getItemOptions();
        $optionText = [];
        if ($this->getItemOptions()) {
            foreach ($options as $option) {
                $optionText[] = $option['value'];
            }
        }
        if ($totalItemPointUsed) {
            $rowTotalText = __('P%1 + %2', $pointUsed, $order->formatPriceTxt($rowSubTotal));
        } else {
            $rowTotalText = __('%1', $order->formatPriceTxt($rowTotalIncludeTax));
        }
        if ($pointUsed) {
            $priceText = __('P%1 + %2', $pointUsed, $order->formatPriceTxt($rowSubTotal));
        } else {
            $priceText = __('%1', $order->formatPriceTxt($price));
        }
        $lines = [];
        $i = 0;

        /// increase 1 line name +weight text should be same line
        $lines[$i][] = [
            'text' => $this->string->split(
                $this->prepareText((string)$item->getName()),
                14, false, true
            ),
            'feed' => 46,
            'font' => 'regular'
        ];

        $lines[$i][] = [
            'text' => $this->string->split('/' . $this->getSku($item), 17, true),
            'feed' => 180,
            'align' => 'left',
            'font' => 'regular',
            'font_size' => 8,
            'color' => new \Zend_Pdf_Color_Html(self::SKU_COLOR)
        ];
        $lines[$i][] = [
            'text' => $this->string->split(join(',', $optionText), 10, true),
            'feed' => 290,
            'align' => 'left',
            'font' => 'regular',
            'font_size' => 8,
        ];
        // draw item Prices
        $lines[$i][] = [
            'text' => $priceText,
            'feed' => 385,
            'align' => 'center',
            'font' => 'regular',
            'font_size' => 8,
        ];
        // draw quantity
        $lines[$i][] = [
            'text' => $item->getQtyOrdered() * 1,
            'feed' => 468,
            'align' => 'center',
            'font' => 'regular',
            'font_size' => 8,
        ];
        // draw total
        $lines[$i][] = [
            'text' => $rowTotalText,
            'feed' => $page->getWidth() - 36,
            'align' => 'right',
            'font' => 'regular',
            'font_size' => 8,
        ];
        $i++;
        if ($weight) {
            $weightText = $weight > 1 ? __('%1 kg', $weight) : __('%1 kgs', $weight);
            $lines[++$i][] = [
                'text' => $weightText,
                'font' => 'regular',
                'feed' => 46,
                'font_size' => 8,
            ];
        }
        $lineBlock = ['lines' => $lines, 'height' => 20, 'shift' => 5];
        $page = $pdf->drawLineBlocks($page, [$lineBlock]);
        $this->setPage($page);
    }

    /**
     * Returns prepared for PDF text, reversed in case of RTL text
     *
     * @param string $string
     * @return string
     */
    private function prepareText(string $string): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return $this->rtlTextHandler->reverseRtlText(html_entity_decode($string));
    }

    /**
     * Return item Sku
     *
     * @param mixed $item
     * @return mixed
     */
    public function getSku($item)
    {
        if ($item instanceof \Magento\Sales\Model\Order\Item) {
            return $item->getSku();
        } else if ($item->getOrderItem()->getProductOptionByCode('simple_sku')) {
            return $item->getOrderItem()->getProductOptionByCode('simple_sku');
        }
    }

    /**
     * Retrieve item options
     *
     * @return array
     */
    public function getItemOptions()
    {
        $result = [];
        /**
         *
         */
        if ($this->getItem() instanceof \Magento\Sales\Model\Order\Item) {
            $options = $this->getItem()->getProductOptions();
        } else {
            $options = $this->getItem()->getOrderItem()->getProductOptions();
        }
        if ($options) {
            if (isset($options['options'])) {
                $result[] = $options['options'];
            }
            if (isset($options['additional_options'])) {
                $result[] = $options['additional_options'];
            }
            if (isset($options['attributes_info'])) {
                $result[] = $options['attributes_info'];
            }
        }
        return array_merge([], ...$result);
    }
}
