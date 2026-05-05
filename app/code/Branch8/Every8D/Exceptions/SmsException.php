<?php

namespace Branch8\Every8D\Exceptions;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Exception\AbstractAggregateException;
use Magento\Framework\Phrase;

class SmsException extends AbstractAggregateException
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
     * @param  Exception $cause
     * @return self
     */
    public static function error(Exception $cause ): SmsException
    {
        return new self(
            new Phrase('SMS Service failed. Please check the logs for more information.'. $cause->getMessage())
        );
    }
}
