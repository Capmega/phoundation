<?php

namespace Phoundation\Notifications;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Traits\DataEntryCode;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryFile;
use Phoundation\Data\DataEntry\Traits\DataEntryIcon;
use Phoundation\Data\DataEntry\Traits\DataEntryLine;
use Phoundation\Data\DataEntry\Traits\DataEntryMessage;
use Phoundation\Data\DataEntry\Traits\DataEntryMode;
use Phoundation\Data\DataEntry\Traits\DataEntryPriority;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryTrace;
use Phoundation\Data\DataEntry\Traits\DataEntryUrl;
use Phoundation\Data\DataEntry\Traits\DataEntryUsersId;
use Phoundation\Data\Interfaces\InterfaceDataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Exception\NotificationBusyException;
use Throwable;


/**
 * Class Notification
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notification
 */
class Notification extends DataEntry
{
    use DataEntryUrl;
    use DataEntryCode;
    use DataEntryIcon;
    use DataEntryMode;
    use DataEntryFile;
    use DataEntryLine;
    use DataEntryPriority;
    use DataEntryUsersId;
    use DataEntryTitle;
    use DataEntryMessage;
    use DataEntryDetails;
    use DataEntryTrace;


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


    /**
     * Notification class constructor
     *
     * @param InterfaceDataEntry|string|int|null $identifier
     */
    public function __construct(InterfaceDataEntry|string|int|null $identifier = null)
    {
        static::$auto_log   = Config::get('notifications.auto-log', true);
        static::$entry_name = 'notification';
        $this->table        = 'notifications';

        $this->data['mode']     = 'unknown';
        $this->data['priority'] = 1;

        parent::__construct($identifier);
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

            if (!$this->getRoles() and !$this->getUsersId()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no notification roles or target users id specified'));
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
     * Validates the provider record with the specified validator object
     *
     * @param ArgvValidator|PostValidator|GetValidator $validator
     * @param bool $no_arguments_left
     * @param bool $modify
     * @return array
     */
    protected function validate(ArgvValidator|PostValidator|GetValidator $validator, bool $no_arguments_left = false, bool $modify = false): array
    {
        $data = $validator
            ->select($this->getAlternateValidationField('users_id'), true)->isId()->isQueryColumn('SELECT `id` FROM `accounts_users` WHERE `id` = :id', [':id' => '$id'])
            ->select($this->getAlternateValidationField('code'), true)->isOptional('-')->hasMaxCharacters(16)->isPrintable()
            ->select($this->getAlternateValidationField('mode'), true)->isMode()
            ->select($this->getAlternateValidationField('icon'), true)->isUrl()
            ->select($this->getAlternateValidationField('title'), true)->hasMaxCharacters(255)->isPrintable()
            ->select($this->getAlternateValidationField('message'), true)->isOptional()->hasMaxCharacters(65_530)->isPrintable()
            ->select($this->getAlternateValidationField('details'), true)->isOptional()->hasMaxCharacters(65_530)
            ->select($this->getAlternateValidationField('priority'), true)->isOptional(0)->isMoreThan(1, true)->isLessThan(9, true)
            ->select($this->getAlternateValidationField('url'), true)->isOptional()->isUrl()
            ->select($this->getAlternateValidationField('file'), true)->isOptional()->isFile()
            ->select($this->getAlternateValidationField('line'), true)->isOptional()->isPositive()
            ->select($this->getAlternateValidationField('trace'), true)->isOptional()->hasMaxCharacters(65_530)->isJson()
            ->noArgumentsLeft($no_arguments_left)
            ->validate();

        // Ensure the name doesn't exist yet as it is a unique identifier
        if ($data['name']) {
            static::notExists($data['name'], $this->getId(), true);
        }

        return $data;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        return [
            'users_id' => [
                'visible' => false
            ],
            'code' => [
                'readonly'   => true,
                'size'       => 4,
                'maxlength'  => 16,
                'label'      => tr('Code'),
                'default'    => '-',
            ],
            'mode' => [
                'readonly'  => true,
                'size'      => 4,
                'maxlength' => 16,
                'label'     => tr('Mode'),
            ],
            'icon' => [
                'visible' => false,
            ],
            'priority' => [
                'readonly'   => true,
                'type'       => 'numeric',
                'size'       => 4,
                'label'      => tr('Priority'),
                'default'    => 0,
                'min'        => 1,
                'max'        => 9,
            ],
            'title' => [
                'readonly'  => true,
                'size'      => 4,
                'label'     => tr('Title'),
                'maxlength' => 255,
            ],
            'message' => [
                'readonly'  => true,
                'element'   => 'text',
                'size'      => 12,
                'label'     => tr('Message'),
                'maxlength' => 65535,
            ],
            'file' => [
                'readonly'  => true,
                'size'      => 8,
                'label'     => tr('File'),
                'maxlength' => 255,
            ],
            'line' => [
                'readonly' => true,
                'type'     => 'numeric',
                'size'     => 4,
                'label'    => tr('Line'),
            ],
            'url' => [
                'readonly'  => true,
                'size'      => 12,
                'label'     => tr('Url'),
                'maxlength' => 2048,
            ],
            'trace' => [
                'readonly'  => true,
                'element'   => 'text',
                'size'      => 12,
                'label'     => tr('Trace'),
                'rows'      => 10,
                'maxlength' => 65535,
            ],
            'details' => [
                'readonly'  => true,
                'element'   => 'text',
                'size'      => 12,
                'label'     => tr('Details'),
                'rows'      => 10,
                'maxlength' => 65535,
            ],
        ];
    }
}