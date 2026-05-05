<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data;

/**
 * Interface BlogConfigInterface
 * @package Branch8\Blog\Api\Data
 */
interface BlogConfigInterface
{
    const GENERAL = 'general';
    const SIDEBAR = 'sidebar';
    const SEO     = 'seo';

    /**
     * @return \Branch8\Blog\Api\Data\Config\GeneralInterface
     */
    public function getGeneral();

    /**
     * @param \Branch8\Blog\Api\Data\Config\GeneralInterface $value
     *
     * @return $this
     */
    public function setGeneral($value);

    /**
     * @return \Branch8\Blog\Api\Data\Config\SidebarInterface
     */
    public function getSidebar();

    /**
     * @param \Branch8\Blog\Api\Data\Config\SidebarInterface $value
     *
     * @return $this
     */
    public function setSidebar($value);

    /**
     * @return \Branch8\Blog\Api\Data\Config\SeoInterface
     */
    public function getSeo();

    /**
     * @param \Branch8\Blog\Api\Data\Config\SeoInterface $value
     *
     * @return $this
     */
    public function setSeo($value);
}
