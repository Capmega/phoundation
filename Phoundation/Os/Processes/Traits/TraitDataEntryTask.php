<?php

/**
 * Trait TraitDataEntryTask
 *
 * This trait contains methods for DataEntry objects that require a task
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Traits;

use Phoundation\Os\Processes\Interfaces\TaskInterface;
use Phoundation\Os\Tasks\Task;


trait TraitDataEntryTask
{
    /**
     * @var TaskInterface|null $o_task
     */
    protected ?TaskInterface $o_task;


    /**
     * Returns the tasks_id for this object
     *
     * @return int|null
     */
    public function getTasksId(): ?int
    {
        return $this->getTypesafe('int', 'tasks_id');

    }


    /**
     * Sets the tasks_id for this object
     *
     * @param int|null $id
     *
     * @return static
     */
    public function setTasksId(?int $id): static
    {
        return $this->setTaskData(Task::new()->loadOrNull($id));
    }


    /**
     * Returns the tasks hostname for this object
     *
     * @return string|null
     */
    public function getTasksCode(): ?string
    {
        return $this->getTypesafe('string', 'tasks_code');
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
        return $this->setTaskData(Task::new()->loadOrNull(['code' => $code]));
    }


    /**
     * Returns the TaskInterface object for this object
     *
     * @return TaskInterface|null
     */
    public function getTask(): ?TaskInterface
    {
        return $this->o_task;
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
        return $this->setTaskData($task);
    }


    /**
     * Sets the task ID, Practitioner Number, and Email
     *
     * @param TaskInterface|null $o_task
     *
     * @return static
     */
    protected function setTaskData(?TaskInterface $o_task): static
    {
        $this->o_task = $o_task;

        return $this->set($o_task?->getId(false), 'tasks_id')
                    ->set($o_task?->getCode()   , 'tasks_code');
    }
}
