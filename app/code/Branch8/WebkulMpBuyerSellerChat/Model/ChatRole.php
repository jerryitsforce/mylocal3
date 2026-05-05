<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model;
class ChatRole implements \Magento\Framework\Data\OptionSourceInterface
{
    const CUSTOMER = 'customer';

    const SELLER = 'seller';

    const DEALER = 'dealer';
    /**
     * @var array|array[]
     */
    private $options = null;

    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return string
     */
    public static function getNameFromCustomer(\Magento\Customer\Api\Data\CustomerInterface $customer)
    {
        return sprintf('%s %s ', $customer->getFirstname(), $customer->getLastname());
    }

    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        if (null == $this->options) {
            $this->options = [
                ['value' => self::SELLER,
                    'label' => __('Seller')
                ],
                ['value' => self::CUSTOMER,
                    'label' => __('Customer')
                ],
                ['value' => self::DEALER,
                    'label' => __('Dealer')
                ],
            ];
        }
        return $this->options;
    }
}
