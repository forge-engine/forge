<?php
declare(strict_types=1);

namespace Forge\Core\Http;

final readonly class Request
{
    public function __construct(
        public array $queryParams,
        public array $postData,
        public array $serverParams,
        public string $requestMethod
    ) {}

    public static function createFromGlobals(): self
    {
        $method = $_SERVER["REQUEST_METHOD"];
        if ($method === "POST") {
            if (isset($_POST["_method"])) {
                $spoofedMethod = strtoupper($_POST["_method"]);
                if (in_array($spoofedMethod, ["PUT", "PATCH", "DELETE"])) {
                    $method = $spoofedMethod;
                }
            }
        }

        return new self($_GET, $_POST, $_SERVER, $method);
    }

    public function getMethod(): string
    {
        return $this->requestMethod;
    }
}
