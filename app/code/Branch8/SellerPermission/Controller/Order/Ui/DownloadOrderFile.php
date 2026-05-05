<?php

namespace Branch8\SellerPermission\Controller\Order\Ui;

use Magento\Framework\App\Action\Action;
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
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory as MpOrdersCollection;

/**
 * Download Seller Order File controller.
 */
class DownloadOrderFile extends Action implements \Magento\Framework\App\CsrfAwareActionInterface
{
    /**
     * @var Filter
     */
    protected $filter;

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
     * @var MpOrdersCollection
     */
    protected $mpOrdersCollection;

    /**
     * @var InvoiceCollection
     */
    protected $invoiceCollection;

    /**
     * @var InvoicePdf
     */
    protected $invoicePdf;

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

    /**
     * @param Context               $context
     * @param Filter                $filter
     * @param Session               $customerSession
     * @param CollectionFactory     $orderCollectionFactory
     * @param HelperData            $helper
     * @param CustomerUrl           $customerUrl
     * @param CustomerFactory       $customerFactory,
     * @param MpOrdersCollection    $mpOrdersCollection
     * @param InvoiceCollection     $invoiceCollection
     * @param DateTime              $date
     * @param FileFactory           $fileFactory
     * @param Filesystem            $filesystem
     * @param ScopeConfigInterface  $scopeConfig
     */
    public function __construct(
        Context $context,
        Filter $filter,
        Session $customerSession,
        CollectionFactory $orderCollectionFactory,
        HelperData $helper,
        CustomerUrl $customerUrl,
        CustomerFactory $customerFactory,
        MpOrdersCollection $mpOrdersCollection,
        InvoiceCollection $invoiceCollection,
        DateTime $date,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        ScopeConfigInterface $scopeConfig 
    ) {
        $this->filter = $filter;
        $this->_customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->helper = $helper;
        $this->customerUrl = $customerUrl;
        $this->customerFactory = $customerFactory;
        $this->mpOrdersCollection = $mpOrdersCollection;
        $this->invoiceCollection = $invoiceCollection;
        $this->fileFactory = $fileFactory;
        $this->date = $date;
        $this->filesystem = $filesystem;
        $this->scopeConfig = $scopeConfig; 
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

    public function getNumberOfDaysLimit() 
    { 
        return $this->scopeConfig->getValue( 
        'seller_info/dashboard/number_of_days_limit_to_download_order', 
        \Magento\Store\Model\ScopeInterface::SCOPE_STORE, 
     ); 
    }

    /**
     * Mass delete seller products action.
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $isPartner = $this->helper->isSeller();
        if ($isPartner == 1) {
            try {
                $sellerId = $this->helper->getCustomerId();
                $customer = $this->customerFactory->create()->load($sellerId);
                $sellerName = $customer->getName();
                $collection = $this->filter->getCollection(
                    $this->orderCollectionFactory->create()
                );
                $ids = $collection->getAllIds();
                if (!empty($ids)) {
                    //default is limit 14 days to download order ( can config the number of limit days)
                    $numberOfDaysLimit = $this->getNumberOfDaysLimit();
                    $to = date("Y-m-d h:i:s"); // current date
                    $from = strtotime('-' . $numberOfDaysLimit . ' day', strtotime($to));
                    $from = date('Y-m-d h:i:s', $from);

                    $orderCollection = $this->orderCollectionFactory->create()
                    ->addAttributeToSelect('*')
                    ->addAttributeToFilter(
                        'created_at',
                        ['from' =>  $from]
                    )
                    ->addAttributeToFilter(
                        'entity_id',
                        ['in' => $ids]
                    );

                    if (!$orderCollection->getSize()) {
                        $this->messageManager->addNotice(
                            __('There are no download file related to selected order(s). Number Of Days Limit To Download Order is %1', $numberOfDaysLimit)
                        );
                        return $this->resultRedirectFactory->create()->setPath(
                            'marketplace/order/history',
                            [
                                '_secure' => $this->getRequest()->isSecure(),
                            ]
                        );
                    }

                    $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
                    $directory->create('export');
                    $fileName = 'seller' . $sellerId . '_orders.csv';
                    $filepath = 'export/' . $fileName;
                    $stream = $directory->openFile($filepath, 'w+');
                    $stream->lock();
                    $header = [
                        __('Date'),
                        __('Order Number'),
                        __('Order Status'),
                        __('Name'),
                        __('Phone'),
                        __('Product Type'),
                        __('Total Amount'),
                        __('Seller Name'),
                        __('Shipping Method'),
                        __('Tracking Number'),
                        __('Logistics Unit'),
                        __('Recipient’s Name'),
                        __('Recipient’s contact number'),
                        __('Home Delivery Address'),
                        __('Pick-up Delivery Store'),
                        __('Entity Id'),
                        __('Product Name'),
                        __('SKU'),
                        __('Product Specifications'),
                        __('Qty'),
                        __('Point'),
                        __('Product Amount'),
                        __('Subtotal'),
                        __('Order Cancellation Time'),
                        __('Reason For Cancellation'),
                        __('Time To Apply For Return'),
                        __('Apply For Return Review Results'),
                        __('Agree On Return Time'),
                        __('Return Result'),
                        __('Confirm Return Result Time'),
                        __('Delivery Method(temperature)'),
                        __('Order free shipping threshold'),
                        __('Shipping fee')
                    ];
                    $stream->writeCsv($header);

                    foreach ($orderCollection as $order) {
                        $orderItems = $order->getAllVisibleItems();
                        $billingAddress = $order->getBillingAddress();
                        $shippingAddress = $order->getShippingAddress();
                        $tracksCollection = $order->getTracksCollection();
                        $trackNumber = '';
                        $trackTitle = '';
                        foreach ($tracksCollection->getItems() as $track) {
                            $trackNumber = $track->getTrackNumber(); // Tracking number
                            $trackTitle = $track->getTitle(); // Logistics Unit
                        }
                        foreach ($order->getAllVisibleItems() as $item) {
                            $data = [];
                            $data[] = $order->getData('created_at'); //Date
                            $data[] = (string) $order->getData('increment_id'); //Order Number
                            $data[] = $order->getData('status'); //Order Status
                            $data[] = $order->getData('customer_firstname') . ' ' . $order->getData('customer_lastname'); //Name
                            $data[] = $billingAddress->getData('telephone'); //Phone
                            $data[] = $item->getData('product_type'); //Product Type
                            $data[] = $order->getData('grand_total'); //Product Type
                            $data[] = $sellerName; //Seller Name
                            $data[] = $order->getData('shipping_method'); //Shipping Method
                            $data[] = $trackNumber; //Tracking Number
                            $data[] = $trackTitle; //Logistics Unit
                            if ($shippingAddress) {
                                $data[] = $shippingAddress->getData('firstname') . ' ' . $shippingAddress->getData('lastname'); //Recipient’s Name
                                $data[] = $shippingAddress->getData('telephone'); //Recipient’s contact number
                                $data[] = $shippingAddress->getData('street'); //Home Delivery Address
                            } else {
                                $data[] = ''; //Recipient’s Name
                                $data[] = ''; //Recipient’s contact number
                                $data[] = ''; //Home Delivery Address
                            }
                            $data[] = ''; //Pick-up Delivery Store

                            $data[] = $item->getData('item_id'); //Entity id = item id
                            $data[] = $item->getData('name'); //Product Name
                            $data[] = $item->getData('sku'); //SKU
                            $data[] = ''; //Product Specifications
                            $data[] = $item->getData('qty_ordered'); //Qty
                            $data[] = $item->getData('point_used'); //Point
                            $data[] = $item->getData('price_incl_tax'); //Product Amount
                            $data[] = $item->getData('row_total_incl_tax'); //Subtotal
                            if ($order->getData('status') == 'cancel') {
                                $data[] = $order->getData('updated_at'); //Order Cancellation Time
                            } else {
                                $data[] = '';
                            }
                            $data[] = ''; //Reason For Cancellation
                            $data[] = ''; //Time To Apply For Return
                            $data[] = ''; //Apply For Return Review Results
                            $data[] = ''; //Agree On Return Time
                            $data[] = ''; //Return Result
                            $data[] = ''; //Confirm Return Result Time
                            $data[] = ''; //Delivery Method(temperature)
                            $data[] = ''; //Order free shipping threshold
                            $data[] = $order->getData('shipping_amount'); //Shipping Fee

                            $stream->writeCsv($data);
                        }   
                    }

                    $content = [];
                    $content['type'] = 'filename';
                    $content['value'] = $filepath;
                    $content['rm'] = '1';

                    return $this->fileFactory->create(
                        $fileName,
                        $content,
                        DirectoryList::VAR_DIR
                    );
                } else {
                    $this->messageManager->addNotice(
                        __('There are no download files related to selected order(s).')
                    );
                }
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Order_Ui_DownloadOrderFile execute : ".$e->getMessage()
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
