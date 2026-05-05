<?php
namespace Branch8\GiftToFriend\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;

class CustomPlaceHelper extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'Branch8_GiftToFriend::system/config/custom_placeholder.phtml';

    protected $_conn;

    public function __construct(
        Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_conn = $resourceConnection->getConnection();
    }

    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    public function getCities(){
        $sqlCity = $this->_conn->select()
            ->from(['city' => 'hotai_city_directory']);
        $rows = [];
        $queryCity = $this->_conn->query($sqlCity);
        while($row = $queryCity->fetch()){
            $rows[$row['region_id']][$row['entity_id']] = $row['city'];
        }
        return json_encode($rows);
    }

    public function getCurrentCity(){
        $currentCity = $this->_scopeConfig->getValue('gift_order/placeholder_address/city');
        return ($currentCity) ? $currentCity : '';
    }
}
