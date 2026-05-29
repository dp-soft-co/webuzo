<?php

declare(strict_types=1);

namespace Webuzo\Clients;

use Webuzo\Clients\Concerns\HasAdminEndpoints;

class AdminClient extends BaseClient
{
    use HasAdminEndpoints;

    protected function portKey(): string
    {
        return 'admin_port';
    }
}
