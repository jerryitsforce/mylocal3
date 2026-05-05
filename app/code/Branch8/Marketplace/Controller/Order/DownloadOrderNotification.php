<?php

namespace Branch8\Marketplace\Controller\Order;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
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
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as HelperData;
use Branch8\MarketPlaceSeller\Helper\OrderDailyNotificationHelper;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;
use Branch8\Marketplace\Model\Export\Writer;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

/**
 * Download Seller Order File controller.
 */
class DownloadOrderNotification extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{


    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var HelperData
     */
    protected $helper;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var CustomerFactory
     */
    private $customerFactory;


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
    protected LoggerInterface $logger;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param Session $customerSession
     * @param CollectionFactory $orderCollectionFactory
     * @param HelperData $helper
     * @param CustomerUrl $customerUrl
     * @param CustomerFactory $customerFactory
     * @param DateTime $date
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderDailyNotificationHelper $orderDailyNotificationHelper
     * @param Writer $writer
     * @param LoggerInterface $logger
     * @param MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        HelperData $helper,
        CustomerUrl $customerUrl,
        CustomerFactory $customerFactory,
        DateTime $date,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig,
        OrderDailyNotificationHelper $orderDailyNotificationHelper,
        Writer $writer,
        LoggerInterface $logger,
        MarketplaceLogger $marketplaceLogger = null
    ) {
        $this->_customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->customerFactory = $customerFactory;
        $this->fileFactory = $fileFactory;
        $this->date = $date;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig;
        $this->orderDailyNotificationHelper = $orderDailyNotificationHelper;
        $this->writer = $writer;
        $this->logger = $logger;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
        parent::__construct(
            $context
        );
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $orderData = $this->orderDailyNotificationHelper->getOrderDataForExcel($sellerId);
                if (!empty($orderData)) {
                    $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
                    //$directory->create('export');
                    list($fileName, $zipFileName) = $this->orderDailyNotificationHelper->getFileName();
                    $password = $this->orderDailyNotificationHelper->generatePassword($sellerId);
                    $fileDirectoryPath = $directory->getAbsolutePath('export');
                    $zipFileDirectoryPath = $fileDirectoryPath . "/" . $zipFileName;
                    $this->writer->setFileName($fileName)
                        ->setData($orderData)
                        ->writeHeader()
                        ->writeRecords()
                        ->save();
                    @unlink($zipFileDirectoryPath);
                    $command = 'cd ' . $fileDirectoryPath . ' && zip -P ' . $password . ' ' . $zipFileName . ' ' . $fileName;
                    try {
                        exec($command);
                    } catch (\Exception $e) {
                        $this->marketplaceLogger->logException('DownloadOrderNotification', $e, [
                            'command' => $command,
                            'seller_id' => $sellerId,
                        ]);
                        throw new LocalizedException(__('Download failed'));
                    }
                    $this->marketplaceLogger->log('DownloadOrderNotification', [
                        'message' => 'DownloadOrderNotification password generated for seller.',
                        'seller_id' => $sellerId,
                    ]);
                    $content = [
                        'type' => 'filename',
                        'value' => $zipFileDirectoryPath,
                        'rm' => 0
                    ];
                    return $this->fileFactory->create(
                        $zipFileName,
                        $content,
                        DirectoryList::VAR_EXPORT,
                        'application/zip'
                    );
                } else {
                    $this->messageManager->addNotice(
                        __('There are no download files related to this report.')
                    );
                }
            } catch (\Exception $e) {
                $this->marketplaceLogger->logException('DownloadOrderNotification', $e);
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
