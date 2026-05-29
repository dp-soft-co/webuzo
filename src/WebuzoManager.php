<?php

declare(strict_types=1);

namespace Webuzo;

use Webuzo\Clients\AdminClient;
use Webuzo\Clients\EnduserClient;

class WebuzoManager
{
    public function __construct(private array $config)
    {
    }

    public function admin(): AdminClient
    {
        return new AdminClient($this->config);
    }

    public function enduser(?string $loginAs = null): EnduserClient
    {
        return new EnduserClient($this->config, $loginAs);
    }

    public function enduserAs(string $username): EnduserClient
    {
        return $this->enduser($username);
    }

    public function config(): array
    {
        return $this->config;
    }
}
