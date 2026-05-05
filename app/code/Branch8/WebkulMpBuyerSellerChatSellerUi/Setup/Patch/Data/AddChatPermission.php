<?php

namespace Branch8\WebkulMpBuyerSellerChatSellerUi\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Webkul\Marketplace\Model\ControllersRepository;

class AddChatPermission implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ControllersRepository
     */
    private $controllersRepository;

    /**
     * Initialize Dependencies
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ControllersRepository $controllersRepository
     * @return void
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ControllersRepository $controllersRepository
    ) {
        $this->moduleDataSetup          = $moduleDataSetup;
        $this->controllersRepository    = $controllersRepository;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $data = [];
        $connection = $this->moduleDataSetup->getConnection();
        if (!count($this->controllersRepository->getByPath(\Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Config\ChatPermission::CHAT_PERMISSION_KEY))) {
            $data = [
                'module_name' => 'Branch8_WebkulMpBuyerSellerChatSellerUi',
                'controller_path' => \Branch8\WebkulMpBuyerSellerChatSellerUi\Model\Config\ChatPermission::CHAT_PERMISSION_KEY,
                'label' => 'Chat',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }

        $connection->insert($this->moduleDataSetup->getTable('marketplace_controller_list'), $data);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Using for get Aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * Using for get Dependencies
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [

        ];
    }
}