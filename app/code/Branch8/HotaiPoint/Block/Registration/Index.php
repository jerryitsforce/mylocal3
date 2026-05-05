<?php

namespace Branch8\HotaiPoint\Block\Registration;

use Branch8\HotaiPoint\Block\Detail\AbstractBlock;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Data as PointHelper;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointHistory\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Index extends AbstractBlock 
{
    /**
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @var PointHelper
     */
    protected $pointHelper;

    /**
     * @param ApiHelper $apiHelper
     * @param Session $customerSession
     * @param PointHelper $pointHelper
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        ApiHelper         $apiHelper,
        Session           $customerSession,
        PointHelper       $pointHelper,
        Context           $context,
        array             $data = []
    ) {
        $this->apiHelper = $apiHelper;
        $this->customerSession = $customerSession;
        $this->pointHelper = $pointHelper;

        parent::__construct($context, $pointHelper, $data);
    }

    public function getRegistrationStaticContent() {
        $blockIdentify = $this->pointHelper->getPointRegistrationStaticContentIdentity();
        return $blockIdentify
            ? $this->getLayout()
                ->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($blockIdentify)
                ->toHtml()
            : '';
    }

    /**
     * Get the URL for the QR code
     *
     * @param string $content
     * @return string
     */
    public function getQRCodeUrl($content = '')
    {
        if (!$content) {
            return $this->_urlBuilder->getUrl('hotai_core/barcode/display', [
                "barcode_content" => $this->getRegistrationUrl(),
                "barcode_type"    => \Branch8\HotaiCore\Model\Product\BarcodeType::TYPE_QRCODE,
            ]);
        }

        return $this->_urlBuilder->getUrl('hotai_core/barcode/display', [
            "barcode_content" => $content,
            "barcode_type"    => \Branch8\HotaiCore\Model\Product\BarcodeType::TYPE_QRCODE,
        ]);
    }
    
    /**
     * Get the Registration URL
     *
     * @return string
     */
    public function getRegistrationUrl() {
        return $this->_urlBuilder->getUrl('hotai_point/registration');
    }
}
