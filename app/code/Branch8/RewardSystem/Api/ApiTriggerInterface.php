<?php
namespace Branch8\RewardSystem\Api;

interface ApiTriggerInterface
{
    /**
     * Add customer to event
     *
     * @param \Branch8\RewardSystem\Api\ApiRequestInterface $requestData
     * @return \Branch8\RewardSystem\Api\ApiResponseInterface
     * @throws \Exception
     */
    public function addData(\Branch8\RewardSystem\Api\ApiRequestInterface $requestData);

}
