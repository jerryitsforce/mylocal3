<?php
declare(strict_types=1);

namespace Branch8\Blog\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\App\Request\Http;

class BlogPostData implements SectionSourceInterface
{
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var Http
     */
    protected $request;

    /**
     * BlogPostData constructor.
     * @param CookieManagerInterface $cookieManager
     * @param Http $request
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        Http $request
    ) {
        $this->cookieManager = $cookieManager;
        $this->request = $request;
    }

    /**
     * @inheritdoc
     */
    public function getSectionData()
    {
        $cookieValue = $this->cookieManager->getCookie('blog_post_data');
        $data = $cookieValue ? json_decode($cookieValue, true) : [];

        return [
            'posts' => $data
        ];
    }
}
