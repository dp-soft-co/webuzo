<?php

declare(strict_types=1);

namespace Webuzo\Exceptions;

class ApiException extends WebuzoException
{
    public static function fromResponse(int $status, string $error, array $context = []): self
    {
        return new self(
            "Webuzo API error ({$status}): {$error}",
            $status,
            null,
            $context
        );
    }

    public static function networkError(string $message, array $context = []): self
    {
        return new self(
            "Network error: {$message}",
            0,
            null,
            $context
        );
    }

    public static function authenticationError(string $message = "Authentication failed"): self
    {
        return new self(
            $message,
            401,
            null,
            ['type' => 'authentication']
        );
    }
}
