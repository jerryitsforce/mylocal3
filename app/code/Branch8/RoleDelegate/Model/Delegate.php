<?php
namespace Branch8\RoleDelegate\Model;

use Magento\Framework\Model\AbstractModel;

class Delegate extends AbstractModel implements \Branch8\RoleDelegate\Api\Data\DelegateInterface
{
    protected function _construct()
    {
        $this->_init(\Branch8\RoleDelegate\Model\ResourceModel\Delegate::class);
    }

    public function getId() { return $this->getData('id'); }
    public function getUserId() { return (int)$this->getData('user_id'); }
    public function getDelegateUserId() { return (int)$this->getData('delegate_user_id'); }
    public function getRoleId() { return $this->getData('role_id'); }
    public function getStartAt() { return $this->getData('start_at'); }
    public function getEndAt() { return $this->getData('end_at'); }
    public function getStatus() { return $this->getData('status'); }
    public function getCreatedBy() { return $this->getData('created_by'); }
    public function getUpdatedBy() { return $this->getData('updated_by'); }
    public function getCreatedAt() { return $this->getData('created_at'); }
}
