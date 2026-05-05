<?php
declare(strict_types=1);

namespace Branch8\Blog\Plugin;

use Branch8\Blog\CustomerData\BlogPostData;
use Branch8\Customer\CustomerData\Common as CustomerDataCommon;

class CustomerDataCommonPlugin
{
    /**
     * @var BlogPostData
     */
    protected $blogPostData;

    /**
     * @param BlogPostData $blogPostData
     */
    public function __construct(BlogPostData $blogPostData)
    {
        $this->blogPostData = $blogPostData;
    }

    /**
     * @param CustomerDataCommon $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(CustomerDataCommon $subject, array $result)
    {
        $result['blog_post_data'] = $this->blogPostData->getSectionData();
        return $result;
    }
}
