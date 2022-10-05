<?php

namespace Phoundation\Notify;



use Phoundation\Exception\OutOfBoundsException;
use Throwable;

/**
 * Class Notification
 *
 * This is the default MemCached object
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notify
 */
class Notification
{
    /**
     * The group that will receive  this notification
     *
     * @var array $groups
     */
    protected array $groups = [];

    /**
     * The code for this notification
     *
     * @var string|null $code
     */
    protected ?string $code = null;

    /**
     * The title for this notification
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * The message for this notification
     *
     * @var string|null $message
     */
    protected ?string $message = null;

    /**
     * The file that generated this notification
     *
     * @var string|null $file
     */
    protected ?string $file = null;

    /**
     * The line that generated this notification
     *
     * @var int|null $line
     */
    protected ?int $line = null;

    /**
     * The trace for this notification
     *
     * @var array|null $trace
     */
    protected ?array $trace = null;

    /**
     * The data for this notification
     *
     * @var mixed $data
     */
    protected mixed $data = null;

    /**
     * The exception for this notification
     *
     * @var Throwable|null $e
     */
    protected ?Throwable $e = null;



    /**
     * Returns a new notification object instance
     *
     * @return Notification
     */
    public static function getInstance(): Notification
    {
        return new Notification();
    }



    /**
     * Sets the code for this notification
     *
     * @param string $code
     * @return Notification
     */
    public function setCode(string $code): Notification
    {
        if (!$code) {
            throw new OutOfBoundsException('No code specified for this notification');
        }

        $this->code = $code;
        return $this;
    }



    /**
     * Returns the code for this notification
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }



    /**
     * Returns the title for this notification
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }



    /**
     * Sets the title for this notification
     *
     * @param string $title
     * @return Notification
     */
    public function setTitle(string $title): Notification
    {
        if (!$title) {
            throw new OutOfBoundsException('No title specified for this notification');
        }

        $this->title = $title;
        return $this;
    }



    /**
     * Returns the message for this notification
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }



    /**
     * Sets the message for this notification
     *
     * @param string $message
     * @return Notification
     */
    public function setMessage(string $message): Notification
    {
        if (!$message) {
            throw new OutOfBoundsException('No message specified for this notification');
        }

        $this->message = $message;
        return $this;
    }



    /**
     * Returns the data for this notification
     *
     * @return mixed
     */
    public function getData(): mixed
    {
        return $this->data;
    }



    /**
     * Sets the exception for this notification
     *
     * @param Throwable $e
     * @return Notification
     */
    public function setException(Throwable $e): Notification
    {
        $this->e = $e;
        $this->code = $e->getCode();
        $this->message = $e->getMessage();
        $this->previous_e = $e->getPrevious();
        return $this;
    }



    /**
     * Returns the exception for this notification
     *
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->e;
    }



    /**
     * Sets the data for this notification
     *
     * @param mixed $data
     * @return Notification
     */
    public function setData(mixed $data): Notification
    {
        $this->data = $data;
        return $this;
    }



    /**
     * Returns the groups for this notification
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }



    /**
     * Sets the message for this notification
     *
     * @note: This will reset the current already registered groups
     * @param array $groups
     * @return Notification
     */
    public function setGroups(array $groups): Notification
    {
        if (!$groups) {
            throw new OutOfBoundsException('No groups specified for this notification');
        }

        $this->groups = [];
        $this->addGroups($groups);
        return $this;
    }



    /**
     * Sets the message for this notification
     *
     * @param array $groups
     * @return Notification
     */
    public function addGroups(array $groups): Notification
    {
        if (!$groups) {
            throw new OutOfBoundsException('No groups specified for this notification');
        }

        foreach ($groups as $group) {
            $this->addGroup($group);
        }

        return $this;
    }


    /**
     * Sets the message for this notification
     *
     * @param string $group
     * @return Notification
     */
    public function addGroup(string $group): Notification
    {
        $group = trim($group);

        if (!$group) {
            throw new OutOfBoundsException('Empty or no group specified for this notification');
        }

        $this->groups[] = $group;
        return $this;
    }



    /**
     * Send the notification
     *
     * @todo Implement!
     * @return void
     */
    public function send(): void
    {
        if (!$this->code) {
            throw new OutOfBoundsException('Cannot send notification, no notification code specified');
        }

        if (!$this->title) {
            throw new OutOfBoundsException('Cannot send notification, no notification title specified');
        }

        if (!$this->message) {
            throw new OutOfBoundsException('Cannot send notification, no notification message specified');
        }

        if (!$this->groups) {
            throw new OutOfBoundsException('Cannot send notification, no notification groups specified');
        }

        // TODO IMPLEMENT

        if ($this->e) {
            $this->file = $this->e->getFile();
            $this->line = $this->e->getLine();
            $this->trace = $this->e->getTrace();
        }
    }
}