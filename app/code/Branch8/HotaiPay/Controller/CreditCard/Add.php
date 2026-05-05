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
use Branch8\HotaiPay\Model\CreditCard\Path;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;

class Add extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public $hotaiPayService;
    protected $pageFactory;
    private $path;

    private HotaiPayLogHelper $hotaiPayLogHelper;

    /**
     * __construct
     *
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
    public function execute()
    {
        $data = $this->getDataFromRequest($this->getRequest());
        $redirectUrl = $this->cleanUrl($data['redirectUrl'] ?? '');

        $payload = [];
        $payload['RedirectURL'] = $redirectUrl;

        try{
            //Get Response
            $response = $this->hotaiPayService->api->creditCard->addCreditCardManually($payload);
        } catch (\Exception $e) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $e->getMessage(), __CLASS__);
            $resultRedirect = $this->resultRedirectFactory->create();
            $returnPath = $this->path->getReturnPath($redirectUrl);
            
            return $resultRedirect->setPath($returnPath, [
                '_secure' => $this->getRequest()->isSecure(), '_query' => ['StatusDesc' => $e->getMessage()]
            ]);
        }

        //If success params is false, then redirect to setup page and notice customer
        if ($response && isset($response['success'])) {
            if ($response['success'] == false) {
                $resultRedirect = $this->resultRedirectFactory->create();
                $returnPath = $this->path->getReturnPath($redirectUrl);

                return $resultRedirect->setPath($returnPath, [
                    '_secure' => $this->getRequest()->isSecure(), '_query' => ['StatusDesc' => $response['error']]
                ]);
            }
        }

        try{
            $page = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $block = $page->getLayout()->getBlock('hotaipay.creditcard.add');

            $text = is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE);

            $this->hotaiPayLogHelper->writeLog($text, __CLASS__);

            $block->setData('reqjsonpwd', $response['data']['reqjsonpwd']);
            return $page;
        } catch(\Exception $e) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $returnPath = $this->path->getReturnPath($redirectUrl);

            $this->hotaiPayLogHelper->writeLog('[Exception] ' . json_encode($e->getMessage()), __CLASS__);

            $text = is_string($response) ? $response :json_encode($response, JSON_UNESCAPED_UNICODE);

            $this->hotaiPayLogHelper->writeLog($text, __CLASS__);
            
            return $resultRedirect->setPath($returnPath, [
                '_secure' => $this->getRequest()->isSecure(), '_query' => ['StatusDesc' => $e->getMessage()]
            ]);
        }
        
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

    /**
     * Clean URL by removing query string and fragment
     *
     * @param string $url
     * @return string
     */
    private function cleanUrl(string $url): string
    {
        if (empty($url)) {
            return $url;
        }
        
        // Remove query string (?...) and fragment (#...)
        return preg_replace('/[?#].*$/', '', $url);
    }
}
