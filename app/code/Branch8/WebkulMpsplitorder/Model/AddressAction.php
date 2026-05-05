<?php
declare(strict_types=1);


namespace Branch8\WebkulMpsplitorder\Model;

use Magento\Quote\Model\Quote;

class AddressAction
{
    private $transferMasterQuoteService = null;

    private $shippingResolver = null;
    /**
     * @param Quote $subQuote
     * @param array $masterQuoteShippingAddress
     * @param $s_company
     * @param $s_fax
     * @return Quote\Address
     */
    public function addShippingAddress(
        Quote $subQuote,
        array $masterQuoteShippingAddress,
              $s_company,
              $s_fax
    )
    {
        $shippingAddress = $subQuote->getShippingAddress()->addData(
            [
                'customer_id' => $masterQuoteShippingAddress['customer_id'],
                'address_type' => $masterQuoteShippingAddress['address_type'],
                'firstname' => $masterQuoteShippingAddress['firstname'],
                'lastname' => $masterQuoteShippingAddress['lastname'],
                'email' => $masterQuoteShippingAddress['email'],
                'street' => $masterQuoteShippingAddress['street'],
                'city' => $masterQuoteShippingAddress['city'],
                'country_id' => $masterQuoteShippingAddress['country_id'],
                'region_id' => $masterQuoteShippingAddress['region_id'],
                'postcode' => $masterQuoteShippingAddress['postcode'],
                'telephone' => $masterQuoteShippingAddress['telephone'],
                'same_as_billing' => $masterQuoteShippingAddress['same_as_billing'],
                'company' => $s_company,
                'fax' => $s_fax
            ]
        );
        return $shippingAddress;
    }

    /**
     * @param Quote $subQuote
     * @param array $masterQuoteBillingAddress
     * @param $b_company
     * @param $b_fax
     * @return Quote\Address
     */
    public function addBillingAddress(
        Quote $subQuote,
        array $masterQuoteBillingAddress,
              $b_company,
              $b_fax
    )
    {
        $billingAddress = $subQuote->getBillingAddress()->addData(
            [
                'customer_id' => $masterQuoteBillingAddress['customer_id'],
                'address_type' => $masterQuoteBillingAddress['address_type'],
                'firstname' => $masterQuoteBillingAddress['firstname'],
                'lastname' => $masterQuoteBillingAddress['lastname'],
                'email' => $masterQuoteBillingAddress['email'],
                'street' => $masterQuoteBillingAddress['street'],
                'city' => $masterQuoteBillingAddress['city'],
                'country_id' => $masterQuoteBillingAddress['country_id'],
                'region_id' => $masterQuoteBillingAddress['region_id'],
                'postcode' => $masterQuoteBillingAddress['postcode'],
                'telephone' => $masterQuoteBillingAddress['telephone'],
                'same_as_billing' => $masterQuoteBillingAddress['same_as_billing'],
                'company' => $b_company,
                'fax' => $b_fax
            ]
        );
        return $billingAddress;
    }
}
