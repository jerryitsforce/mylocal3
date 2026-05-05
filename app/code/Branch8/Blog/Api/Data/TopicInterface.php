<?php
/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

namespace Branch8\Blog\Api\Data;

/**
 * Interface TopicInterface
 * @package Branch8\Blog\Api\Data
 */
interface TopicInterface
{
    /**
     * Constants used as data array keys
     */
    const TOPIC_ID         = 'topic_id';
    const NAME             = 'name';
    const IMAGE            = 'image';
    const BACKGROUND_SIZE  = 'background_size';
    const BACKGROUND_POSITION = 'background_position';
    const DESCRIPTION      = 'description';
    const STORE_IDS        = 'store_ids';
    const URL_KEY          = 'url_key';
    const META_TITLE       = 'meta_title';
    const META_DESCRIPTION = 'meta_description';
    const META_KEYWORDS    = 'meta_keywords';
    const META_ROBOTS      = 'meta_robots';
    const UPDATED_AT       = 'updated_at';
    const CREATED_AT       = 'created_at';
    const IMPORT_SOURCE    = 'import_source';

    const ATTRIBUTES = [
        self::TOPIC_ID,
        self::NAME,
        self::DESCRIPTION,
        self::STORE_IDS,
        self::URL_KEY,
        self::META_TITLE,
        self::META_DESCRIPTION,
        self::META_KEYWORDS,
        self::META_ROBOTS,
        self::IMPORT_SOURCE
    ];

    /**
     * Get Post id
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set Topic id
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id);

    /**
     * Get Topic Name
     *
     * @return string/null
     */
    public function getName();

    /**
     * Set Topic Name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName($name);

    /**
     * Get Topic Image
     *
     * @return string/null
     */
    public function getImage();

    /**
     * Set Topic Image
     *
     * @param string $content
     *
     * @return $this
     */
    public function setImage($content);

    /**
     * Get Topic Background Size
     *
     * @return string/null
     */
    public function getBackgroundSize();

    /**
     * Set Topic Background Size
     *
     * @param string $backgroundSize
     *
     * @return $this
     */
    public function setBackgroundSize($backgroundSize);

    /**
     * Get Topic Background Position
     *
     * @return string/null
     */
    public function getBackgroundPosition();

    /**
     * Set Topic Background Position
     *
     * @param string $backgroundPosition
     *
     * @return $this
     */
    public function setBackgroundPosition($backgroundPosition);

    /**
     * Get Topic Description
     *
     * @return string/null
     */
    public function getDescription();

    /**
     * Set Topic Short Description
     *
     * @param string $content
     *
     * @return $this
     */
    public function setDescription($content);

    /**
     * Get Topic Store Id
     *
     * @return int/null
     */
    public function getStoreIds();

    /**
     * Set Topic Store Id
     *
     * @param int $storeId
     *
     * @return $this
     */
    public function setStoreIds($storeId);

    /**
     * Get Topic Url Key
     *
     * @return string/null
     */
    public function getUrlKey();

    /**
     * Set Topic Url Key
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrlKey($url);

    /**
     * Get Topic Meta Title
     *
     * @return string/null
     */
    public function getMetaTitle();

    /**
     * Set Topic Meta Title
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaTitle($meta);

    /**
     * Get Topic Meta Description
     *
     * @return string/null
     */
    public function getMetaDescription();

    /**
     * Set Topic Meta Description
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaDescription($meta);

    /**
     * Get Topic Meta Keywords
     *
     * @return string/null
     */
    public function getMetaKeywords();

    /**
     * Set Topic Meta Keywords
     *
     * @param string $meta
     *
     * @return $this
     */
    public function setMetaKeywords($meta);

    /**
     * Get Topic Meta Robots
     *
     * @return string/null
     */
    public function getMetaRobots();

    /**
     * Set Topic Meta Robots
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
     * Get Topic updated date
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set Topic updated date
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
}
