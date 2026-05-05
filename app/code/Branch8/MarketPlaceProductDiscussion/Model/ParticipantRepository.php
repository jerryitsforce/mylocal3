<?php

namespace Branch8\MarketPlaceProductDiscussion\Model;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ParticipantInterface;
use Branch8\MarketPlaceProductDiscussion\Api\ParticipantRepositoryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\ResourceModel\Participant as Resource;

/**
 * Participant RepositoryInterface
 */
class ParticipantRepository implements ParticipantRepositoryInterface
{
    private Resource $resource;

    /**
     * @param Resource $resource
     */
    public function __construct(
        Resource $resource,
    )
    {
        $this->resource = $resource;
    }

    /**
     * @param ParticipantInterface $participant
     * @return ParticipantInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ParticipantInterface $participant)
    {
        try {
            $this->resource->save($participant);
            return $participant;
        } catch (\Exception $exception) {
            throw $exception;
        }
    }
}
