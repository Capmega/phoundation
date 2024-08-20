<?php

/**
 * SshAccounts class
 *
 *
 *
 * @see       DataIterator
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Business
 */


declare(strict_types=1);

namespace Phoundation\Servers;

use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Filesystem\FsFile;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;
use Phoundation\Web\Html\Components\Tables\Interfaces\HtmlTableInterface;
use Phoundation\Web\Html\Enums\EnumTableIdColumn;


class SshAccounts extends DataIterator
{
    /**
     * SshAccounts class constructor
     */
    public function __construct()
    {
        $this->configuration_path = 'ssh.accounts';

        parent::__construct();
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'ssh_accounts';
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataTypes(): ?string
    {
        return SshAccount::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'seo_name';
    }


    /**
     * Creates and returns an HTML table for the data in this list
     *
     * @param array|string|null $columns
     *
     * @return HtmlTableInterface
     */
    public function getHtmlTable(array|string|null $columns = null): HtmlTableInterface
    {
        $table = parent::getHtmlTable();

        $table->setCheckboxSelectors(EnumTableIdColumn::checkbox);

        return $table;
    }


    /**
     * Returns an HTML <select> for the available object entries
     *
     * @param string      $value_column
     * @param string|null $key_column
     * @param string|null $order
     * @param array|null  $joins
     * @param array|null  $filters
     *
     * @return InputSelectInterface
     */
    public function getHtmlSelect(string $value_column = 'name', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        return parent::getHtmlSelect($value_column, $key_column, $order, $joins, $filters)
                     ->setName('ssh_accounts_id')
                     ->setNotSelectedLabel(tr('Select an SSH account'))
                     ->setComponentEmptyLabel(tr('No SSH accounts available'));
    }


    /**
     * @inheritDoc
     */
    public function load(?array $identifiers = null, bool $clear = true, bool $only_if_empty = false): static
    {
        parent::load($identifiers, $clear, $only_if_empty);

        // If any of the accounts have the "file" key, replace that key with ssh_key value
        foreach ($this->source as &$entry) {
            if (empty($entry['name'])) {
                // No name always default to username
                $entry['name'] = $entry['username'];
            }

            if (array_key_exists('file', $entry)) {
                if ($entry['file']) {
                    $entry['ssh_key'] = FsFile::new($entry['file'], FsRestrictions::getFilesystemRoot())->getContentsAsString();
                }
            }
        }

        unset($entry);
        return $this;
    }


    /**
     * Load the data for this right list
     *
     * @param array|string|null $columns
     * @param array $filters
     * @param array $order_by
     * @return array
     */
    public function loadDetails(array|string|null $columns, array $filters = [], array $order_by = []): array
    {
        // Default columns
        if (!$columns) {
            $columns = 'id,name,created_on';
        }

        // Default ordering
        if (!$order_by) {
            $order_by = ['name' => false];
        }

        // Get column information
        $columns = Arrays::force($columns);
        $columns = Strings::force($columns);

        // Build query
        $builder = new QueryBuilder();
        $builder->addSelect($columns);
        $builder->addFrom('`ssh_accounts`');

        // Add ordering
        foreach ($order_by as $column => $direction) {
            $builder->addOrderBy('`' . $column . '` ' . ($direction ? 'DESC' : 'ASC'));
        }

        // Build filters
        foreach ($filters as $key => $value) {
            switch ($key) {
                case 'deleted':
                    $no_delete = true;
            }
        }

        if (isset($no_delete)) {
            $builder->addWhere('`status` IS NULL');
        }

        return sql()->list($builder->getQuery(), $builder->getExecute());
    }
}
