<?php

declare(strict_types=1);

namespace Webuzo;

use Webuzo\Clients\AdminClient;
use Webuzo\Clients\EnduserClient;

class Webuzo
{
    public function __construct(private WebuzoManager $manager)
    {
    }

    public function admin(): AdminClient
    {
        return $this->manager->admin();
    }

    public function enduser(?string $loginAs = null): EnduserClient
    {
        return $this->manager->enduser($loginAs);
    }

    public function enduserAs(string $username): EnduserClient
    {
        return $this->manager->enduserAs($username);
    }
}
