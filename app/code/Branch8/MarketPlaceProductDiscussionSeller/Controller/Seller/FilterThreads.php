<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionSeller\Controller\Seller;
use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Controller\AbstractFilterThreads;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Branch8\MarketPlaceProductDiscussionSeller\Model\Actions\FromThreadDataToRawData;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Webkul\SellerSubAccount\Helper\Data as SubAccountHelper;

class FilterThreads extends AbstractFilterThreads implements CsrfAwareActionInterface
{
    private Session $session;
    private FromThreadDataToRawData $converter;
    private SubAccountHelper $subAccountHelper;

    /**
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param Session $session
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param FromThreadDataToRawData $converter
     * @param SubAccountHelper $subAccountHelper
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        RawFactory                       $resultRawFactory,
        JsonFactory                      $jsonFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        RequestInterface                 $request,
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        FilterGroupBuilder               $filterGroupBuilder,
        FilterBuilder                    $filterBuilder,
        Session                          $session,
        ProductDiscussionsQueryInterface $productDiscussionsQuery,
        FromThreadDataToRawData          $converter,
        SubAccountHelper                 $subAccountHelper,
        TimezoneInterface                $timezone
    ) {
        parent::__construct(
            $resultRawFactory,
            $jsonFactory,
            $jsonHelper,
            $request,
            $searchCriteriaBuilder,
            $filterGroupBuilder,
            $filterBuilder,
            $productDiscussionsQuery,
            $timezone
        );
        $this->session = $session;
        $this->converter = $converter;
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * @return bool
     */
    protected function isAllowed(): bool
    {
        return (bool)$this->getSellerId();
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
            'field' => 'seller_id',
            'value' => $this->getSellerId(),
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
            $threads[] = $this->converter->convert($thread);
        }

        return [$threads, $result->getSize(), $searchCriteria->getCurrentPage()];
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

