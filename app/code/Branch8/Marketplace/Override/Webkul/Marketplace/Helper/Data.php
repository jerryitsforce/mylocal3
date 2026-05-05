<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Marketplace\Override\Webkul\Marketplace\Helper;

class Data extends \Webkul\MpGroupedProduct\Helper\Data
{
    /**
     * GetParams function used to get the http params
     *
     * @return mixed[]
     */
    public function getParams()
    {
        $paramsData = $this->_customerSessionFactory->create()->getEarningParams();
        if ($paramsData === null) {
            return [];
        }
        return json_decode($paramsData, true);
    }
}
