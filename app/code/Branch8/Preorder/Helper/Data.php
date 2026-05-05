<?php

namespace Branch8\Preorder\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
class Data extends \Magento\Framework\App\Helper\AbstractHelper{
    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;

    /**
     * @param \Magento\Catalog\Model\Product\Action $productAction
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timeZone;

    public function __construct(
        \Magento\Catalog\Model\Product\Action $productAction,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timeZone
    ){
        $this->productAction = $productAction;
        $this->productRepository = $productRepository;
        $this->timeZone = $timeZone;
    }

    /**
     * @param $productId
     * @param $attribute
     * @param $value
     * @param $storeId
     * @return void
     */
    public function updateProductAttribute($productId, $attribute, $value, $storeId){
        $this->productAction->updateAttributes([$productId], [$attribute => $value], $storeId);
    }

    public function getPdpMessage($productId){
        $msg = '';
        if(in_array(gettype($productId), ['integer', 'string'])){
            $product = $this->productRepository->getById($productId);
        }else{
            $product = $productId;
        }

        $preorderMode  = $product->getData('preorder_mode');
        if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE){
            $availableDate = $product->getData('wk_marketplace_availability');
//            $avaiDate = $this->timeZone->formatDate($availableDate, 0, false);
            $avaiDate = $this->timeZone->date($availableDate)->format('m/d');
            $msg .= "<div class='wk-msg-box wk-info wk-availability-block'>";
            $msg .= "<span class='wk-date-title'>";
            $msg .= sprintf(__('Estimated shipping on %s'), $avaiDate);
            $msg .= ' </span>';
            $msg .= '</div>';
        }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS){
            $xDays = $product->getData('preorder_x_days');
            if($xDays < 2){
                $avaiDate =sprintf(__('%s day later'), $xDays);
            }else{
                $avaiDate = sprintf(__('%s days later'), $xDays);
            }
            // $avaiDate = $xDays.' '.$xDaySuffix;
            $msg .= "<div class='wk-msg-box wk-info wk-availability-block'>";
            $msg .= "<span class='wk-date-title'>";
            $msg .= sprintf(__('Estimated shipping %s ordering.'), $avaiDate);
            $msg .= ' </span>';
            $msg .= '</div>';
            
        }else if($preorderMode == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE){
            $availableDate = $product->getData('preorder_ship_date');
//            $avaiDate = $this->timeZone->formatDate($availableDate, 0, false);
            $avaiDate =  $this->timeZone->date($availableDate)->format('m/d');
            $msg .= "<div class='wk-msg-box wk-info wk-availability-block'>";
            $msg .= "<span class='wk-date-title'>";
            $msg .= sprintf(__('Estimated shipping on %s'), $avaiDate);
            $msg .= ' </span>';
            $msg .= '</div>';
        }


        return $msg;
    }



}
