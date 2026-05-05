<?php

namespace Branch8\Catalog\Api;

interface WishlistResponseInterface
{
    const ERROR = 'error';

    const MESSAGE = 'message';

    /**
     * @return bool
     */
    public function getError();

    /**
     * @param bool $error
     * @return this
     */
    public function setError(bool $error);

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $message
     * @return this
     */
    public function setMessage(string $message);

}