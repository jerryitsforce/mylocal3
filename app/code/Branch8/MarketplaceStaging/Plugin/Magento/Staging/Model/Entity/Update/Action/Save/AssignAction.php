<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceStaging\Plugin\Magento\Staging\Model\Entity\Update\Action\Save;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\Config;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\StoreChangedData;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceStaging\Api\ProductVersionDataRepositoryInterface;
use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Framework\App\Area;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Staging\Model\Entity\Update\Action\Save\AssignAction as StagingAssignAction;
use Magento\Staging\Model\VersionManager;
use Webkul\Marketplace\Helper\Data as MarketplaceHelper;
use Webkul\Marketplace\Model\Product;
use Webkul\MarketplacePreorder\Helper\Data;

class AssignAction
{
    /**
     * @var StoreChangedData
     */
    protected StoreChangedData $storeChangedData;

    /**
     * @var MarketplaceHelper
     */
    protected MarketplaceHelper $marketplaceHelper;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var ProductVersionDataRepositoryInterface
     */
    private ProductVersionDataRepositoryInterface $productVersionDataRepository;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $preorderHelper;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * @var State
     */
    private State $state;

    /**
     * @param StoreChangedData $storeChangedData
     * @param MarketplaceHelper $marketplaceHelper
     * @param Config $config
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param VersionManager $versionManager
     * @param SerializerInterface $serializer
     * @param ProductVersionDataRepositoryInterface $productVersionDataRepository
     * @param RequestInterface $request
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param Data $preorderHelper
     * @param TimezoneInterface $timezoneInterface
     * @param State $state
     */
    public function __construct(
        StoreChangedData $storeChangedData,
        MarketplaceHelper $marketplaceHelper,
        Config $config,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        VersionManager $versionManager,
        SerializerInterface $serializer,
        ProductVersionDataRepositoryInterface $productVersionDataRepository,
        RequestInterface $request,
        MarketplaceProductManagement $marketplaceProductManagement,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        TimezoneInterface $timezoneInterface,
        State $state
    ) {
        $this->storeChangedData = $storeChangedData;
        $this->marketplaceHelper = $marketplaceHelper;
        $this->config = $config;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->versionManager = $versionManager;
        $this->serializer = $serializer;
        $this->productVersionDataRepository = $productVersionDataRepository;
        $this->request = $request;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->preorderHelper = $preorderHelper;
        $this->timezoneInterface = $timezoneInterface;
        $this->state = $state;
    }

    public function aroundExecute(
        StagingAssignAction $subject,
        \Closure $proceed,
        array $params
    ){
        $controller = $this->request->getRouteName();
        if ($this->state->getAreaCode() == Area::AREA_CRONTAB
            || !$this->marketplaceHelper->getIsProductEditApproval()
            || in_array($controller, ['catalogstaging', 'cmsstaging', 'catalogrulestaging', 'salesrulestaging'])) {
            return $proceed($params);
        }
        $this->validateParams($params);
        $stagingId = $params['stagingData']['update_id'] ?? null;
        $productId = $params['entityData']['id'] ?? null;
        if ($this->request->getParam('seller_id')) {
            $sellerId = $this->request->getParam('seller_id');
        } else {
            $sellerId = $this->marketplaceHelper->getCustomerId();
        }
        if (!$productId) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Product ID is missing.')
            );
        }
        $currentDateTime = new \DateTime();
        if ($stagingId && $currentDateTime->getTimestamp() >= strtotime($params['stagingData']['start_time'])) {
            $message = __('The schedule is currently in progress. Editing products is not supported at this time. Please wait until the schedule is completed before making any changes. Thank you.');
            throw new \Magento\Framework\Exception\LocalizedException(
                $message
            );
        }

        $entityData = $params['entityData'];
        $attributeOptions = $this->preorderHelper->getPreorderAttribute('simple');
        $enabledId = -1;
        foreach ($attributeOptions as $attributeOption) {
            if ($attributeOption['label']=='Enable' || $attributeOption['label']=='啟用') {
                $enabledId = $attributeOption['value'];
            }
        }
        $isPreorderMatch = 0;
        if (is_array($entityData) && is_array($entityData['product']) &&
            array_key_exists("wk_marketplace_preorder", $entityData['product'])
            && array_key_exists("wk_marketplace_availability", $entityData['product'])
            && $entityData['product']['wk_marketplace_preorder'] == $enabledId
            && $entityData['product']['preorder_mode'] == 1 /*Only Mode start-end date have to use available date*/
        ) {
            $isPreorderMatch = 1;
            $today = date('m/d/y');
            if (strtotime($entityData['product']['wk_marketplace_availability']) < strtotime($today)) {
                $message = __("Preorder Availability date should be of future");
                throw new \Magento\Framework\Exception\LocalizedException(
                    $message
                );
            }
        }
        if ($isPreorderMatch) {
            if ($entityData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::START_END_DATE) {
                //validate start/ end date
                $startDate = $this->convertDate($entityData['product']['preorder_start_date']);
                $endDate = $this->convertDate($entityData['product']['preorder_end_date']);
                if (strtotime($startDate) >= strtotime($endDate)) {
                    $message = __('Pre-order Start date must be less than End date.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                //validate available/ end date
                $availableDate = $this->convertDate($entityData['product']['wk_marketplace_availability']);
                if (strtotime($endDate) > strtotime($availableDate)) {
                    $message = __('Pre-order End date must be less than Available date.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
            } else if ($entityData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::X_DAYS) {
                $endDate = $this->convertDate($entityData['product']['preorder_end_date']);
                if ($endDate == '') {
                    $message = __('Please set End date for PreOrder X-Days mode.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if (strtotime($todayDate) > strtotime($endDate)) {
                    $message = __('Pre-order End date must be in the future.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                if ((int)$entityData['product']['preorder_x_days'] == 0) {
                    $message = __('Pre-order XDays field must be great than 0 for XDays mode.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
            } else if ($entityData['product']['preorder_mode'] == \Branch8\Preorder\Model\Source\PreorderMode::SPECIFY_SHIPPING_DATE) {
                $endDate = $this->convertDate($entityData['product']['preorder_end_date']);
                if ($endDate == '') {
                    $message = __('Please set End date for PreOrder Specify Shipping Date mode.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                $todayDate = $this->timezoneInterface->date()->format('Y-m-d 00:00:00');
                if (strtotime($todayDate) > strtotime($endDate)) {
                    $message = __('Pre-order End date must be in the future.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                $shipDate = $this->convertDate($entityData['product']['preorder_ship_date']);
                if ($shipDate == '') {
                    $message = __('Please set Ship Date for PreOrder Specify Shipping Date mode.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
                if (strtotime($todayDate) > strtotime($shipDate)) {
                    $message = __('Pre-order Ship date must be in the future.');
                    throw new \Magento\Framework\Exception\LocalizedException(
                        $message
                    );
                }
            }
        }
        $returnArr = $this->storeChangedData->execute($entityData, $productId, $sellerId, CreatedFrom::CREATED_FROM_SCHEDULE);
        $isError = $returnArr['error'] ?? false;
        if ($isError) {
            $message = $returnArr['message'] ?? 'Something went wrong while saving this product.';
            throw new \Magento\Framework\Exception\LocalizedException(
                $message
            );
        } else {
            $changedData = $returnArr['data'] ?? [];
            $logId = $returnArr['log_id'] ?? null;
        }

        $excludeAttributes = $this->config->getExcludeAttributes();
        $excludeAttributes[] = BuildConfigurableProduct::VARIATIONS_MATRIX;
        $shouldSave = false;
        $skipLog = true;
        foreach ($changedData as $attribute => $changed) {
            if (empty($changed['after'])) {
                continue;
            }

            $checkSave = (in_array($attribute, $excludeAttributes)) ? true : false;
            if (!$checkSave && $controller !== 'marketplacectrl') {
                $params['entityData']['product'][$attribute] = $changed['before'];
                unset($params['entityData']['product']['media_gallery']);
                $skipLog = false;
            } else {
                $shouldSave = true;
            }
        }
        if (empty($changedData)) $shouldSave = true;
        if ($skipLog) {
            $productVersion = $this->productVersionRepository->getById((int)$logId);
            $productVersion->setStatus(Product::STATUS_ENABLED);
            $this->productVersionRepository->save($productVersion);
        } else {
            $this->request->setPostValue('product', $params['entityData']['product']);
            $marketplaceProduct = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            $marketplaceProduct->setData('status', '');
            $this->marketplaceProductManagement->save($marketplaceProduct);
        }
        if ($shouldSave) {
            if (!$stagingId && !$skipLog) {
                $proceed($params);
                $stagingId = $this->versionManager->getCurrentVersion()->getId();
                if ($stagingId) {
                    $productVersionData = $this->productVersionDataRepository->get((int)$logId);
                    $data = $params;
                    $data['stagingData']['update_id'] = $stagingId;
                    $productVersionData->setInformation($this->serializer->serialize($data));
                    $this->productVersionDataRepository->save($productVersionData);
                }
                return true;
            }
            return $proceed($params);
        }

        return true;
    }

    /**
     * Validate input parameters
     *
     * @param array $params
     * @return void
     */
    protected function validateParams(array $params)
    {
        foreach (['stagingData', 'entityData'] as $requiredParam) {
            if (!isset($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The required parameter is "%1". Set parameter and try again.', $requiredParam)
                );
            }
            if (!is_array($params[$requiredParam])) {
                throw new \InvalidArgumentException(
                    __('The "%1" parameter is invalid. Verify the parameter and try again.', $requiredParam)
                );
            }
        }
    }

    /**
     * @param string $date
     * @return string
     */
    private function convertDate($date){
        try {
            $time = strtotime($date);
            return date('Y-m-d', $time) . ' 00:00:00';
        }catch(\Exception $e){
            return '';
        }
    }
}
