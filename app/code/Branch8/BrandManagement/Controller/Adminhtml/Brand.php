<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Branch8\BrandManagement\Api\BrandOptionRepositoryInterface;

/**
 * Brand Controller
 */
abstract class Brand extends Action implements CsrfAwareActionInterface
{
    /**
     * Authorization level of a basic admin session
     */
    const ADMIN_RESOURCE = 'Branch8_BrandManagement::brand_management';

    /**
     * @var BrandOptionRepositoryInterface
     */
    protected $brandOptionRepository;

    /**
     * @var Registry
     */
    protected $coreRegistry;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param BrandOptionRepositoryInterface $brandOptionRepository
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        BrandOptionRepositoryInterface $brandOptionRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->brandOptionRepository = $brandOptionRepository;
        parent::__construct($context);
    }

    /**
     * Init brand option
     *
     * @param int $optionId
     * @return array|null
     */
    protected function initBrandOption($optionId = null)
    {
        if ($optionId) {
            $brandOption = $this->brandOptionRepository->getBrandOptionById($optionId);
            if ($brandOption) {
                $this->coreRegistry->register('brand_option', $brandOption);
                return $brandOption;
            }
        }
        return null;
    }

    /**
     * Create exception in case CSRF validation failed.
     * Return null if default exception will suffice.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * Perform custom request validation.
     * Return null if default validation is sufficient.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
