<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml\Message;

use Branch8\HelpDesk\Model\Ticket\AttachmentUploaderConfig;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Post Attachment Class
 */
class PostAttachment extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    /**
     * @var
     */
    protected $imageUploader;
    private AttachmentUploaderConfig $uploaderConfig;

    /**
     * @param Context $context
     * @param AttachmentUploaderConfig $uploaderConfig
     * @param \Magento\Catalog\Model\ImageUploader $imageUploader
     */
    public function __construct(
        Context                              $context,
        AttachmentUploaderConfig             $uploaderConfig,
        \Magento\Catalog\Model\ImageUploader $imageUploader
    )
    {
        $this->imageUploader = $imageUploader;
        $this->uploaderConfig = $uploaderConfig;
        parent::__construct($context);
    }

    /**
     * Execute function
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|(\Magento\Framework\Controller\Result\Json&\Magento\Framework\Controller\ResultInterface)|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $imageId = $this->_request->getParam('param_name', 'attachment');
        try {
            $result = $this->imageUploader->saveFileToTmpDir($imageId);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode(), 'name' => $this->_request->getParam('name')];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
