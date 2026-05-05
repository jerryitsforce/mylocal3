<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Block\Info;

use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var HttpContext
     */
    protected $httpContext;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        HttpContext $httpContext,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->httpContext = $httpContext;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    public function getCustomer(){
        if ($this->httpContext->getValue('customer_id')) {
            return $this->customerRepository->getById($this->httpContext->getValue('customer_id'));
        }
        return null;
    }
}

