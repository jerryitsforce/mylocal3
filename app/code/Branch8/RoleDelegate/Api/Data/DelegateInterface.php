<?php
namespace Branch8\RoleDelegate\Api\Data;

interface DelegateInterface
{
    public function getId();
    public function getUserId();
    public function getDelegateUserId();
    public function getRoleId();
    public function getStartAt();
    public function getEndAt();
    public function getStatus();
    public function getCreatedBy();
    public function getUpdatedBy();
    public function getCreatedAt();
}
