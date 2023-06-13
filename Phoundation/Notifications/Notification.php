<?php

declare(strict_types=1);

namespace Phoundation\Notifications;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Arrays;
use Phoundation\Core\Config;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
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
use Phoundation\Data\Interfaces\DataEntryInterface;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Exception\NotificationBusyException;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Phoundation\Web\Http\Html\Enums\InputType;
use Phoundation\Web\Http\Html\Enums\InputTypeExtended;
use Throwable;

/**
 * Class Notification
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
     * @param DataEntryInterface|string|int|null $identifier
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null)
    {
        static::$auto_log = Config::get('notifications.auto-log', true);
        $this->entry_name = 'notification';

        $this->data['mode']     = 'unknown';
        $this->data['priority'] = 1;

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
    {
        return 'notifications';
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
                throw NotificationBusyException::new(tr('The notifications system is already busy sending another notification and cannot send the new ":title" notification with message ":message"', [
                    ':title'   => $this->getTitle(),
                    ':message' => $this->getMessage()
                ]))->setData($this->data);
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
            case DisplayMode::danger:
                Log::error($this->getTitle());
                Log::error($this->getMessage());
                break;

            case DisplayMode::warning:
                Log::warning($this->getTitle());
                Log::warning($this->getMessage());
                break;

            case DisplayMode::success:
                Log::success($this->getTitle());
                Log::success($this->getMessage());
                break;

            case DisplayMode::info:
                Log::information($this->getTitle());
                Log::information($this->getMessage());
                break;

            default:
                Log::notice($this->getTitle());
                Log::notice($this->getMessage());
                break;
        }

        Log::information(tr('Details'));

        foreach ($this->getDetails() as $key => $value) {
            Log::write($key, 'debug');
            Log::table($value);
            Log::cli();
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
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $field_definitions
     */
    protected function initFieldDefinitions(DefinitionsInterface $field_definitions): void
    {
        $field_definitions
            ->add(Definition::new('users_id')
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->addValidationFunction(function ($validator) {
                    $validator->isId()->isQueryColumn('SELECT `id` FROM `accounts_users` WHERE `id` = :id', [':id' => '$id']);
                }))
            ->add(Definition::new('code')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel(tr('Code'))
                ->setDefault(tr('-'))
                ->setSize(4)
                ->setMaxlength(16)
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(Definition::new('mode')
                ->setReadonly(true)
                ->setLabel(tr('Mode'))
                ->setSize(4)
                ->setMaxlength(16)
                ->addValidationFunction(function ($validator) {
                    $validator->isMode()->isInArray(DisplayMode::cases());
                }))
            ->add(Definition::new('icon')
                ->setVisible(false)
                ->setInputType(InputType::url))
            ->add(Definition::new('priority')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::integer)
                ->setLabel(tr('Priority'))
                ->setDefault(5)
                ->setMin(1)
                ->setMax(9)
                ->setSize(4))
            ->add(Definition::new('title')
                ->setReadonly(true)
                ->setLabel(tr('Title'))
                ->setMaxlength(255)
                ->setSize(4)
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(Definition::new('message')
                ->setReadonly(true)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Message'))
                ->setMaxlength(65_535)
                ->setSize(12)
                ->addValidationFunction(function ($validator) {
                    $validator->isPrintable();
                }))
            ->add(Definition::new('file')
                ->setReadonly(true)
                ->setInputType(InputType::file)
                ->setLabel(tr('File'))
                ->setMaxlength(255)
                ->setSize(8))
            ->add(Definition::new('line')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::natural)
                ->setLabel(tr('File'))
                ->setMin(1)
                ->setSize(4))
            ->add(Definition::new('url')
                ->setReadonly(true)
                ->setInputType(InputType::url)
                ->setLabel(tr('URL'))
                ->setMaxlength(2048)
                ->setSize(12))
            ->add(Definition::new('trace')
                ->setReadonly(true)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Trace'))
                ->setMaxlength(65_535)
                ->setRows(10)
                ->setSize(12)
                ->addValidationFunction(function ($validator) {
                    $validator->isJson();
                }))
            ->add(Definition::new('details')
                ->setReadonly(true)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Details'))
                ->setMaxlength(65_535)
                ->setRows(10)
                ->setSize(12));
    }
}