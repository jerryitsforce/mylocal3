<?php

declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\DataExporter\Exception\UnableRetrieveData;
use Magento\DataExporter\Model\Logging\CommerceDataExportLoggerInterface as LoggerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\QueryXml\Model\QueryProcessor;

/**
 * Product attributes data provider
 */
class AttributesPlugin
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var ResourceConnection
     */
    private $queryProcessor;
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param QueryProcessor $queryProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        QueryProcessor     $queryProcessor,
        LoggerInterface    $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->queryProcessor = $queryProcessor;
    }

    /**
     * @param $subject
     * @param $output
     * @param array $values
     * @return array
     */
    public function afterGet($subject, $output, array $values): array
    {

        $keys = [];
        $ids = [];
        $storeViewCodes = [];
        foreach ($values as $value) {
            $key = implode('-',
                [$value['storeViewCode'],
                    $value['productId'],
                    'index_seller_id'
                ]
            );
            $keys[$key] = ['storeViewCode' => $value['storeViewCode'], 'key' => $key];
            $ids[$value['productId']] = $value['productId'];
            $storeViewCodes[$value['storeViewCode']] = $value['storeViewCode'];
        }
        if (count($ids)) {
            try {
                $cursor = $this->queryProcessor->execute('sellerIdQuery',
                    ['ids' => $ids]
                );
                while ($row = $cursor->fetch()) {
                    if (isset($ids[$row['ProductId']]) && isset($row['IndexSellerId'])) {
                        foreach ($storeViewCodes as $storeViewCode) {
                            $key = implode('-',
                                [
                                    $storeViewCode,
                                    $row['ProductId'],
                                    'index_seller_id'
                                ]
                            );
                            if (isset($output[$key])) {
                                $output[$key]['attributes']['value'] = [(int)$row['IndexSellerId']];
                            } else {
                                $output[$key] = [
                                    'productId' => $row['ProductId'],
                                    'storeViewCode' => $storeViewCode,
                                    'attributes' => [
                                        'attributeCode' => 'index_seller_id',
                                        'value' => [
                                            (int)$row['IndexSellerId']
                                        ],
                                        'valueId' => null
                                    ]
                                ];
                            }
                        }
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->error("Branch8_SellerInformationProductDataExport::overrideIndexSellerAttribute".$exception->getMessage(), ['exception' => $exception]);
                throw new UnableRetrieveData('Unable to retrieve attributes data', 0, $exception);
            }
        }
        return $output;
    }
}
