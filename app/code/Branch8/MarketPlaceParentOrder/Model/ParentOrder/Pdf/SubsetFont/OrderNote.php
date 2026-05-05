<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/03/2026
 */

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Pdf\SubsetFont;

use Branch8\EmailNotification\Helper\ScopeConfig;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;

class OrderNote implements TextExtractorInterface
{
    const XML_PATH_SALES_PDF_ORDER_NOTE = 'sales_pdf/order/note';
    const XML_PATH_SALES_PDF_ORDER_FOOTER_NOTE = 'sales_pdf/order/footer_note';

    const XML_PATH_COPY_RIGHT_TEXT = 'design/footer/copyright';
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    )
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return string[]
     */
    public function extract(ParentOrder $parentOrder)
    {
        $orderNote = (string)$this->scopeConfig->getValue(self::XML_PATH_SALES_PDF_ORDER_NOTE);
        $footerNote = trim((string)$this->scopeConfig->getValue(self::XML_PATH_SALES_PDF_ORDER_FOOTER_NOTE));
        $copyRight = trim((string)$this->scopeConfig->getValue(self::XML_PATH_COPY_RIGHT_TEXT));
        return [
            $orderNote,
            $footerNote,
            $copyRight,
            '•'
        ];
    }
}
