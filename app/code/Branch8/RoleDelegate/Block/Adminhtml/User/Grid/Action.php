<?php

namespace Branch8\RoleDelegate\Block\Adminhtml\User\Grid;

/**
 * Class Action
 * @package Branch8\RoleDelegate\Block\Adminhtml\User\Grid
 */
class Action extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action
{
    public function render(\Magento\Framework\DataObject $row): string
    {
        if ($row->getStatus() != 'pending' && $row->getStatus() != 'active') {
            return '&nbsp;';
        }
        return parent::render($row);
    }
    /**
     * @param $action
     * @param $actionCaption
     * @param \Magento\Framework\DataObject $row
     * @return $this
     */
    protected function _transformActionData(&$action, &$actionCaption, \Magento\Framework\DataObject $row)
    {
        foreach ($action as $attribute => $value) {
            if (isset($action[$attribute]) && !is_array($action[$attribute])) {
                $this->getColumn()->setFormat($action[$attribute]);
                $action[$attribute] = \Magento\Backend\Block\Widget\Grid\Column\Renderer\Text::render($row);
            } else {
                $this->getColumn()->setFormat(null);
            }

            switch ($attribute) {
                case 'caption':
                    if ($row->getStatus() == 'active' && $value == __('Edit')) {
                        $action = [];
                        return $this;
                    }
                    $actionCaption = $action['caption'];
                    unset($action['caption']);
                    break;

                case 'url':
                    if (is_array($action['url']) && isset($action['field'])) {
                        $params = [$action['field'] => $this->_getValue($row)];
                        if (isset($action['url']['params'])) {
                            //core fix
                            if(is_array($action['url']['params'])) {
                                foreach ($action['url']['params'] as $key => $value) {
                                    $params[$key] = $value;
                                }
                            }
                        }
                        $action['href'] = $this->getUrl($action['url']['path'], $params);
                        unset($action['field']);
                    } else {
                        $action['href'] = $action['url'];
                    }
                    unset($action['url']);
                    break;

                case 'popup':
                    $action['onclick'] = 'popWin(this.href,\'_blank\',\'width=800,height=700,resizable=1,'
                        . 'scrollbars=1\');return false;';
                    break;
            }
        }
        return $this;
    }
}
