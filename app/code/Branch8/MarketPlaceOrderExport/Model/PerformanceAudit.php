<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;

class PerformanceAudit
{
    private $timeStart = 0;
    private $timeEnd = 0;
    private $executeTime = 0;
    private $totalOrders = 0;
    private LoggerInterface $logger;
    private $startMem = 0;
    private $usedMem = 0;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
    }

    /**
     * @param $totalOrders
     * @return $this
     */
    public function setTotalOrders($totalOrders)
    {
        $this->totalOrders = $totalOrders;
        return $this;
    }

    /**
     * @return $this
     */
    public function begin()
    {
        $this->logger->info("=======================================================================");
        $this->timeStart = microtime(true);
        $this->startMem = memory_get_usage();
        return $this;
    }

    /**
     * @return PerformanceAudit
     */
    public function end()
    {
        $this->timeEnd = microtime(true);
        $this->executeTime = ($this->timeEnd - $this->timeStart) / 60;
        $this->usedMem = memory_get_usage() - $this->startMem;
        $this->summarize();
        return $this;
    }

    /**
     * @return $this
     */
    public function reset()
    {
        $this->timeStart = 0;
        $this->timeEnd = 0;
        $this->startMem = 0;
        $this->usedMem = 0;
        $this->executeTime = 0;
        $this->totalOrders = 0;
        return $this;
    }

    /**
     * @param $message
     * @return PerformanceAudit
     */
    public function log($message)
    {
        $this->logger->info($message);
        return $this;
    }

    /**
     * @return $this
     */
    public function summarize()
    {
        $peak = round(memory_get_peak_usage() / 1024 / 1024, 2);
        $usedMem = round($this->usedMem / 1024 / 1024, 2);
        $minutes = round($this->executeTime / 60);
        $this->logger->info("Total Orders: {$this->totalOrders}");
        $this->logger->info("Execution time: {$this->executeTime} seconds - {$minutes} minutes");
        $this->logger->info("Memory Usage: {$usedMem} MB");
        $this->logger->info("Memory Peak: {$peak} MB");
        return $this;
    }
}
