<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       15/04/2026
 */

namespace Branch8\Report\Model\Queue;

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
     * @param LoggerInterface $logger
     * @param \Magento\Reports\Model\EventFactory $event
     */
    public function __construct(
        LoggerInterface                              $logger,
        readonly \Magento\Reports\Model\EventFactory $event,
    )
    {

        $this->logger = $logger;
    }

    /**
     * @param string $json
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function execute(string $json)
    {
        try {
            $data = json_decode($json, true);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return;
        }
        try {
            $eventModel = $this->event->create();
            $eventModel->setData($data);
            $eventModel->save();
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->info('JSON: ' . $json);
        }
    }
}
