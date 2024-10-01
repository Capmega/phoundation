<?php

/**
 * Class Tasks
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes;

use Phoundation\Core\Interfaces\ArrayableInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataIterator;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Os\Processes\Exception\NoTasksPendingExceptions;
use Phoundation\Os\Processes\Exception\TasksException;
use Phoundation\Os\Processes\Interfaces\TasksInterface;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Input\InputSelect;
use Phoundation\Web\Html\Components\Input\Interfaces\InputSelectInterface;


class Tasks extends DataIterator implements TasksInterface
{
    /**
     * Tracks the maximum number of tasks workers
     *
     * @var int $max_task_workers
     */
    protected static int $max_task_workers;

    /**
     * Tracks if tasks execution has started
     *
     * @var DateTimeInterface $executing
     */
    protected static DateTimeInterface $executing;


    /**
     * @param ArrayableInterface|array|null $source
     */
    public function __construct(ArrayableInterface|array|null $source = null)
    {
        if (!isset(static::$max_task_workers)) {
            static::$max_task_workers = Config::getInteger('tasks.workers.maximum', 25);
        }

        parent::__construct($source);
    }


    /**
     * Returns the class for a single DataEntry in this Iterator object
     *
     * @return string|null
     */
    public static function getDefaultContentDataType(): ?string
    {
        return Task::class;
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'code';
    }


    /**
     * Returns the date since when this object is executing tasks, or false instead
     *
     * @return DateTimeInterface|false
     */
    public function getExecuting(): DateTimeInterface|false
    {
        if (isset(static::$executing)) {
            return static::$executing;
        }

        return false;
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
    public function getHtmlSelect(string $value_column = '', ?string $key_column = 'id', ?string $order = null, ?array $joins = null, ?array $filters = ['status' => null]): InputSelectInterface
    {
        if (!$value_column) {
            $value_column = 'CONCAT(`command`, " [", `status`, "]") AS command';
        }

        return InputSelect::new()
                          ->setConnectorObject($this->getConnectorObject())
                          ->setSourceQuery('SELECT   `' . $key_column . '`, ' . $value_column . ' 
                                            FROM     `' . static::getTable() . '` 
                                            WHERE    `status` IS NULL 
                                            ORDER BY `created_on` ASC')
                          ->setName('tasks_id')
                          ->setNotSelectedLabel(tr('Select a task'))
                          ->setComponentEmptyLabel(tr('No tasks available'));
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'os_tasks';
    }


    /**
     * Execute the tasks in this list
     *
     * @return static
     */
    public function execute(): static
    {
        if (isset(static::$executing)) {
            throw new OutOfBoundsException(tr('Cannot execute pending tasks, tasks are already being executed'));
        }

        static::$executing = now();

        $keys = $this->getSourceKeys();

        if (!count($keys)) {
            throw NoTasksPendingExceptions::new(tr('There are no pending tasks'))
                                          ->makeWarning();
        }

        Log::action(tr('Executing ":count" pending tasks with ":workers" child worker', [
            ':count'   => count($keys),
            ':workers' => static::$max_task_workers,
        ]));

        try {
            Pho::new()
               ->setPhoCommands('tasks execute')
               ->setLabel(tr('task'))
               ->addArguments(['-t', ':TASKSID'])
               ->setKey(':TASKSID')
               ->setValues($keys)
               ->setMaximumWorkers(static::$max_task_workers)
               ->start();

        } catch (TasksException $e) {
            Log::error(tr('Execution of pending tasks failed'));
            Log::exception($e);

            // Restart tasks execution in a separate process
            Log::action(tr('Restarting pending tasks executer in new background process'));

            Pho::new()
               ->setPhoCommands('tasks,execute')
               ->executeBackground();
        }

        return $this;
    }
}
