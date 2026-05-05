<?php
namespace Branch8\Spin2Win\Model;


class SpinInfor extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface{

    const CACHE_TAG = 'spintowin_info';

    protected $_cacheTag = 'spintowin_info';

    protected $_eventPrefix = 'spintowin_info';

    /**
     * @return void
     */
    protected function _construct(){
        $this->_init('Branch8\Spin2Win\Model\ResourceModel\SpinInfor');
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