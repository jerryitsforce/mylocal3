<?php

namespace Branch8\Marketplace\Helper;

use Branch8\Marketplace\Model\Actions\GetSellerInformation;
use Branch8\Marketplace\Model\Actions\GetSellerWysiwygTextDirectory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Math\Random;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;

class Data extends AbstractHelper
{
    /**
     * @var ShipmentTrackRepositoryInterface
     */
    protected $shipmentTrackRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    protected $shipmentInfo = [];
    private GetSellerWysiwygTextDirectory $getSellerWysiwygTextDirectory;

    private \Magento\Framework\Math\Random $random;
    private GetSellerInformation $getSellerInformation;

    /**
     * @param ShipmentTrackRepositoryInterface $shipmentTrackRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GetSellerWysiwygTextDirectory $getSellerWysiwygTextDirectory
     */
    public function __construct(
        ShipmentTrackRepositoryInterface $shipmentTrackRepository,
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        GetSellerWysiwygTextDirectory    $getSellerWysiwygTextDirectory,
        GetSellerInformation             $getSellerInformation,
        \Magento\Framework\Math\Random $random
    )
    {
        $this->shipmentTrackRepository = $shipmentTrackRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->getSellerWysiwygTextDirectory = $getSellerWysiwygTextDirectory;
        $this->getSellerInformation = $getSellerInformation;
        $this->random = $random;
    }

    /**
     * Get shipment info by order id
     *
     * @param int $orderId
     * @param string $field
     * @return string
     */
    public function getShipmentInfoByOrderId($orderId, $field)
    {
        if (!isset($this->shipmentInfo[$orderId])) {
            $result = ['title' => '', 'track_number' => ''];
            $items = $this->shipmentTrackRepository->getList(
                $this->searchCriteriaBuilder->addFilter('order_id', $orderId)->create()
            )->getItems();
            foreach ($items as $res) {
                $result['title'] = $result['title'] . $res->getTitle() . "<br>";
                $result['track_number'] = $result['track_number'] . $res->getTrackNumber() . "<br>";
            }
            $this->shipmentInfo[$orderId] = $result;
        }
        return $this->shipmentInfo[$orderId][$field] ?? '';
    }

    /**
     * @param $sellerId
     * @return string
     */
    public function getSellerWysiwygTextDirectory($sellerId)
    {
        return $this->getSellerWysiwygTextDirectory->execute($sellerId);
    }

    /**
     * @param $sellerId
     * @return mixed|void
     */
    public function getSellerCode($sellerId)
    {
        $seller = $this->getSellerInformation->get((int)$sellerId);
        if ($seller) {
            return $seller['seller_code'];
        }
        return '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getRandomString()
    {
        return $this->random->getRandomString(20, Random::CHARS_UPPERS.Random::CHARS_DIGITS);
    }
}
