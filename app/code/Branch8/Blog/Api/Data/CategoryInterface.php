<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data;

/**
 * Interface CategoryInterface
 * @package Branch8\Blog\Api\Data
 */
interface CategoryInterface
{
    /**
     * Constants used as data array keys
     */
    const CATEGORY_ID      = 'category_id';
    const NAME             = 'name';
    const URL_KEY          = 'url_key';
    const IMAGE            = 'image';
    const BACKGROUND_SIZE  = 'background_size';
    const BACKGROUND_POSITION = 'background_position';
    const DESCRIPTION      = 'description';
    const STORE_IDS        = 'store_ids';
    const ENABLED          = 'enabled';
    const UPDATED_AT       = 'updated_at';
    const CREATED_AT       = 'created_at';
    const META_TITLE       = 'meta_title';
    const META_DESCRIPTION = 'meta_description';
    const META_KEYWORDS    = 'meta_keywords';
    const META_ROBOTS      = 'meta_robots';
    const IMPORT_SOURCE    = 'import_source';
    const PARENT_ID        = 'parent_id';
    const PATH             = 'path';
    const POSITION         = 'position';
    const LEVEL            = 'level';
    const CHILDREN_COUNT   = 'children_count';

    const ATTRIBUTES = [
        self::CATEGORY_ID,
        self::NAME,
        self::IMAGE,
        self::BACKGROUND_SIZE,
        self::BACKGROUND_POSITION,
        self::DESCRIPTION,
        self::STORE_IDS,
        self::ENABLED,
        self::URL_KEY,
        self::META_TITLE,
        self::META_DESCRIPTION,
        self::META_KEYWORDS,
        self::META_ROBOTS,
        self::PARENT_ID,
        self::PATH,
        self::POSITION,
        self::LEVEL,
        self::CHILDREN_COUNT
    ];

    /**
     * @return int|null
     */
    public function getId();

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * @return string/null
     */
    public function getName();

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Get Category Image
     *
     * @return string/null
     */
    public function getImage();

    /**
     * Set Category Image
     *
     * @param string $content
     *
     * @return $this
     */
    public function setImage($content);

    /**
     * Get Category Background Size
     *
     * @return string/null
     */
    public function getBackgroundSize();

    /**
     * Set Category Background Size
     *
     * @param string $backgroundSize
     *
     * @return $this
     */
    public function setBackgroundSize($backgroundSize);

    /**
     * Get Category Background Position
     *
     * @return string/null
     */
    public function getBackgroundPosition();

    /**
     * Set Category Background Position
     *
     * @param string $backgroundPosition
     *
     * @return $this
     */
    public function setBackgroundPosition($backgroundPosition);

    /**
     * Get Category Description
     *
     * @return string/null
     */
    public function getDescription();

    /**
     * Set Category Short Description
     *
     * @param string $content
     *
     * @return $this
     */
    public function setDescription($content);

    /**
     * Get Category Store Id
     *
     * @return int/null
     */
    public function getStoreIds();

    /**
     * Set Category Store Id
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreIds($storeId);

    /**
     * Get Category Enabled
     *
     * @return int/null
     */
    public function getEnabled();

    /**
     * Set Category Enabled
     *
     * @param int $enabled
     *
     * @return $this
     */
    public function setEnabled($enabled);

    /**
     * Get Category Url Key
     *
     * @return string/null
     */
    public function getUrlKey();

    /**
     * Set Category Url Key
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrlKey($url);

    /**
     * Get Category Meta Title
     *
     * @return string/null
     */
    public function getMetaTitle();

    /**
     * Set Category Meta Title
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaTitle($meta);

    /**
     * Get Category Meta Description
     *
     * @return string/null
     */
    public function getMetaDescription();

    /**
     * Set Category Meta Description
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaDescription($meta);

    /**
     * Get Category Meta Keywords
     *
     * @return string/null
     */
    public function getMetaKeywords();

    /**
     * Set Category Meta Keywords
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaKeywords($meta);

    /**
     * Get Category Meta Robots
     *
     * @return string/null
     */
    public function getMetaRobots();

    /**
     * Set Category Meta Robots
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaRobots($meta);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get Category updated date
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Category updated date
     *
     * @param string $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * @return string|null
     */
    public function getImportSource();

    /**
     * @param string $importSource
     *
     * @return $this
     */
    public function setImportSource($importSource);

    /**
     * @return int|null
     */
    public function getParentId();

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setParentId($id);

    /**
     * @return string|null
     */
    public function getPath();

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path);

    /**
     * @return int|null
     */
    public function getPosition();

    /**
     * @param int $position
     *
     * @return $this
     */
    public function setPosition($position);

    /**
     * @return int|null
     */
    public function getLevel();

    /**
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level);

    /**
     * @return int|null
     */
    public function getChildrenCount();

    /**
     * @param int $count
     *
     * @return $this
     */
    public function setChildrenCount($count);
}
