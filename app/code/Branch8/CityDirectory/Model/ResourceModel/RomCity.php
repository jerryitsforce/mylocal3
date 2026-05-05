<?php

namespace Branch8\CityDirectory\Model\ResourceModel;

use Branch8\CityDirectory\Api\Data\RomCityInterface;
use Branch8\CityDirectory\Setup\InstallSchema;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class RomCity
 * @package Branch8\CityDirectory\Model\ResourceModel
 */
class RomCity extends AbstractDb
{
    /**
     * RomCity constructor.
     * @param Context $context
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        string $connectionName = null
    ) {
        parent::__construct(
            $context,
            $connectionName
        );
    }

    public function _construct()
    {
        $this->_init('hotai_city_directory', RomCityInterface::ENTITY_ID);
    }
}
