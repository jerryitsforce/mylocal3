<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       23/02/2026
 */

namespace Branch8\Shipping\Model;

use Magento\Sales\Model\Order\Shipment\Track;

class TrackingToArray
{
    /**
     * @param Track $track
     * @return array
     */
    public static function convert(Track $track)
    {
        return [
            'id' => $track->getId(),
            'shipment_id' => $track->getParentId(),
            'carrier' => $track->getTitle(),
            'tracking_number' => $track->getTrackNumber()
        ];
    }
}
