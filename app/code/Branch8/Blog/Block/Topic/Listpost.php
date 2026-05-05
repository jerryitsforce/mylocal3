<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Topic;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\ResourceModel\Post\Collection;
use Branch8\Blog\Model\TopicFactory;

/**
 * Class Listpost
 * @package Branch8\Blog\Block\Topic
 */
class Listpost extends \Branch8\Blog\Block\Listpost
{
    /**
     * @var TopicFactory
     */
    protected $_topic;

    /**
     * Override this function to apply collection for each type
     *
     * @return Collection
     * @throws NoSuchEntityException
     */
    protected function getCollection()
    {
        if ($topic = $this->getBlogObject()) {
            return $this->helperData->getPostCollection(Data::TYPE_TOPIC, $topic->getId());
        }

        return null;
    }

    /**
     * @return mixed
     */
    protected function getBlogObject()
    {
        if (!$this->_topic) {
            $id = $this->getRequest()->getParam('id');

            if ($id) {
                $topic = $this->helperData->getObjectByParam($id, null, Data::TYPE_TOPIC);
                if ($topic && $topic->getId()) {
                    $this->_topic = $topic;
                }
            }
        }

        return $this->_topic;
    }

    /**
     * @inheritdoc
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $topic     = $this->getBlogObject();
            $topicName = preg_replace('/[^A-Za-z0-9\-]/', ' ', $topic->getName());
            if ($topic) {
                $breadcrumbs->addCrumb($topic->getUrlKey(), [
                    'label' => __($topicName),
                    'title' => __($topicName)
                ]);
            }
        }
    }

    /**
     * @param bool $meta
     *
     * @return array|Phrase|string
     */
    public function getBlogTitle($meta = false)
    {
        $blogTitle = parent::getBlogTitle($meta);
        $topic     = $this->getBlogObject();
        if (!$topic) {
            return $blogTitle;
        }

        if ($meta) {
            if ($topic->getMetaTitle()) {
                array_push($blogTitle, $topic->getMetaTitle());
            } else {
                array_push($blogTitle, ucfirst($topic->getName()));
            }

            return $blogTitle;
        }

        return ucfirst($topic->getName());
    }

    /**
     * @return mixed
     */
    public function getTopic()
    {
        return $this->getBlogObject();
    }
}
