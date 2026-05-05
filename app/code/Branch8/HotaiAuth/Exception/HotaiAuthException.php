<?php

namespace Branch8\HotaiAuth\Exception;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Phrase;

class HotaiAuthException extends AbstractAggregateException
{
    /**
     *  Initialize Hotai Auth exception.
     * @param  Phrase|null  $phrase
     * @param  Exception|null  $cause
     * @param $code
     */
    public function __construct(Phrase $phrase = null, Exception $cause = null, $code = 0)
    {
        if ($phrase === null) {
            $phrase = new Phrase('One or more input exceptions have occurred.');
        }
        parent::__construct($phrase, $cause, $code);
    }

    /**
     * @param  GuzzleException  $cause
     * @return self
     */
    public static function invalidGetToken(GuzzleException $cause): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai Get Token failed. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00001')
        );
    }

    /**
     * @param  GuzzleException  $cause
     * @return self
     */
    public static function invalidRefreshToken(GuzzleException $cause): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai Refresh Token failed. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00002')
        );
    }

    /**
     * @param  Exception  $cause
     * @return self
     */
    public static function invalidGetUserProfile(Exception $cause ): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai Get User Profile failed. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00003')
        );
    }

    /**
     * @param  Exception $cause
     * @return self
     */
    public static function invalidExternalDecryptToken(Exception $cause ): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai External Decrypt Token failed. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00004')
        );
    }

    /**
     * @param  Exception $cause
     * @return self
     */
    public static function invalidExternalDecryptPlatform(Exception $cause ): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai External Decrypt Token failed. The platform is not supported. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00005')
        );
    }

    /**
     * @param  Exception $cause
     * @return self
     */
    public static function invalidExternalEncryptToken(Exception $cause ): HotaiAuthException
    {
        return new self(
            // new Phrase('Hotai External Encrypt Token failed. Please check the logs for more information.'. $cause->getMessage())
            new Phrase('請重新登入以獲得更好的購物體驗。' . '00004')
        );
    }
}
