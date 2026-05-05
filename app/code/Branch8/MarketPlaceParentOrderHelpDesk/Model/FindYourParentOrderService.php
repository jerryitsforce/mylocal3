<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderHelpDesk\Model;

use Branch8\MarketPlaceParentOrderHelpDesk\Api\Data\FindYourParentOrdersSearchResultInterfaceFactory;
use Branch8\MarketPlaceParentOrderHelpDesk\Api\FindYourParentOrderServiceInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Find Customer Order Service
 */
class FindYourParentOrderService implements FindYourParentOrderServiceInterface
{

    /**
     * @var CollectionProcessorInterface
     */
    public CollectionProcessorInterface $collectionProcessor;
    /**
     * @var JoinProcessorInterface
     */
    public JoinProcessorInterface $extesionJoinProcessor;
    /**
     * @var ExtensibleDataObjectConverter
     */
    public ExtensibleDataObjectConverter $extensibleDataObjectConverter;
    public FindYourParentOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory;

    /**
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param FindYourParentOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory
     */
    public function __construct(
        CollectionProcessorInterface               $collectionProcessor,
        JoinProcessorInterface                     $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter              $extensibleDataObjectConverter,
        FindYourParentOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory
    )
    {
        $this->collectionProcessor = $collectionProcessor;
        $this->extesionJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->findYourOrdersSearchResultInterfaceFactory = $findYourOrdersSearchResultInterfaceFactory;
    }

    /**
     * Search
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Branch8\HelpDesk\Api\Data\FindYourOrdersSearchResultInterface
     */
    public function search(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $searchResult = $this->findYourOrdersSearchResultInterfaceFactory->create();
        $this->collectionProcessor->process($searchCriteria, $searchResult);
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
