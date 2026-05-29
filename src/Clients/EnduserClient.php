<?php

declare(strict_types=1);

namespace Webuzo\Clients;

use Webuzo\Clients\Concerns\HasEnduserEndpoints;

class EnduserClient extends BaseClient
{
    use HasEnduserEndpoints;

    protected function portKey(): string
    {
        return 'enduser_port';
    }

    public function as(string $username): static
    {
        $clone = clone $this;
        $clone->loginAs = $username;

        return $clone;
    }
}
