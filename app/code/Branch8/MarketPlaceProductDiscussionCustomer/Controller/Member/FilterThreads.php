<?php
namespace Branch8\MarketPlaceProductDiscussionCustomer\Controller\Member;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Controller\AbstractFilterThreads;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Branch8\MarketPlaceProductDiscussionCustomer\Model\Actions\FromThreadDataToRawData;
use Magento\Customer\Controller\AccountInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class FilterThreads extends AbstractFilterThreads implements AccountInterface, CsrfAwareActionInterface
{
    private Session $session;
    private FromThreadDataToRawData $converter;

    /**
     * @param JsonFactory $jsonFactory
     * @param RawFactory $resultRawFactory
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param FromThreadDataToRawData $converter
     * @param \Magento\Framework\Json\Helper\Data $helper
     * @param Session $session
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        JsonFactory                      $jsonFactory,
        RawFactory                       $resultRawFactory,
        RequestInterface                 $request,
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        ProductDiscussionsQueryInterface $productDiscussionsQuery,
        FromThreadDataToRawData          $converter,
        \Magento\Framework\Json\Helper\Data $helper,
        Session                          $session,
        FilterGroupBuilder               $filterGroupBuilder,
        FilterBuilder                    $filterBuilder,
        TimezoneInterface                $timezone
    ) {
        parent::__construct(
            $resultRawFactory,
            $jsonFactory,
            $helper,
            $request,
            $searchCriteriaBuilder,
            $filterGroupBuilder,
            $filterBuilder,
            $productDiscussionsQuery,
            $timezone
        );
        $this->session = $session;
        $this->converter = $converter;
    }

    /**
     * @return bool
     */
    protected function isAllowed(): bool
    {
        return $this->session->isLoggedIn();
    }

    /**
     * @param string $tab
     * @param array $request
     * @return array
     */
    protected function getTabData(string $tab, array $request): array
    {
        $threads = [];
        $input = $request['searchCriteria'];
        $input['filter_groups'][]['filters'][] = [
            'field' => 'author_type',
            'value' => Thread::THREAD_AUTHOR_TYPE_CUSTOMER,
            'condition_type' => 'eq'
        ];
        $input['filter_groups'][]['filters'][] = [
            'field' => 'author_id',
            'value' => (int)$this->session->getCustomerId(),
            'condition_type' => 'eq'
        ];
        $input['filter_groups'][]['filters'][] = [
            'field' => 'thread_type',
            'value' => Thread::THREAD_TYPE_QUESTION,
            'condition_type' => 'eq'
        ];

        switch ($tab) {
            case 'replied':
                $input['filter_groups'][]['filters'][] = [
                    'field' => 'is_seller_reply',
                    'value' => 1,
                    'condition_type' => 'eq'
                ];
                break;
            case 'unreplied':
                $input['filter_groups'][]['filters'][] = [
                    'field' => 'is_seller_reply',
                    'value' => 0,
                    'condition_type' => 'eq'
                ];
                break;
        }

        $searchCriteria = $this->getSearchCriteria($input);
        $result = $this->productDiscussionsQuery->get($searchCriteria);

        foreach ($result->getItems() as $thread) {
            $threads[] = $this->converter->convert($thread, (int)$this->session->getCustomerId());
        }

        return [$threads, $result->getSize(), $searchCriteria->getCurrentPage()];
    }
}

