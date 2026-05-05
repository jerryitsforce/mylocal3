<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MagentoVisualMerchandiser\Plugin\Magento\VisualMerchandiser\Model;

use Branch8\MagentoVisualMerchandiser\Model\RuleIndex;
use Magento\Framework\App\ResourceConnection;
use Branch8\MagentoVisualMerchandiser\Helper\Logger as LoggerInterface;

/**
 * @method bool getIsActive()
 * @method string getConditionsSerialized()
 *
 * @api
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @since 100.0.2
 */
class RulesPlugin
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        LoggerInterface    $logger
    )
    {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param \Magento\VisualMerchandiser\Model\Rules $subject
     * @param $result
     * @param ...$args
     * @return void
     */
    public function afterAfterSave(\Magento\VisualMerchandiser\Model\Rules $subject, $result, ...$args)
    {
        if ($subject->isObjectNew() && $subject->getId()) {
            $this->resourceConnection->getConnection()->insertOnDuplicate(
                'visual_merchandiser_rule_index', [['vm_rule_id' => $subject->getId(), 'status' => RuleIndex::STATUS_DONE]], ['vm_rule_id', 'status']
            );
        }
    }
}
