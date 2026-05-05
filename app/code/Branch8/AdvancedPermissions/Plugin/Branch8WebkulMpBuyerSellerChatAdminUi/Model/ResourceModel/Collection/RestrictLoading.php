<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Branch8WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Collection;

use Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\Collection\RestrictInterface as CollectionRestrictInterface;
use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\Model\ResourceModel\Profiles\Grid as ChatProfileGridCollection;
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
    private $chatProfileGridRetrictLoading;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $customerCollectionRestrict
    ) {
        $this->request = $request;
        $this->chatProfileGridRetrictLoading = $customerCollectionRestrict;
    }

    public function beforeLoad(\Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Profiles\Grid\Collection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->chatProfileGridRetrictLoading->execute($subject);
    }
}
