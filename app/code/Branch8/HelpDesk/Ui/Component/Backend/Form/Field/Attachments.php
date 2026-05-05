<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Ui\Component\Backend\Form\Field;

use Branch8\HelpDesk\Model\Ticket\AttachmentUploaderConfig;
use Branch8\HelpDesk\ViewModel\TicketData;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Attachments extends \Magento\Ui\Component\Form\Field
{
    private $urlInterface;

    private $scopeConfig;

    private $uploaderConfig;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $url
     * @param AttachmentUploaderConfig $attachmentUploaderConfig
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface         $context,
        UiComponentFactory       $uiComponentFactory,
        ScopeConfigInterface     $scopeConfig,
        UrlInterface             $url,
        AttachmentUploaderConfig $attachmentUploaderConfig,
        array                    $components = [],
        array                    $data = []
    )
    {
        $this->uploaderConfig = $attachmentUploaderConfig;
        $this->scopeConfig = $scopeConfig;
        $this->urlInterface = $url;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();
        $this->_data['config']['searchUrl'] = $this->urlInterface->getUrl('helpdesk/ticket/LoadRecentOrders');
        $this->_data['config']['template'] = 'ui/form/element/uploader/uploader';
        $this->_data['config']['uploaderConfig'] = [
            'url' => $this->urlInterface->getUrl('helpdesk/message/postAttachment')
        ];
        $this->_data['config']['maxImageUploadCount'] = (int)$this->scopeConfig->getValue(TicketData::XML_PATH_MAX_IMAGE_UPLOAD_FILES);
        $this->_data['config']['allowedExtensions'] = implode(' ', $this->uploaderConfig->getAllowExtensionFiles());
        $this->_data['config']['notice'] = __('<span>※ Upload restrictions</span> <br/><span>File type: %1</span><br/><span>File size: %2</span>',
            implode(',', $this->uploaderConfig->getAllowExtensionFiles()),
            $this->scopeConfig->getValue(TicketData::XML_PATH_MAX_IMAGE_MAX_FILE_SIZE) . 'mb'
        );
    }
}
