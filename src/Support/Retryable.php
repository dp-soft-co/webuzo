<?php

declare(strict_types=1);

namespace Webuzo\Support;

use Illuminate\Support\Facades\Log;
use Webuzo\Exceptions\ApiException;

trait Retryable
{
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // milliseconds
    protected array $retryableStatuses = [429, 500, 502, 503, 504];

    protected function withRetry(callable $callback, string $act): mixed
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                return $callback();
            } catch (ApiException $e) {
                $lastException = $e;
                $attempt++;

                if ($this->shouldRetry($e, $attempt)) {
                    $delay = $this->retryDelay * $attempt;
                    $this->logRetryAttempt($act, $attempt, $delay, $e->getMessage());
                    usleep($delay * 1000); // Convert to microseconds
                    continue;
                }

                throw $e;
            }
        }

        throw $lastException;
    }

    protected function shouldRetry(ApiException $e, int $attempt): bool
    {
        if ($attempt >= $this->maxRetries) {
            return false;
        }

        $code = $e->getCode();
        return in_array($code, $this->retryableStatuses, true) || $code === 0;
    }

    protected function logRetryAttempt(string $act, int $attempt, int $delay, string $error): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        Log::warning('Webuzo API Retry', [
            'act' => $act,
            'attempt' => $attempt,
            'delay_ms' => $delay,
            'error' => $error,
        ]);
    }

    public function setMaxRetries(int $max): void
    {
        $this->maxRetries = max(0, $max);
    }

    public function setRetryDelay(int $milliseconds): void
    {
        $this->retryDelay = max(0, $milliseconds);
    }

    public function setRetryableStatuses(array $statuses): void
    {
        $this->retryableStatuses = $statuses;
    }
}
