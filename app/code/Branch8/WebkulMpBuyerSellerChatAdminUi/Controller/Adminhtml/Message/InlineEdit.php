<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChatAdminUi\Controller\Adminhtml\Message;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Magento\Backend\App\Action;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Controller for the 'mpchatsystem/message/inlineedit' URL route.
 */
class InlineEdit extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session.
     */
    const ADMIN_RESOURCE = 'Webkul_MpBuyerSellerChat::message';

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

    private  $messageDataFactory;

    /**
     * @param Action\Context $context
     * @param \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory $messageDataFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Api\DataObjectHelper $dataObjectHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Escaper|null $escaper
     */
    public function __construct(
        Action\Context                                                     $context,
        \Branch8\WebkulMpBuyerSellerChat\Model\Api\Data\MessageDataFactory $messageDataFactory,
        \Magento\Framework\Controller\Result\JsonFactory                   $resultJsonFactory,
        \Magento\Framework\Api\DataObjectHelper                            $dataObjectHelper,
        \Psr\Log\LoggerInterface                                           $logger,
        \Magento\Framework\Escaper                                         $escaper = null
    )
    {
        $this->messageDataFactory = $messageDataFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger = $logger;
        $this->escaper = $escaper ?: ObjectManager::getInstance()->get(\Magento\Framework\Escaper::class);
        parent::__construct($context);
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
            $message = $this->messageDataFactory->create()->load($id);
            foreach ($postItems[$id] as $key => $value) {
                $message->setData($key, $value);
            }
            $message->save();
        }

        return $resultJson->setData(
            [
                'messages' => [],
                'error' => false
            ]
        );
    }
}
