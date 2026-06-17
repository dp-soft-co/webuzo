<?php

declare(strict_types=1);

namespace Webuzo\Support;

use Webuzo\Exceptions\ValidationException;

trait ValidatesParameters
{
    protected function validateRequired(array $params, array $required, string $method): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === '' || $params[$param] === null) {
                throw ValidationException::missingRequired($param, $method);
            }
        }
    }

    protected function validateRequiredIf(array $params, string $param, callable $condition, string $method): void
    {
        if ($condition($params) && (!isset($params[$param]) || $params[$param] === '' || $params[$param] === null)) {
            throw ValidationException::missingRequired($param, $method);
        }
    }

    protected function validateOneOf(array $params, string $param, array $allowed, string $method): void
    {
        if (isset($params[$param]) && !in_array($params[$param], $allowed, true)) {
            throw ValidationException::invalidValue(
                $param,
                $method,
                "Must be one of: " . implode(', ', $allowed)
            );
        }
    }

    protected function validateEmail(array $params, string $param, string $method): void
    {
        if (isset($params[$param]) && !filter_var($params[$param], FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidValue($param, $method, "Must be a valid email address");
        }
    }

    protected function validateNumeric(array $params, string $param, string $method): void
    {
        if (isset($params[$param]) && !is_numeric($params[$param])) {
            throw ValidationException::invalidValue($param, $method, "Must be numeric");
        }
    }

    protected function validateInteger(array $params, string $param, string $method): void
    {
        if (isset($params[$param]) && !is_int($params[$param]) && !ctype_digit((string) $params[$param])) {
            throw ValidationException::invalidValue($param, $method, "Must be an integer");
        }
    }
}
