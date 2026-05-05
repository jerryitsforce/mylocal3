<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Api;

use Branch8\BlackListKeyWords\Api\Data\KeywordInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface KeywordRepositoryInterface
{
    /**
     * @param KeywordInterface $keyword
     * @return mixed
     */
    public function save(KeywordInterface $keyword);

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param KeywordInterface $keyword
     * @return mixed
     */
    public function delete(KeywordInterface $keyword);

    /**
     * @param $id
     * @return mixed
     */
    public function deleteById($id);
}
