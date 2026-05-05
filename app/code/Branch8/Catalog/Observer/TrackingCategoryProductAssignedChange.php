<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Catalog\Model\Category;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class TrackingCategoryProductAssignedChange implements ObserverInterface
{
    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * Constructor.
     *
     * @param AuthSession $authSession
     */
    public function __construct(AuthSession $authSession)
    {
        $this->authSession = $authSession;
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        $newData = $category->getData();

        $oldProducts = $category->getProductsPosition() && is_array($category->getProductsPosition()) ? array_keys($category->getProductsPosition()) : [];
        $newProducts = isset($newData['posted_products']) ? array_keys($newData['posted_products']) : [];

        if ($oldProducts != $newProducts) {
            $logFile = BP . '/var/log/branch8_category_product_assigned_trace.log';
            $logger = new \Monolog\Logger('branch8_category_product_assigned_trace');
            $logger->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::INFO));

            $added = array_diff($newProducts, $oldProducts);
            $removed = array_diff($oldProducts, $newProducts);
            $logger->info('Trace Log',
                [
                    'category' => $category->getId(),
                    'auth' => $this->authSession->getUser()?->getUserName() ?: 'system',
                    'added_products' => implode(',', $added),
                    'removed_products' => implode(',', $removed),
                    'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
                ]
            );
        }
    }
}
