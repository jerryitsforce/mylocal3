<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */

declare (strict_types = 1);

namespace Branch8\HotaiPay\Model;

use Branch8\HotaiPay\Api\CreditcardRepositoryInterface;
use Branch8\HotaiPay\Api\Data\CreditcardInterface;
use Branch8\HotaiPay\Api\Data\CreditcardInterfaceFactory;
use Branch8\HotaiPay\Api\Data\CreditcardSearchResultsInterfaceFactory;
use Branch8\HotaiPay\Helper\CreditCard\BankCode;
use Branch8\HotaiPay\Helper\Log as HotaiPayLogHelper;
use Branch8\HotaiPay\Model\ResourceModel\Creditcard as ResourceCreditcard;
use Branch8\HotaiPay\Model\ResourceModel\Creditcard\CollectionFactory as CreditcardCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;

class CreditcardRepository implements CreditcardRepositoryInterface
{

    const ACTION_TYPE_DELETE = "Delete";
    const ACTION_TYPE_SET_DEFAULT_CARD = "SetDefaultCard";
    const ACTION_TYPE_UPDATE_ALIAS_NAME = "UpdateAliasName";
    const HT_CODE = [8686, 8687, 8688, 8689, 8690];

    /**
     * @var ResourceCreditcard
     */
    protected $resource;

    /**
     * @var CreditcardInterfaceFactory
     */
    protected $creditcardFactory;

    /**
     * @var CreditcardCollectionFactory
     */
    protected $creditcardCollectionFactory;

    /**
     * @var Creditcard
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime $date */
    private $date;

    /** @var \Branch8\HotaiPay\Helper\CreditCard\BankCode $bankCode */
    protected $bankCode;
    /** @var HotaiPayLogHelper */
    private $hotaiPayLogHelper;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        ResourceCreditcard $resource,
        DateTime $date,
        CreditcardInterfaceFactory $creditcardFactory,
        CreditcardCollectionFactory $creditcardCollectionFactory,
        CreditcardSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        BankCode $bankCode,
        HotaiPayLogHelper $hotaiPayLogHelper
    ) {
        $this->resource = $resource;
        $this->creditcardFactory = $creditcardFactory;
        $this->creditcardCollectionFactory = $creditcardCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->date = $date;
        $this->bankCode = $bankCode;
        $this->hotaiPayLogHelper = $hotaiPayLogHelper;
    }

    /**
     * @inheritDoc
     */
    public function save(CreditcardInterface $creditcard)
    {
        try {
            $this->resource->save($creditcard);
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            throw new CouldNotSaveException(__(
                'Could not save the creditcard: %1',
                $exception->getMessage()
            ));
        }
        return $creditcard;
    }

    /**
     * @inheritDoc
     */
    public function get($creditcardId)
    {
        $creditcard = $this->creditcardFactory->create();
        $this->resource->load($creditcard, $creditcardId);
        if (!$creditcard->getId()) {
            throw new NoSuchEntityException(__('creditcard with id "%1" does not exist.', $creditcardId));
        }
        return $creditcard;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->creditcardCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(CreditcardInterface $creditcard)
    {
        try {
            $creditcardModel = $this->creditcardFactory->create();
            $this->resource->load($creditcardModel, $creditcard->getCreditcardId());
            $this->resource->delete($creditcardModel);
        } catch (\Exception $exception) {
            $this->hotaiPayLogHelper->writeLog('[Exception] ' . $exception->getMessage(), __CLASS__);
            throw new CouldNotDeleteException(__(
                'Could not delete the creditcard: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById($creditcardId)
    {
        return $this->delete($this->get($creditcardId));
    }

    public function getDefaultCard($customerId)
    {
        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(CreditcardInterface::IS_DEFAULT, true);

        return $collection;
    }

    public function getDefaultCardList($customerId)
    {
        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(CreditcardInterface::IS_DEFAULT, true);

        $item = [];
        foreach ($collection->getItems() as $data) {
            $item[] = $data->getCreditcardTokeId();
        }
        return $item;
    }

    /**
     * getMemberCardList
     *
     * @param  mixed $customerId
     * @return void  |array
     */
    public function getMemberCardList($customerId)
    {
        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId);

        $item = [];
        foreach ($collection->getItems() as $data) {
            $item[] = $data->getCreditcardTokeId();
        }
        return $item;
    }

    /**
     * resetCreditCardInfo
     *
     * @param  mixed $customerId
     * @param  mixed $creditCardData
     * @return void
     */
    public function resetCreditCardInfo(string $customerId, array $creditCardData)
    {
        //delete customer all credit card info
        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId);
        $collection->walk('delete');

        //setup customer credit card info
        foreach ($creditCardData['data'] as $data) {
            $this->setupSingleCreditCardInfo($data, $customerId);
        }
    }

    /**
     * setupSingleCreditCardInfo
     *
     * @param  mixed $data
     * @param  mixed $customerId
     * @return void
     */
    public function setupSingleCreditCardInfo(array $data, string $customerId)
    {

        $issetBinInfo = isset($data['BinInfo']);

        $creditcardCollection = $this->creditcardFactory->create();
        $creditcardCollection->setCreditcardTokenId($data['Id'] ?? '');
        $creditcardCollection->setMemberOneId($data['MemberOneID'] ?? '');
        $creditcardCollection->setType($data['CardType'] ?? '');
        $creditcardCollection->setBankDesc($data['BankDesc'] ?? '');
        $creditcardCollection->setAliasName($data['AliasName'] ?? '');
        $creditcardCollection->setNumberMask($data['CardNoMask'] ?? '');
        $creditcardCollection->setIsDebit($issetBinInfo ? $this->getIsDebit($data) : false);
        $creditcardCollection->setIsCtbc($issetBinInfo ? $this->getIsCtbc($data) : false);
        $creditcardCollection->setIsHt($this->getIsHt($data));
        $creditcardCollection->setAffinityCode($data['AffinityCode'] ?? '');
        $creditcardCollection->setBinInfoCode($issetBinInfo ? $data['BinInfo']['Code'] : '');
        $creditcardCollection->setCustomerId($customerId);
        $creditcardCollection->setIsDefault(false);
        $creditcardCollection->setBin($issetBinInfo ? $data['BinInfo']['BIN'] : '');
        $creditcardCollection->setBank($issetBinInfo ? $this->bankCode->getBankName($data['BinInfo']['Code']) : '');
        $creditcardCollection->setCreatedAt($data['createdAt'] ?? $this->date->date());
        $creditcardCollection->setUpdatedAt($data['updatedAt'] ?? $this->date->date());
        $creditcardCollection->save();
    }
    /**
     * getIsCtbc
     *
     * @param  mixed $data
     * @return bool
     */
    private function getIsCtbc($data): bool
    {
        return ((int) (substr($data['BinInfo']['Code'], 0, 3))) === 822 ? true : false;
    }

    /**
     * getIsHt
     *
     * @param  mixed $data
     * @return bool
     */
    private function getIsHt($data): bool
    {
        return in_array((int) $data['AffinityCode'], self::HT_CODE) ? true : false;
    }

    /**
     * getIsDebit
     *
     * @param  mixed $data
     * @return bool
     */
    private function getIsDebit(array $data): bool
    {
        return ($data['BinInfo']['IsDebit']) ? true : false;
    }

    /**
     * setDefaultCard
     *
     * @param  mixed $cardInfo
     * @param  mixed $customerId
     * @param  mixed $isDefualtCard
     * @return void
     */
    public function setDefaultCard(array $cardInfo, string $customerId, bool $isDefualtCard)
    {
        $dafaultCardCollection = $this->getDefaultCard($customerId);
        $num = 0;

        //set credit card
        if ($isDefualtCard) {
            //update all default card record, supposed only one record
            foreach ($dafaultCardCollection->getItems() as $data) {
                $num = $num + 1;
                $creditCardTokenId = $data->getCreditcardTokenId();
                if ($creditCardTokenId == $cardInfo['tokenId']) {
                    // $sameDefaultCard = true;
                    continue;
                }

                $data->setIsDefault(false);
                $data->setUpdatedAt($this->date->date());
            }

            $dafaultCardCollection->save();

            $this->updateCreditCard($cardInfo, $customerId, self::ACTION_TYPE_SET_DEFAULT_CARD);

            if ($num > 0) {
                return;
            }

        }

        $dafaultCardCollection->addFieldToFilter(
            CreditcardInterface::CREDITCARD_TOKEN_ID,
            $cardInfo['tokenId']
        );

        foreach ($dafaultCardCollection->getItems() as $data) {
            $data->setIsDefault(false);
            $data->setUpdatedAt($this->date->date());
        }
        
        $dafaultCardCollection->save();

    }

    /**
     * updateCreditCard
     *
     * @param  mixed $cardInfo
     * @param  mixed $customerId
     * @param  mixed $type
     * @return void
     */
    public function updateCreditCard(array $cardInfo, string $customerId, string $type)
    {
        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(CreditcardInterface::CREDITCARD_TOKEN_ID, $cardInfo['tokenId']);

        if ($type == self::ACTION_TYPE_DELETE) {
            $collection->walk('delete');
            return;
        }

        foreach ($collection as $item) {
            switch ($type) {
                case self::ACTION_TYPE_SET_DEFAULT_CARD:
                    $item->setIsDefault(true);
                    break;
                case self::ACTION_TYPE_UPDATE_ALIAS_NAME:
                    $item->setAliasName($cardInfo['aliasName']);
                    break;
            }

            $item->save();
        };
    }

    public function checkCreditCardData($cardInfo, $customerId, $creditcardData){

        $collection = $this->creditcardCollectionFactory->create()
            ->addFieldToFilter(CreditcardInterface::CUSTOMER_ID, $customerId)
            ->addFieldToFilter(CreditcardInterface::CREDITCARD_TOKEN_ID, $cardInfo['tokenId'])
            ->getFirstItem();
        
        $count = 0;
        foreach($collection as $item) {
            $count ++;
        }

        if(!$count) {
            $this->setupSingleCreditCardInfo($creditcardData, $customerId);
        }

    }
}
