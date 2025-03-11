<?php
declare(strict_types=1);

namespace Forge\Core\Http;

final readonly class Request
{
    public function __construct(
        public array $queryParams,
        public array $postData,
        public array $serverParams
    )
    {
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }
}