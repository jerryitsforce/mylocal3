<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\User\Model\User;

class ChatProfileEntity
{
    const CUSTOMER = 'customer';

    const SELLER = 'seller';

    const DEALER = 'dealer';

    /**
     * @param $type
     * @param $object
     * @return string
     */
    public static function getProfileName($type, $object)
    {
        /**
         * @var $object CustomerInterface|User
         */
        switch ($type) {
            case self::SELLER:
                $name = sprintf('%s %s', $object->getFirstname(), $object->getLastname());
                break;
            case self::CUSTOMER:
                $name = sprintf('%s %s', $object->getFirstname(), $object->getLastname());
                break;
            case self::DEALER:
                $name = sprintf('%s', $object->getName());
                break;
            default :
                $name = '';
                break;
        }
        return $name;
    }
}
