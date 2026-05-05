<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Ajax;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\ProductCertification\Model\CertificationTypeRepository;
use Branch8\ProductCertification\Helper\ImageUploader;
use Branch8\ProductCertification\Helper\Config;

/**
 * AJAX: Returns active certification types for a given category ID
 * Used by the product edit page's dynamic accordion
 */
class GetByCategoryAction extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Catalog::products';

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var CertificationTypeRepository
     */
    private CertificationTypeRepository $repository;

    /**
     * @var ImageUploader
     */
    private ImageUploader $imageUploader;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CertificationTypeRepository $repository
     * @param ImageUploader $imageUploader
     * @param Config $config
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CertificationTypeRepository $repository,
        ImageUploader $imageUploader,
        Config $config
    ) {
        parent::__construct($context);
        $this->jsonFactory   = $jsonFactory;
        $this->repository    = $repository;
        $this->imageUploader = $imageUploader;
        $this->config        = $config;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result      = $this->jsonFactory->create();
        $categoryIds = $this->getRequest()->getParam('category_ids');

        if (!is_array($categoryIds)) {
            $categoryIds = $categoryIds ? explode(',', (string)$categoryIds) : [];
        }

        // Filter and clean IDs
        $categoryIds = array_filter(array_map('intval', $categoryIds));

        if (!$this->config->isEnabled() || empty($categoryIds)) {
            return $result->setData(['certifications' => []]);
        }

        $types         = $this->repository->getActiveByCategoryIds($categoryIds);
        $certifications = [];

        foreach ($types as $type) {
            $certifications[] = [
                'id'       => $type->getId(),
                'name'     => $type->getData('certification_name'),
                'icon_url' => $type->getData('icon')
                    ? $this->imageUploader->getIconUrl($type->getData('icon'))
                    : '',
            ];
        }

        return $result->setData(['certifications' => $certifications]);
    }
}
