<?php

namespace Forge\Http;

class Request
{
    private string $method;
    private string $uri;
    private ParameterBag $query;
    private ParameterBag $data;
    private array $headers = [];
    private array $cookies = [];
    private ?string $csrfToken = null;

    private array $attributes = [];

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function setAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Check if the request contains a file.
     *
     * @param string $key The name of the file input field.
     * @return bool True if the file exists, false otherwise.
     */
    public function hasFile(string $key): bool
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get the uploaded file details.
     *
     * @param string $key The name of the file input field.
     * @return array|null The file details or null if the file does not exist.
     */
    public function file(string $key): ?array
    {
        if ($this->hasFile($key)) {
            return $_FILES[$key];
        }
        return null;
    }

    public static function createFromGlobals(): self
    {
        $request = new self();
        $request->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $request->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $request->query = new ParameterBag(self::sanitize($_GET));

        $request->data = new ParameterBag(self::sanitize($_POST));

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $request->headers[$header] = $value;
            }
        }

        $request->cookies = self::sanitize($_COOKIE);
        $request->csrfToken = $request->data->get['_csrf'] ?? $request->headers['X-CSRF-TOKEN'] ?? null;

        return $request;
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

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function all(): array
    {
        return array_merge($this->query->all(), $this->data->all());
    }

    /**
     * @param mixed $default
     */
    public function getQuery(?string $key = null, $default = null): mixed
    {
        return $this->query->get($key, $default);
    }

    /**
     * @param mixed $default
     */
    public function getData(?string $key = null, $default = null): mixed
    {
        return $this->data->get($key, $default);
    }

    /**
     * @param mixed $default
     */
    public function getHeader(string $key, $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * @param mixed $default
     */
    public function getCookie(string $key, $default = null): mixed
    {
        return $this->cookies[$key] ?? $default;
    }

    public function getCsrfToken(): ?string
    {
        return $this->csrfToken;
    }

    // Validate CSRF token
    public function validateCsrfToken(string $token): bool
    {
        return $this->csrfToken && hash_equals($this->csrfToken, $token);
    }

    public function getHeaderLine(string $header): ?string
    {
        $header = ucwords(str_replace('-', ' ', strtolower($header)));
        return $this->headers[$header] ?? null;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function fullUrl(): string
    {
        $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'; //
        $uri = $this->getUri();
        $queryString = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];

        return $scheme . '://' . $host . $uri . $queryString;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function query(): ParameterBag
    {
        return $this->query;
    }

    public function request(): ParameterBag
    {
        return $this->data;
    }
}
