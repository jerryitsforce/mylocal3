<?php

namespace Branch8\Sales\Helper;

use Branch8\HotaiCore\Model\Order\State as HotaiState;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;

class FrontOrderFlow
{
    const PROGRESS_TYPE_DELIVERY = 1;
    const PROGRESS_TYPE_CONVIENCE_STORE = 2;
    const PROGRESS_TYPE_TICKET = 3;

    const FORMAL_STATUS = [
        HotaiStatus::STATUS_PROCESSING,
        HotaiStatus::STATUS_TALLYING,
        HotaiStatus::STATUS_SHIPPING,
        HotaiStatus::STATUS_ARRIVED,
    ];

    const FORMAL_STATUS_PENDING = [
        HotaiStatus::STATUS_PENDING
    ];

    public function getFormalFlow($type, $status) {
        $array = self::FORMAL_STATUS;

        if ($type == self::PROGRESS_TYPE_CONVIENCE_STORE) {
            $array[] =  HotaiStatus::STATUS_PICKED;
        }

        if ($type == self::PROGRESS_TYPE_TICKET) {
            $array =  [
                HotaiStatus::STATUS_PROCESSING,
                HotaiStatus::STATUS_ARRIVED
            ];
        }
        
        //$array[] = HotaiStatus::STATUS_COMPLETE;
        return $this->flowFormatter($array);
    }

    const RETURN_STATUS = [ //退貨
        HotaiState::STATE_APPLYING_RETURN,
        HotaiState::STATE_APPLYING_RETURN_FAIL,
        HotaiState::STATE_PROCESSING_RETURN_REVIEW,
        HotaiState::STATE_PROCESSING_REFUND,
        HotaiState::STATE_RETURNED,
        HotaiState::STATE_RETURNED_FAILED,
        HotaiState::STATE_RETURNED_CANCEL,
        HotaiState::STATE_PROCESSING_SHIPPING,
    ];

    const REPLACE_STATUS = [ //換貨
        HotaiState::STATE_APPLYING_REPLACE,
        HotaiState::STATE_APPLYING_REPLACE_FAIL,
        HotaiState::STATE_PROCESSING_REPLACE_REVIEW,
        HotaiState::STATE_PROCESSING_REPLACE,
        HotaiState::STATE_PROCESSING_REPLACE_SHIPPING,
        HotaiState::STATE_REPLACED,
        HotaiState::STATE_REPLACE_FAILED,
        HotaiState::STATE_REPLACE_CANCEL,
        HotaiState::STATE_REPLACE_PICKED,
        HotaiState::STATE_REPLACE_ARRIVED,
        HotaiState::STATE_PROCESSING_REPLACE_SHIP_TO_SUP
    ];

    public function getRmaFlow($type, $status)
    {
        $isReturn = in_array($status, self::RETURN_STATUS);
        $isReplace = in_array($status, self::REPLACE_STATUS);

        if ($isReturn) {
            $flow = $this->getReturnFlow($type, $status);
            return $this->flowFormatter($flow);
        }

        if ($isReplace) {
            $flow = $this->getReplaceFlow($type, $status);
            return $this->flowFormatter($flow);
        }

        return [];
    }

    public function flowFormatter($array)
    {
        $return = [];
        foreach ($array as $key => $value) {
            $return[$key] = [
                "Status" => $value,
                "Label" => $value,
            ];

        }

        return $return;

    }

    
    /**
     * getReturnFlow  退貨流程   
     *
     * @param  mixed $type
     * @param  mixed $status
     * @return array
     */
    public function getReturnFlow($type, $status)
    {
        //Default Return
        $returnArray = [
            HotaiState::STATE_APPLYING_RETURN,
            HotaiState::STATE_PROCESSING_SHIPPING,
            HotaiState::STATE_PROCESSING_RETURN_REVIEW,
            HotaiState::STATE_PROCESSING_REFUND,
            HotaiState::STATE_RETURNED,
        ];

        // 票券 Default
        if ($type == self::PROGRESS_TYPE_TICKET) {
            $returnArray = [
                HotaiState::STATE_APPLYING_RETURN,
                HotaiState::STATE_PROCESSING_REFUND,
                HotaiState::STATE_RETURNED,
            ];
        }

        // 申請失敗
        if ($status == HotaiState::STATE_APPLYING_RETURN_FAIL) {
            $returnArray = [
                HotaiState::STATE_APPLYING_RETURN,
                HotaiState::STATE_APPLYING_RETURN_FAIL,
            ];
        }

        // 審核失敗
        if ($status == HotaiState::STATE_RETURNED_FAILED) {
            $returnArray = [
                HotaiState::STATE_APPLYING_RETURN,
                HotaiState::STATE_PROCESSING_SHIPPING,
                HotaiState::STATE_PROCESSING_RETURN_REVIEW,
                HotaiState::STATE_RETURNED_FAILED,
            ];
        }

        return $returnArray;
    }

    public function getReplaceFlow($type, $status)
    {
        //票券沒有換貨
        if ($type == self::PROGRESS_TYPE_TICKET) {
            return [];
        }

        // 換貨申請失敗
        if ($status == HotaiState::STATE_APPLYING_REPLACE_FAIL) {
            return [
                HotaiState::STATE_APPLYING_REPLACE,
                HotaiState::STATE_APPLYING_REPLACE_FAIL,
            ];
        }
        // 換貨審核失敗
        if ($status == HotaiState::STATE_APPLYING_REPLACE_FAIL) {
            return [
                HotaiState::STATE_APPLYING_REPLACE,
                HotaiState::STATE_PROCESSING_REPLACE_SHIP_TO_SUP,
                HotaiState::STATE_PROCESSING_REPLACE_REVIEW,
                HotaiState::STATE_REPLACE_FAILED,
            ];
        }

        //Default(宅配) 超取目前也是用宅配
        $returnArray = [
            HotaiState::STATE_APPLYING_REPLACE,
            HotaiState::STATE_PROCESSING_REPLACE_SHIP_TO_SUP,
            HotaiState::STATE_PROCESSING_REPLACE_REVIEW,
            HotaiState::STATE_PROCESSING_REPLACE_SHIPPING,
            HotaiState::STATE_REPLACE_ARRIVED,
        ];

        return $returnArray;

    }

}
