<?php

namespace Branch8\Rma\Helper\Config;

class Shipping
{
    const ORDER_RECEIVED = 'Order Received';
    const ORDER_NOT_RECEIVED = 'Order Not Received';

    const TIME_MORNING = 'Morning（9:00-12:59）';
    const TIME_AFTERNOON = 'Afternoon（13:00-17:59）';
    const TIME_EVENING = 'Evening（17:00-19:00）';
    const TIME_ALL = 'All The Time';

    const TCAT_MORNING = 1;
    const TCAT_AFTERNOON = 2;
    const TCAT_ALL = 4;

    const METHOD_CONVENIENCE_STORE = 'hotai_711_hotai_711';
    const METHOD_HOME = 'hotai_delivery_hotai_delivery';

    /**
     * getDeliveryTime
     *
     * @param  string $code
     * @return array
     */
    public function getDeliveryTime($code)
    {
        $deliveryTime = explode(',', $code);

        foreach ($deliveryTime as $key => $value) {
            switch ($value) {
                case 1:
                    $deliveryTime[$key] = __(self::TIME_MORNING);
                    break;
                case 2:
                    $deliveryTime[$key] = __(self::TIME_AFTERNOON);
                    break;
                case 3:
                    $deliveryTime[$key] = __(self::TIME_EVENING);
                    break;
                default:
                    $deliveryTime[$key] = __(self::TIME_ALL);
            }
        }
        return $deliveryTime;
    }

    /**
     * getTCatDeliveryTime 取得黑貓運送/收貨時間
     *
     * @param  string $data
     * @return string | int
     */
    public function getTCatDeliveryTime($data)
    {

        if (str_contains($data, '0') || str_contains($data, '3')) {
            return self::TCAT_ALL;
        }

        if (str_contains($data, '1')) {
            return self::TCAT_MORNING;
        }

        return self::TCAT_AFTERNOON;
    }

}
