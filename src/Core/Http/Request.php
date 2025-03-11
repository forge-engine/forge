<?php
declare(strict_types=1);

namespace Forge\Core\Http;

final readonly class Request
{
    private array $headers;

    public function __construct(
        public array $queryParams,
        public array $postData,
        public array $serverParams,
        public string $requestMethod
    ) {
        $this->headers = $this->parseHeadersFromServerParams($serverParams);
    }

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

    /**
     * Checks if a request header exists.
     * Header name is case-insensitive.
     */
    public function hasHeader(string $name): bool
    {
        $normalizedName = strtolower($name); // Normalize to lowercase for case-insensitive check
        return isset($this->headers[$normalizedName]);
    }

    /**
     * Gets a request header value.
     * Header name is case-insensitive.
     *
     * @param string $name Header name
     * @param string|null $default Default value to return if header is not found
     * @return string|null Header value or $default if not found
     */
    public function getHeader(string $name, string $default = null): ?string
    {
        $normalizedName = strtolower($name); // Normalize to lowercase
        return $this->headers[$normalizedName] ?? $default;
    }

    /**
     * Gets all request headers as an associative array.
     * Header names are normalized to lowercase.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): string
    {
        return $this->requestMethod;
    }

    /**
     * Parses headers from the $_SERVER array.
     * Normalizes header names to lowercase and removes 'HTTP_' prefix.
     *
     * @param array $serverParams The $_SERVER array
     * @return array<string, string>
     */
    private function parseHeadersFromServerParams(array $serverParams): array
    {
        $headers = [];
        foreach ($serverParams as $key => $value) {
            if (str_starts_with($key, "HTTP_")) {
                $name = strtolower(str_replace("HTTP_", "", $key)); // Normalize header name
                $name = str_replace("_", "-", $name); // Replace underscores with hyphens (standard HTTP header format)
                $headers[$name] = $value;
            } elseif ($key === "CONTENT_TYPE") {
                // Handle Content-Type and Content-Length separately
                $headers["content-type"] = $value;
            } elseif ($key === "CONTENT_LENGTH") {
                $headers["content-length"] = $value;
            }
        }
        return $headers;
    }
}
