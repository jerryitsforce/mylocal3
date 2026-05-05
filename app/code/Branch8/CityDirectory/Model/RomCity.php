<?php

namespace Branch8\CityDirectory\Model;

use Branch8\CityDirectory\Api\Data\RomCityInterface;
use Branch8\CityDirectory\Model\ResourceModel\RomCity as RomCityResourceModel;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource as AbstractResourceModel;
use Magento\Framework\Registry;

class RomCity extends AbstractModel implements RomCityInterface
{
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResourceModel $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function _construct()
    {
        $this->_init(RomCityResourceModel::class);
    }

    public function getEntityId()
    {
        return $this->getData(RomCityInterface::ENTITY_ID);
    }

    public function getRegionId()
    {
        return $this->getData(RomCityInterface::REGION_ID);
    }

    public function getCityName()
    {
        return $this->getData(RomCityInterface::CITY_NAME);
    }


    public function setEntityId($entityId)
    {
        $this->setData(RomCityInterface::ENTITY_ID, $entityId);
    }

    public function setRegionId($regionId)
    {
        $this->setData(RomCityInterface::REGION_ID, $regionId);
    }

    public function setCityName($cityName)
    {
        $this->setData(RomCityInterface::CITY_NAME, $cityName);
    }
}