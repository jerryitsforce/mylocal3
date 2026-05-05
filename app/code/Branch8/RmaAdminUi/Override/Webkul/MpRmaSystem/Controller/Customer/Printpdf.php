<?php

namespace Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Printpdf extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Model\Pdf
     */
    protected $pdf;

    /**
     * @var CustomLogger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param \Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Model\Pdf $pdf
     * @param CustomLogger $logger
     */
    public function __construct(
        Context                                                   $context,
        \Branch8\RmaAdminUi\Override\Webkul\MpRmaSystem\Model\Pdf $pdf,
        CustomLogger                                              $logger
    )
    {
        $this->pdf = $pdf;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * Print Pdf Action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $rmaId = $this->getRequest()->getParam('rma_id');
        try {
            return $this->pdf->generatePdf($rmaId);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
