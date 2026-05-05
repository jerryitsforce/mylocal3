<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Controller\Adminhtml\Profiles;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Magento\Backend\App\Action;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Customer\Ui\Component\Listing\AttributeRepository;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Customer inline edit action
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Webkul_MpBuyerSellerChat::profiles';


    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Framework\Api\DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    private ChatProfileRepository $chatProfileRepository;

    /**
     * @param Action\Context $context
     * @param ChatProfileRepository $chatProfileRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper|null $escaper
     */
    public function __construct(
        Action\Context                                   $context,
        ChatProfileRepository                            $chatProfileRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Api\DataObjectHelper          $dataObjectHelper,
        \Psr\Log\LoggerInterface                         $logger,
        \Magento\Framework\Escaper                       $escaper = null
    )
    {
        $this->chatProfileRepository = $chatProfileRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger = $logger;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(\Magento\Framework\Escaper::class);
        parent::__construct($context);
    }

    /**
     * Get email notification
     *
     * @return EmailNotificationInterface
     * @deprecated 100.1.0
     */
    private function getEmailNotification()
    {
        if (!($this->emailNotification instanceof EmailNotificationInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                EmailNotificationInterface::class
            );
        } else {
            return $this->emailNotification;
        }
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData(
                [
                    'messages' => [
                        __('Please correct the data sent.')
                    ],
                    'error' => true,
                ]
            );
        }
        foreach (array_keys($postItems) as $id) {
            $chatProfile = $this->chatProfileRepository->getById($id);
            foreach ($postItems[$id] as $key => $value) {
                $chatProfile->setData($key, $value);
            }
            $this->chatProfileRepository->save($chatProfile);
        }

        return $resultJson->setData(
            [
                'messages' => [],
                'error' => false
            ]
        );
    }
}
