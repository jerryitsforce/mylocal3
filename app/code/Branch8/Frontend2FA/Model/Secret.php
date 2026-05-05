<?php
/**
 * Created by PhpStorm.
 * User: peterjaap
 * Date: 5-3-19
 * Time: 16:29.
 */

namespace Branch8\Frontend2FA\Model;

use Branch8\Frontend2FA\Model\ResourceModel\Secret as SecretResourceModel;
use Branch8\Frontend2FA\Model\ResourceModel\Secret\Collection as SecretCollection;

/**
 * @method SecretResourceModel getResource()
 * @method SecretCollection    getCollection()
 */
class Secret extends \Magento\Framework\Model\AbstractModel implements
    \Branch8\Frontend2FA\Api\Data\SecretInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'branch8_frontend2fa_secret';
    protected $_cacheTag = 'branch8_frontend2fa_secret';
    protected $_eventPrefix = 'branch8_frontend2fa_secret';

    protected function _construct()
    {
        $this->_init('Branch8\Frontend2FA\Model\ResourceModel\Secret');
    }

    /**
     * @return array|string[]
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG.'_'.$this->getId()];
    }
}
