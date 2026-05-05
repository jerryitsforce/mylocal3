<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Controller\Chat;

use Branch8\WebkulMpBuyerSellerChat\Model\Chat\MediaUploader;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class PostAttachment
 */
class PostMedia extends AbstractController implements HttpPostActionInterface
{
    /**
     * Image uploader
     *
     * @var MediaUploader
     */
    protected $imageUploader;
    private ChatConversationRepository $chatConverstationRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\ImageUploader $imageUploader
     * @param ChatConversationRepository $chatConversationRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ImageUploader  $imageUploader,
        ChatConversationRepository            $chatConversationRepository
    )
    {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->chatConverstationRepository = $chatConversationRepository;
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $imageId = $this->_request->getParam('param_name', 'attachment');
            $conversationId = $this->_request->getParam('conversationUniqueId');
            $conversation = $this->chatConverstationRepository->loadConversationByCode(
                $conversationId
            );
            $result = $this->imageUploader->saveFileToDestinationFolder($conversationId, $imageId);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
