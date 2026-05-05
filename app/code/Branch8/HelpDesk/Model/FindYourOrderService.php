<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\Data\FindYourOrdersSearchResultInterfaceFactory;
use Branch8\HelpDesk\Api\FindYourOrderServiceInterface;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;

/**
 * Find Customer Order Service
 */
class FindYourOrderService implements FindYourOrderServiceInterface
{
    /**
     * @var FindYourOrdersSearchResultInterfaceFactory
     */
    public FindYourOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory;
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

    /**
     * @param CollectionProcessorInterface $collectionProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param FindYourOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory
     */
    public function __construct(
        CollectionProcessorInterface               $collectionProcessor,
        JoinProcessorInterface                     $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter              $extensibleDataObjectConverter,
        FindYourOrdersSearchResultInterfaceFactory $findYourOrdersSearchResultInterfaceFactory
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
