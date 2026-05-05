<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data\Config;

/**
 * Interface SidebarInterface
 * @package Branch8\Blog\Api\Data\Config
 */
interface SidebarInterface
{
    const NUMBER_RECENT    = 'number_recent';
    const NUMBER_MOST_VIEW = 'number_most_view';

    /**
     * @return string/null
     */
    public function getNumberRecent();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setNumberRecent($value);

    /**
     * @return string/null
     */
    public function getNumberMostView();

    /**
     * @param string $value
     *
     * @return $this
     */
    public function setNumberMostView($value);
}
