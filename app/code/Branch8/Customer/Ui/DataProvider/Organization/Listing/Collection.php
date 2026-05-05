<?php
namespace Branch8\Customer\Ui\DataProvider\Organization\Listing;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{

      protected function _initSelect()
      {
//          $this->addFilterToMap('entity_id', 'main_table.entity_id');
//          $this->addFilterToMap('name', 'devgridname.value');
          parent::_initSelect();
      }
}
