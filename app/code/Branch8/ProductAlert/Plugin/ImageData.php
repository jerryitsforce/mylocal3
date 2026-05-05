<?php

namespace Branch8\ProductAlert\Plugin;

use Magento\Checkout\Model\Cart\ImageProvider;
use Magento\Framework\App\ResourceConnection;

class ImageData
{

    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var AdapterInterface
     */
    protected $connection;
    protected $data;

    public function __construct(
        ResourceConnection $resource,
        \Branch8\ProductAlert\Helper\Data $data
    )
    {
        $this->data = $data;
        $this->resource = $resource;
    }
    /**
     * @param ImageProvider $subject
     * @param array $result
     * @param $cartId
     * @return array
     */
    public function afterGetImages(ImageProvider $subject, array $result, $cartId): array
    {
        foreach ($result as $itemId => $item) {
            if($this->data->getEighteenProducts($this->getProductIdFromCart($itemId))){
                $result[$itemId]['eighteen_product'] = true;
                $result[$itemId]['img_src'] = $this->data->getProductAlertImage();
            }else{
                $result[$itemId]['eighteen_product'] = false;
            }
        }

        return $result;
    }

    /**
     * @param $ItemId
     * @return string
     */
    public function getProductIdFromCart($itemId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['quote_item' => 'quote_item'], ['product_id'])
            ->where('item_id = ?', $itemId);
        return $connection->fetchOne($select);
    }

    /**
     * @return AdapterInterface|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }
        return $this->connection;
    }
}
