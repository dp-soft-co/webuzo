<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webuzo\Clients\EnduserClient;
use Webuzo\Exceptions\ValidationException;
use Webuzo\Support\ApiResponse;

class WebuzoEnduserClientTest extends TestCase
{
    private array $config = [
        'host' => 'https://test.example.com',
        'scheme' => 'https',
        'admin_port' => 2005,
        'enduser_port' => 2003,
        'response' => 'json',
        'timeout' => 30,
        'connect_timeout' => 10,
        'ssl_verify' => false,
        'logging' => false,
        'max_retries' => 3,
        'retry_delay' => 1000,
        'auth' => [
            'method' => 'api_key',
            'api_user' => 'root',
            'api_key' => 'test_key',
        ],
    ];

    private EnduserClient $client;

    protected function setUp(): void
    {
        $this->client = new EnduserClient($this->config);
    }

    public function testCreateDomainValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->createDomain([]);
    }

    public function testCreateParkedDomain(): void
    {
        $params = [
            'add' => 1,
            'domain_type' => 'parked',
            'domain' => 'parked.com',
            'wildcard' => 0,
            'issue_lecert' => 1,
        ];

        try {
            $this->client->createDomain($params);
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testCreateSubdomainValidation(): void
    {
        $params = [
            'add' => 1,
            'domain_type' => 'subdomain',
            'domain' => 'test.com',
            'wildcard' => 0,
            'issue_lecert' => 1,
            // Missing required: domainpath, subdomain
        ];

        $this->expectException(ValidationException::class);
        $this->client->createDomain($params);
    }

    public function testCreateSubdomainWithRequiredParams(): void
    {
        $params = [
            'add' => 1,
            'domain_type' => 'subdomain',
            'domain' => 'test.com',
            'wildcard' => 0,
            'issue_lecert' => 1,
            'domainpath' => 'public_html/blog',
            'subdomain' => 'blog',
        ];

        try {
            $this->client->createDomain($params);
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testCreateAddonDomainValidation(): void
    {
        $params = [
            'add' => 1,
            'domain_type' => 'addon',
            'domain' => 'addon.com',
            'wildcard' => 0,
            'issue_lecert' => 1,
            // Missing required: domainpath
        ];

        $this->expectException(ValidationException::class);
        $this->client->createDomain($params);
    }

    public function testAddEmailAccountValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->addEmailAccount([]);
    }

    public function testAddEmailAccountWithRequiredParams(): void
    {
        $params = [
            'add' => 1,
            'login' => 'test',
            'newpass' => 'password123',
            'conf' => 'password123',
            'domain' => 'test.com',
        ];

        try {
            $this->client->addEmailAccount($params);
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testLoginAs(): void
    {
        $client = $this->client->as('testuser');
        $this->assertInstanceOf(EnduserClient::class, $client);
    }
}
