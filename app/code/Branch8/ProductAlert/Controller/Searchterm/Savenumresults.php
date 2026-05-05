<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\ProductAlert\Controller\Searchterm;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class Savenumresults extends \Magento\Framework\App\Action\Action implements HttpPostActionInterface
{

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Json
     */
    protected $serializer;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var Http
     */
    protected $http;

    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    protected $connection;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Http $http
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $resultPageFactory,
        Json $json,
        LoggerInterface $logger,
        ResourceConnection $resourceConnection,
        Http $http
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->serializer = $json;
        $this->logger = $logger;
        $this->connection = $resourceConnection->getConnection();
        $this->http = $http;
    }

    public function execute()
    {
        try{
            $num_results = (int)$this->_request->getParam('num_results');
            $query_text = $this->_request->getParam('query_text');
            if($num_results > 0 && !empty($query_text)){
                $updateData  = [
                    'num_results' => $num_results
                ];
                $whereUpdate = [
                    'query_text = ?' => $query_text
                ];
                $this->connection->update(
                    'search_query',
                    $updateData,
                    $whereUpdate
                );
            }
        } catch (\Exception $e) {}
    }
}
