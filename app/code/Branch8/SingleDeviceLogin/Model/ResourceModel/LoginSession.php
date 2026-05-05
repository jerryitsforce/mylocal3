<?php
namespace Branch8\SingleDeviceLogin\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

/**
 * Service class responsible for persisting and retrieving a customer's active device ID.
 *
 * The module enforces a single-device login by storing a unique device identifier
 * (UUID) for each logged in customer.  When a customer logs in on a new device
 * the value in this table is rotated to the new UUID, thereby invalidating any
 * previous device references on subsequent requests.
 */
class LoginSession extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_singledevice_customer_session', 'entity_id');
    }
}
