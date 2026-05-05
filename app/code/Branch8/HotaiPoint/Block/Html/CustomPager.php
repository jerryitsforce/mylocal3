<?php
namespace Branch8\HotaiPoint\Block\Html;

use Magento\Theme\Block\Html\Pager;

class CustomPager extends Pager
{
    public function getPagerUrl($params = [])
    {
        if($this->getData('tab')) {
           $params['t'] = $this->getData('tab');
        }
        return parent::getPagerUrl($params);
    }


    /**
     * Set collection for pagination
     *
     * @param  \Magento\Framework\Data\Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;

        if(!$this->getData('tab') || $this->getData('tab') == $this->getRequest()->getParam('t')) {
            $this->_collection->setCurPage($this->getCurrentPage());
        }

        // If not int - then not limit
        if ((int)$this->getLimit()) {
            $this->_collection->setPageSize($this->getLimit());
        }

        $this->_setFrameInitialized(false);

        return $this;
    }
}
