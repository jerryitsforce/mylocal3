<?php
namespace Branch8\MarketplaceProduct\Api\Data;

interface ApproveProductDataInterface
{
    /**
     * @return int
     */
    public function getProductId();

    /**
     * @param int $product_id
     * @return $this
     */
    public function setProductId($product_id);

    /**
     * @return int
     */
    public function getSellerId();

    /**
     * @param int $seller_id
     * @return $this
     */
    public function setSellerId($seller_id);

    /**
     * @return int
     */
    public function getMarketplaceProductId();

    /**
     * @param int $marketplace_product_id
     * @return $this
     */
    public function setMarketplaceProductId($marketplace_product_id);

    /**
     * @return string
     */
    public function getReviewerInfo();

    /**
     * @param string $info
     * @return $this
     */
    public function setReviewerInfo($info);

    /**
     * @param $date_time
     * @return mixed
     */
    public function setDateTime($date_time);

    /**
     * @return mixed
     */
    public function getDateTime();

    /**
     * @return string
     */
    public function getGridNamespace();

    /**
     * @param string $gridNamespace
     * @return $this
     */
    public function setGridNamespace($gridNamespace);
}
