<?php

declare(strict_types=1);

namespace Webuzo\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Webuzo\Support\ApiResponse;
use Webuzo\Support\ValidatesParameters;
use Webuzo\Support\Logger;
use Webuzo\Support\Retryable;
use Webuzo\Support\RateLimiter;
use Webuzo\Exceptions\ApiException;

abstract class BaseClient
{
    use ValidatesParameters, Logger, Retryable;

    protected ?RateLimiter $rateLimiter = null;

    public function __construct(
        protected array $config,
        protected ?string $loginAs = null
    ) {
        $this->loggingEnabled = (bool) ($this->config['logging'] ?? false);
        $this->maxRetries = (int) ($this->config['max_retries'] ?? 3);
        $this->retryDelay = (int) ($this->config['retry_delay'] ?? 1000);

        // Initialize rate limiter if enabled
        if (($this->config['rate_limiting'] ?? false) === true) {
            $this->rateLimiter = new RateLimiter(
                (int) ($this->config['rate_limit_max'] ?? 60),
                (int) ($this->config['rate_limit_window'] ?? 60)
            );
        }
    }

    abstract protected function portKey(): string;

    public function call(string $act, array $params = []): ApiResponse
    {
        // Check rate limiting
        if ($this->rateLimiter !== null && !$this->rateLimiter->attempt($act)) {
            $availableIn = $this->rateLimiter->getAvailableIn($act);
            throw new ApiException(
                "Rate limit exceeded for action '{$act}'. Try again in {$availableIn} seconds.",
                429,
                null,
                ['act' => $act, 'available_in' => $availableIn]
            );
        }

        $url = $this->buildUrl($act);
        $payload = $this->buildPayload($params);

        $this->logRequest($act, $params, $url);

        $request = $this->makeRequest();
        [$request, $payload] = $this->applyAuth($request, $payload);

        $response = $request->post($url, $payload);

        $raw = $response->body();
        $data = $this->decode($raw);

        $this->logResponse($act, $response->status(), $raw, $data);

        if (!$response->ok()) {
            $this->logError($act, $raw, ['status' => $response->status()]);
        }

        return new ApiResponse(
            $response->status(),
            $raw,
            $data,
            [
                'act' => $act,
                'url' => $url,
            ]
        );
    }

    public function __call(string $name, array $args): ApiResponse
    {
        $params = $args[0] ?? [];
        if ($params !== [] && !is_array($params)) {
            throw new InvalidArgumentException('Params must be an array.');
        }

        return $this->call($this->normalizeAct($name), $params);
    }

    protected function buildUrl(string $act): string
    {
        $api = $this->config['response'] ?? 'json';

        $query = [
            'api' => $api,
            'act' => $act,
        ];

        if (!empty($this->loginAs)) {
            $query['loginAs'] = $this->loginAs;
        }

        return $this->baseUrl() . '?' . http_build_query($query);
    }

    protected function buildPayload(array $params): array
    {
        return $params;
    }

    protected function makeRequest(): PendingRequest
    {
        $timeout = (int) ($this->config['timeout'] ?? 30);
        $connectTimeout = (int) ($this->config['connect_timeout'] ?? 10);
        $verify = $this->toBool($this->config['ssl_verify'] ?? false);

        return Http::asForm()
            ->timeout($timeout)
            ->connectTimeout($connectTimeout)
            ->withOptions(['verify' => $verify]);
    }

    protected function applyAuth(PendingRequest $request, array $payload): array
    {
        $auth = $this->config['auth'] ?? [];
        $method = $auth['method'] ?? 'api_key';

        if ($method === 'credentials') {
            $username = (string) ($auth['username'] ?? '');
            $password = (string) ($auth['password'] ?? '');
            $request = $request->withBasicAuth($username, $password);
        } else {
            $payload['apiuser'] = (string) ($auth['api_user'] ?? '');
            $payload['apikey'] = (string) ($auth['api_key'] ?? '');
        }

        return [$request, $payload];
    }

    protected function decode(string $raw): mixed
    {
        $format = (string) ($this->config['response'] ?? 'json');

        if ($format === 'serialize') {
            $data = @unserialize($raw);
            if ($data === false && $raw !== 'b:0;') {
                return null;
            }
            return $data;
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    protected function baseUrl(): string
    {
        $host = trim((string) ($this->config['host'] ?? ''));
        if ($host === '') {
            throw new InvalidArgumentException('WEBUZO_HOST is required.');
        }

        if (!preg_match('#^https?://#i', $host)) {
            $scheme = (string) ($this->config['scheme'] ?? 'https');
            $host = $scheme . '://' . $host;
        }

        $host = rtrim($host, '/');
        $port = (int) ($this->config[$this->portKey()] ?? 0);

        $parsed = parse_url($host);
        $hasPort = is_array($parsed) && isset($parsed['port']);

        if ($hasPort || $port === 0) {
            return $host . '/index.php';
        }

        return $host . ':' . $port . '/index.php';
    }

    protected function normalizeAct(string $name): string
    {
        return strtolower(str_replace('_', '', $name));
    }

    protected function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
