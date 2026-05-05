<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Plugin\Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order;

use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Profile;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ProfileFactory;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\Queue\Publish;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile as ProfileResource;
use Magento\Framework\Message\Manager;

class DownLoadOrderFilePlugin extends \Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Order\DownloadOrderFile
{
    private Config $config;
    private ProfileFactory $profileFactory;
    private ProfileResource $profileResource;
    private Publish $publish;

    private Manager $manager;
    private \Magento\Backend\Model\Auth\Session $authSession;
    private \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory;

    /**
     * @param Config $config
     * @param Publish $publish
     * @param ProfileResource $profileResource
     * @param ProfileFactory $profileFactory
     * @param Manager $messageManager
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Branch8\MarketPlaceOrderExportAdminUi\Model\Filter $filter
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Config                                               $config,
        Publish                                              $publish,
        ProfileResource                                      $profileResource,
        ProfileFactory                                       $profileFactory,
        Manager                                              $messageManager,
        \Magento\Backend\Model\Auth\Session                  $authSession,
        \Branch8\MarketPlaceOrderExportAdminUi\Model\Filter  $filter,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
    )
    {
        $this->profileResource = $profileResource;
        $this->profileFactory = $profileFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->publish = $publish;
        $this->authSession = $authSession;
        $this->filter = $filter;
        $this->redirectFactory = $resultRedirectFactory;
    }

    /**
     * @param $subject
     * @param $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundExecute($subject, $proceed)
    {
        if (!$this->config->enable()) {
            return $proceed();
        }
        $collection = $this->filter->getCollection($this->getOrderCollection()->create());
        $ids = array_unique($collection->getAllIds());
        $threshold = $this->config->getThreshold();
        if (count($ids) <= $threshold) {
            return $proceed();
        }
        $user = $this->authSession->getUser();
        $profile = $this->profileFactory->create();
        $profile->setOrderIds($ids)->setProfileType(
            Profile::TYPE_ADMIN
        )->setUserId((int)$user->getId())
            ->setReceiverName($user->getName())
            ->setReceiverEmail($user->getEmail());
        $this->profileResource->save($profile);
        $this->publish->execute($profile);
        $this->messageManager->addSuccessMessage(
            __('Your profile id [%1] ,your export will be emailed to: [%2],you can also check status process at System -> Order Export Profiles',
                $profile->getProfileId(),
                $user->getEmail())
        );
        $redirect = $this->redirectFactory->create();
        return $redirect->setPath('*/*/');
    }

    /**
     * Get Order Collection Factory
     *
     * @return \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     * @deprecated 100.1.3
     */
    private function getOrderCollection()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Sales\Model\ResourceModel\Order\CollectionFactory::class
            );
        }
        return $this->orderCollectionFactory;
    }
}
