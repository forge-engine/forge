<?php
declare(strict_types=1);

namespace Forge\Exceptions;

final class InvalidMiddlewareResponse extends BaseException
{
    public function __construct()
    {
        parent::__construct("Middleware did not return a Response object.");
    }
}