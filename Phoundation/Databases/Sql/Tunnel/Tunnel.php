<?php

declare(strict_types=1);

namespace Phoundation\Databases\Sql\Tunnel;

use Phoundation\Databases\Sql\Exception\SqlException;
use Phoundation\Servers\Server;

/**
 * Tunnel class
 *
 * This class is the main SQL database access class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */
class Tunnel extends Server
{
    /**
     * Test SQL functions over SSH tunnel for the specified server
     *
     * @return static
     */
    public function test(): static
    {
        $this->instance = 'test';
        $port           = 6000;
        $restrictions   = servers_get($restrictions, true);
        if (!$restrictions['database_accounts_id']) {
            throw new SqlException(tr('Cannot test SQL over SSH tunnel, server ":server" has no database account linked', [':server' => $restrictions['domain']]));
        }
        $this->makeConnector($this->instance, [
            'port'       => $port,
            'user'       => $restrictions['db_username'],
            'pass'       => $restrictions['db_password'],
            'ssh_tunnel' => [
                'source_port' => $port,
                'domain'      => $restrictions['domain'],
            ],
        ]);
        $this->get('SELECT TRUE', true, null, $this->instance);

        return $this;
    }
}