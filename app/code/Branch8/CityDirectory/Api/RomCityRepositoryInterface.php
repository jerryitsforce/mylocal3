<?php

namespace Branch8\CityDirectory\Api;

use Branch8\CityDirectory\Api\Data\RomCityInterface;

interface RomCityRepositoryInterface
{
    /**
     * @param RomCityInterface $templates
     * @return mixed
     */
    public function save(RomCityInterface $templates);

    /**
     * @param $value
     * @return mixed
     */
    public function getById($value);

    /**
     * @param RomCityInterface $templates
     * @return mixed
     */
    public function delete(RomCityInterface $templates);

    /**
     * @param $value
     * @return mixed
     */
    public function deleteById($value);
}