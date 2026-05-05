<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Info extends AbstractHelper
{

    /**
     * Get OAuth Name
     *
     * @param string $nickname
     * @param string $name
     * @return string
     */
    public function getOAuthName($nickname, $name)
    {
        if ($nickname) {
            return $nickname;
        }

        $name = trim($name);
        $length = mb_strlen($name);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 1) {
            return $name;
        }

        return str_repeat('O', $length - 1) . mb_substr($name, -1);
    }

     /**
     * Get OAuth Phone
     *
     * @param string $phone
     * @return string
     */
    public function getOAuthPhone($phone)
    {
        $length = strlen($phone);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 5) {
            return $phone;
        }

        $masked = substr($phone, 0, 3) . str_repeat('*', $length - 5) . substr($phone, -2);
        return $masked;
    }

    /**
     * Get OAuth Birthday
     *
     * @param string $birthday
     * @return string
     */
    public function getOAuthBirthday($birthday)
    {
        $length = strlen($birthday);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 4) {
            return $birthday;
        }

        // $masked = substr($birthday, 0, 3);
        $masked = ''; // Masked example: ****/**/*1
        for ($i = 0; $i < $length - 1; $i++) {
            if ($birthday[$i] === '/') {
                $masked .= '/';
            } else {
                $masked .= '*';
            }
        }
        $masked .= substr($birthday, -1);

        return $masked;

        // $masked = substr($birthday, 0, 3) . str_repeat('*', $length - 4) . substr($birthday, -1);
        // return $masked;
    }

    /**
     * Mask all characters of a string except the '/' characters
     *
     * @param string $string
     * @return string
     */
    public function maskStringKeepSlashes($string)
    {
        $masked = '';
        $length = strlen($string);

        for ($i = 0; $i < $length; $i++) {
            if ($string[$i] === '/') {
                $masked .= '/';
            } else {
                $masked .= '*';
            }
        }

        return $masked;
    }

    /**
     * Get OAuth Email
     *
     * @param string $email
     * @return string
     */
    public function getOAuthEmail($email)
    {
        $length = strlen($email);

        if ($email <= 0) {
            return '';
        }

        $atPos = strpos($email, '@');
        if ($atPos === false) {
            return $email;
        }

        $firstChar = substr($email, 0, 1);
        $domainPart = substr($email, $atPos);

        $masked = $firstChar . str_repeat('*', $atPos - 1) . $domainPart;
        return $masked;
    }


    /**
     * Get OAuth Street
     *
     * @param string $street
     * @return string
     */
    public function getOAuthStreet($street)
    {
        $street = str_replace(',','',trim($street));
        $length = mb_strlen($street);

        if ($length <= 0) {
            return '';
        }

        if ($length <= 4) {
            return $street;
        }

        $masked = str_repeat('*', $length - 4).mb_substr($street, -4, 4, 'UTF-8');
        return $masked;
    }


    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getHomeDeliveryAdress($address)
    {
        if (!$address) {
            return '';
        }
        $city = '';
        $region = '';

        if ($address->getRegionId() || $address->getRegion()) {
            $region = $address->getRegion();
        }

        if ($address->getCity()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());

        return $region. $city. $this->getOAuthStreet($street);
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getConvenienceAdress($address)
    {
        if (!$address) {
            return '';
        }

        $city = '';

        if ($address->getCity()) {
            $city = $address->getCity();
        }

        $street = implode('', $address->getStreet());

        return '711'.$city;
    }


}
