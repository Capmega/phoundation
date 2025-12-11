<?php

/**
 * Trait TraitMethodsVirtualColumns
 *
 * @see       \Phoundation\Data\DataEntries\DataEntry
 * @see       \Phoundation\Web\Html\Components\Forms\FilterForm
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\Exception\DataEntryColumnsNotDefinedException;
use Phoundation\Data\DataEntries\Exception\DataEntryInvalidVirtualConfigurationException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Plugins\Medinet\Claims\FilterForm;


trait TraitMethodsVirtualColumns {
    use TraitMethodsGetTypesafe;


    /**
     * Global flag tracking if the source data is currently being initialized
     *
     * @var bool $is_initializing_source
     */
    protected bool $is_initializing_source = false;

    /**
     * Setup for virtual columns
     *
     * @var array $virtual_configuration
     */
    protected array $virtual_configuration = [];

    /**
     * Cache for the payee data
     *
     * @var array $virtual_objects
     */
    protected array $virtual_objects = [];


    /**
     * Returns the value for the requested table_key
     *
     * If the current value is null or not set, this method will automatically try to load the data
     *
     * @param string $table
     * @param string $type
     * @param string $key
     *
     * @return mixed
     */
    protected function getVirtualData(string $table, string $type, string $key): mixed
    {
        $key = $table . '_' . $key;

        if (empty($this->source[$key])) {
            $this->setVirtualObject($table);
        }

        return $this->getTypesafe($type, $key);
    }


    /**
     * This will reset all virtual data for the specified table
     *
     * @param string $table
     * @param mixed  $value
     * @param string $column
     *
     * @return static
     */
    protected function setVirtualData(string $table, mixed $value, string $column): static
    {
        if ($this->get($table . '_' . $column) === $value) {
            // The column has not changed, don't change anything
            return $this;
        }

        // Reset all columns for the table except the specified one, that one will have the specified value
        foreach ($this->getVirtualColumns($table) as $virtual_column => $virtual_table_column) {
            try {
                if ($virtual_column === $column) {
                    $this->set($value, $virtual_table_column);

                } else {
                    if (!$this->is_initializing_source and !$this->isApplying()) {
                        $this->set(null, $virtual_table_column);
                    }
                }

            } catch (DataEntryColumnsNotDefinedException $e) {
                // We're trying to set a column that doesn't exist in the Definitions object
                throw DataEntryInvalidVirtualConfigurationException::new(tr('Virtual columns configuration for table ":table" in class ":class" contains column ":column" but that column does not exist in the definitions for this class', [
                    ':table'  => $table,
                    ':class'  => $this::class,
                    ':column' => $virtual_table_column,
                ]), $e)->setData([
                    'table'   => $table,
                    'class'   => $this::class,
                    'column'  => $virtual_table_column,
                ]);
            }
        }

        return $this;
    }


    /**
     * Returns the virtual object for the requested table
     *
     * @param string $table
     *
     * @return DataEntryInterface|null
     */
    protected function getVirtualObject(string $table): ?DataEntryInterface
    {
        if (empty($this->virtual_objects[$table])) {
            $this->setVirtualObject($table);
        }

        return array_get_safe($this->virtual_objects, $table);
    }


    /**
     * Loads data to all virtual columns
     *
     * @param string                  $table
     * @param DataEntryInterface|null $o_object
     *
     * @return static
     */
    protected function setVirtualObject(string $table, ?DataEntryInterface $o_object = null): static
    {
        if (array_key_exists($table, $this->virtual_objects)) {
            // The virtual object has already been loaded
            return $this;
        }

        $configuration = $this->getVirtualConfiguration($table);

        if (empty($o_object)) {
            try {
                $identifier = $this->getVirtualLoadIdentifier($configuration['columns'], array_get_safe($configuration, 'additional_filters'));

                if (empty($identifier)) {
                    // There is no identifier for this object, meaning that all related columns are empty, so the requested object column will be empty also.
                    return $this;
                }

                Log::warning(tr('Automatically loading virtual object ":object" for class ":class" with identifier ":identifier"', [
                    ':object'     => $configuration['class'],
                    ':class'      => static::class,
                    ':identifier' => Json::encode($identifier),
                ]), 3);

                $o_object = $configuration['class']::new()
                                                   ->setDebug($this->getDebug())
                                                   ->setMetaEnabled($this->getMetaEnabled())
                                                   ->loadNull($identifier);

            } catch (DataEntryInvalidVirtualConfigurationException $e) {
                // This means that a column was specified to be checked that doesn't exist in the Definitions object
                throw DataEntryInvalidVirtualConfigurationException::new(tr('Cannot find value for defined virtual column ":column" in class ":class", this column does not exist in the definitions object', [
                    ':class'  => $this::class,
                    ':column' => $e->getDataKey('column'),
                ]), $e)->setData([
                    'column'        => $e->getDataKey('column'),
                    'table'         => $table,
                    'configuration' => $configuration,
                ]);
            }
        }

        // Cache the loaded object
        $this->virtual_objects[$table] = $o_object;

        // Set all configured columns
        foreach ($configuration['columns'] as $column => $table_column) {
            $this->set($o_object?->get($column), $table_column);
        }

        return $this;
    }


    /**
     * Returns a DataEntry identifier array with virtual column values from this DataEntry to load the virtual object
     *
     * @param array      $columns
     * @param array|null $additional_filters
     *
     * @return array|null
     */
    protected function getVirtualLoadIdentifier(array $columns, ?array $additional_filters = null): ?array
    {
        $return = [];

        foreach ($columns as $column => $table_column) {
            try {
                if ($this instanceof FilterForm) {
                    // For filter form use object::getForce() because the column that is being searched for likely is
                    // not rendered and because of that will return NULL for object::get()
                    $value = $this->getForce($table_column);

                } else {
                    $value = $this->get($table_column);
                }

            } catch (OutOfBoundsException $e) {
                // This means that a column was specified to be checked that does not exist in the Definitions object
                throw DataEntryInvalidVirtualConfigurationException::new(tr('Cannot find value for defined virtual column ":column", this column does not exist in the definitions object', [
                    ':column' => $table_column,
                ]), $e)->setData([
                    'column'  => $table_column,
                ]);
            }

            if (is_empty($value)) {
                continue;
            }

            $return[$column] = $value;
        }

        if ($return) {
            if ($additional_filters) {
                // Additional identifier filters were specified, add those too
                $return = array_merge($additional_filters, $return);
            }

        } else {
            // There are no identifiers for this virtual column
            return null;
        }

        // If we have the unique table id then return only that.
        if (array_key_exists('id', $return)) {
            return ['id' => $return['id']];
        }

        return $return;
    }


    /**
     * Initializes the configuration for multiple virtual columns
     *
     * @param array $configuration
     *
     * @return static
     */
    protected function initializeVirtualConfiguration(array $configuration): static
    {
        foreach ($configuration as $table => $columns) {
            $this->addVirtualConfiguration($table, null, $columns);
        }

        return $this;
    }


    /**
     * Adds the virtual column configuration for the specified table
     *
     * @param string      $table
     * @param string|null $class
     * @param array       $columns
     * @param array|null  $additional_filters
     *
     * @return static
     */
    protected function addVirtualConfiguration(string $table, ?string $class, array $columns, ?array $additional_filters = null): static
    {
        $table_columns = [];

        foreach ($columns as $column) {
            $table_columns[$column] = $table . '_' . $column;
        }

        if (!array_key_exists($table, $this->virtual_configuration)) {
            $this->virtual_configuration[$table] = ['columns' => $table_columns];
        }

        if ($class) {
            $this->virtual_configuration[$table]['class'] = $class;
        }

        if ($additional_filters) {
            $this->virtual_configuration[$table]['additional_filters'] = $additional_filters;
        }

        return $this;
    }


    /**
     * Returns the virtual data configuration for the specified table
     *
     * @param string $table
     *
     * @return array
     */
    protected function getVirtualConfiguration(string $table): array
    {
        if (array_key_exists($table, $this->virtual_configuration)) {
            // Configuration exists, but it may be a partial configuration setup in the DataEntry class itself
            if (array_key_exists('class', $this->virtual_configuration[$table])) {
                // "class" configuration exists too, this is a complete configuration, we're done
                return $this->virtual_configuration[$table];
            }
        }

        // Configuration doesn't exist. Can we autoload it?
        $table_name = Strings::capitalize($table);

        if (str_contains($table_name, '_')) {
            // Replace underscores with camelCase
            $table_name = '';

            foreach (explode('_', $table) as $part) {
                $part = Strings::capitalize($part);
                $table_name .= $part;
            }
        }

        // Determine the method to auto add the configuration
        $method = 'addVirtualConfiguration' . $table_name;

        if (method_exists($this, $method)) {
            $this->$method();
        }

        // Try again if the configuration exists now
        if (!array_key_exists($table, $this->virtual_configuration)) {
            throw new OutOfBoundsException(tr('Cannot return virtual configuration for table ":table", the virtual table columns have not been defined or the method ":method" does not exist', [
                ':table'  => $table,
                ':method' => $method,
            ]));
        }

        return $this->virtual_configuration[$table];
    }


    /**
     * Returns the virtual data configuration for the specified table
     *
     * @param string $table
     *
     * @return array
     */
    protected function getVirtualColumns(string $table): array
    {
        $configuration = $this->getVirtualConfiguration($table);
        return $configuration['columns'];
    }
}
