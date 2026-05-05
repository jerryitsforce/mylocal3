<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Ajax;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ThreadInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data as JsonHelper;

/**
 * Controller for the 'product_discussion/ajax/deleteThread' URL route.
 */
class DeleteThread implements HttpPostActionInterface
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ThreadRepositoryInterface
     */
    private ThreadRepositoryInterface $threadRepository;

    /**
     * @var Session
     */
    private Session $session;

    /**
     * @var JsonHelper
     */
    private JsonHelper $jsonHelper;

    /**
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param ThreadRepositoryInterface $threadRepository
     * @param Session $session
     * @param JsonHelper $jsonHelper
     */
    public function __construct(
        JsonFactory $jsonFactory,
        RequestInterface $request,
        ThreadRepositoryInterface $threadRepository,
        Session $session,
        JsonHelper $jsonHelper
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->threadRepository = $threadRepository;
        $this->session = $session;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Execute delete thread action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonFactory->create();

        try {
            // Basic security and request validation
            if (!$this->request->isXmlHttpRequest() ||
                $this->request->getMethod() !== 'POST' ||
                !$this->session->isLoggedIn()
            ) {
                throw new LocalizedException(__('Invalid request or session expired.'));
            }
            $threadId = $this->request->getParam('thread_id');
            if (!$threadId && $this->request->getContent()) {
                try {
                    $contentBody = $this->jsonHelper->jsonDecode($this->request->getContent());
                    if (is_array($contentBody) && isset($contentBody['thread_id'])) {
                        $threadId = $contentBody['thread_id'];
                    }
                } catch (\Exception $e) {
                    // Not valid JSON
                }
            }
            if (!$threadId) {
                throw new LocalizedException(__('Invalid thread ID.'));
            }

            $thread = $this->threadRepository->getById((int)$threadId);
            $customerId = (int)$this->session->getCustomerId();

            // Security check: only allow author to delete their own thread
            if ((int)$thread->getAuthorId() !== $customerId ||
                $thread->getAuthorType() !== ThreadInterface::THREAD_AUTHOR_TYPE_CUSTOMER
            ) {
                throw new LocalizedException(__('You are not authorized to delete this thread.'));
            }
            $this->threadRepository->deleteById((int)$threadId);
            return $resultJson->setData([
                'success' => true,
                'message' => __('Thread deleted successfully.')
            ]);
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('An error occurred while deleting the thread.')
            ]);
        }
    }
}
