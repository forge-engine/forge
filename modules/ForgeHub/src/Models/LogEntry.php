<?php

declare(strict_types=1);

namespace App\Modules\ForgeHub\Models;

use DateTimeImmutable;

final class LogEntry
{
    public readonly DateTimeImmutable $date;
    public readonly string $level;
    public readonly string $message;
    public readonly array $context;

    public function __construct(DateTimeImmutable $date, string $level, string $message, array $context)
    {
        $this->date = $date;
        $this->level = $level;
        $this->message = $message;
        $this->context = $context;
    }

    public static function fromString(string $logLine): self
    {
        $pattern = '/\[(?<date>.*?)\] (?<level>[^:]+): (?<message>.*?) (?<context>\{.*?\})/';
        preg_match($pattern, trim($logLine), $matches);

        $dateString = $matches['date'] ?? null;
        $level = trim($matches['level'] ?? 'UNKNOWN');
        $message = trim($matches['message'] ?? $logLine);
        $contextString = $matches['context'] ?? '{"context":""}';

        try {
            $dateTime = $dateString ? new DateTimeImmutable($dateString) : new DateTimeImmutable('now');
            $context = json_decode($contextString, true) ?? [];
        } catch (\Exception $e) {
            $dateTime = new DateTimeImmutable('now');
            $context = [];
            $message = "Error parsing log line: " . $logLine;
            $level = 'ERROR';
        }

        return new self(
            $dateTime,
            $level,
            $message,
            $context
        );
    }
}
