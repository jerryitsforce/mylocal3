<?php

declare(strict_types=1);

namespace Branch8\TicketApi\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Branch8\TicketApi\Model\TicketApiBrand;
use Branch8\TicketApi\Model\TicketApiBrandRepository;
use Branch8\TicketApi\Model\TicketApiBrandFactory;
use Branch8\TicketApi\Model\TicketApiBrand\Source\Brand;
use Branch8\TicketApi\Setup\Patch\Data\InitTicketApiBrandTableContent;

class AddGeneralNotifyTicketToBrandTable implements DataPatchInterface
{

    /** @var ModuleDataSetupInterface */
    private $moduleDataSetup;

    /** @var TicketApiBrandRepository */
    protected $ticketApiBrandRepository;

    /** @var TicketApiBrandFactory */
    protected $ticketApiBrandFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        TicketApiBrandRepository $ticketApiBrandRepository,
        TicketApiBrandFactory $ticketApiBrandFactory,
    ) {
        $this->moduleDataSetup          = $moduleDataSetup;
        $this->ticketApiBrandRepository = $ticketApiBrandRepository;
        $this->ticketApiBrandFactory    = $ticketApiBrandFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        // 20240911, add BRAND_CODE_GENERAL_NOTIFY
        $brandNameMap = Brand::getBrandNameMap();
        $brandCodeMap = Brand::getBrandCodeMap();

        foreach ($brandCodeMap as $brandId => $brandCode) {
            $brand = $this->ticketApiBrandRepository->getSettingByBrandCode($brandCode);

            if ($brand) {
                continue;
            }

            $brandName = $brandNameMap[$brandId];

            /** @var TicketApiBrand $newBrand */
            $newBrand = $this->ticketApiBrandFactory->create();

            $newBrand->setBrandId($brandId);
            $newBrand->setBrandName($brandName);
            $newBrand->setBrandCode($brandCode);

            $this->ticketApiBrandRepository->save($newBrand);
        }
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
        return [
            InitTicketApiBrandTableContent::class
        ];
    }
}

