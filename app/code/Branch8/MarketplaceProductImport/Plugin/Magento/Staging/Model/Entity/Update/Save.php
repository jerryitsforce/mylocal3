<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceProductImport\Plugin\Magento\Staging\Model\Entity\Update;

use Branch8\MarketplaceStaging\Model\Product\Source\CreatedFrom;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\Result\Json;
use Magento\Staging\Model\Entity\Update\Action\Pool;
use Magento\Staging\Model\Entity\Update\Save as CoreStagingSave;

class Save
{
    /**
     * @var Pool
     */
    protected $actionPool;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @param Pool $actionPool
     * @param string $entityName
     */
    public function __construct(
        Pool $actionPool,
        $entityName
    ) {
        $this->actionPool = $actionPool;
        $this->entityName = $entityName;
    }

    /**
     * Execute actions
     *
     * @param CoreStagingSave $subject
     * @param callable $proceed
     * @param array $params
     * @return Json
     */
    public function aroundExecute(
        CoreStagingSave $subject,
        callable $proceed,
        array $params
    ){
        if (isset($params['entityData']['created_from']) && $params['entityData']['created_from'] == CreatedFrom::CREATED_FROM_SCHEDULE_IMPORTED) {
            $action = $this->actionPool->getAction($this->entityName, 'save', $this->getActionType($params));
            $executor = $this->actionPool->getExecutor($action);
            !$executor->execute($params);
        } else {
            return $proceed($params);
        }
    }

    /**
     * Retrieve staging mode
     *
     * @param array $params
     * @return string
     * @throws LocalizedException
     */
    protected function getActionType(array $params)
    {
        if (!isset($params['stagingData']['mode'])) {
            throw new LocalizedException(__("The 'mode' value is unexpected."));
        }
        return $params['stagingData']['mode'];
    }
}
