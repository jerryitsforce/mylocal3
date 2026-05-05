<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Block\Author;

use Branch8\Blog\Block\Frontend;

/**
 * Class SignupForm
 * @package Branch8\Blog\Block\Author
 */
class SignupForm extends Frontend
{
    /**
     * @return mixed
     */
    public function getUrlSuffix()
    {
        return $this->helperData->getUrlSuffix();
    }

    /**
     * @return array
     */
    public function getAuthor()
    {
        $author = $this->coreRegistry->registry('mp_author');

        if ($author) {
            return [
                'name' => $author->getName(),
                'status' => $this->authorStatusType->toArray()[$author->getStatus()],
                'url_key' => $author->getUrlKey(),
                'short_description' => $author->getShortDescription(),
                'image' => $author->getImage(),
                'facebook_link' => $author->getFacebookLink(),
                'twitter_link' => $author->getTwitterLink(),
            ];
        }

        return [
            'name' => '',
            'url_key' => '',
            'short_description' => '',
            'image' => '',
            'facebook_link' => '',
            'twitter_link' => '',
        ];
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        $array = explode('/', $this->helperData->getConfigValue('cms/wysiwyg/editor') ?? '');
        if ($array[count($array) - 1] === 'tinymce4Adapter') {
            return 4;
        }

        return 3;
    }

    /**
     * @return int
     */
    public function getMagentoVersion()
    {
        return (int)$this->helperData->versionCompare('2.4.0', '>=') ? '' : '4';
    }
}
