<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiPay\Model;

use Branch8\HotaiPay\Api\Data\CreditcardInterface;
use Magento\Framework\Model\AbstractModel;

class Creditcard extends AbstractModel implements CreditcardInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Branch8\HotaiPay\Model\ResourceModel\Creditcard::class);
    }

    /**
     * @inheritDoc
     */
    public function getCreditcardId()
    {
        return $this->getData(CreditcardInterface::CREDITCARD_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreditcardId($creditcardId)
    {
        return $this->setData(CreditcardInterface::CREDITCARD_ID, $creditcardId);
    }

    /**
     * @inheritDoc
     */
    public function getIsMain()
    {
        return $this->getData(CreditcardInterface::IS_MAIN);
    }

    /**
     * @inheritDoc
     */
    public function setIsMain($isMain)
    {
        return $this->setData(CreditcardInterface::IS_MAIN, $isMain);
    }

    
    /**
     * @inheritDoc
     */
    public function getMemberOneId()
    {
        return $this->getData(CreditcardInterface::MEMBER_ONE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setMemberOneId($memberOneId)
    {
        return $this->setData(CreditcardInterface::MEMBER_ONE_ID, $memberOneId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(CreditcardInterface::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(CreditcardInterface::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCreditcardTokenId()
    {
        return $this->getData(CreditcardInterface::CREDITCARD_TOKEN_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreditcardTokenId($creditcardTokenId)
    {
        return $this->setData(CreditcardInterface::CREDITCARD_TOKEN_ID, $creditcardTokenId);
    }
    /**
     * @inheritDoc
     */
    public function getType()
    {
        return $this->getData(CreditcardInterface::TYPE);
    }

    /**
     * @inheritDoc
     */
    public function setType($type)
    {
        return $this->setData(CreditcardInterface::TYPE, $type);
    }
    /**
     * @inheritDoc
     */
    public function getBankDesc()
    {
        return $this->getData(CreditcardInterface::BANK_DESC);
    }

    /**
     * @inheritDoc
     */
    public function setBankDesc($bankDesc)
    {
        return $this->setData(CreditcardInterface::BANK_DESC, $bankDesc);
    }
    /**
     * @inheritDoc
     */
    public function getNumberMask()
    {
        return $this->getData(CreditcardInterface::NUMBER_MASK);
    }

    /**
     * @inheritDoc
     */
    public function setNumberMask($numberMask)
    {
        return $this->setData(CreditcardInterface::NUMBER_MASK, $numberMask);
    }

    /**
     * @inheritDoc
     */
    public function setAffinityCode($affinityCode)
    {
        return $this->setData(CreditcardInterface::AFFINITY_CODE, $affinityCode);
    }
    /**
     * @inheritDoc
     */
    public function getAffinityCode()
    {
        return $this->getData(CreditcardInterface::AFFINITY_CODE);
    }

    /**
     * @inheritDoc
     */
    public function setAliasName($aliasName)
    {
        return $this->setData(CreditcardInterface::ALIAS_NAME, $aliasName);
    }
    /**
     * @inheritDoc
     */
    public function getAliasName()
    {
        return $this->getData(CreditcardInterface::ALIAS_NAME);
    }

    /**
     * @inheritDoc
     */
    public function setIsDefault($isDefault)
    {
        return $this->setData(CreditcardInterface::IS_DEFAULT, $isDefault);
    }
    /**
     * @inheritDoc
     */
    public function getIsDefault()
    {
        return $this->getData(CreditcardInterface::IS_DEFAULT);
    }

    /**
     * @inheritDoc
     */
    public function setIsDebit($isDebit)
    {
        return $this->setData(CreditcardInterface::IS_DEBIT, $isDebit);
    }
    /**
     * @inheritDoc
     */
    public function getIsDebit()
    {
        return $this->getData(CreditcardInterface::IS_DEBIT);
    }

    /**
     * @inheritDoc
     */
    public function setIsCtbc($isCtbc)
    {
        return $this->setData(CreditcardInterface::IS_CTBC, $isCtbc);
    }
    /**
     * @inheritDoc
     */
    public function getIsCtbc()
    {
        return $this->getData(CreditcardInterface::IS_CTBC);
    }

    /**
     * @inheritDoc
     */
    public function setIsHt($isHt)
    {
        return $this->setData(CreditcardInterface::IS_HT, $isHt);
    }
    /**
     * @inheritDoc
     */
    public function getIsHt()
    {
        return $this->getData(CreditcardInterface::IS_HT);
    }

     /**
     * @inheritDoc
     */
    public function setBinInfoCode($binInfoCode)
    {
        return $this->setData(CreditcardInterface::BIN_INFO_CODE, $binInfoCode);
    }
    /**
     * @inheritDoc
     */
    public function getBinInfoCode()
    {
        return $this->getData(CreditcardInterface::BIN_INFO_CODE);
    }
}

