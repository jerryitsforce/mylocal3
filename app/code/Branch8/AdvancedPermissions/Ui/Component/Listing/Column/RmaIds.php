<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\AdvancedPermissions\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class RmaIds extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    protected $rmaData = [];

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    if (!isset($item[$fieldName])) {
                        $item[$fieldName] = '';
                        try {
                            $orderId = (int)$item['entity_id'];
                            if(!count($this->rmaData)){
                                $orderIds = array_column($dataSource['data']['items'], 'entity_id');

                                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                                $connection = $resource->getConnection();

                                $table = $resource->getTableName('marketplace_rma_details');

                                $rmaData = $connection->fetchAll(
                                    $connection->select()
                                        ->from($table, ['order_id' => 'order_id','rmaids' =>   "GROUP_CONCAT(DISTINCT marketplace_rma_details.id SEPARATOR ',') AS rmaids"])
                                        ->where('order_id IN (?)', $orderIds)
                                );
                                foreach($rmaData as $_rmaData){
                                    $this->rmaData[$_rmaData['order_id']] = $_rmaData['rmaids'];
                                }
                                
                            }
                            $item[$fieldName] = isset($this->rmaData[$orderId]) ? $this->rmaData[$orderId] : [];

                        } catch (\Exception $e) {
                        }
                    }
                    $rmaIds = $item[$fieldName] ? explode(',', $item[$fieldName]) : [];
                    $result = [];
                    foreach ($rmaIds as $id) {
                        $result[] = "<a href='" . $this->urlBuilder->getUrl(
                                'mprmasystem/rma/edit',
                                ['id' => $id]
                            ) . "' target='_blank' title='" .
                            __('%1') . "'>" . $id . '</a>';
                    }
                    $item[$fieldName] = implode(' ', $result);
                }
            }
        }

        return $dataSource;
    }
}
