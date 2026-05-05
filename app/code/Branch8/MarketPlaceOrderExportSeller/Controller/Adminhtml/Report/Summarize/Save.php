<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportSeller\Controller\Adminhtml\Report\Summarize;

use Branch8\MarketPlaceOrderExportSeller\Model\Actions\MonthlySummarizeReport;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Controller for the 'reports/report_detail/sold' URL route.
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceOrderExportSeller::seller_summarize_report';

    private \Magento\Framework\Controller\Result\RawFactory $resultRawFactory;

    private \Magento\Framework\App\Response\Http\FileFactory $fileFactory;

    private \Magento\Framework\Filesystem\DirectoryList $dir;

    private MonthlySummarizeReport $monthlySummarizeReport;

    /**
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param MonthlySummarizeReport $monthlySummarizeReport
     * @param Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RawFactory  $resultRawFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem\DirectoryList      $dir,
        MonthlySummarizeReport                           $monthlySummarizeReport,
        \Magento\Backend\App\Action\Context              $context

    )
    {
        $this->monthlySummarizeReport = $monthlySummarizeReport;
        $this->resultRawFactory = $resultRawFactory;
        $this->fileFactory = $fileFactory;
        $this->dir = $dir;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $from = $this->_request->getParam('from_date') . ' 00:00:00';
            $to = $this->_request->getParam('to_date') . ' 23:59:59';
            $sellerCode = $this->_request->getParam('seller_id');
            $filePath = $this->monthlySummarizeReport->execute($from, $to, $sellerCode);
            $name = pathinfo($filePath, PATHINFO_FILENAME) . '.xlsx';
            $content['type'] = 'filename';
            $content['value'] = $filePath;
            $content['rm'] = 0;
            return $this->fileFactory->create(
                $name,
                $content,
                DirectoryList::VAR_EXPORT,
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $this->resultRedirectFactory->create()->setPath('reports/report_detail/sold');
        }
    }
}
