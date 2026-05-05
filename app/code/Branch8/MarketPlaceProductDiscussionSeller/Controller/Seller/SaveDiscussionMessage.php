<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionSeller\Controller\Seller;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\DiscussionManagement;
use Branch8\MarketPlaceProductDiscussion\Model\Message;
use Branch8\MarketPlaceProductDiscussion\Model\MessageRepository;
use Branch8\MarketPlaceProductDiscussionSeller\Model\Actions\FromThreadDataToRawData;
use Branch8\MarketPlaceProductDiscussionSeller\Model\Actions\MessageRawConverter;
use Branch8\MarketPlaceProductDiscussionSeller\Model\Actions\SellerReplyToThread;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\Manager;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Controller for the 'marketplace/seller/savediscussionmessage' URL route.
 */
class SaveDiscussionMessage implements HttpPostActionInterface
{
    private HelperData $helper;
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private RawFactory $resultRawFactory;
    private \Magento\Framework\Json\Helper\Data $jsonHelper;
    private \Webkul\SellerSubAccount\Helper\Data $subAccountHelper;
    private Session $session;
    private ProductDiscussionsQueryInterface $productDiscussionsQuery;
    private FromThreadDataToRawData $converter;

    private ThreadRepositoryInterface $threadRepository;
    /**
     * @var MessageRepository
     */
    private MessageRepository $messageRepository;
    /**
     * @var MessageRawConverter
     */
    private MessageRawConverter $messageRawConverter;
    private Manager $messageManager;
    private SellerReplyToThread $sellerReplyToThread;
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param RequestInterface $request
     * @param HelperData $helper
     * @param Session $session
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param FromThreadDataToRawData $converter
     * @param ThreadRepositoryInterface $threadRepository
     * @param MessageRepository $messageRepository
     * @param SellerReplyToThread $sellerReplyToThread
     * @param MessageRawConverter $messageRawConverter
     * @param \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
     * @param CustomerRepositoryInterface $customerRepository
     * @param Manager $messageManager
     */
    public function __construct(
        RawFactory                           $resultRawFactory,
        JsonFactory                          $jsonFactory,
        \Magento\Framework\Json\Helper\Data  $jsonHelper,
        RequestInterface                     $request,
        HelperData                           $helper,
        Session                              $session,
        ProductDiscussionsQueryInterface     $productDiscussionsQuery,
        FromThreadDataToRawData              $converter,
        ThreadRepositoryInterface            $threadRepository,
        MessageRepository                    $messageRepository,
        SellerReplyToThread                  $sellerReplyToThread,
        MessageRawConverter                  $messageRawConverter,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper,
        CustomerRepositoryInterface          $customerRepository,
        Manager                              $messageManager
    )
    {
        $this->messageManager = $messageManager;
        $this->messageRawConverter = $messageRawConverter;
        $this->resultRawFactory = $resultRawFactory;
        $this->helper = $helper;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->subAccountHelper = $subAccountHelper;
        $this->productDiscussionsQuery = $productDiscussionsQuery;
        $this->converter = $converter;
        $this->session = $session;
        $this->messageRepository = $messageRepository;
        $this->threadRepository = $threadRepository;
        $this->customerRepository = $customerRepository;
        $this->sellerReplyToThread = $sellerReplyToThread;
    }

    /**
     * Execute controller action.
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $httpBadRequestCode = 404;
        try {
            $postDetail = $this->jsonHelper->jsonDecode($this->request->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        $sellerId = $this->getSellerId();
        $seller = $this->customerRepository->getById($sellerId);
        if (empty($postDetail)
            || $sellerId === false
            || empty($postDetail['thread'])
            || $this->request->getMethod() !== 'POST'
            || $this->request->isXmlHttpRequest() === false
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        try {
            $data = [];
            $thread = $this->threadRepository->getById($postDetail['thread']);
            if (isset($postDetail['id'])) {
                $reply = $this->messageRepository->getById($postDetail['id']);
                $reply->setMessage($postDetail['message']);
                $reply = $this->messageRepository->save($reply);
            } else {
                $reply = $this->sellerReplyToThread->reply(
                    $thread,
                    $sellerId,
                    sprintf("%s %s", $seller->getFirstname(), $seller->getLastname()),
                    $postDetail['message'],
                );
            }
            $data['message'] = $this->messageRawConverter->convert($reply);
            return $this->jsonFactory->create()->setData([
                'success' => true,
                'data' => $data,
                'message' => __('Save reply successfully.'),
            ]);

        } catch (\Exception $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
                'data' => [],
                'message' => __('Something went wrong while saving the reply.'),
            ]);
        }
    }

    /**
     * @return int|null
     */
    private function getSellerId()
    {
        $sellerId = $this->session->getCustomerId();
        $subAccount = $this->subAccountHelper->getCurrentSubAccount();
        if ($subAccount->getId()) {
            $sellerId = $subAccount->getSellerId();
        }
        return $sellerId;
    }
}
