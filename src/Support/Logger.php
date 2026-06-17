<?php

declare(strict_types=1);

namespace Webuzo\Support;

use Illuminate\Support\Facades\Log;

trait Logger
{
    protected bool $loggingEnabled = false;

    protected function enableLogging(bool $enabled = true): void
    {
        $this->loggingEnabled = $enabled;
    }

    protected function logRequest(string $act, array $params, string $url): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        Log::debug('Webuzo API Request', [
            'act' => $act,
            'url' => $url,
            'params' => $this->sanitizeParams($params),
            'login_as' => $this->loginAs,
        ]);
    }

    protected function logResponse(string $act, int $status, string $raw, ?array $data): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        Log::debug('Webuzo API Response', [
            'act' => $act,
            'status' => $status,
            'success' => $status >= 200 && $status < 300,
            'data' => $data,
        ]);
    }

    protected function logError(string $act, string $error, array $context = []): void
    {
        if (!$this->loggingEnabled) {
            return;
        }

        Log::error('Webuzo API Error', [
            'act' => $act,
            'error' => $error,
            'context' => $context,
        ]);
    }

    protected function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['user_passwd', 'cnf_user_passwd', 'newpass', 'conf', 'dbpassword', 'password', 'apikey'];

        foreach ($sensitiveKeys as $key) {
            if (isset($params[$key])) {
                $params[$key] = '******';
            }
        }

        return $params;
    }
}
