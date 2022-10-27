<?php

namespace Phoundation\Notify;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log;
use Phoundation\Data\DataEntry;
use Phoundation\Exception\OutOfBoundsException;
use Throwable;



/**
 * Class Notification
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notify
 */
class Notification
{
    use DataEntry;



//    /**
//     * The group that will receive  this notification
//     *
//     * @var array $groups
//     */
//    protected array $groups = [];
//
//    /**
//     * The type of notification, either "INFORMATION", "NOTICE", "WARNING", or "ERROR"
//     *
//     * @var string $type
//     */
//    #[ExpectedValues(values: ["INFORMATION", "NOTICE", "WARNING", "ERROR"])]  protected string $type = 'ERROR';
//
//    /**
//     * The code for this notification
//     *
//     * @var string|null $code
//     */
//    protected ?string $code = null;
//
//    /**
//     * The priority level, lowest 10, highest 1
//     *
//     * @var int $priority
//     */
//    protected int $priority = 10;
//
//    /**
//     * The title for this notification
//     *
//     * @var string|null $title
//     */
//    protected ?string $title = null;
//
//    /**
//     * The message for this notification
//     *
//     * @var string|null $message
//     */
//    protected ?string $message = null;
//
//    /**
//     * The file that generated this notification
//     *
//     * @var string|null $file
//     */
//    protected ?string $file = null;
//
//    /**
//     * The line that generated this notification
//     *
//     * @var int|null $line
//     */
//    protected ?int $line = null;
//
//    /**
//     * The trace for this notification
//     *
//     * @var array|null $trace
//     */
//    protected ?array $trace = null;
//
//    /**
//     * The details for this notification
//     *
//     * @var mixed $details
//     */
//    protected mixed $details = null;
//
//    /**
//     * The exception for this notification
//     *
//     * @var Throwable|null $e
//     */
//    protected ?Throwable $e = null;



    /**
     * Returns a new notification object instance
     *
     * @return Notification
     */
    public static function create(): Notification
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

        if (strlen($code) > 16) {
            throw new OutOfBoundsException('Invalid code specified for this notification, it should be less than or equal to 16 characters');
        }

        $this->setDataValue('code', $code);
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
     * Sets the status for this notification
     *
     * @param string|null $status
     * @return Notification
     */
    public function setStatus(?string $status): Notification
    {
        $this->setDataValue('status', $status);
        return $this;
    }



    /**
     * Returns the status for this notification
     *
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }



    /**
     * Sets the priority level for this notification
     *
     * @param int $priority
     * @return Notification
     */
    public function setPriority(int $priority): Notification
    {
        if (($priority < 1) or ($priority > 10)) {
            throw new OutOfBoundsException('Invalid priority level ":priority" specified. It should be an integer between 1 and 10', [
                ':priority' => $priority
            ]);
        }

        $this->setDataValue('priority', $priority);
        return $this;
    }



    /**
     * Returns the priority level for this notification
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }



    /**
     * Sets the type for this notification
     *
     * @param string $type
     * @return Notification
     */
    public function setType(#[ExpectedValues(values: ["INFORMATION", "NOTICE", "WARNING", "ERROR"])] string $type): Notification
    {
        $clean_type = strtoupper(trim($type));

        switch ($clean_type) {
            case 'INFORMATION':
                // no-break
            case 'NOTICE':
                // no-break
            case 'WARNING':
                // no-break
            case 'ERROR':
                break;

            case '':
                throw new OutOfBoundsException(tr('No type specified for this notification'));

            default:
                throw new OutOfBoundsException(tr('Unknown type ":type" specified for this notification, please ensure it is one of "WARNING", "ERROR", "NOTICE", or "INFORMATION"', [':type' => $type]));
        }

        $this->setDataValue('type', $clean_type);
        return $this;
    }



    /**
     * Returns the type for this notification
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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

        $this->setDataValue('title', $title);
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

        $this->setDataValue('message', $message);
        return $this;
    }



    /**
     * Sets the exception for this notification
     *
     * @param Throwable $e
     * @return Notification
     */
    public function setException(Throwable $e): Notification
    {
        $this->setCode($e->getCode());
        $this->setCode($e->getMessage());
        $this->setPreviousException($e->getPrevious());
        $this->setCode();

        $this->setDataValue('e', $e);
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
     * Sets the details for this notification
     *
     * @param mixed $details
     * @return Notification
     */
    public function setDetails(mixed $details): Notification
    {
        $this->setDataValue('details', $details);
        return $this;
    }



    /**
     * Returns the details for this notification
     *
     * @return mixed
     */
    public function getDetails(): mixed
    {
        return $this->details;
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
     * @param array|string $groups
     * @return Notification
     */
    public function setGroups(array|string $groups): Notification
    {
        if (!$groups) {
            throw new OutOfBoundsException('No groups specified for this notification');
        }

        $this->setDataValue('groups', []);
        $this->addGroups($groups);
        return $this;
    }



    /**
     * Sets the message for this notification
     *
     * @param array|string $groups
     * @return Notification
     */
    public function addGroups(array|string $groups): Notification
    {
        if (!$groups) {
            throw new OutOfBoundsException('No groups specified for this notification');
        }

        foreach (Arrays::force($groups) as $group) {
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

        $this->addDataValue('groups', $group);
        return $this;
    }



    /**
     * Send the notification
     *
     * @todo Implement!
     * @return Notification
     */
    public function send(): Notification
    {
        Log::warning('Notifications::send() not yet implemented! Not sending subsequent message');
        Log::warning($this->title);
        Log::warning($this->message);
return $this;

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

        return $this;
    }



    /**
     * Log this notification to the system logs as well
     *
     * @return Notification
     */
    public function log(): Notification
    {
        switch ($this->getType()) {
            case 'ERROR':
                Log::error($this->getTitle());
                Log::error($this->getMessage());
                Log::error($this->getDetails());
                break;

            case 'WARNING':
                Log::warning($this->getTitle());
                Log::warning($this->getMessage());
                Log::warning($this->getDetails());
                break;

            case 'NOTICE':
                Log::notice($this->getTitle());
                Log::notice($this->getMessage());
                Log::notice($this->getDetails());
                break;

            case 'INFORMATION':
                Log::information($this->getTitle());
                Log::information($this->getMessage());
                Log::information($this->getDetails());
                break;
        }

        return $this;
    }



    /**
     * Load the specified notification from the database
     *
     * @param int $id
     * @return void
     */
    protected function load(int $id): void
    {
        $data = sql()->get('SELECT * FROM `notifications` WHERE `id` = :id', [
            ':id' => $id
        ]);

        $this->setData($data);
    }



    /**
     * Save this notification to the database
     *
     * @return void
     */
    protected function save(): void
    {
        sql()->write('notifications', $this->getInsertColumns(), $this->getUpdateColumns());
    }



    /**
     * Sets the available data keys for the Notification class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'id',
            'created_by',
            'created_on',
            'modified_by',
            'modified_on',
            'meta_id',
            'status',
            'code',
            'type',
            'priority',
            'title',
            'message',
            'file',
            'line',
            'trace',
            'details'
       ];
    }
}