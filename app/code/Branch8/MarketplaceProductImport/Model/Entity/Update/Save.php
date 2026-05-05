<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceProductImport\Model\Entity\Update;

use Magento\Framework\Exception\LocalizedException;
use Magento\Staging\Model\Entity\Update\Action\Pool;

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
     * @param array $params
     */
    public function execute(
        array $params
    ){
        $action = $this->actionPool->getAction($this->entityName, 'save', $this->getActionType($params));
        $executor = $this->actionPool->getExecutor($action);
        !$executor->execute($params);
        return ['error'=>false, 'message'=> __('Product imported successfully.')];
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
