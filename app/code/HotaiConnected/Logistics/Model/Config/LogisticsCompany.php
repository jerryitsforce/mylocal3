<?php
/**
 * Copyright © HotaiConnected. All rights reserved.
 */

namespace HotaiConnected\Logistics\Model\Config;

/**
 * Logistics Company Configuration
 */
class LogisticsCompany
{
    /**
     * 新竹物流
     */
    const COMPANY_HCT = '1';
    const COMPANY_HCT_NAME = '新竹物流';

    /**
     * 順豐速運
     */
    const COMPANY_SF = '2';
    const COMPANY_SF_NAME = '順豐速運';

    /**
     * 中華郵政
     */
    const COMPANY_POST = '3';
    const COMPANY_POST_NAME = '中華郵政';

    /**
     * 黑貓宅急便
     */
    const COMPANY_TCAT = '4';
    const COMPANY_TCAT_NAME = '黑貓宅急便';

    /**
     * 嘉里大榮
     */
    const COMPANY_KERRY = '5';
    const COMPANY_KERRY_NAME = '嘉里大榮';

    /**
     * Get all logistics companies
     *
     * @return array
     */
    public static function getAllCompanies()
    {
        return [
            self::COMPANY_HCT => self::COMPANY_HCT_NAME,
            self::COMPANY_SF => self::COMPANY_SF_NAME,
            self::COMPANY_POST => self::COMPANY_POST_NAME,
            self::COMPANY_TCAT => self::COMPANY_TCAT_NAME,
            self::COMPANY_KERRY => self::COMPANY_KERRY_NAME,
        ];
    }

    /**
     * Get company name by ID
     *
     * @param string $companyId
     * @return string|null
     */
    public static function getCompanyName($companyId)
    {
        $companies = self::getAllCompanies();
        return $companies[$companyId] ?? null;
    }

    /**
     * Get companies for option array
     *
     * @return array
     */
    public static function getOptionArray()
    {
        $options = [];
        foreach (self::getAllCompanies() as $id => $name) {
            $options[] = ['value' => $id, 'label' => $name];
        }
        return $options;
    }

    /**
     * Check if company ID is valid
     *
     * @param string $companyId
     * @return bool
     */
    public static function isValidCompany($companyId)
    {
        return array_key_exists($companyId, self::getAllCompanies());
    }
}
