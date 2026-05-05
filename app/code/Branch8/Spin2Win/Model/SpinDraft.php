<?php
namespace Branch8\Spin2Win\Model;


class SpinDraft extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'spintowin_draft';

    protected $_cacheTag = 'spintowin_draft';

    protected $_eventPrefix = 'spintowin_draft';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Spin2Win\Model\ResourceModel\SpinDraft');
    }

    /**
     * @return string[]
     */
    public function getIdentities(){
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @return int
     */
    public function getDefaultValues(){
        $values = 1;

        return $values;
    }
}