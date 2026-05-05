<?php

declare(strict_types=1);

namespace Branch8\HotaiCore\Setup\Patch\Data;

use Branch8\HotaiCore\Model\Config\Source\LogOption;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\State;
use Branch8\Yoxi\Model\ResourceModel\YoxiBatchSetting\CollectionFactory as YoxiBatchSettingCollectionFactory;
use Branch8\Yoxi\Model\YoxiBatchSetting;
use Branch8\FamilyBonusPin\Model\ResourceModel\FamilyBonusPinBatchSetting\CollectionFactory as FamilyBonusPinBatchSettingCollectionFactory;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting;
use Branch8\GeneralNotifyTicket\Model\GeneralNotifyTicketBatchSetting as GeneralNotifyBatchSetting;
use Branch8\GeneralNotifyTicket\Model\ResourceModel\GeneralNotifyTicketBatchSetting\CollectionFactory as GeneralNotifyBatchSettingCollectionFactory;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting as GeneralNonNotifyBatchSetting;
use Branch8\GeneralNonNotifyTicket\Model\ResourceModel\GeneralNonNotifyTicketBatchSetting\CollectionFactory as GeneralNonNotifyBatchSettingCollectionFactory;
use Branch8\HotaiCore\Helper\VirtualProduct as VirtualProductHelper;
use Branch8\HotaiCore\Helper\Common as CommonHelper;

class CreateBatchSettingCustomOption implements DataPatchInterface
{
    const LOG_FOLDER_NAME = 'HotaiCore/Patch/CreateBatchSettingCustomOption';

    private const DEBUG_LOG_OPTION = LogOption::LOG_CREATE_BATCH_SETTING_CUSTOM_OPTION;

    /** @var ModuleDataSetupInterface */
    protected $moduleDataSetup;

    /** @var State */
    protected $state;

    /** @var YoxiBatchSettingCollectionFactory */
    protected $yoxiBatchSettingCollectionFactory;

    /** @var FamilyBonusPinBatchSettingCollectionFactory */
    protected $familyBonusPinBatchSettingCollectionFactory;

    /** @var GeneralNotifyBatchSettingCollectionFactory */
    protected $generalNotifyBatchSettingCollectionFactory;

    /** @var GeneralNonNotifyBatchSettingCollectionFactory */
    protected $generalNonNotifyBatchSettingCollectionFactory;

    /** @var VirtualProductHelper */
    protected $virtualProductHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    protected $logArray;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        State $state,
        YoxiBatchSettingCollectionFactory $yoxiBatchSettingCollectionFactory,
        FamilyBonusPinBatchSettingCollectionFactory $familyBonusPinBatchSettingCollectionFactory,
        GeneralNotifyBatchSettingCollectionFactory $generalNotifyBatchSettingCollectionFactory,
        GeneralNonNotifyBatchSettingCollectionFactory $generalNonNotifyBatchSettingCollectionFactory,
        VirtualProductHelper $virtualProductHelper,
        CommonHelper $commonHelper
    ) {
        $this->moduleDataSetup                               = $moduleDataSetup;
        $this->state                                         = $state;
        $this->yoxiBatchSettingCollectionFactory             = $yoxiBatchSettingCollectionFactory;
        $this->familyBonusPinBatchSettingCollectionFactory   = $familyBonusPinBatchSettingCollectionFactory;
        $this->generalNotifyBatchSettingCollectionFactory    = $generalNotifyBatchSettingCollectionFactory;
        $this->generalNonNotifyBatchSettingCollectionFactory = $generalNonNotifyBatchSettingCollectionFactory;
        $this->virtualProductHelper                          = $virtualProductHelper;
        $this->commonHelper                                  = $commonHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        try {
            $this->state->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        }

        $yoxiBonusPinProductIdArray = $this->getYoxiProductIdArray();
        $this->createBatchSettingCustomOptionFlow($yoxiBonusPinProductIdArray);

        $familyBonusPinProductIdArray = $this->getFamilyBonusPinProductIdArray();
        $this->createBatchSettingCustomOptionFlow($familyBonusPinProductIdArray);

        $generalNotifyBonusPinProductIdArray = $this->getGeneralNotifyProductIdArray();
        $this->createBatchSettingCustomOptionFlow($generalNotifyBonusPinProductIdArray);

        $generalNonNotifyBonusPinProductIdArray = $this->getGeneralNonNotifyProductIdArray();
        $this->createBatchSettingCustomOptionFlow($generalNonNotifyBonusPinProductIdArray);

        $this->commonHelper->writeLogIfEnabled(
            json_encode($this->logArray),
            self::LOG_FOLDER_NAME,
            self::DEBUG_LOG_OPTION
        );
    }

    protected function createBatchSettingCustomOptionFlow($productIdArray)
    {
        foreach ($productIdArray as $productId) {
            try {
                $this->virtualProductHelper->createBatchSettingCustomOptionValueBasedOnCurrentBatchSetting($productId);
            } catch (\Exception $e) {
                $this->logArray[] = [
                    'productId'        => $productId,
                    'exceptionMessage' => $e->getMessage()
                ];

                continue;
            }
        }
    }

    protected function getYoxiProductIdArray(): array
    {
        $collection = $this->yoxiBatchSettingCollectionFactory->create();

        $collection->addFieldToSelect(
            [
                YoxiBatchSetting::BELONG_TO_PRODUCT_ID,
            ]
        );

        $collection->getSelect()->group(YoxiBatchSetting::BELONG_TO_PRODUCT_ID);

        $returnArray = [];

        foreach ($collection as $batchSetting) {
            $returnArray[] = $batchSetting->getData(YoxiBatchSetting::BELONG_TO_PRODUCT_ID);
        }

        return $returnArray;
    }

    protected function getFamilyBonusPinProductIdArray(): array
    {
        $collection = $this->familyBonusPinBatchSettingCollectionFactory->create();

        $collection->addFieldToSelect(
            [
                FamilyBonusPinBatchSetting::BELONG_TO_PRODUCT_ID,
            ]
        );

        $collection->getSelect()->group(FamilyBonusPinBatchSetting::BELONG_TO_PRODUCT_ID);

        $returnArray = [];

        foreach ($collection as $batchSetting) {
            $returnArray[] = $batchSetting->getData(FamilyBonusPinBatchSetting::BELONG_TO_PRODUCT_ID);
        }

        return $returnArray;
    }

    protected function getGeneralNotifyProductIdArray(): array
    {
        $collection = $this->generalNotifyBatchSettingCollectionFactory->create();

        $collection->addFieldToSelect(
            [
                GeneralNotifyBatchSetting::BELONG_TO_PRODUCT_ID,
            ]
        );

        $collection->getSelect()->group(GeneralNotifyBatchSetting::BELONG_TO_PRODUCT_ID);

        $returnArray = [];

        foreach ($collection as $batchSetting) {
            $returnArray[] = $batchSetting->getData(GeneralNotifyBatchSetting::BELONG_TO_PRODUCT_ID);
        }

        return $returnArray;
    }

    protected function getGeneralNonNotifyProductIdArray(): array
    {
        $collection = $this->generalNonNotifyBatchSettingCollectionFactory->create();

        $collection->addFieldToSelect(
            [
                GeneralNonNotifyBatchSetting::BELONG_TO_PRODUCT_ID,
            ]
        );

        $collection->getSelect()->group(GeneralNonNotifyBatchSetting::BELONG_TO_PRODUCT_ID);

        $returnArray = [];

        foreach ($collection as $batchSetting) {
            $returnArray[] = $batchSetting->getData(GeneralNonNotifyBatchSetting::BELONG_TO_PRODUCT_ID);
        }

        return $returnArray;
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
