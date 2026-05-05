<?php

declare(strict_types=1);

namespace Branch8\HotaiPay\Model;

use Magento\Customer\Model\Address\AddressModelInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderAddressInterface;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;

class ParentOrderPayment extends \Magento\Framework\Model\AbstractExtensibleModel
{
    private $parentOrder;
    private $regionFactory;

    /**
     * Entity ID.
     */
    const ENTITY_ID = 'entity_id';

    /**
     * Parent ID.
     */
    const PARENT_ID = 'parent_id';
    /*
     * Base amount ordered.
     */
    const BASE_AMOUNT_ORDERED = 'base_amount_ordered';
    /*
     * Base amount paid.
     */
    const BASE_AMOUNT_PAID = 'base_amount_paid';
    /*
     * Base amount refunded.
     */
    const BASE_AMOUNT_REFUNDED = 'base_amount_refunded';
    /*
     * Base amount canceled.
     */
    const BASE_AMOUNT_CANCELED = 'base_amount_canceled';
    /*
     * Quote payment ID.
     */
    const QUOTE_PAYMENT_ID = 'quote_payment_id';
    /*
     * CC TOKEN ID ID.
     */
    const CC_TOKEN_ID = 'cc_token_id';
    /*
     * Credit card type.
     */
    const CC_TYPE = 'cc_type';
    /*
     * Credit card owner.
     */
    const CC_OWNER = 'cc_owner';
    /*
     * Last four digits of credit card number.
     */
    const CC_LAST_4 = 'cc_last_4';
    /**
     *  MAC
     */
    const MAC = 'mac';

    /**
     * TXN
     **/
    const TXN = 'txn';
    /**
     * ReqJsonPwd
     */
    const REQJSONPWD = 'reqjsonpwd';
    /**
     * Get Txn Err Result
     */
    const GET_TXN_ERR_RESULT = 'get_txn_err_result';
    /*
     * Additional information.
     */
    const ADDITIONAL_INFORMATION = 'additional_information';
    const BIN_INFO_CODE = 'bin_info_code';
    const CO_BRANDED = 'co_branded';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        ExtensionAttributesFactory                              $extensionFactory,
        AttributeValueFactory                                   $customAttributeFactory,
        \Magento\Directory\Model\RegionFactory                  $regionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $resource,
            $resourceCollection,
            $data
        );
        $this->regionFactory = $regionFactory;
    }

    /**
     * Initialize resource
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\HotaiPay\Model\ResourceModel\ParentOrderPayment::class
        );
    }

    /**
     * Sets the ID for the order address.
     *
     * @param int $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * @inheritdoc
     */
    public function setParentId($id)
    {
        return $this->setData(self::PARENT_ID, $id);
    }

    /**
     * @inheritdoc
     */
    public function setBaseAmountOrdered($baseAmountOrdered)
    {
        return $this->setData(self::BASE_AMOUNT_ORDERED, $baseAmountOrdered);
    }

    /**
     * @inheritdoc
     */
    public function setBaseAmountPaid($baseAmountPaid)
    {
        return $this->setData(self::BASE_AMOUNT_PAID, $baseAmountPaid);
    }


    /**
     * @inheritdoc
     */
    public function setBaseAmountRefunded($baseAmountRefunded)
    {
        return $this->setData(self::BASE_AMOUNT_REFUNDED, $baseAmountRefunded);
    }

    /**
     * @inheritdoc
     */
    public function setBaseAmountCanceled($baseAmountCanceled)
    {
        return $this->setData(self::BASE_AMOUNT_CANCELED, $baseAmountCanceled);
    }

    /**
     * @inheritdoc
     */
    public function setQuotePaymentId($quotePaymentId)
    {
        return $this->setData(self::QUOTE_PAYMENT_ID, $quotePaymentId);
    }

    /**
     * @inheritdoc
     */
    public function setCcToken($ccToken)
    {
        return $this->setData(self::CC_TOKEN_ID, $ccToken);
    }

    /**
     * @inheritdoc
     */
    public function setCcType($ccType)
    {
        return $this->setData(self::CC_TYPE, $ccType);
    }

    /**
     * @inheritdoc
     */
    public function setCcOwner($ccOwner)
    {
        return $this->setData(self::CC_OWNER, $ccOwner);
    }

    /**
     * @inheritdoc
     */
    public function setCcLast4($ccLast4)
    {
        return $this->setData(self::CC_LAST_4, $ccLast4);
    }

    /**
     * @inheritdoc
     */
    public function setMac($mac)
    {
        return $this->setData(self::MAC, $mac);
    }
    /**
     * @inheritdoc
     */
    public function setTxn($txn)
    {
        return $this->setData(self::TXN, $txn);
    }

    /**
     * @inheritdoc
     */
    public function setReqjsonpwd($reqjsonpwd)
    {
        return $this->setData(self::REQJSONPWD, $reqjsonpwd);
    }


    /**
     * @inheritdoc
     */
    public function setGetTxnErrResult($getTxnErrResult)
    {
        return $this->setData(self::GET_TXN_ERR_RESULT, $getTxnErrResult);
    }

    /**
     * @inheritdoc
     */
    public function setAdditionalInformation($additionalInformation)
    {
        return $this->setData(self::ADDITIONAL_INFORMATION, $additionalInformation);
    }

    /**
     * Returns entity_id
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Returns parent_order_id
     *
     * @return int
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * Returns base_amount_ordered
     *
     * @return int
     */
    public function getBaseAmountOrdered()
    {
        return $this->getData(self::BASE_AMOUNT_ORDERED);
    }

    /**
     * Returns base_amount_paid
     *
     * @return int
     */
    public function getBaseAmountPaid()
    {
        return $this->getData(self::BASE_AMOUNT_PAID);
    }

    /**
     * Returns base_amount_refunded
     *
     * @return int
     */
    public function getBaseAmountRefunded()
    {
        return $this->getData(self::BASE_AMOUNT_REFUNDED);
    }

    /**
     * Returns base_amount_canceled
     *
     * @return int
     */
    public function getBaseAmountCanceled()
    {
        return $this->getData(self::BASE_AMOUNT_CANCELED);
    }

    /**
     * Returns quote_payment_id
     *
     * @return int
     */
    public function getQuotePaymentId()
    {
        return $this->getData(self::QUOTE_PAYMENT_ID);
    }

    /**
     * Returns cc_token_id
     *
     * @return int
     */
    public function getCcToken()
    {
        return $this->getData(self::CC_TOKEN_ID);
    }

    /**
     * Returns cc_type
     *
     * @return array|int|mixed|null
     */
    public function getCcType()
    {
        return $this->getData(self::CC_TYPE);
    }

    /**
     * Returns cc_owner
     *
     * @return array|int|mixed|null
     */
    public function getCcOwner()
    {
        return $this->getData(self::CC_OWNER);
    }

    /**
     * Returns cc_last_4
     *
     * @return array|int|mixed|null
     */
    public function getCcLast4()
    {
        return $this->getData(self::CC_LAST_4);
    }

    /**
     * Returns mac
     *
     * @return array|int|mixed|null
     */
    public function getMac()
    {
        return $this->getData(self::MAC);
    }

    /**
     * Returns txn
     *
     * @return array|int|mixed|null
     */
    public function getTxn()
    {
        return $this->setData(self::TXN);
    }

    /**
     * Returns reqjsonpwd
     *
     * @return array|int|mixed|null
     */
    public function getReqjsonpwd()
    {
        return $this->getData(self::REQJSONPWD);
    }

    /**
     * Returns get_txn_err_result
     *
     * @return array|int|mixed|null
     */
    public function getGetTxnErrResult()
    {
        return $this->getData(self::GET_TXN_ERR_RESULT);
    }

    /**
     * Returns additional_information
     *
     * @return array|int|mixed|null
     */
    public function getAdditionalInformation()
    {
        return $this->getData(self::ADDITIONAL_INFORMATION);
    }

    /**
     * @inheritDoc
     */
    public function setBinInfoCode($binInfoCode)
    {
        return $this->setData(self::BIN_INFO_CODE, $binInfoCode);
    }
    /**
     * @inheritDoc
     */
    public function getBinInfoCode()
    {
        return $this->getData(self::BIN_INFO_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setCoBranded($coBranded)
    {
        return $this->setData(self::CO_BRANDED, $coBranded);
    }
    /**
     * @inheritDoc
     */
    public function getCoBranded()
    {
        return $this->getData(self::CO_BRANDED);
    }
}
