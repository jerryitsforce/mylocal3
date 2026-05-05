<?php
namespace Branch8\MarketplaceProduct\Model\Data;

use Branch8\MarketplaceProduct\Api\Data\ApproveProductDataInterface;

class ApproveProductData implements ApproveProductDataInterface
{
    protected $productId;
    protected $marketplaceProductId;
    protected $sellerId;
    protected $date;
    protected $reviewerInfo;
    protected $gridNamespace;

    public function getProductId()
    {
        return $this->productId;
    }

    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }

    public function getMarketplaceProductId()
    {
        return $this->marketplaceProductId;
    }

    public function setMarketplaceProductId($marketplaceProductId)
    {
        $this->marketplaceProductId = $marketplaceProductId;
        return $this;
    }

    public function getSellerId()
    {
        return $this->sellerId;
    }

    public function setSellerId($sellerId)
    {
        $this->sellerId = $sellerId;
        return $this;
    }

    public function getReviewerInfo()
    {
        return $this->reviewerInfo;
    }

    public function setReviewerInfo($info)
    {
        $this->reviewerInfo = $info;
        return $this;
    }

    /**
     * @param $date_time
     * @return $this|mixed
     */
    public function setDateTime($date_time)
    {
        $this->date = $date_time;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateTime()
    {
       return $this->date;
    }

    public function getGridNamespace()
    {
        return $this->gridNamespace;
    }

    public function setGridNamespace($gridNamespace)
    {
        $this->gridNamespace = $gridNamespace;
        return $this;
    }

}
