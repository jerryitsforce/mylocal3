<?php
namespace Branch8\PointMoneyCollect\Controller\Cart;

use Branch8\PointMoneyConfig\Helper\Common as PointMoneyConfigCommon;
use Branch8\PointMoneyConfig\Model\Product\PointMoneyConfigFreeRatioRedeemLimitType;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Item;

class Apply extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\PointMoneyCollect\Helper\Data
     */
    protected $pointHelperData;
    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Branch8\PointMoneyCollect\Helper\Data $pointHelperData,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->pointHelperData = $pointHelperData;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        try {
            $point = $this->getRequest()->getParam('point');
            if (($point && is_numeric($point)) || $point == 0) {
                // TODO: validate and apply the point to the current quote
                $quote = $this->checkoutSession->getQuote();
                $applyResult = $this->pointHelperData->applyPoint($point, $quote);
                $result->setData($applyResult);

            } else {
                $result->setData(['success' => false, 'msg' => __('Invalid point.')]);
            }
        } catch (\Exception $e) {
            $writer = new \Zend_Log_Writer_Stream(BP .'/var/log/custom.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info($e->getMessage());
            $result->setData(['success' => false, 'msg' =>__('Something went wrong. Please try again later.')]);
        }
        return $result;
    }

//    public function getCustomerPoints()
//    {
//        // TODO: use your API to get the current customer's available points
//        // For example, you can use curl to send a request to your API endpoint
//        $customerId = $this->checkoutSession->getQuote()->getCustomerId();
//        $apiUrl = 'https://your-api-url.com/points?customer_id=' . $customerId;
//        $curl = curl_init();
//        curl_setopt($curl, CURLOPT_URL, $apiUrl);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
//        $response = curl_exec($curl);
//        curl_close($curl);
//        // Assume the response is a JSON string with a 'points' key
//        $data = json_decode($response, true);
//        $points = $data['points'];
//        return $points;
//    }



}
