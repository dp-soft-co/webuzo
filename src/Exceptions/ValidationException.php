<?php

declare(strict_types=1);

namespace Webuzo\Exceptions;

class ValidationException extends WebuzoException
{
    public static function missingRequired(string $parameter, string $method): self
    {
        return new self(
            "Required parameter '{$parameter}' is missing for method '{$method}'",
            422,
            null,
            ['parameter' => $parameter, 'method' => $method]
        );
    }

    public static function invalidValue(string $parameter, string $method, string $reason): self
    {
        return new self(
            "Invalid value for parameter '{$parameter}' in method '{$method}': {$reason}",
            422,
            null,
            ['parameter' => $parameter, 'method' => $method, 'reason' => $reason]
        );
    }
}
