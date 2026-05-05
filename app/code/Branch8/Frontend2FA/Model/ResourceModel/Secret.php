<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 16:29.
 */

namespace Branch8\Frontend2FA\Model\ResourceModel;

class Secret extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init('branch8_frontend2fa_secrets', 'secret_id');
    }
}
