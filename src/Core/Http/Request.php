<?php
declare(strict_types=1);

namespace Forge\Core\Http;

final readonly class Request
{
    private array $headers;
    private string $uri;
    private Session $session;

    public function __construct(
        public array  $queryParams,
        public array  $postData,
        public array  $serverParams,
        public string $requestMethod,
        public array  $cookies,
        Session       $session,
    )
    {
        $this->headers = $this->parseHeadersFromServerParams($serverParams);
        $this->session = $session;
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $postData = $_POST;
        $cookies = self::sanitize($_COOKIE);

        if ($method === "POST") {
            if (isset($_POST["_method"])) {
                $spoofedMethod = strtoupper($_POST["_method"]);
                if (in_array($spoofedMethod, ["PUT", "PATCH", "DELETE"])) {
                    $method = $spoofedMethod;
                }
            }

            if (isset($_SERVER["CONTENT_TYPE"]) && $_SERVER["CONTENT_TYPE"] === "application/json") {
                $rawBody = file_get_contents("php://input");
                $jsonData = json_decode($rawBody, true);
                if (is_array($jsonData)) {
                    $postData = array_merge($postData, $jsonData);
                }
            }
        }

        $session = new Session();
        return new self($_GET, $postData, $_SERVER, $method, $cookies, $session);
    }

    /**
     * @return array<<missing>,string>
     */
    private static function sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = self::sanitize($value);
            } else {
                $sanitized[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        }
        return $sanitized;
    }

    public function getUri(): string
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
    public function getHeader(string $name, ?string $default = null): ?string
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
                $name = strtolower(str_replace("HTTP_", "", $key));
                $name = str_replace("_", "-", $name);
                $headers[$name] = $value;
            } elseif ($key === "CONTENT_TYPE") {
                $headers["content-type"] = $value;
            } elseif ($key === "CONTENT_LENGTH") {
                $headers["content-length"] = $value;
            }
        }
        return $headers;
    }
}
