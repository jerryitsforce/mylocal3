<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Catalog\Plugin\Magento\Framework\Stdlib\DateTime\Filter;

use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Date/Time filter. Converts datetime from localized to internal format.
 *
 * @api
 * @since 100.0.2
 */
class DateTime
{
    /**
     * @var TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * @param TimezoneInterface $localeDate
     * @param AuthSession $authSession
     */
    public function __construct(TimezoneInterface $localeDate, AuthSession $authSession)
    {
        $this->_localeDate = $localeDate;
        $this->authSession = $authSession;
    }

    public function beforeFilter($subject, $value)
    {
        $currentLocaleCode = $this->authSession->getUser()->getInterfaceLocale();
        $value = $this->_localeDate->formatDateTime($value, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT, $currentLocaleCode, null, null);

        return [$value];
    }
}
