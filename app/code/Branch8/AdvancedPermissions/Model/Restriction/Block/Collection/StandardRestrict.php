<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Model\Restriction\Block\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Cms\Model\ResourceModel\Block\Grid\Collection as BlockGridCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Block;

class StandardRestrict implements RestrictInterface
{
    /**
     * @var array
     */
    private $restrictedObjects = [];

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        Helper $helper,
        State $appState
    ) {
        $this->helper = $helper;
        $this->appState = $appState;
    }

    public function execute(BlockGridCollection $collection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($collection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictBlockCollection($rule, $collection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictBlockCollection($rule, BlockGridCollection $collection): void
    {
        switch ($rule->getBlockAccessMode()) {
            case Block::MODE_ANY:
                break;
            case Block::MODE_SELECTED:
                if ($rule->getBlocks()) {
                    $collection->addFieldToFilter('block_id', ['in' => $rule->getBlocks()]);
                }
                break;
        }
    }
}
