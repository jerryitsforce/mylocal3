<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionSeller\Controller\Seller;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Message\Manager;
use Webkul\Marketplace\Helper\Data as HelperData;

/**
 * Controller for the 'marketplace/seller/ToggleStatus' URL route.
 */
class ToggleStatus implements HttpPostActionInterface
{
    private HelperData $helper;
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private RawFactory $resultRawFactory;
    private \Magento\Framework\Json\Helper\Data $jsonHelper;
    private \Webkul\SellerSubAccount\Helper\Data $subAccountHelper;
    private Session $session;
    private ThreadRepositoryInterface $threadRepository;
    /**
     *
     */
    const STATUS_MAPPING = [
        1 => ThreadInterface::  STATUS_APPROVED,
        0 => ThreadInterface::  STATUS_REJECTED,
    ];

    /**
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param RequestInterface $request
     * @param HelperData $helper
     * @param Session $session
     * @param \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
     * @param ThreadRepositoryInterface $threadRepository
     */
    public function __construct(
        RawFactory                           $resultRawFactory,
        JsonFactory                          $jsonFactory,
        \Magento\Framework\Json\Helper\Data  $jsonHelper,
        RequestInterface                     $request,
        HelperData                           $helper,
        Session                              $session,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper,
        ThreadRepositoryInterface            $threadRepository
    )
    {
        $this->resultRawFactory = $resultRawFactory;
        $this->helper = $helper;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->jsonHelper = $jsonHelper;
        $this->subAccountHelper = $subAccountHelper;
        $this->session = $session;
        $this->threadRepository = $threadRepository;
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
        $isPartner = $this->getSellerId();
        if (empty($postDetail)
            || $isPartner === false
            || empty($postDetail['thread'])
            || is_null($postDetail['status'])
            || $this->request->getMethod() !== 'POST'
            || $this->request->isXmlHttpRequest() === false
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }
        try {
            $thread = $this->threadRepository->getById($postDetail['thread']);
            $status = self::STATUS_MAPPING[$postDetail['status']] ?? ThreadInterface::STATUS_REJECTED;
            $thread->setStatus($status);
            $this->threadRepository->save($thread);
            return $this->jsonFactory->create()->setData([
                'success' => true,
                'message' => $status === ThreadInterface::STATUS_APPROVED ? __('Message is now visible on the storefront.')
                    : __('Message hidden from frontend.'),
            ]);

        } catch (\Exception $e) {
            return $this->jsonFactory->create()->setData([
                'success' => false,
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
