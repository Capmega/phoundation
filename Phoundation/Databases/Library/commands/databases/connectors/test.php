<?php

declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlConnectException;
use Phoundation\Databases\Sql\Exception\SqlDatabaseDoesNotExistException;
use Phoundation\Databases\Sql\Exception\SqlInvalidConfigurationException;


/**
 * Command databases/connectors/test
 *
 * General database connector test script
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


// Test each connector
foreach (Connectors::new()->load() as $connector) {
    try {
        switch ($connector->getType()) {
            case 'sql':
                Log::action(tr('Attempting to connect to ":type" type database connector ":connector" with database ":database" on host ":hostname"', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getName(),
                    ':database'  => $connector->getDatabase(),
                    ':hostname'  => $connector->getHostname(),
                ]));

                $connector->connect(true);

                Log::success(tr('Successfully connected to ":type" database connector ":connector"', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getName(),
                ]));
                break;

            default:
                Log::warning(tr('Skipping ":type" type connector ":connector", it is not yet supported', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getDisplayName(),
                ]));
        }

    } catch (SqlAccessDeniedException $e) {
        Log::warning(tr('Failed to connect to ":type" database connector ":connector", access was denied', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
        ]));

    } catch (SqlDatabaseDoesNotExistException $e) {
        Log::warning(tr('Failed to connect to ":type" database connector ":connector", the configured database ":database" does not exist', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
            ':database'  => $connector->getDatabase(),
        ]));

    } catch (SqlInvalidConfigurationException $e) {
        Log::warning(tr('Failed to connect to ":type" database connector ":connector", the connector has an invalid configuration', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
        ]));

    } catch (SqlConnectException $e) {
        Log::warning(tr('Failed to connect to ":type" database connector ":connector" because ":reason"', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
            ':reason'    => $e->getMessage(),
        ]));
    }
}