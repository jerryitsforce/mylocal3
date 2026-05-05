<?php
namespace Branch8\HotaiPay\Controller\CreditCard;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Branch8\HotaiPay\Service\HotaiPay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Result\PageFactory;
use Branch8\HotaiPay\Model\CreditCard\Path;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Exception;

class FastAdd extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public $hotaiPayService;
    protected $pageFactory;
    private $path;
    private HotaiPayLogHelper $hotaiPayLogHelper;
    
    /**
     * __construct
     *
     * @param Context $context
     * @param HotaiPay $hotaiPayService
     * @param Path $path
     * @param HotaiPayLogHelper $hotaiPayLogHelper
     * @return void
     */
    public function __construct(
        Context $context,
        HotaiPay $hotaiPayService,
        Path $path,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->hotaiPayService = $hotaiPayService;
        $this->path  = $path;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
        parent::__construct($context);
    }  

    /**
     * execute
     *
     * @return void | array | \Magento\Framework\Controller\Result\Redirect | \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $data = $this->getDataFromRequest($this->getRequest());
        $redirectUrl = $data['redirectUrl'];
        
        $payload = [];
        $payload['RedirectURL'] = $redirectUrl;
        $payload['IdNo'] = $data['idNo'];
        $payload['Birthday'] = $data['birthday'];
        
        try{
            //Get Response
            $response = $this->hotaiPayService->api->creditCard->addCreditCardFast($payload);
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $e->getMessage(), __CLASS__);
            $resultRedirect = $this->resultRedirectFactory->create();
            $returnPath = $this->path->getReturnPath($redirectUrl);
            
            return $resultRedirect->setPath($returnPath, [
                '_secure' => $this->getRequest()->isSecure(), '_query' => ['StatusDesc' => $e->getMessage()]
            ]);
        }
        

        //If success params is false, then redirect to setup page and notice customer
        if(isset($response['success'])) {
            if ($response['success'] == false) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $returnPath = $this->path->getReturnPath($redirectUrl);

                return $resultRedirect->setPath($returnPath, [
                    '_secure' => $this->getRequest()->isSecure(), '_query' => ['StatusDesc'=> $response['error']]
                ]);
            }
        } else {
            
            $this->messageManager->addErrorMessage(
                __('無法快速綁訂信用卡')
            );

            $resultRedirect = $this->resultRedirectFactory->create();
                $returnPath = $this->path->getReturnPath($redirectUrl);

            return $resultRedirect->setPath($returnPath, [
                '_secure' => $this->getRequest()->isSecure()
            ]);
        }

        //Send Request to CTBC
        $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $block = $page->getLayout()->getBlock('hotaipay.creditcard.add');
        $block->setData('encryptData', $response['data']['encryptData']);
        $block->setData('sessionId', $response['data']['sessionId']);
        return $page;
    } 

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    /**
     * getDataFromRequest
     *
     * @param  mixed $request
     * @return array
     */
    private function getDataFromRequest(RequestInterface $request): array
    {
        /** @var Http $request */
        if ($request->isPost()) {
            $data = $request->getPostValue();
        } else {
            return [];
        }
        return $data;
    }
}