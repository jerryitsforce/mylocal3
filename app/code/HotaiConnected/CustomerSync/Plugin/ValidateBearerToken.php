<?php
namespace HotaiConnected\CustomerSync\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Psr\Log\LoggerInterface as Logger;
use Magento\Framework\App\ResourceConnection;

class ValidateBearerToken
{
    protected $request;
    protected $response;
    protected $logger;
    protected $resourceConnection;
    
    public function __construct(
        RequestInterface $request,
        Response $response,
        Logger $logger,
        ResourceConnection $resourceConnection
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }
    
    public function beforeDeleteCustomerSync($subject)
    {
        $this->validateToken();
        return ;
    }
    
    public function beforeGetCustomerSync($subject)
    {
        $this->validateToken();
        return;
    }
    
    protected function validateToken()
    {
        $headers = getallheaders();

        if (!isset($headers['Apikey'])) {
            $this->logger->info("[customer_sync] APIKEY is missing in header");
            $this->serverResponse(2, false, ["刪除失敗，請稍後再試", "APIKEY_NOT_FOUND_IN_HEADER"]);
            return;
        }
        
        if (!$this->isValidToken($headers['Apikey'])) {
            $this->logger->info("[customer_sync] APIKEY is not valided");
            $this->serverResponse(2, false, ["刪除失敗，請稍後再試", "APIKEY_NOT_VALIDED"]);
            return;
        }
    }
    
    protected function isValidToken($token)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('ticket_api_merchant', ['aes_key'])
            ->where('merchant_name = ?', 'MemberAutoDelete');
        
        $validToken = $connection->fetchOne($select);
        
        if (!$validToken) {
            $this->logger->error("[customer_sync] Failed to retrieve API key from database");
            return false;
        }
        
        return $token === $validToken;
    }

    protected function serverResponse($code, $success, $msg)
    {
        $response = [
            'rtnCode' => $code,
            'success' => $success,
            'msg' => $msg,
        ];
    
        return $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setStatusCode(200)
            ->setBody(json_encode($response))
            ->sendResponse();
    }
}
