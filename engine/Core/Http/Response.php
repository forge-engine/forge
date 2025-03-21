<?php
declare(strict_types=1);

namespace Forge\Core\Http;

final class Response
{
    public function __construct(
        private string $content,
        private int    $status = 200,
        private array  $headers = []
    )
    {

    }

    public function send(): void
    {
        http_response_code(($this->status));
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        echo $this->content;

        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }

    public function setStatusCode(int $code): self
    {
        $this->status = $code;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
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
}