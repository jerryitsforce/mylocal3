<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       01/02/2026
 */

namespace Branch8\Customer\Model\Queue\Visitor\Queue;

use Magento\Customer\Model\VisitorFactory;
use Magento\Framework\Event\Manager;
use Psr\Log\LoggerInterface;

class Consumers
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var Manager
     */
    private Manager $eventManger;
    /**
     * @var VisitorFactory
     */
    private VisitorFactory $visitorFactory;

    /**
     * @param LoggerInterface $logger
     * @param Manager $manager
     * @param VisitorFactory $visitorFactory
     */
    public function __construct(
        LoggerInterface $logger,
        Manager         $manager,
        VisitorFactory  $visitorFactory
    )
    {
        $this->visitorFactory = $visitorFactory;
        $this->eventManger = $manager;
        $this->logger = $logger;
    }

    /**
     * @param string $json
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(string $json)
    {
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
            $this->logger->info('JSON:' . $json);
        }
        try {
            $data = json_decode($json, true);
        } catch (\Exception $exception) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->critical($exception->getMessage());
            }
        }
        if (empty($data['visitor_id'])
            || !($visitor = $this->visitorFactory->create()->load($data['visitor_id']))
            || !$visitor->getId()
        ) {
            return;
        }
        try {
            $visitor->addData($data);
            $visitor->getResource()->save(
                $visitor
            );
            $this->eventManger->dispatch('visitor_activity_save', ['visitor' => $visitor]);
        } catch (\Exception $exception) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'exceptionlog')){
                $this->logger->critical($exception->getMessage());
            }
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Customer', 'systemlog')){
                $this->logger->info($exception->getTraceAsString());
            }
        }
    }
}
