<?php

namespace Branch8\MarketPlaceOrderExportSeller\Controller\Order\Ui;

use Branch8\MarketPlaceOrderExport\Model\Writer;
use Branch8\MarketPlaceOrderExport\Model\Writer2;
use Magento\Framework\App\Action\Action;
use Branch8\MarketPlaceOrderExportSeller\Override\Magento\Ui\Component\MassAction\Filter;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Customer\Model\CustomerFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;

/**
 * Download Seller Order File controller.
 */
class DownloadOrderFile extends \Branch8\SellerPermission\Controller\Order\Ui\DownloadOrderFile
{
    private $customerUrl;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;
    /**
     * @var DateTime
     */
    private DateTime $dateTime;
    private Writer $writter;
    private Writer2 $writter2;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param HelperData $helper
     * @param CustomerUrl $customerUrl
     * @param CustomerFactory $customerFactory
     * @param MpOrdersCollection $mpOrdersCollection
     * @param InvoiceCollection $invoiceCollection
     * @param DateTime $date
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param DateTime $dateTime
     * @param Writer $writer
     * @param Writer2 $writer2
     */
    public function __construct(
        Context              $context,
        Filter               $filter,
        Session              $customerSession,
        CollectionFactory    $orderCollectionFactory,
        HelperData           $helper,
        CustomerUrl          $customerUrl,
        CustomerFactory      $customerFactory,
        MpOrdersCollection   $mpOrdersCollection,
        InvoiceCollection    $invoiceCollection,
        DateTime             $date,
        FileFactory          $fileFactory,
        Filesystem           $filesystem,
        ScopeConfigInterface $scopeConfig,
        DateTime             $dateTime,
        Writer               $writer,
        Writer2              $writer2
    )
    {

        parent::__construct(
            $context,
            $filter,
            $customerSession,
            $orderCollectionFactory,
            $helper,
            $customerUrl,
            $customerFactory,
            $mpOrdersCollection,
            $invoiceCollection,
            $date,
            $fileFactory,
            $filesystem,
            $scopeConfig
        );
        $this->customerUrl = $customerUrl;
        $this->customerFactory = $customerFactory;
        $this->dateTime = $dateTime;
        $this->writter = $writer;
        $this->writter2 = $writer2;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $useOptimize = (bool)$this->scopeConfig->getValue('order_export/optimize/use_writer2');
                $useStream = (bool)$this->scopeConfig->getValue('order_export/optimize/use_stream');
                $sellerId = $this->helper->getCustomerId();
                $customer = $this->customerFactory->create()->load($sellerId);
                $sellerName = $customer->getName();
                $collection = $this->filter->getCollection(
                    $this->orderCollectionFactory->create()
                );
                $ids = $collection->getAllIds();
                if (!empty($ids)) {
                    $date = $this->dateTime->date('Y-m-d_H-i-s');
                    $fileName = sprintf('orders-%s.xlsx', $date);
                    $fileNameUniqueId = sprintf('orders-%s.xlsx', uniqid());
                    if ($useOptimize) {
                        $this->writter2->initCache();
                        if ($useStream) {
                            $this->writter2->setIsstream(true);
                        }
                        $this->writter2->setFileName($fileNameUniqueId)
                            ->setOrderIds($ids)
                            ->writeHeader()
                            ->writeRecords()
                            ->save();
                    } else {
                        $this->writter->setFileName($fileNameUniqueId)
                            ->setOrderIds($ids)
                            ->writeHeader()
                            ->writeRecords()
                            ->save();
                    }

                    $content = [
                        'type' => 'filename',
                        'value' => $this->writter->getFilePath(),
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
                        __('There are no download files related to selected order(s).')
                    );
                }
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Order_Ui_DownloadOrderFile execute : " . $e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/order/history',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
