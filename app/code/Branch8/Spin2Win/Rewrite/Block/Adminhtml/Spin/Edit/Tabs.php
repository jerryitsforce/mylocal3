<?php

namespace Branch8\Spin2Win\Rewrite\Block\Adminhtml\Spin\Edit;

class Tabs extends \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tabs
{
    protected function _prepareLayout()
    {
        $spininfoModel = $this->registry->registry('spininfo');

        $this->addTab(
            'information',
            [
                'label' => __('Information'),
                'content' => $this->getLayout()->createBlock(
                    \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Information::class
                )->toHtml(),
                'active' => true
            ]
        );

        if ($spininfoModel->getId()) {
            // $this->addTab(
            //     'form',
            //     [
            //         'label' => __('Form'),
            //         'content' => $this->getLayout()->createBlock(
            //             \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Form::class
            //         )->toHtml()
            //     ]
            // );
            // $this->addTab(
            //     'wheel',
            //     [
            //         'label' => __('Spin Wheel'),
            //         'content' => $this->getLayout()->createBlock(
            //             \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Wheel::class
            //         )->toHtml()
            //     ]
            // );
            $this->addTab(
                'addsegment',
                [
                    'label' => __('Add Segment'),
                    'url' => $this->getUrl('spintowin/segment/edit', ['spin_id' => $spininfoModel->getId()]),
                    'class' => 'ajax'
                ]
            );
            $this->addTab(
                'segments',
                [
                    'label' => __('Prizes'),
                    'url' => $this->getUrl('spintowin/segment/index', ['spin_id' => $spininfoModel->getId()]),
                    'class' => 'ajax'
                ]
            );
            $this->addTab(
                'consolation',
                [
                    'label' => __('Consolation'),
                    'content' => $this->getLayout()->createBlock(
                        \Branch8\Spin2Win\Block\Adminhtml\Spin\Edit\Tab\Consolation::class
                    )->toHtml()
                ]
            );
            $this->addTab(
                'layout',
                [
                    'label' => __('Layout'),
                    'content' => $this->getLayout()->createBlock(
                        \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Layout::class
                    )->toHtml()
                ]
            );
            // $this->naddTab(
            //     'trigger',
            //     [
            //         'label' => __('Trigger'),
            //         'content' => $this->getLayout()->createBlock(
            //             \Webkul\SpinToWin\Block\Adminhtml\Spin\Edit\Tab\Trigger::class
            //         )->toHtml()
            //     ]
            // );
            // $this->addTab(
            //     'report',
            //     [
            //         'label' => __('Report'),
            //         'url' => $this->getUrl('spintowin/report/index', ['spin_id' => $spininfoModel->getId()]),
            //         'class' => 'ajax'
            //     ]
            // );
        }

        return $this;
    }
}