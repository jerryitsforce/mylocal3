<?php

namespace Branch8\Marketplace\Controller\Import;

use Branch8\Marketplace\Helper\Import;
use Branch8\Marketplace\Model\Import\GetTrackingNumberFromCsv;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Webkul\Marketplace\Helper\Data as HelperData;

class Execute extends \Magento\Framework\App\Action\Action{
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

    protected $formKeyValidator;

    /**
     * @var GetTrackingNumberFromCsv
     */
    private GetTrackingNumberFromCsv $getTrackingNumberFromCsv;

    /**
     * @var LoggerInterface
     */
    private MarketplaceLogger $marketplaceLogger;
    /**
     * @var \Webkul\SellerSubAccount\Helper\Data
     */
    private $subAccountHelper;

    /**
     * @param Context $context
     * @param HelperData $sellerHelper
     * @param Import $importHelper
     * @param JsonFactory $resultJsonFactory
     * @param Validator $formKeyValidator
     * @param GetTrackingNumberFromCsv $getTrackingNumberFromCsv
     * @param MarketplaceLogger $marketplaceLogger
     * @param \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
     */
    public function __construct(
        Context $context,
        HelperData $sellerHelper,
        \Branch8\Marketplace\Helper\Import  $importHelper,
        JsonFactory $resultJsonFactory,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        GetTrackingNumberFromCsv $getTrackingNumberFromCsv,
        MarketplaceLogger $marketplaceLogger,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
    ){
        parent::__construct($context);
        $this->sellerHelper = $sellerHelper;
        $this->importHelper = $importHelper;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->getTrackingNumberFromCsv = $getTrackingNumberFromCsv;
        $this->marketplaceLogger = $marketplaceLogger;
        $this->subAccountHelper = $subAccountHelper;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute(){
        $jsonResult = $this->resultJsonFactory->create();
        if(!$this->formKeyValidator->validate($this->getRequest())){
            return $jsonResult->setData([
                'error' => 1,
                'msg' => 'Please login your seller account.'
            ]);
        }
        $isPartner = $this->sellerHelper->isSeller();
        if(!$isPartner && !$this->subAccountHelper->isSubAccount()){
            return $jsonResult->setData([
                'error' => 1,
                'msg' => 'Please login your seller account or sub account.'
            ]);
        }

        //validate list order of seller
        try {
            $data = $this->getTrackingNumberFromCsv->execute();

            $resultValidate = $this->importHelper->validateImportData($data);
            if (count($resultValidate) != 0) {
                return $jsonResult->setData([
                    'error' => 1,
                    'msg' => 'Please upload a valid file.'
                ]);
            }

            $this->importHelper->doImport($data);
        } catch (LocalizedException $e) {
            return $jsonResult->setData([
                'error' => 1,
                'msg' => 'Import failed. Please try again. ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('Execute', $e);
            return $jsonResult->setData([
                'error' => 1,
                'msg' => __('Something went wrong while importing the data. Please try again later.'),
            ]);
        }

        return $jsonResult->setData([
            'error' => 0,
            'msg' => 'Import successful.'
        ]);
    }
}
