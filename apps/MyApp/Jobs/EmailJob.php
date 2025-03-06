<?php

namespace MyApp\Jobs;

use Forge\Modules\ForgeQueue\Contracts\JobInterface;

class EmailJob implements JobInterface
{
    public int $delay = 2;
    private string $to;
    private string $subject;
    private $body;

    public function __construct(...$args)
    {
        if (count($args) >= 3) {
            $this->to = $args[0];
            $this->subject = $args[1];
            $this->body = $args[2];
        } else {
            throw new \InvalidArgumentException("EmailJob constructor requires at least 3 arguments: to, subject, body.");
        }
    }

    public function serialize(): array
    {
        return [$this->to, $this->subject, $this->body];
    }

    public function handle(): void
    {
        error_log("sending email");
    }

    public function failed(\Throwable $exception): void
    {
        error_log("failed to send email");
    }

}