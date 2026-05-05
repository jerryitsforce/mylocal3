<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\MonthlyArchive;

use Magento\Framework\Exception\LocalizedException;
use Branch8\Blog\Helper\Data;
use Branch8\Blog\Model\ResourceModel\Post\Collection;

/**
 * Class Listpost
 * @package Branch8\Blog\Block\MonthlyArchive
 */
class Listpost extends \Branch8\Blog\Block\Listpost
{
    /**
     * @return Collection|null
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCollection()
    {
        return $this->helperData->getPostCollection(Data::TYPE_MONTHLY, $this->getMonthKey());
    }

    /**
     * @return mixed
     */
    protected function getMonthKey()
    {
        return $this->getRequest()->getParam('month_key');
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    protected function getMonthLabel()
    {
        return $this->helperData->getDateFormat($this->getMonthKey() . '-10', true);
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbs->addCrumb($this->getMonthKey(), [
                'label' => __('Monthy Archive'),
                'title' => __('Monthy Archive')
            ]);
        }
    }

    /**
     * @param bool $meta
     *
     * @return array|false|string
     */
    public function getBlogTitle($meta = false)
    {
        $blogTitle = parent::getBlogTitle($meta);

        if ($meta) {
            array_push($blogTitle, $this->getMonthLabel());

            return $blogTitle;
        }

        return $this->getMonthLabel();
    }
}
