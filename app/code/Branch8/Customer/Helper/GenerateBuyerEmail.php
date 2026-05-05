<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class GenerateBuyerEmail extends AbstractHelper
{

    protected $random;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Math\Random $random
        )
    {
        parent::__construct($context);
        $this->random = $random;
    }

    public function generateBuyerEmail(){
        return \Branch8\Customer\Helper\Data::BUYER_EMAIL_PREFIX.microtime(true).'_'.$this->random->getRandomString(5).'@hotaiauthprivate.com';
    }
}
