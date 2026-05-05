<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Helper;

use Mageplaza\Core\Helper\Media;

/**
 * Class Image
 * @package Branch8\Blog\Helper
 */
class Image extends Media
{
    const TEMPLATE_MEDIA_PATH = 'branch8/blog';
    const TEMPLATE_MEDIA_TYPE_AUTH = 'auth';
    const TEMPLATE_MEDIA_TYPE_POST = 'post';
    const TEMPLATE_MEDIA_TYPE_TOPIC = 'topic';
    const TEMPLATE_MEDIA_TYPE_CAT = 'cat';
}
