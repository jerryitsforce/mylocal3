<?php
namespace Branch8\Spin2Win\Plugin\Block\Adminhtml\Segment\Renderer;

class Action{

    public function afterRender($subject, $result, $row){
        $delUrl = $subject->getUrl('spintowin/segment/delete', ['id' => $row->getId()]);
        $result = '<a href="#" class="spin-segment-delete-action" data-del-url="'.$delUrl.'">Delete</a>'.' | '.$result;
        return $result;
    }
}