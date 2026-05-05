<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model;

use Magento\Quote\Model\Quote;

class SubQuoteShippingMethodResolver
{
    /**
     * @param Quote $quote
     * @param $originMethod
     * @param array $subQuoteShippingInformationParams
     * @param $shippingAmount
     * @return array
     */
    public function resolveShippingMethod(Quote $quote, $originMethod, array $subQuoteShippingInformationParams, $shippingAmount)
    {
        /**
         * Temporary keep origin  method for ECPAY logistic
         * 'ecpaylogisticcsvunimart_ecpaylogisticcsvunimart',
         * 'ecpaylogisticcsvfamily_ecpaylogisticcsvfamily',
         * 'ecpaylogisticcsvhilife_ecpaylogisticcsvhilife',
         * 'ecpaylogisticcsvokmart_ecpaylogisticcsvokmart',
         * 'ecpaylogistichometcat_ecpaylogistichometcat',
         * 'ecpaylogistichomepost_ecpaylogistichomepost'
         *
         */
        $subQuoteShippingInformationParams['shipping_method'] = $originMethod;
        return $subQuoteShippingInformationParams;
    }
}
