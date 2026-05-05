<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Plugin\Branch8WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\ChatHistory\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\ChatHistory\Collection\RestrictInterface as CollectionRestrictInterface;
use Magento\Framework\App\RequestInterface;

class RestrictLoading
{
    private const API_MODULE_NAME = 'api';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionRestrictInterface
     */
    private $chatHistoryGridRetrictLoading;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $collectionRestrictInterface
    ) {
        $this->request = $request;
        $this->chatHistoryGridRetrictLoading = $collectionRestrictInterface;
    }

    public function beforeLoad(\Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Message\Grid\Collection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->chatHistoryGridRetrictLoading->execute($subject);
    }
}
