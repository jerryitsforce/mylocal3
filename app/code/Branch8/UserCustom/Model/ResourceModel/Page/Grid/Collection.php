<?php

namespace Branch8\UserCustom\Model\ResourceModel\Page\Grid;

use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Cms\Model\ResourceModel\Page\Grid\Collection
{

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_adminSession;

    /**
     * @var \Branch8\RoleDelegate\Api\DelegateRepositoryInterface
     */
    protected $delegateRepository;

    /**
     * @var \Magento\User\Model\UserFactory
     */
    protected $userFactory;

    protected $_totalRecords;

    /**
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param MetadataPool $metadataPool
     * @param \Magento\Backend\Model\Auth\Session $adminSession
     * @param \Branch8\RoleDelegate\Api\DelegateRepositoryInterface $delegateRepository
     * @param \Magento\User\Model\UserFactory $userFactory
     * @param mixed|null $mainTable
     * * @param string $eventPrefix
     * * @param mixed $eventObject
     * * @param mixed $resourceModel
     * * @param string $model
     * * @param mixed|null $connection
     * * @param AbstractDb|null $resource
     * * @param TimezoneInterface|null $timeZone
     */
    public function __construct(
        EntityFactoryInterface                                $entityFactory,
        LoggerInterface                                       $logger,
        FetchStrategyInterface                                $fetchStrategy,
        ManagerInterface                                      $eventManager,
        StoreManagerInterface                                 $storeManager,
        MetadataPool                                          $metadataPool,
        \Magento\Backend\Model\Auth\Session                   $adminSession,
        \Branch8\RoleDelegate\Api\DelegateRepositoryInterface $delegateRepository,
        \Magento\User\Model\UserFactory                       $userFactory,
                                                              $mainTable,
                                                              $eventPrefix,
                                                              $eventObject,
                                                              $resourceModel,
                                                              $model = \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class,
                                                              $connection = null,
        AbstractDb                                            $resource = null,
        TimezoneInterface                                     $timeZone = null
    )
    {
        $this->_adminSession = $adminSession;
        $this->delegateRepository = $delegateRepository;
        $this->userFactory = $userFactory;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $storeManager, $metadataPool,
            $mainTable, $eventPrefix, $eventObject, $resourceModel, $model, $connection, $resource, $timeZone);
    }

    public function getItems()
    {
        $currentUser = $this->_adminSession->getUser();
        $cmsPage = $currentUser->getCmsPage();

        // If $cmsPage is not set or empty, return all items
        if (!$cmsPage) {
            //$this->_totalRecords = count(parent::getItems());
            return parent::getItems();
        }
        // Get Delegate Repository & UserFactory via DI (add in constructor)
        $delegation = $this->delegateRepository->getActiveForUser($currentUser->getId());

        // Start with the current user’s CMS pages
        $allowedPageIds = array_filter(explode(',', $cmsPage));

        // If user is a delegate, also include delegator’s CMS pages
        if ($delegation && $delegation->getId()) {
            $originalUser = $this->userFactory->create()->load($delegation->getUserId());
            if ($originalUser && $originalUser->getId()) {
                $delegatorCmsPage = $originalUser->getCmsPage();
                if ($delegatorCmsPage) {
                    $delegatorPageIds = array_filter(explode(',', $delegatorCmsPage));

                    // Merge and remove duplicates
                    $allowedPageIds = array_unique(array_merge($allowedPageIds, $delegatorPageIds));
                }
            }
        }

        // Convert comma-separated string to an array of page IDs
        // $allowedPageIds = explode(',', $cmsPage);

        // Get items from the parent
        $items = parent::getItems();

        // Filter out items not in the allowedPageIds array
        $filteredItems = array_filter($items, function ($item) use ($allowedPageIds) {
            // Assuming $item is an object with a method or property containing the page_id
            // Adjust the condition based on the actual structure of your items
            return in_array($item->getPageId(), $allowedPageIds);
        });
        $this->_totalRecords = count($filteredItems);
        // Return the filtered items
        return $filteredItems;
    }

}
