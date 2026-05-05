<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Controller\Chat;

use Branch8\WebkulMpBuyerSellerChat\Model\Actions\GetOrCreateChatProfile;
use Branch8\WebkulMpBuyerSellerChat\Model\Chat\MediaUploader;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatConversationRepository;
use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Class PostAttachment
 */
class UseProfileImage extends AbstractController implements HttpPostActionInterface
{
    /**
     * Image uploader
     *
     * @var MediaUploader
     */
    protected $imageUploader;
    /**
     * @var ChatConversationRepository
     */
    private ChatConversationRepository $chatConverstationRepository;
    /**
     * @var Session
     */
    private Session $session;
    /**
     * @var GetOrCreateChatProfile
     */
    private GetOrCreateChatProfile $getOrCreateChatProfile;
    /**
     * @var ChatProfileRepository
     */
    private ChatProfileRepository $chatProfileRepository;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\ImageUploader $imageUploader
     * @param ChatConversationRepository $chatConversationRepository
     * @param GetOrCreateChatProfile $getOrCreateChatProfile
     * @param ChatProfileRepository $chatProfileRepository
     * @param Session $session
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ImageUploader  $imageUploader,
        ChatConversationRepository            $chatConversationRepository,
        GetOrCreateChatProfile                $getOrCreateChatProfile,
        ChatProfileRepository                 $chatProfileRepository,
        Session                               $session
    )
    {
        parent::__construct($context);
        $this->imageUploader = $imageUploader;
        $this->chatProfileRepository = $chatProfileRepository;
        $this->session = $session;
        $this->getOrCreateChatProfile = $getOrCreateChatProfile;
    }

    /**
     * Upload file controller action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            $uniqueId = $this->_request->getParam('unique_id');
            $profile = $this->chatProfileRepository->getByUniqueId($uniqueId);
            $imageId = $this->_request->getParam('fileName');
            $result = $this->imageUploader->moveFileFromTmp(
                $imageId
            );
            $profile->setImage($result);
            $this->chatProfileRepository->save($profile);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
