<?php

namespace Branch8\Hopes\Helper\Response;

class Message {
    const DATA_KEY_RETURN_CODE = "code";
    const DATA_KEY_RETURN_MESSAGE = "message";

    const RETURN_SUCCESS_UPDATE = 100;
    const RETURN_EMPTY_ARRAY = 101;
    const RETURN_ERROR_NO_INVOICE = 102;
    const RETURN_ERROR_FAILED_CREATE_SHIPMENT = 103;
    const RETURN_ERROR_FAILED_CREATE_RMA = 104;
    const RETURN_ERROR_FAILED_CREATE_CREDITMEMO = 105;
    const RETURN_ERROR_FAILED_UPDATE_SHIPMENT = 106;
    const RETURN_ERROR_FAILED_UPDATE_RMA = 107;
    const RETURN_ERROR_FAILED_CREATE_RMA_SHIPMENT = 108;
    const RETURN_ERROR_OTHER = 201;

    public static function getReturnMessageList($type) {
        
        switch ($type) {
            case self::RETURN_SUCCESS_UPDATE:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_SUCCESS_UPDATE,
                    self::DATA_KEY_RETURN_MESSAGE => 'Successful Updated.'
                ];
            case self::RETURN_EMPTY_ARRAY:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_EMPTY_ARRAY,
                    self::DATA_KEY_RETURN_MESSAGE => 'There Is No Need to Update Any Record.'
                ];
            case self::RETURN_ERROR_NO_INVOICE:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_NO_INVOICE,
                    self::DATA_KEY_RETURN_MESSAGE => 'There Is No Invoice Data.'
                ];
            case self::RETURN_ERROR_FAILED_CREATE_SHIPMENT:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_CREATE_SHIPMENT,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Create Shipment.'
                ];
            case self::RETURN_ERROR_FAILED_CREATE_RMA:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_CREATE_RMA,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Create Rma.'
                ];
            case self::RETURN_ERROR_FAILED_CREATE_CREDITMEMO:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_CREATE_CREDITMEMO,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Create Credit Memo.'
                ];
            case self::RETURN_ERROR_FAILED_UPDATE_SHIPMENT:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_UPDATE_SHIPMENT,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Update Shipment.'
                ];
            case self::RETURN_ERROR_FAILED_UPDATE_RMA:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_UPDATE_RMA,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Update Rma.'
                ];
            case self::RETURN_ERROR_FAILED_CREATE_RMA_SHIPMENT:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_FAILED_CREATE_RMA_SHIPMENT,
                    self::DATA_KEY_RETURN_MESSAGE => 'Failed To Create Rma Shipping Number.'
                ];
            default:
                return [
                    self::DATA_KEY_RETURN_CODE => self::RETURN_ERROR_OTHER,
                    self::DATA_KEY_RETURN_MESSAGE => 'There is Something Wrong.'
                ];
        }

    }
}