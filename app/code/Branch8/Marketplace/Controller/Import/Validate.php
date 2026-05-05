<?php

namespace Branch8\Marketplace\Controller\Import;

use Branch8\Marketplace\Model\Import\GetTrackingNumberFromCsv;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Webkul\Marketplace\Helper\Data as HelperData;
use Magento\Framework\Controller\Result\JsonFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class Validate extends \Magento\Framework\App\Action\Action{
    /**
     * @var HelperData
     */
    protected $sellerHelper;
    /**
     * @var \Branch8\Marketplace\Helper\Import
     */
    protected $importHelper;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    protected $formKey;

    /**
     * @var GetTrackingNumberFromCsv
     */
    private GetTrackingNumberFromCsv $getTrackingNumberFromCsv;

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param Context $context
     * @param HelperData $sellerHelper
     * @param \Branch8\Marketplace\Helper\Import $importHelper
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Framework\Data\Form\FormKey $formKey
     * @param GetTrackingNumberFromCsv $getTrackingNumberFromCsv
     * @param MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        Context $context,
        HelperData $sellerHelper,
        \Branch8\Marketplace\Helper\Import  $importHelper,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        GetTrackingNumberFromCsv $getTrackingNumberFromCsv,
        MarketplaceLogger $marketplaceLogger = null
    ){
        parent::__construct($context);
        $this->sellerHelper = $sellerHelper;
        $this->importHelper = $importHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKey = $formKey;
        $this->getTrackingNumberFromCsv = $getTrackingNumberFromCsv;
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute(){

        $isPartner = $this->sellerHelper->isSeller();
        if(!$isPartner){
            return $this->resultRedirectFactory->create()->setPath(
                '/',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }

        //validate list order of seller
        try {
            $data = $this->getTrackingNumberFromCsv->execute();

            $resultValidate = $this->importHelper->validateImportData($data);

            if (empty($resultValidate)) {
                $result = ['error' => 0, 'msg' => __('File is valid'), 'formKey' => $this->formKey->getFormKey()];
            } else {
                $lineError = '';
                foreach ($resultValidate as $result) {
                    $lineError .= __('Line ') . $result['line'] . ': ' . $result['error'] . '<br>';
                }
                $result = ['error' => 1, 'msg' => __('File is invalid') . '<br />' . $lineError];
            }
        } catch (LocalizedException $e) {
            $result = ['error' => 0, 'msg' => $e->getMessage()];
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('Validate', $e);
            $result = ['error' => 1, 'msg' => __('Something went wrong while validating the file. Please check the file and try again.')];
        }

        return $this->resultJsonFactory->create()->setData($result);
    }
}
