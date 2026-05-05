<?php
namespace Branch8\RoleDelegate\Api;

use Branch8\RoleDelegate\Api\Data\DelegateInterface;

interface DelegateRepositoryInterface
{
    public function save(DelegateInterface $delegate): DelegateInterface;
    public function getById(int $id): DelegateInterface;
    public function getActiveForUser(int $userId, ?\DateTime $date = null): DelegateInterface;
    public function hasOverlap(int $userId, int $delegateId, string $startAt, string $endAt): bool;
    public function hasOverlapForDelegateUser(int $delegateUserId, int $delegateId, string $startAt, string $endAt): bool;
    public function cancel(int $id, int $actorId): void;
    public function search(array $filters = []): array;
}
