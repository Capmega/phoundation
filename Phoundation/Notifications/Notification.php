<?php

namespace Phoundation\Notifications;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Exception\Exception;
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
 * @package Phoundation\Notification
 */
class Notification extends DataEntry
{
    /**
     * Keeps track of if this noticication was logged or not
     *
     * @var bool
     */
    protected static bool $logged = false;

    /**
     * Keeps track of if noticications should abe automatically logged or not
     *
     * @var bool
     */
    protected static bool $auto_log = false;



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
     * Notification class constructor
     *
     * @param int|string|null $identifier
     */
    public function __construct(int|string|null $identifier = null)
    {
        self::$auto_log   = Config::get('notifications.auto-log', true);
        self::$entry_name = 'notification';
        $this->table      = 'notifications';

        parent::__construct($identifier);
    }



    /**
     * Sets the code for this notification
     *
     * @param string|null $code
     * @return static
     */
    public function setCode(?string $code): static
    {
        if (strlen((string) $code) > 16) {
            throw new OutOfBoundsException('Invalid code specified for this notification, it should be less than or equal to 16 characters');
        }

        $this->setDataValue('code', $code);
        return $this;
    }



    /**
     * Returns the code for this notification
     *
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->getDataValue('code');
    }



    /**
     * Sets the status for this notification
     *
     * @param string|null $status
     * @return static
     */
    public function setStatus(?string $status): static
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
        return $this->getDataValue('status');
    }



    /**
     * Sets the priority level for this notification
     *
     * @param int $priority
     * @return static
     */
    public function setPriority(int $priority): static
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
        return (int) $this->getDataValue('priority');
    }



    /**
     * Sets the type for this notification
     *
     * @param string $type
     * @return static
     */
    public function setType(#[ExpectedValues(values: ["INFORMATION", "NOTICE", "WARNING", "ERROR"])] string $type): static
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
                throw new OutOfBoundsException(tr('Unknown type ":type" specified for this notification, please ensure it is one of "WARNING", "ERROR", "NOTICE", or "INFORMATION"', [
                    ':type' => $type
                ]));
        }

        $this->setDataValue('type', $clean_type);
        return $this;
    }



    /**
     * Returns the type for this notification
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getDataValue('type');
    }



    /**
     * Returns the title for this notification
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->getDataValue('title');
    }



    /**
     * Sets the title for this notification
     *
     * @param string $title
     * @return static
     */
    public function setTitle(string $title): static
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
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->getDataValue('message');
    }



    /**
     * Sets the message for this notification
     *
     * @param string $message
     * @return static
     */
    public function setMessage(string $message): static
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
     * @return static
     */
    public function setException(Throwable $e): static
    {
        $this->setCode($e->getCode());
        $this->setMessage($e->getMessage());
        $this->setDetails([
            'trace' => $e->getTrace(),
            'data' => (($e instanceof Exception) ? $e->getData() : 'No a Phoundation exception, no data available')
        ]);

        $this->setDataValue('e', $e);
        return $this;
    }



    /**
     * Returns the exception for this notification
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->getDataValue('e');
    }



    /**
     * Sets the details for this notification
     *
     * @param mixed $details
     * @return static
     */
    public function setDetails(mixed $details): static
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
        return $this->getDataValue('details');
    }



    /**
     * Returns the groups for this notification
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->getDataValue('groups');
    }



    /**
     * Clears the message for this notification
     *
     * @return static
     */
    public function clearGroups(): static
    {
        $this->setDataValue('groups', []);
        return $this;
    }



    /**
     * Sets the message for this notification
     *
     * @note: This will reset the current already registered groups
     * @param array|string $groups
     * @return static
     */
    public function setGroups(array|string $groups): static
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
     * @return static
     */
    public function addGroups(array|string $groups): static
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
     * @return static
     */
    public function addGroup(string $group): static
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
     * @param bool|null $log
     * @return static
     * @todo Implement!
     */
    public function send(?bool $log = null): static
    {
        if ($log === null) {
            $log = self::$auto_log;
        }

        if (!self::$logged and $log) {
            // Automatically log this notification
            self::log();
        }

        Log::warning('Notifications::send() not yet implemented! Not sending subsequent message');
        Log::warning($this->getTitle());
        Log::warning($this->getMessage());
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
     * @return static
     */
    public function log(): static
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

        self::$logged = true;

        return $this;
    }



    /**
     * Load the specified notification from the database
     *
     * @param string|int $identifier
     * @return void
     */
    protected function load(string|int $identifier): void
    {
        $data = sql()->get('SELECT * FROM `notifications` WHERE `id` = :id', [
            ':id' => $identifier
        ]);

        $this->setData($data);
    }



    /**
     * Save this notification to the database
     *
     * @return static
     */
    public function save(): static
    {
        sql()->write('notifications', $this->getInsertColumns(), $this->getUpdateColumns());
        return $this;
    }



    /**
     * Sets the available data keys for the Notification class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->data = [
            'groups' => [],
            'priority' => 10
        ];

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