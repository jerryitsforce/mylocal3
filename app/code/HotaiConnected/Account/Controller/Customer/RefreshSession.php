<?php
namespace HotaiConnected\Account\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use HotaiConnected\Account\Service\AutoLoginService;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;

class RefreshSession extends Action
{
    private $jsonFactory;
    private $customerSession;
    private $autoLoginService;
    private $formKeyValidator;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        AutoLoginService $autoLoginService,
        \Magento\Customer\Model\Session $customerSession,
        FormKeyValidator $formKeyValidator
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
        $this->autoLoginService = $autoLoginService;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        // post檢查
        if (!$this->getRequest()->isPost()) {
            return $result->setData([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        // 驗證表單金鑰
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'success' => false,
                'message' => 'Invalid form key'
            ]);
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData([
                'success' => false,
                'message' => 'Not logged in'
            ]);
        }

        // 刷新 session
        $status = $this->autoLoginService->refreshLogin();

        if ($status) {
            return $result->setData([
                'success' => true,
                'message' => 'Session refreshed'
            ]);
        }

        return $result->setData([
            'success' => false,
            'message' => 'refresh error'
        ]);
    }
}