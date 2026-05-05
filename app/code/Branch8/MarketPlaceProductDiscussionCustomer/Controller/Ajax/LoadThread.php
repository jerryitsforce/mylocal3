<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Ajax;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ThreadRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;

/**
 * Controller for the 'product_discussion/ajax/loadthread' URL route.
 */
class LoadThread implements HttpGetActionInterface
{
    private $jsonFactory;
    private $request;
    private $searchCriteriaBuilder;
    private $threadRepository;

    private SortOrderBuilder $sortOrderBuilder;

    private ProductDiscussionsQueryInterface $productDiscussionsQuery;
    private Session $session;
    private \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone;
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;
    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @param JsonFactory $jsonFactory
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param ThreadRepositoryInterface $threadRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param Session $session
     */
    public function __construct(
        JsonFactory                                          $jsonFactory,
        RequestInterface                                     $request,
        SearchCriteriaBuilder                                $searchCriteriaBuilder,
        FilterBuilder                                        $filterBuilder,
        FilterGroupBuilder                                   $filterGroupBuilder,
        SortOrderBuilder                                     $sortOrderBuilder,
        ProductDiscussionsQueryInterface                     $productDiscussionsQuery,
        ThreadRepositoryInterface                            $threadRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        Session                                              $session
    )
    {
        $this->timezone = $timezone;
        $this->session = $session;
        $this->productDiscussionsQuery = $productDiscussionsQuery;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->threadRepository = $threadRepository;
    }

    /**
     * @param $productId
     * @param $pageSize
     * @param $currentPage
     * @param $customerId
     * @return \Magento\Framework\Api\SearchCriteria
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getSearchCriteriaBuilder($productId, $pageSize, $currentPage, $customerId = null)
    {
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder->create()->setDirection('desc')
                ->setField('created_at')
        )->setCurrentPage($currentPage)->setPageSize($pageSize);
        $group1 = $this->filterGroupBuilder->addFilter($this->filterBuilder
            ->setField('product_id')->setValue($productId)->setConditionType('eq')->create())->create();
        $group2 = $this->filterGroupBuilder->addFilter($this->filterBuilder
            ->setField('thread_type')->setValue(Thread::THREAD_TYPE_QUESTION)->setConditionType('eq')->create())->create();
        if (!$customerId) {
            // Guest mode: Only approved
            $group3 = $this->filterGroupBuilder->addFilter($this->filterBuilder
                ->setField('status')->setValue(Thread::STATUS_APPROVED)->setConditionType('eq')->create())->create();
            $this->searchCriteriaBuilder->setFilterGroups([$group1, $group2, $group3]);
        } else {
            // Customer mode: (Approved OR (My ID AND My Type))
            // Rewritten as: (Approved OR My ID) AND (Approved OR My Type)
            $statusFilter = $this->filterBuilder
                ->setField('status')->setValue(Thread::STATUS_APPROVED)->setConditionType('eq')->create();
            $authorIdFilter = $this->filterBuilder
                ->setField('author_id')->setValue($customerId)->setConditionType('eq')->create();
            $authorTypeFilter = $this->filterBuilder
                ->setField('author_type')->setValue(Thread::THREAD_AUTHOR_TYPE_CUSTOMER)->setConditionType('eq')->create();
            // Group 3: (Status Approved OR Author ID = C)
            $group3 = $this->filterGroupBuilder->setFilters([$statusFilter, $authorIdFilter])->create();
            // Group 4: (Status Approved OR Author Type = customer)
            $group4 = $this->filterGroupBuilder->setFilters([$statusFilter, $authorTypeFilter])->create();
            $this->searchCriteriaBuilder->setFilterGroups([$group1, $group2, $group3, $group4]);
        }

        return $this->searchCriteriaBuilder->create();
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\InputException
     */
    public function execute()
    {
        $productId = (int)$this->request->getParam('product_id');
        $page = (int)$this->request->getParam('page', 1);
        $customerId = (int)$this->session->getCustomerId();
        $pageSize = 10;
        /**
         * @var $result \Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Thread\Collection
         * @var $thread \Branch8\MarketPlaceProductDiscussion\Model\Thread
         */
        $result = $this->productDiscussionsQuery->get(
            $this->getSearchCriteriaBuilder($productId, $pageSize, $page, $customerId),
        );
        $totalCount = $result->getSize();
        $threads = [];
        foreach ($result->getItems() as $thread) {
            $messages = $this->getMessages($thread);
            $threads[] = [
                'id' => $thread->getId(),
                'title' => $thread->getTitle(),
                'is_my_thread' => (int)$thread->getAuthorId() === (int)$this->session->getCustomerId() && $thread->getAuthorType() === Thread::THREAD_AUTHOR_TYPE_CUSTOMER,
                'content' => $thread->getContent(),
                'product_spec'=>$thread->getProductSpec(),
                'created_at' => $this->formatDate($thread->getCreatedAt()),
                'messages' => $messages,
                'has_messages' => count($messages) > 0,
            ];
        }
        return $this->jsonFactory->create()->setData([
            'success' => true,
            'threads' => $threads,
            'total_count' => $totalCount,
        ]);
    }

    /**
     * @param $date
     * @return string
     */
    private function formatDate($date)
    {
        return $this->timezone->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            null,
            'Y/MM/dd'
        );
    }

    /**
     * @param Thread $thread
     * @return array
     */
    private function getMessages(Thread $thread)
    {
        $messages = [];
        foreach ($thread->getMessages(1, 10) as $message) {
            $messages[] = [
                'title' => $message->getMessage(),
                'message' => $message->getMessage(),
                'created_at' => $this->formatDate($message->getCreatedAt()),
                'author_type' => $message->getAuthorType(),
            ];
        }
        return $messages;
    }
}
