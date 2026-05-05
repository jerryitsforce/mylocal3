<?php

namespace Branch8\HotaiPoint\Console\Command\Test;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HTGOTWO3364 extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Command/Test';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    public function __construct(
        State $state,
        ApiHelper $apiHelper,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->state = $state;
        $this->apiHelper = $apiHelper;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:test:HTGOTWO3364');
        $this->setDescription('Test Api::sendRequest User-Agent header via reflection.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $output->writeln('<info>Start testing Api::sendRequest User-Agent header...</info>');

        // 重置 Api 內部狀態，避免前一次呼叫干擾
        $this->apiHelper->reset();

        $apiHelper = $this->apiHelper;

        $refClass = new \ReflectionClass(ApiHelper::class);

        try {
            $refMethod = $refClass->getMethod('sendRequest');

            $refMethod->invoke(
                $apiHelper,
                "",
                []
            );

            $output->writeln('<info>sendRequest finished (no exception thrown).</info>');
        } catch (\Throwable $e) {
            $output->writeln('<comment>sendRequest threw exception (ignored for header test): ' . $e->getMessage() . '</comment>');
        }

        // 透過反射讀取 Api 內部的 request header / body 與 response header / body
        try {
            $apiPathProperty = $refClass->getProperty('apiPath');
            $apiPath = $apiPathProperty->getValue($apiHelper);

            $curlHeaderProperty = $refClass->getProperty('curlHeader');
            $curlHeader = $curlHeaderProperty->getValue($apiHelper);

            $curlBodyProperty = $refClass->getProperty('curlBody');
            $curlBody = $curlBodyProperty->getValue($apiHelper);

            $curlObjProperty = $refClass->getProperty('curl');
            $curlObj = $curlObjProperty->getValue($apiHelper);

            $responseHeaders = is_object($curlObj) ? $curlObj->getHeaders() : null;
            $responseBody    = $this->apiHelper->getLastResponse();
            $statusCode      = $this->apiHelper->getLastResponseStatus();

            $debugData = [
                'request'  => [
                    'apiPath' => $apiPath,
                    'header'  => $curlHeader,
                    'body'    => $curlBody,
                ],
                'response' => [
                    'statusCode' => $statusCode,
                    'header'     => $responseHeaders,
                    'body'       => $responseBody,
                ],
            ];

            $this->hotaiCoreCommonHelper->writeLog(
                json_encode($debugData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                self::LOG_FOLDER_NAME
            );

            $output->writeln('<info>Request/response debug data has been written to log.</info>');
        } catch (\Throwable $e) {
            $output->writeln('<error>Failed to dump request/response via reflection: ' . $e->getMessage() . '</error>');
        }

        return Command::SUCCESS;
    }
}

