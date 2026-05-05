<?php

namespace Branch8\CityDirectory\Api\Data;

interface RomCityInterface
{
    public const ENTITY_ID = 'entity_id';
    public const REGION_ID = 'region_id';
    public const CITY_NAME = 'city';


    public function getEntityId();

    public function getRegionId();

    public function getCityName();

    public function setEntityId($entityId);

    public function setRegionId($regionId);

    public function setCityName($cityName);
}
