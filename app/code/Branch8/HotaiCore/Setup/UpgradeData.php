<?php

namespace Branch8\HotaiCore\Setup;

use Branch8\HotaiCore\Model\Order\State as HotaiOrderState;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;
use Branch8\HotaiCore\Model\Product\BarcodeType;
use Branch8\HotaiCore\Model\Product\ExchangeUrl;
use Branch8\HotaiCore\Model\Product\ExchangeHint;
use Branch8\HotaiCore\Model\Product\IsOfflineOperation;
use Branch8\HotaiCore\Model\Product\DisplaySerialNumber;
use Branch8\HotaiCore\Model\Product\DisplayBarcode;
use Branch8\HotaiCore\Model\Product\ReturnTicketValue;
use Branch8\HotaiCore\Model\Product\EdenredOrderNumber;
use Branch8\HotaiCore\Model\Product\EdenredProductCode;
use Branch8\HotaiCore\Model\Product\EdenredMerchantCode;
use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Branch8\HotaiCore\Model\Product\QwareGuid;
use Branch8\HotaiCore\Model\Product\QwareSaleStartDate;
use Branch8\HotaiCore\Model\Product\QwareSaleEndDate;
use Branch8\HotaiCore\Model\Product\OpenHubProductId;
use Branch8\HotaiCore\Setup\Flow\StateAndStatus;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Sales\Model\Order\Status;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;
use Magento\Sales\Model\ResourceModel\Order\StatusFactory as StatusResourceFactory;

class UpgradeData implements UpgradeDataInterface
{
    const ASSIGN_GROUP = "Product Details";

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var EavSetupFactory */
    private $eavSetupFactory;

    /** @var StatusFactory */
    protected $statusFactory;

    /** @var StatusResourceFactory */
    protected $statusResourceFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory,
        StatusFactory $statusFactory,
        StatusResourceFactory $statusResourceFactory
    ) {
        $this->moduleDataSetup       = $moduleDataSetup;
        $this->eavSetupFactory       = $eavSetupFactory;
        $this->statusFactory         = $statusFactory;
        $this->statusResourceFactory = $statusResourceFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->moduleDataSetup->getConnection()->startSetup();

        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', VirtualProductType::ATTRIBUTE_CODE)) {
                $this->createAttributeVirtualProductType($eavSetup);
            }
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', ExchangeUrl::ATTRIBUTE_CODE)) {
                $this->createAttributeExchangeUrl($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', BarcodeType::ATTRIBUTE_CODE)) {
                $this->createAttributeBarcodeType($eavSetup);
            }
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            $this->createOrderStatusShipping();
            $this->createOrderStatusArrived();
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            $this->createFlowOrderStatus(StateAndStatus::VERSION_1);
        }

        if (version_compare($context->getVersion(), '1.0.5', '<')) {
            $this->createOrderStatusPicked();
            $this->createOrderStatusTallying();
        }

        if (version_compare($context->getVersion(), '1.0.6', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', ExchangeHint::ATTRIBUTE_CODE)) {
                $this->createAttributeExchangeHint($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', IsOfflineOperation::ATTRIBUTE_CODE)) {
                $this->createAttributeIsOfflineOperation($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', DisplaySerialNumber::ATTRIBUTE_CODE)) {
                $this->createAttributeDisplaySerialNumber($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', DisplayBarcode::ATTRIBUTE_CODE)) {
                $this->createAttributeDisplayBarcode($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', ReturnTicketValue::ATTRIBUTE_CODE)) {
                $this->createAttributeReturnTicketValue($eavSetup);
            }
        }

        if (version_compare($context->getVersion(), '1.0.7', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', EdenredOrderNumber::ATTRIBUTE_CODE)) {
                $this->createAttributeEdenredOrderNumber($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', EdenredProductCode::ATTRIBUTE_CODE)) {
                $this->createAttributeEdenredProductCode($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', EdenredMerchantCode::ATTRIBUTE_CODE)) {
                $this->createAttributeEdenredMerchantCode($eavSetup);
            }

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                EdenredOrderNumber::ATTRIBUTE_CODE,
                'is_required',
                1
            );

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                EdenredProductCode::ATTRIBUTE_CODE,
                'is_required',
                1
            );

            $eavSetup->updateAttribute(
                \Magento\Catalog\Model\Product::ENTITY,
                EdenredMerchantCode::ATTRIBUTE_CODE,
                'is_required',
                1
            );
        }

        if (version_compare($context->getVersion(), '1.0.8', '<')) {
            $this->deleteUnusedStatus(\Branch8\HotaiCore\Setup\Unused\Status::VERSION_1);
            $this->createFlowOrderStatus(StateAndStatus::VERSION_2);
        }

        if (version_compare($context->getVersion(), '1.0.9', '<')) {
            $this->deleteUnusedStatus(\Branch8\HotaiCore\Setup\Unused\Status::VERSION_2);
            $this->createFlowOrderStatus(StateAndStatus::VERSION_2);
        }

        if (version_compare($context->getVersion(), '1.0.10', '<')) {
            $this->createFlowOrderStatus(StateAndStatus::VERSION_CANCELLATION_PENDING);
        }

        if (version_compare($context->getVersion(), '1.0.11', '<')) {
            $this->createFlowOrderStatus(StateAndStatus::VERSION_NEW_RMA_CANCEL_STATUS);
        }

        if (version_compare($context->getVersion(), '1.0.12', '<')) {
            $this->createPendingCompleteStatus(StateAndStatus::VERSION_PENDING_COMPLETE_STATUS);
        }

        if (version_compare($context->getVersion(), '1.0.13', '<')) {
            $this->createFlowOrderStatus(StateAndStatus::VERSION_GIFT_ORDER_STATUS);
        }

        if (version_compare($context->getVersion(), '1.0.14', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', QwareGuid::ATTRIBUTE_CODE)) {
                $this->createAttributeQwareGuid($eavSetup);
            }
        }

        if (version_compare($context->getVersion(), '1.0.15', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', QwareSaleStartDate::ATTRIBUTE_CODE)) {
                $this->createAttributeQwareSaleStartDate($eavSetup);
            }

            if (!$eavSetup->getAttributeId('catalog_product', QwareSaleEndDate::ATTRIBUTE_CODE)) {
                $this->createAttributeQwareSaleEndDate($eavSetup);
            }
        }

        if (version_compare($context->getVersion(), '1.0.16', '<')) {
            $this->createFlowOrderStatus(StateAndStatus::VERSION_CANCEL_PENDING_FOR_PARENT_ORDER_RECREATE);
        }

        if (version_compare($context->getVersion(), '1.0.17', '<')) {
            if (!$eavSetup->getAttributeId('catalog_product', OpenHubProductId::ATTRIBUTE_CODE)) {
                $this->createAttributeOpenHubProductId($eavSetup);
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    protected function createAttributeVirtualProductType(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            VirtualProductType::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Virtual Product Type',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => VirtualProductType::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => false,
                'default'                 => VirtualProductType::TYPE_DEFAULT,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    protected function createAttributeExchangeUrl(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            ExchangeUrl::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Exchange Url',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => false,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    protected function createAttributeBarcodeType(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            BarcodeType::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Barcode Type',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => BarcodeType::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => false,
                'default'                 => BarcodeType::TYPE_CODE_39,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => self::ASSIGN_GROUP,
            ]
        );
    }

    protected function createOrderStatusShipping()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();

        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => HotaiOrderStatus::STATUS_SHIPPING,
            'label'  => 'Shipping',
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $e) {
            return;
        }

        $status->assignState(HotaiOrderState::STATE_PROCESSING, false, true);
    }

    protected function createOrderStatusArrived()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();

        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => HotaiOrderStatus::STATUS_ARRIVED,
            'label'  => 'Arrived',
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $e) {
            return;
        }

        $status->assignState(HotaiOrderState::STATE_PROCESSING, false, true);
    }

    /**
     * createRmaOrderStatus
     *
     * @return void
     */
    protected function createFlowOrderStatus($data)
    {
        foreach ($data as $state => $groupStatus) {

            foreach ($groupStatus as $singleStatus) {
                $statusResource = $this->statusResourceFactory->create();

                $tt = $this->statusFactory->create()->load($singleStatus['status']);
                if($tt->getSize()) {
                    $statusResource->delete($tt);
                }

                /** @var Status $status */
                $status = $this->statusFactory->create();
                $status->setData($singleStatus);

                try {
                    $statusResource->save($status);
                } catch (AlreadyExistsException $e) {
                    continue;
                }

                $status->assignState($state, false, true);
            }

        }

    }

    protected function createPendingCompleteStatus($data)
    {
        foreach ($data as $state => $groupStatus) {
            foreach ($groupStatus as $singleStatus) {
                $statusResource = $this->statusResourceFactory->create();

                $tt = $this->statusFactory->create()->load($singleStatus['status']);
                if($tt->getSize()) {
                    $statusResource->delete($tt);
                }

                /** @var Status $status */
                $status = $this->statusFactory->create();
                $status->setData($singleStatus);

                try {
                    $statusResource->save($status);
                } catch (AlreadyExistsException $e) {
                    continue;
                }

                $status->assignState($state, false, true);
            }
        }
    }


    /**
     * createOrderStatusPicked
     *
     * @return void
     */
    protected function createOrderStatusPicked()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();

        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => HotaiOrderStatus::STATUS_PICKED,
            'label'  => 'Picked',
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $e) {
            return;
        }

        $status->assignState(HotaiOrderState::STATE_PROCESSING, false, true);
    }

    /**
     * createOrderStatusTallying
     *
     * @return void
     */
    protected function createOrderStatusTallying()
    {
        /** @var StatusResource $statusResource */
        $statusResource = $this->statusResourceFactory->create();

        /** @var Status $status */
        $status = $this->statusFactory->create();
        $status->setData([
            'status' => HotaiOrderStatus::STATUS_TALLYING,
            'label'  => 'Tallying',
        ]);

        try {
            $statusResource->save($status);
        } catch (AlreadyExistsException $e) {
            return;
        }

        $status->assignState(HotaiOrderState::STATE_PROCESSING, false, true);
    }

    protected function createAttributeExchangeHint(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            ExchangeHint::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Exchange Hint',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => false,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeIsOfflineOperation(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            IsOfflineOperation::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Is Offline Operation',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => IsOfflineOperation::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => IsOfflineOperation::TYPE_IS_OFFLINE,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeDisplaySerialNumber(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            DisplaySerialNumber::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Display Serial Number',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => DisplaySerialNumber::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => DisplaySerialNumber::TYPE_NO_DISPLAY,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeDisplayBarcode(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            DisplayBarcode::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Display Barcode',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => DisplayBarcode::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => DisplayBarcode::TYPE_NO_DISPLAY,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeReturnTicketValue(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            ReturnTicketValue::ATTRIBUTE_CODE,
            [
                'type'                    => 'int',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Return Ticket Value',
                'input'                   => 'select',
                'class'                   => '',
                'source'                  => ReturnTicketValue::class,
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => ReturnTicketValue::TYPE_NO_RETURN,
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeEdenredOrderNumber(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            EdenredOrderNumber::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Edenred Order Number',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeEdenredProductCode(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            EdenredProductCode::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Edenred Product Code',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeEdenredMerchantCode(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            EdenredMerchantCode::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Edenred Merchant Code',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeQwareGuid(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            QwareGuid::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Qware Guid',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeQwareSaleStartDate(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            QwareSaleStartDate::ATTRIBUTE_CODE,
            [
                'type'                    => 'datetime',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Qware Sale Start Date',
                'input'                   => 'date',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeQwareSaleEndDate(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            QwareSaleEndDate::ATTRIBUTE_CODE,
            [
                'type'                    => 'datetime',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'Qware Sale End Date',
                'input'                   => 'date',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => false,
                'filterable'              => false,
                'comparable'              => false,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function createAttributeOpenHubProductId(EavSetup $eavSetup)
    {
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            OpenHubProductId::ATTRIBUTE_CODE,
            [
                'type'                    => 'text',
                'backend'                 => '',
                'frontend'                => '',
                'label'                   => 'OpenHub Product ID',
                'input'                   => 'text',
                'class'                   => '',
                'source'                  => '',
                'global'                  => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'                 => true,
                'required'                => true,
                'user_defined'            => true,
                'default'                 => '',
                'searchable'              => true,
                'filterable'              => true,
                'comparable'              => true,
                'visible_on_front'        => false,
                'used_in_product_listing' => true,
                'unique'                  => false,
                'apply_to'                => 'virtual',
                'group'                   => '',
            ]
        );
    }

    protected function deleteUnusedStatus($array) {

        foreach ($array as $status_code) {
            $statusResource = $this->statusResourceFactory->create();

            /** @var Status $status */
            $status = $this->statusFactory->create()->load($status_code);
            $statusResource->delete($status);
        }

    }
}
