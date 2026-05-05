<?php

namespace Branch8\HotaiPoint\Block\Transfer;

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

    public function getMaximumPointTransfer()
    {
        $result = $this->apiHelper->requestApiGetTransInfo($this->customerSession->getCustomerId());

        if ($this->apiHelper->getReturnCodeFromResponse($result) == $this->apiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
            return 0;
        }

        $data = $this->apiHelper->getDataFromResponse($result);

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/points.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(print_r(json_encode($data), true));

        return $this->apiHelper->getPointFromResponse($result);
    }

    public function getTransferStaticContent() {
        $blockIdentify = $this->pointHelper->getPointTransferStaticContentIdentity();
        return $blockIdentify
            ? $this->getLayout()
                ->createBlock('Magento\Cms\Block\Block')
                ->setBlockId($blockIdentify)
                ->toHtml()
            : '';
    }
}
