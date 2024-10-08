<?php

declare(strict_types=1);

namespace Phoundation\Os\Processes\Traits;

use Phoundation\Os\Processes\Interfaces\TaskInterface;
use Phoundation\Os\Processes\Task;

/**
 * Trait TraitDataEntryTask
 *
 * This trait contains methods for DataEntry objects that require a task
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryTask
{
    /**
     * @var TaskInterface|null $task
     */
    protected ?TaskInterface $task;


    /**
     * Sets the tasks_id for this object
     *
     * @param int|null $tasks_id
     *
     * @return static
     */
    public function setTasksId(?int $tasks_id): static
    {
        unset($this->task);

        return $this->set($tasks_id, 'tasks_id');
    }


    /**
     * Returns the tasks hostname for this object
     *
     * @return string|null
     */
    public function getTasksCode(): ?string
    {
        return $this->getTask()
                    ?->getCode();
    }


    /**
     * Returns the TaskInterface object for this object
     *
     * @return TaskInterface|null
     */
    public function getTask(): ?TaskInterface
    {
        if (!isset($this->task)) {
            $this->task = Task::loadOrNull($this->getTasksId());
        }

        return $this->task;
    }


    /**
     * Sets the TaskInterface object for this object
     *
     * @param TaskInterface|null $task
     *
     * @return static
     */
    public function setTask(?TaskInterface $task): static
    {
        if ($task) {
            $this->task = $task;

            return $this->set($task->getId(), 'tasks_id');
        }

        return $this->setTasksId(null);
    }


    /**
     * Returns the tasks_id for this object
     *
     * @return int|null
     */
    public function getTasksId(): ?int
    {
        return $this->getValueTypesafe('int', 'tasks_id');

    }


    /**
     * Sets the task code for this object
     *
     * @param string|null $code
     *
     * @return static
     */
    public function setTasksCode(?string $code): static
    {
        return $this->setTask(Task::load($code, 'code'));
    }
}
