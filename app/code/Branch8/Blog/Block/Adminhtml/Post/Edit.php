<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Adminhtml\Post;

use Magento\Backend\Block\Widget\Context;
use Magento\Backend\Block\Widget\Form\Container;
use Magento\Framework\Registry;
use Branch8\Blog\Model\Post;

/**
 * Class Edit
 * @package Branch8\Blog\Block\Adminhtml\Post
 */
class Edit extends Container
{
    /**
     * Core registry
     *
     * @var Registry
     */
    public $coreRegistry;

    /**
     * constructor
     *
     * @param Registry $coreRegistry
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Registry $coreRegistry,
        Context $context,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;

        parent::__construct($context, $data);
    }

    /**
     * Initialize Post edit block
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_blockGroup = 'Branch8_Blog';
        $this->_controller = 'adminhtml_post';

        parent::_construct();

        if (!$this->getRequest()->getParam('history')) {
            $post = $this->coreRegistry->registry('branch8_blog_post');

            $this->buttonList->remove('save');
            $this->buttonList->add(
                'save',
                [
                    'label' => __('Save'),
                    'class' => 'save primary',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => [
                                'event' => 'save',
                                'target' => '#edit_form'
                            ]
                        ]
                    ],
                    'class_name' => \Magento\Ui\Component\Control\Container::SPLIT_BUTTON,
                    'options' => $this->getOptions($post),
                ],
                -100
            );

            $this->buttonList->add(
                'save-and-continue',
                [
                    'label' => __('Save and Continue Edit'),
                    'class' => 'save',
                    'data_attribute' => [
                        'mage-init' => [
                            'button' => [
                                'event' => 'saveAndContinueEdit',
                                'target' => '#edit_form'
                            ]
                        ]
                    ]
                ],
                -100
            );
            if ($post->getId() && !$this->_request->getParam('duplicate')) {
                $this->buttonList->add(
                    'duplicate',
                    [
                        'label' => __('Duplicate'),
                        'class' => 'duplicate',
                        'onclick' => sprintf("location.href = '%s';", $this->getDuplicateUrl()),
                    ],
                    -101
                );
            } else {
                $this->buttonList->remove('delete');
            }
        }
    }

    /**
     * Retrieve options
     *
     * @param Post $post
     *
     * @return array
     */
    protected function getOptions($post)
    {
        if ($post->getId() && !$this->_request->getParam('duplicate')) {
            $options[] = [
                'id_hard' => 'save_and_draft',
                'label' => __('Save as Draft'),
                'data_attribute' => [
                    'mage-init' => [
                        'button' => [
                            'event' => 'save',
                            'target' => '#edit_form',
                            'eventData' => [
                                'action' => ['args' => ['action' => 'draft']]
                            ],
                        ]
                    ]
                ]
            ];
        }
        $options[] = [
            'id_hard' => 'save_and_history',
            'label' => __(' Save & add History'),
            'data_attribute' => [
                'mage-init' => [
                    'button' => [
                        'event' => 'save',
                        'target' => '#edit_form',
                        'eventData' => [
                            'action' => ['args' => ['action' => 'add']]
                        ],
                    ]
                ]
            ]
        ];

        return $options;
    }

    /**
     * Retrieve text for header element depending on loaded Post
     *
     * @return string
     */
    public function getHeaderText()
    {
        /** @var Post $post */
        $post = $this->coreRegistry->registry('branch8_blog_post');

        if ($this->getRequest()->getParam('history')) {
            return __("Edit History Post '%1'", $this->escapeHtml($post->getName()));
        }

        if ($post->getId() && $post->getDuplicate()) {
            return __("Edit Post '%1'", $this->escapeHtml($post->getName()));
        }

        return __('New Post');
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        /** @var Post $post */
        $post = $this->coreRegistry->registry('branch8_blog_post');
        if ($post->getId()) {
            if ($post->getDuplicate()) {
                $ar = [];
            } else {
                $ar = ['id' => $post->getId()];
            }
            if ($this->getRequest()->getParam('history')) {
                $ar['post_id'] = $this->getRequest()->getParam('post_id');
            }

            return $this->getUrl('*/*/save', $ar);
        }

        return parent::getFormActionUrl();
    }

    /**
     * @return string
     */
    protected function getDuplicateUrl()
    {
        $post = $this->coreRegistry->registry('branch8_blog_post');

        return $this->getUrl('*/*/duplicate', ['id' => $post->getId(), 'duplicate' => true]);
    }

    /**
     * @return string
     */
    protected function getSaveDraftUrl()
    {
        return $this->getUrl('*/*/save', ['action' => 'draft']);
    }

    /**
     * @return string
     */
    protected function getSaveAddHistoryUrl()
    {
        return $this->getUrl('*/*/save', ['action' => 'add']);
    }
}
