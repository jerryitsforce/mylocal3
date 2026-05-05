<?php
declare(strict_types=1);

namespace Branch8\ProductAlert\Controller\Confirm;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Index extends Action implements CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Create exception in case CSRF validation failed.
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(400);
        $result->setData(['success' => false, 'message' => __('Invalid Form Key. Please refresh the page.')]);

        return new InvalidRequestException(
            $result,
            [__('Invalid Form Key. Please refresh the page.')]
        );
    }

    /**
     * Perform custom CSRF validation.
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true; // Default Magento CSRF validation is sufficient for this action
    }

    /**
     * Set confirm_productalert flag in session
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $this->customerSession->setConfirmProductAlert(true);
        
        $result = $this->resultJsonFactory->create();
        return $result->setData(['success' => true]);
    }
}
