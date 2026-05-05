<?php
namespace HotaiConnected\BigQuery\Helper;

use Google\Cloud\BigQuery\BigQueryClient;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    const XML_PATH_JSON_KEY = 'hotai_bigquery/general/key_content';

    protected $encryptor;
    protected $logger;
    private $client;

    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->logger = $logger;
    }

    /**
     * 初始化並取得 BigQueryClient
     * 
     * @return BigQueryClient
     * @throws \Exception
     */
    public function getClient()
    {
        if ($this->client === null) {
            $encryptedJson = $this->scopeConfig->getValue(self::XML_PATH_JSON_KEY);
            if (!$encryptedJson) {
                throw new \Exception("BigQuery JSON key is not configured.");
            }

            $jsonContent = $this->encryptor->decrypt($encryptedJson);
            $keyData = json_decode($jsonContent, true);

            if (!$keyData || !isset($keyData['project_id'])) {
                throw new \Exception("Invalid BigQuery JSON key format.");
            }

            $this->client = new BigQueryClient([
                'projectId' => $keyData['project_id'],
                'keyFile' => $keyData,
            ]);
        }
        return $this->client;
    }

    /**
     * 執行 SELECT 查詢
     * 
     * @param string $query
     * @param array $parameters
     * @return \Google\Cloud\BigQuery\QueryResults
     */
    public function select($query, $parameters = [])
    {
        try {
            $client = $this->getClient();
            $queryConfig = $client->query($query);
            if (!empty($parameters)) {
                $queryConfig->parameters($parameters);
            }
            return $client->runQuery($queryConfig);
        } catch (\Exception $e) {
            $this->logger->error("BigQuery Select Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 執行 INSERT (串流插入資料)
     * 
     * @param string $datasetId
     * @param string $tableId
     * @param array $rows 多維陣列，格式如 [['field' => 'val'], ...]
     * @return bool
     */
    public function insert($datasetId, $tableId, $rows)
    {
        try {
            $dataset = $this->getClient()->dataset($datasetId);
            $table = $dataset->table($tableId);
            $insertResponse = $table->insertRows($rows);

            if (!$insertResponse->isSuccessful()) {
                foreach ($insertResponse->failedRows() as $row) {
                    $this->logger->error("BigQuery Insert Row Failed: " . json_encode($row['errors']));
                }
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this->logger->error("BigQuery Insert Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 執行 DELETE (透過 DML 查詢語法)
     * 
     * @param string $query DELETE FROM ... WHERE ...
     * @return void
     */
    public function delete($query)
    {
        // BigQuery 的 DELETE 是透過 DML Query 執行的
        $this->select($query);
    }
}
