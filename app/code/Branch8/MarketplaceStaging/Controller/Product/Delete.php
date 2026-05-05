<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceStaging\Controller\Product;

use Magento\Customer\Model\Session;
use Magento\Customer\Model\Url as CustomerUrl;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Staging\Model\Entity\Update\Delete as StagingUpdateDelete;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\StoreManagerInterface;

class Delete extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * Entity request identifier
     */
    const ENTITY_IDENTIFIER = 'product_id';

    /**
     * Entity name
     */
    const ENTITY_NAME = 'product';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CustomerUrl
     */
    private $customerUrl;

    /**
     * @var StagingUpdateDelete
     */
    protected $stagingUpdateDelete;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param StagingUpdateDelete $stagingUpdateDelete
     * @param Session $customerSession
     * @param Registry $coreRegistry
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Session $customerSession,
        Registry $coreRegistry,
        StagingUpdateDelete $stagingUpdateDelete,
        StoreManagerInterface $storeManager,
        CustomerUrl $customerUrl = null
    ) {
        $this->_customerSession = $customerSession;
        $this->_coreRegistry = $coreRegistry;
        $this->stagingUpdateDelete = $stagingUpdateDelete;
        $this->storeManager = $storeManager;
        $this->customerUrl = $customerUrl ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(CustomerUrl::class);
        parent::__construct($context);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->customerUrl->getLoginUrl();

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (isset($data['product']['current_store_id'])) {
            $selectedStore = $this->storeManager->getStore((int)$data['product']['current_store_id']);
            $this->storeManager->setCurrentStore($selectedStore);
        }
        $this->_coreRegistry->register('isSecureArea', 1);
        return $this->stagingUpdateDelete->execute(
            [
                'entityId' => $this->getRequest()->getParam(static::ENTITY_IDENTIFIER),
                'updateId' => $this->getRequest()->getParam('update_id'),
                'stagingData' => $this->getRequest()->getParam('staging')
            ]
        );
    }
}
