<?php

namespace Phoundation\Notifications;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryCode;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryException;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Exception\NotificationBusyException;
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
    use DataEntryUrl;
    use DataEntryCode;
    use DataEntryTitle;
    use DataEntryDetails;


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

    /**
     * The roles where this notification should be sent to
     *
     * @var array|null $roles
     */
    protected ?array $roles = null;

    /**
     * Optional exception source for this notification
     *
     * @var Throwable|null $e
     */
    protected ?Throwable $e = null;



//    /**
//     * The role that will receive  this notification
//     *
//     * @var array $roles
//     */
//    protected array $roles = [];
//
//    /**
//     * The mode of notification, either "INFORMATION", "NOTICE", "WARNING", or "ERROR"
//     *
//     * @var string $mode
//     */
//    #[ExpectedValues(values: ["INFORMATION", "NOTICE", "WARNING", "ERROR"])]  protected string $mode = 'ERROR';
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
        static::$auto_log   = Config::get('notifications.auto-log', true);
        static::$entry_name = 'notification';
        $this->table        = 'notifications';

        $this->data['mode']     = 'unknown';
        $this->data['priority'] = 1;

        parent::__construct($identifier);
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

        return $this->setDataValue('priority', $priority);
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
     * Sets the mode for this notification
     *
     * @param string $mode
     * @return static
     */
    public function setMode(#[ExpectedValues(values: ["INFORMATION", "INFO", "SUCCESS", "NOTICE", "WARNING", "DANGER", "ERROR", "EXCEPTION", "UNKNOWN"])] string $mode): static
    {
        $clean_mode = strtoupper(trim($mode));

        switch ($clean_mode) {
            case 'INFO':
                // no-break
            case 'INFORMATION':
                $clean_mode = 'INFO';
                break;

            case 'ERROR':
                // no-break
            case 'EXCEPTION':
                // no-break
            case 'DANGER':
                $clean_mode = 'DANGER';
                break;

            case 'NOTICE':
                // no-break
            case 'WARNING':
                // no-break
            case 'SUCCESS':
                // no-break
            case 'UNKNOWN':
                break;

            case '':
                throw new OutOfBoundsException(tr('No mode specified for this notification'));

            default:
                throw new OutOfBoundsException(tr('Unknown mode ":mode" specified for this notification, please ensure it is one of "WARNING", "ERROR", "NOTICE", or "INFO"', [
                    ':mode' => $mode
                ]));
        }

        $this->setDataValue('mode', $clean_mode);
        return $this;
    }



    /**
     * Returns the mode for this notification
     *
     * @return string|null
     */
    public function getMode(): ?string
    {
        return $this->getDataValue('mode');
    }



    /**
     * Returns the users_id for this notification
     *
     * @return int|null
     */
    public function getUsersId(): ?int
    {
        return $this->getDataValue('users_id');
    }



    /**
     * Sets the users_id for this notification
     *
     * @param int $users_id
     * @return static
     */
    public function setUsersId(int $users_id): static
    {
        if (!$users_id) {
            throw new OutOfBoundsException('No users_id specified for this notification');
        }

        $this->setDataValue('users_id', $users_id);
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
     * Returns the icon for this notification
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        $return = $this->getDataValue('icon');

        if (!$return) {
            // Assign default icon
            return match ($this->getMode()) {
                'WARNING', 'DANGER' => 'exclamation-circle',
                'SUCCESS'           => 'check-circle',
                'INFO'              => 'info-circle',
                default             => 'question-circle',
            };
        }

        return $return;
    }



    /**
     * Sets the icon for this notification
     *
     * @param string|null $icon
     * @return static
     */
    public function setIcon(?string $icon): static
    {
        $this->setDataValue('icon', $icon);
        return $this;
    }



    /**
     * Returns the file for this notification
     *
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->getDataValue('file');
    }



    /**
     * Sets the file for this notification
     *
     * @param string|null $file
     * @return static
     */
    public function setFile(?string $file): static
    {
        $this->setDataValue('file', $file);
        return $this;
    }



    /**
     * Returns the line for this notification
     *
     * @return string|null
     */
    public function getLine(): ?string
    {
        return $this->getDataValue('line');
    }



    /**
     * Sets the line for this notification
     *
     * @param string|null $line
     * @return static
     */
    public function setLine(?string $line): static
    {
        $this->setDataValue('line', $line);
        return $this;
    }



    /**
     * Returns the trace for this notification
     *
     * @return string|null
     */
    public function getTrace(): ?string
    {
        return $this->getDataValue('trace');
    }



    /**
     * Sets the trace for this notification
     *
     * @param string|null $trace
     * @return static
     */
    public function setTrace(?string $trace): static
    {
        $this->setDataValue('trace', $trace);
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
        $this->setFile($e->getFile());
        $this->setLine($e->getLine());
        $this->setTrace($e->getTraceAsString());
        $this->setCode('E-' . $e->getCode());
        $this->setTitle(tr('Phoundation encountered an exception'));
        $this->setMessage($e->getMessage());
        $this->addRole('developer');
        $this->setDetails([
            'trace' => $e->getTrace(),
            'data' => (($e instanceof Exception) ? $e->getData() : 'No a Phoundation exception, no data available')
        ]);

        $this->e = $e;
        return $this;
    }



    /**
     * Returns the exception for this notification
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->e;
    }



    /**
     * Returns the roles for this notification
     *
     * @return array|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }



    /**
     * Clears the message for this notification
     *
     * @return static
     */
    public function clearRoles(): static
    {
        $this->roles = [];
        return $this;
    }



    /**
     * Sets the message for this notification
     *
     * @note: This will reset the current already registered roles
     * @param array|string $roles
     * @return static
     */
    public function setRoles(array|string $roles): static
    {
        if (!$roles) {
            throw new OutOfBoundsException('No roles specified for this notification');
        }

        return $this
            ->clearRoles()
            ->addRoles($roles);
    }



    /**
     * Sets the message for this notification
     *
     * @param array|string $roles
     * @return static
     */
    public function addRoles(array|string $roles): static
    {
        if (!$roles) {
            throw new OutOfBoundsException('No roles specified for this notification');
        }

        foreach (Arrays::force($roles) as $role) {
            $this->addRole($role);
        }

        return $this;
    }



    /**
     * Sets the message for this notification
     *
     * @param string|null $role
     * @return static
     */
    public function addRole(?string $role): static
    {
        $role = trim((string) $role);

        if ($role) {
            $this->roles[] = $role;
        }

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
        try {
            static $sending = false;

            if ($sending) {
                throw new NotificationBusyException(tr('The notifications system is already busy sending another notification and cannot send the new ":title" notification with message ":message"', [
                    ':title'   => $this->getTitle(),
                    ':message' => $this->getMessage()
                ]), $this->data);
            }

            $sending = true;

            if ($log === null) {
                $log = static::$auto_log;
            }

            if (!static::$logged and $log) {
                // Automatically log this notification
                static::log();
            }

            if (!$this->getTitle()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no notification title specified'));
            }

            if (!$this->getMessage()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no notification message specified'));
            }

            if (!$this->getRoles()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no notification roles specified'));
            }

            // Save and send this notification to the assigned user
            $this
                ->saveFor($this->getUsersId())
                ->sendTo($this->getUsersId());

            // Save and send this notification to all users that are members of the specified roles
            $roles = Roles::new()->listIds($this->getRoles());

            foreach ($roles as $role) {
                $users = Role::get($role)->users();

                foreach ($users as $user) {
                    $this
                        ->saveFor($user->getId())
                        ->sendTo($user->getId());
                }
            }

            $sending = false;

        } catch (Throwable $e) {
            Log::error(tr('Failed to send the following notification with the following exception'));
            Log::write(tr('Code : ":code"', [':code' => $this->getCode()]), 'debug', 10, false);
            Log::write(tr('Title : ":title"', [':title' => $this->getTitle()]), 'debug', 10, false);
            Log::write(tr('Message : ":message"', [':message' => $this->getMessage()]), 'debug', 10, false);
            Log::write(tr('Details :'), 'debug', 10, false);
            Log::write(print_r($this->getDetails(), true), 'debug', 10, false);
            Log::error($e);
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
        switch ($this->getMode()) {
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

            case 'INFO':
                Log::information($this->getTitle());
                Log::information($this->getMessage());
                Log::information($this->getDetails());
                break;
        }

        static::$logged = true;

        return $this;
    }



    /**
     * Save this notification for the specified user
     *
     * @param User|int|null $user
     * @return $this
     */
    protected function saveFor(User|int|null $user): static
    {
        if (!$user) {
            // No user specified, save nothing
            return $this;
        }

        if (is_object($user)) {
            $user = $user->getId();

            if (!$user) {
                throw new OutOfBoundsException(tr('Cannot save notification for specified user because the user has no users_id'));
            }
        }

        // Set the id to NULL so that the DataEntry will save a new record
        $this
            ->setDataValue('id', null)
            ->setUsersId($user);

        return parent::save();
    }



    /**
     * Send this notification to the specified user
     *
     * @param User|int|null $user
     * @return $this
     */
    protected function sendTo(User|int|null $user): static
    {
        if (!$user) {
            // No user specified, save nothing
            return $this;
        }

        if (is_object($user)) {
            $user = $user->getLogId();
        }

        Log::warning(tr('Not sending notification ":title" to user ":user", sending notifications has not yet been implemented', [
            ':title' => $this->getTitle(),
            ':user'  => $user
        ]));

        return $this;
    }



    /**
     * Sets the available data keys for the Notification class
     *
     * @return void
     */
    protected function setKeys(): void
    {
        $this->keys = [
            'users_id' => [
                'visible' => false
            ],
            'code' => [
                'label'           => tr('Code'),
                'disabled'        => true,
                'display_default' => '-',
            ],
            'mode' => [
                'label'    => tr('Class'),
                'disabled' => true,
            ],
            'icon' => [
                'visible' => false,
            ],
            'priority' => [
                'label'           => tr('Priority'),
                'disabled'        => true,
                'mode'            => 'numeric',
                'default'         => 0,
                'display_default' => 0,
            ],
            'title' => [
                'label'     => tr('Title'),
                'disabled'  => true,
            ],
            'message' => [
                'label'    => tr('Message'),
                'disabled' => true,
                'element'  => 'text',
            ],
            'file' => [
                'label'    => tr('File'),
                'disabled' => true,
            ],
            'url' => [
                'label'    => tr('Url'),
                'disabled' => true,
            ],
            'line' => [
                'label'    => tr('Line'),
                'disabled' => true,
            ],
            'trace' => [
                'label'    => tr('Trace'),
                'disabled' => true,
                'element'  => 'text',
                'rows'     => 10,
            ],
            'details' => [
                'label'    => tr('Details'),
                'disabled' => true,
                'element'  => 'text',
                'rows'     => 10,
            ],
        ];

        $this->keys_display = [
            'code'     => 4,
            'mode'     => 4,
            'priority' => 4,
            'title'    => 12,
            'message'  => 12,
            'file'     => 8,
            'line'     => 4,
            'trace'    => 12,
            'details'  => 12,
       ];

        parent::setKeys();
    }
}