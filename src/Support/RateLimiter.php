<?php

declare(strict_types=1);

namespace Webuzo\Support;

class RateLimiter
{
    private array $requests = [];
    private int $maxRequests;
    private int $timeWindow; // in seconds

    public function __construct(int $maxRequests = 60, int $timeWindow = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->timeWindow = $timeWindow;
    }

    public function attempt(string $key = 'default'): bool
    {
        $now = microtime(true);
        $windowStart = $now - $this->timeWindow;

        // Clean old requests
        if (!isset($this->requests[$key])) {
            $this->requests[$key] = [];
        }

        $this->requests[$key] = array_filter($this->requests[$key], function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Check if limit exceeded
        if (count($this->requests[$key]) >= $this->maxRequests) {
            return false;
        }

        // Add current request
        $this->requests[$key][] = $now;

        return true;
    }

    public function getAvailableIn(string $key = 'default'): int
    {
        if (!isset($this->requests[$key]) || empty($this->requests[$key])) {
            return 0;
        }

        $oldest = min($this->requests[$key]);
        $windowStart = microtime(true) - $this->timeWindow;

        if ($oldest < $windowStart) {
            return 0;
        }

        return (int) ceil($oldest - $windowStart);
    }

    public function getRemainingRequests(string $key = 'default'): int
    {
        $this->attempt($key); // Clean old requests
        return max(0, $this->maxRequests - count($this->requests[$key] ?? []));
    }

    public function reset(string $key = 'default'): void
    {
        $this->requests[$key] = [];
    }

    public function setMaxRequests(int $max): void
    {
        $this->maxRequests = max(1, $max);
    }

    public function setTimeWindow(int $seconds): void
    {
        $this->timeWindow = max(1, $seconds);
    }
}
