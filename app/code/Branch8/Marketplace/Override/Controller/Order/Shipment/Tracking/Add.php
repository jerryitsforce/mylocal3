<?php

declare(strict_types=1);

namespace Branch8\Marketplace\Override\Controller\Order\Shipment\Tracking;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Webkul\Marketplace\Block\Order\View;
use Webkul\Marketplace\Controller\Order;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class Add extends Order
{
    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number = $this->getRequest()->getPost('number');
            $title = $this->getRequest()->getPost('title');
            $lpName = $this->getRequest()->getPost('lpName'); // override here
            $lcUrl = $this->getRequest()->getPost('lcUrl'); // override here
            $orderId = $this->getRequest()->getParam('order_id');
            $shipmentId = $this->getRequest()->getParam('shipment_id');
            if (empty($carrier) || empty($title)) {
                throw new LocalizedException(__('Please specify a carrier.'));
            }
            if (empty($number)) {
                throw new LocalizedException(
                    __('Please enter a tracking number.')
                );
            }
            if ($shipment = $this->_initShipment()) {
                if ($title === '其他：自行填寫名稱') {
                    if (empty($lpName)) {
                        throw new LocalizedException(__('Please enter a logistics provider\'s name.'));
                    }
                    $title = $lpName;
                }
                $track = $this->_objectManager->create(
                    Track::class
                )->setNumber(
                    $number
                )->setCarrierCode(
                    $carrier
                )->setTitle(
                    $title
                )->setLogisticsCompanyUrl(
                    $lcUrl
                );
                $shipment->addTrack($track)->save();
                $trackId = $track->getId();
                if ($track->isCustom()) {
                    $numberclass = 'display';
                    $numberclasshref = 'no-display';
                    $trackingPopupUrl = '';
                } else {
                    $numberclass = 'no-display';
                    $numberclasshref = 'display';
                    $trackingPopupUrl = $this->_objectManager->create(
                        \Magento\Shipping\Helper\Data::class
                    )->getTrackingPopupUrlBySalesModel($track);
                }
                $response = [
                    'error' => false,
                    'carrier' => $this->_objectManager->create(
                        View::class
                    )->getCarrierTitle($carrier),
                    'title' => $title,
                    'number' => $number,
                    'numberclass' => $numberclass,
                    'numberclasshref' => $numberclasshref,
                    'trackingPopupUrl' => $trackingPopupUrl,
                    'trackingDeleteUrl' => $this->_objectManager->create(
                        UrlInterface::class
                    )->getUrl(
                        'marketplace/order_shipment_tracking/delete',
                        [
                            'order_id' => $orderId,
                            'shipment_id' => $shipmentId,
                            'id' => $trackId,
                            '_secure' => $this->getRequest()->isSecure()
                        ]
                    )
                ];
            } else {
                $response = [
                    'error' => true,
                    'message' => __(
                        'We can\'t initialize shipment for adding tracking number.'
                    ),
                ];
            }
        } catch (LocalizedException $e) {
            ObjectManager::getInstance()->get(MarketplaceLogger::class)->logException('Add', $e);
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            ObjectManager::getInstance()->get(MarketplaceLogger::class)->logException('Add', $e);
            $response = [
                'error' => true,
                'message' => __('Cannot add tracking number.%1', $e->getMessage())
            ];
        }
        if (is_array($response)) {
            $response = $this->_objectManager->get(Data::class)->jsonEncode($response);
            $this->getResponse()->representJson($response);
        } else {
            $this->getResponse()->setBody($response);
        }
    }
}
