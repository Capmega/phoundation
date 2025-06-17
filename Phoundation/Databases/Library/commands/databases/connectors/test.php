<?php

/**
 * Command databases connectors test
 *
 * General database connector test script
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Scripts
 */


declare(strict_types=1);

use Phoundation\Core\Log\Log;
use Phoundation\Databases\Connectors\Connectors;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Databases\Sql\Exception\SqlConnectException;
use Phoundation\Databases\Sql\Exception\SqlInvalidConfigurationException;
use Phoundation\Databases\Sql\Exception\SqlUnknownDatabaseException;

// Test each connector
foreach (Connectors::new()->load() as $connector) {
    try {
        switch ($connector->getType()) {
            case 'sql':
                Log::action(ts('Attempting to connect to ":type" type database connector ":connector" with database ":database" on host ":hostname"', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getName(),
                    ':database'  => $connector->getDatabase(),
                    ':hostname'  => $connector->getHostname(),
                ]), 10);

                $connector->connect(true);

                Log::success(ts('Successfully connected to ":type" database connector ":connector"', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getName(),
                ]), 10);
                break;

            default:
                Log::warning(ts('Skipping ":type" type connector ":connector", it is not yet supported', [
                    ':type'      => $connector->getType(),
                    ':connector' => $connector->getDisplayName(),
                ]), 10);
        }

    } catch (SqlAccessDeniedException $e) {
        Log::warning(ts('Failed to connect to ":type" database connector ":connector", access was denied', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
        ]), 10);

    } catch (SqlUnknownDatabaseException $e) {
        Log::warning(ts('Failed to connect to ":type" database connector ":connector", the configured database ":database" does not exist', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
            ':database'  => $connector->getDatabase(),
        ]), 10);

    } catch (SqlInvalidConfigurationException $e) {
        Log::warning(ts('Failed to connect to ":type" database connector ":connector", the connector has an invalid configuration', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
        ]), 10);

    } catch (SqlConnectException $e) {
        Log::warning(ts('Failed to connect to ":type" database connector ":connector" because ":reason"', [
            ':type'      => $connector->getType(),
            ':connector' => $connector->getName(),
            ':reason'    => $e->getMessage(),
        ]), 10);
    }
}
