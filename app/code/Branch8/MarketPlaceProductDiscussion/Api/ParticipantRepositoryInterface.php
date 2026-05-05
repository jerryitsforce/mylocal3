<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       25/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Api;

use Branch8\MarketPlaceProductDiscussion\Api\Data\ParticipantInterface;
/**
 *
 */
interface ParticipantRepositoryInterface
{
    /**
     * @param ParticipantInterface $thread
     * @return mixed
     */
    public function save(ParticipantInterface $participant);
}
