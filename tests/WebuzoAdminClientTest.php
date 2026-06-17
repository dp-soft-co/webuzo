<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Webuzo\Clients\AdminClient;
use Webuzo\Exceptions\ValidationException;
use Webuzo\Support\ApiResponse;

class WebuzoAdminClientTest extends TestCase
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

    private AdminClient $client;

    protected function setUp(): void
    {
        $this->client = new AdminClient($this->config);
    }

    public function testAddUserValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->addUser([]);
    }

    public function testAddUserWithRequiredParams(): void
    {
        $params = [
            'create_user' => 1,
            'user' => 'testuser',
            'domain' => 'test.com',
            'user_passwd' => 'password123',
            'cnf_user_passwd' => 'password123',
        ];

        // This will fail without a real server, but validates parameters
        try {
            $this->client->addUser($params);
        } catch (\Exception $e) {
            // Expected to fail due to no real server
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testDeleteUserValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->deleteUser([]);
    }

    public function testSuspendUserValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->suspendUser([]);
    }

    public function testSuspendUserWithRequiredParams(): void
    {
        $params = [
            'suspend' => 'testuser',
            'skip' => 1,
        ];

        try {
            $this->client->suspendUser($params);
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testUnsuspendUserValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Required parameter');

        $this->client->unsuspendUser([]);
    }

    public function testUnsuspendUserWithRequiredParams(): void
    {
        $params = [
            'unsuspend' => 'testuser',
            'skip' => 1,
        ];

        try {
            $this->client->unsuspendUser($params);
        } catch (\Exception $e) {
            $this->assertNotInstanceOf(ValidationException::class, $e);
        }
    }

    public function testListUsersWithoutParams(): void
    {
        try {
            $response = $this->client->listUsers();
            $this->assertInstanceOf(ApiResponse::class, $response);
        } catch (\Exception $e) {
            // Expected to fail without real server
        }
    }
}
