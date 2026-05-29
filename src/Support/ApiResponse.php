<?php

declare(strict_types=1);

namespace Webuzo\Support;

class ApiResponse
{
    public function __construct(
        public readonly int $status,
        public readonly string $raw,
        public readonly mixed $data,
        public readonly array $meta = []
    ) {
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function error(): ?string
    {
        if (!is_array($this->data)) {
            return null;
        }

        $error = $this->data['error'] ?? null;
        if (is_string($error)) {
            return $error;
        }

        if (is_array($error)) {
            return json_encode($error);
        }

        $errorLog = $this->data['error_log'] ?? null;
        if (is_string($errorLog)) {
            return $errorLog;
        }

        return null;
    }
}
