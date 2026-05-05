<?php
namespace Branch8\OneStepCheckout\Model;

use Magento\Customer\Model\Session;

class AdditionalConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * AdditionalConfigProvider constructor.
     *
     * @param Session $session
     */
    public function __construct(
        Session $session
    ) {
        $this->session = $session;
    }

   public function getConfig()
   {
       $additionalVariables['home_delivery_methods'] = [
            'flatrate',
            'ecpaylogistichomepost',
            'ecpaylogistichometcat',
            'hotai_delivery'
        ];
       $additionalVariables['convenience_store_methods'] = [
            'ecpaylogisticcsvfamily',
            'ecpaylogisticcsvhilife',
            'ecpaylogisticcsvokmart',
            'ecpaylogisticcsvunimart',
            'hotai_711'
        ];
       if ($this->session->isLoggedIn()) {
           $additionalVariables['checkoutAddress'] = $this->session->getCheckoutAddress();
           $additionalVariables['storeCheckoutData'] = $this->session->getStoreCheckoutData();
       }
       return $additionalVariables;
   }
}
