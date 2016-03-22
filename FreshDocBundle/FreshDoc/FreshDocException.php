<?php

namespace FreshDocBundle\FreshDoc;

/**
 * Class FreshDocException
 * @package FreshDocBundle\FreshDoc
 */
class FreshDocException extends \Exception
{
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}