<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Controller\Adminhtml\Report\Summarize;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Controller for the 'reports/report_sumarize/sold' URL route.
 */
class Sold extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceOrderExportSeller::seller_summarize_report';

    /**
     * Execute controller action.
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        /**
         * @var $resultPage \Magento\Framework\View\Result\Page
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->getConfig()->getTitle()->prepend(__('Report Sales Summarize'));
        $content = $resultPage->getLayout()->createBlock(
            \Branch8\MarketPlaceOrderExportSeller\Block\Adminhtml\Reports\ReportForm::class,
            'report_form',
            ['data' => ['action' => $this->getUrl('reports/report_summarize/save')]]
        );
        $resultPage->addContent($content);
        $resultPage->addBreadcrumb(__('Reports'), __('Reports'));
        return $resultPage;
    }
}
