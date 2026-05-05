<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper;
use Branch8\Marketplace\Model\Export\Writer;

/**
 *
 */
class DownloadOrderNotification extends \Magento\Backend\App\Action
{
    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;


    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    protected OrderDailyNotificationHelper $orderDailyNotificationHelper;

    protected Writer $writer;

    public function __construct(
        Context $context,
        HelperData $helper,
        CustomerUrl $customerUrl,
        DateTime $date,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        OrderDailyNotificationHelper $orderDailyNotificationHelper,
        Writer $writer
    ) {
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->fileFactory = $fileFactory;
        $this->date = $date;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig;
        $this->orderDailyNotificationHelper = $orderDailyNotificationHelper;
        $this->writer = $writer;
        parent::__construct(
            $context
        );
    }


    /**
     * Mass delete seller products action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        try {
            $sellerId = $this->helper->getCustomerId();
            $orderData = $this->orderDailyNotificationHelper->getOrderDataForExcel($sellerId);
            if (!empty($orderData)) {
                $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
                $directory->create('export');
                list($fileName, $zipFileName) = $this->orderDailyNotificationHelper->getFileName();
                $this->writer->setFileName($fileName)
                    ->setData($orderData)
                    ->writeHeader()
                    ->writeRecords()
                    ->save();
                $content = [
                    'type' => 'filename',
                    'value' => $this->writer->getFilePath(),
                    'rm' => 1
                ];
                return $this->fileFactory->create(
                    $fileName,
                    $content,
                    DirectoryList::VAR_EXPORT,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                );
            } else {
                $this->messageManager->addNotice(
                    __('There are no download files related to this report.')
                );
            }
        } catch (\Exception $e) {
            $this->helper->logDataInLogger(
                "DownloadOrderNotification execute : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
}
