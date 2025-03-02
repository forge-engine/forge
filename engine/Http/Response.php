<?php

namespace Forge\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $key): ?string
    {
        $normalizedKey = strtolower($key);

        foreach ($this->headers as $headerKey => $headerValue) {
            if (strtolower($headerKey) === $normalizedKey) {
                return $headerValue;
            }
        }
        return null;
    }

    public function setCookie(
        string $name,
        string $value,
        int    $expires = 0,
        string $path = '/',
        string $domain = '',
        bool   $secure = true,
        bool   $httpOnly = true,
        string $sameSite = 'Strict'
    ): self
    {
        $cookieHeader = sprintf(
            '%s=%s; Path=%s; Expires=%s; Max-Age=%s; Domain=%s; %s%s; SameSite=%s',
            rawurlencode($name),
            rawurlencode($value),
            $path,
            gmdate('D, d M Y H:i:s T', $expires),
            $expires > 0 ? $expires - time() : 0,
            $domain,
            $secure ? 'Secure; ' : '',
            $httpOnly ? 'HttpOnly' : '',
            $sameSite
        );

        $this->headers['Set-Cookie'][] = $cookieHeader;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @param array<int,mixed> $data
     */
    public function json(array $data): self
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->content = json_encode(['data' => $data]);

        return $this;
    }

    public function text(string $text): self
    {
        $this->setHeader('Content-Type', 'text/plain');
        $this->content = $text;
        return $this;
    }

    public function html(string $html): self
    {
        $this->setHeader('Content-Type', 'text/html');
        $this->content = $html;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo $this->content;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }
}
