<?php

namespace Branch8\AdvancedPermissions\Plugin\AmastyRolepermissions\Ui\DataProvider\Product\Form\Modifier;

use Amasty\Rolepermissions\Helper\Data;
use Amasty\Rolepermissions\Model\Entity\Attribute\Source\Admins;
use Amasty\Rolepermissions\Ui\DataProvider\Product\Form\Modifier\Owner as AmRoleUiOwner;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Stdlib\ArrayManager;

class Owner
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Admins
     */
    private $admins;

    /**
     * @var AuthorizationInterface
     */
    private $authorization;

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        ArrayManager $arrayManager,
        Admins $admins,
        AuthorizationInterface $authorization,
        Data $helper
    ) {
        $this->arrayManager = $arrayManager;
        $this->admins = $admins;
        $this->authorization = $authorization;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function aroundModifyMeta(AmRoleUiOwner $subject, \Closure $proceed, array $meta)
    {
        foreach ($meta as $sectionName => &$section) {
            if (isset($section['children']['container_amrolepermissions_owner'])) {
                $meta = $this->arrayManager->remove(
                    "$sectionName/children/container_amrolepermissions_owner",
                    $meta
                );
            }
        }

        $model = $this->helper->currentRule();

        if ($model
            && $model->getLimitProductSourcesManagement()
            && isset($meta['sources'])
        ) {
            $meta['sources']['arguments']['data']['config']['visible'] = false;
        }

        return $meta;
    }
}
