<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\ProductCertification\Helper\ImageUploader;

/**
 * Admin AJAX: Upload certification icon to tmp
 */
class Upload extends Action
{
    public const ADMIN_RESOURCE = 'Branch8_ProductCertification::certification_manage';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ImageUploader $imageUploader
    ) {
        parent::__construct($context);
        $this->jsonFactory   = $jsonFactory;
        $this->imageUploader = $imageUploader;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        try {
            $data = $this->imageUploader->uploadToTmp('icon');
            return $result->setData($data);
        } catch (\Exception $e) {
            return $result->setData(['error' => $e->getMessage(), 'errorcode' => $e->getCode()]);
        }
    }
}
