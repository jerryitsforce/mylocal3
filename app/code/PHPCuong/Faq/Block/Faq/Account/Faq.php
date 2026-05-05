<?php

/**
 *
 * @Author              Ngo Quang Cuong <bestearnmoney87@gmail.com>
 * @Date                2016-12-23 18:16:21
 * @Last modified by:   nquangcuong
 * @Last Modified time: 2017-11-28 17:21:05
 */

namespace PHPCuong\Faq\Block\Faq\Account;

use Magento\Framework\View\Element\Template\Context;
use PHPCuong\Faq\Helper\Question as QuestionHelper;
use PHPCuong\Faq\Helper\Category as CategoryHelper;
use PHPCuong\Faq\Model\ResourceModel\Faq as FaqResourceModel;
use Magento\Framework\App\Filesystem\DirectoryList;
use PHPCuong\Faq\Helper\Config as ConfigHelper;
use Magento\Cms\Model\Template\FilterProvider;

class Faq extends \PHPCuong\Faq\Block\Faq\Faq
{
    /**
     * Build the base FAQ query by category.
     *
     * @param int $categoryId
     * @return \Zend_Db_Select
     */
    protected function buildFaqQueryByCategory($categoryId)
    {
        $select = $this->_faqResourceModel->getConnection()->select()
            ->from(['faq' => $this->_faqResourceModel->getMainTable()])
            ->joinLeft(
                ['faq_store' => $this->_faqResourceModel->getTable('phpcuong_faq_store')],
                'faq.faq_id = faq_store.faq_id',
                ['store_id']
            )
            ->joinLeft(
                ['faq_category' => $this->_faqResourceModel->getTable('phpcuong_faq_category_id')],
                'faq.faq_id = faq_category.faq_id',
                []
            )
            ->where('faq_store.store_id = ?', $this->_storeManager->getStore()->getId())
            ->where('faq.is_active = ?', '1')
            ->where('faq_category.category_id = ?', $categoryId);

        return $select;
    }

    /**
     * Get FAQs for a specific category with pagination.
     *
     * @param int $categoryId
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFrequentlyAskedQuestionsByCategory($categoryId, $page = 1, $pageSize = 5)
    {
        $offset = ($page - 1) * $pageSize;

        // Get the base query and apply pagination
        $select = $this->buildFaqQueryByCategory($categoryId)
            ->order('faq.sort_order ASC')
            ->limit($pageSize, $offset);

        return $this->_faqResourceModel->getConnection()->fetchAll($select);
    }

    /**
     * Get the total number of FAQs by category.
     *
     * @param int $categoryId
     * @return int
     */
    public function getTotalFaqsByCategory($categoryId)
    {
        // Get the base query and modify it for counting
        $select = $this->buildFaqQueryByCategory($categoryId)
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['COUNT(*)']);

        return (int) $this->_faqResourceModel->getConnection()->fetchOne($select);
    }
    public function getParams()
    {
        return $this->getRequest()->getParams(); ;
    }

}
