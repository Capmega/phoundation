<?php

declare(strict_types=1);

namespace Phoundation\Notifications;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
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
use Phoundation\Data\DataEntry\Traits\DataEntryUser;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Exception\NotificationBusyException;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\DisplayMode;
use Phoundation\Web\Html\Enums\InputElement;
use Phoundation\Web\Html\Enums\InputType;
use Phoundation\Web\Html\Enums\InputTypeExtended;
use Throwable;


/**
 * Class Notification
 *
 *
 * @todo Change the Notification::roles to a Data\Iterator class instead of a plain array
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Notification
 */
class Notification extends DataEntry implements NotificationInterface
{
    use DataEntryUrl;
    use DataEntryCode;
    use DataEntryIcon;
    use DataEntryMode;
    use DataEntryFile;
    use DataEntryLine;
    use DataEntryPriority;
    use DataEntryUser;
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
     * @var array $roles
     */
    protected array $roles = [];

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
     * @param string|null $column
     * @param bool $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, bool $meta_enabled = true)
    {
        static::$auto_log = Config::getBoolean('notifications.auto-log', false);

        $this->source['mode']     = 'notice';
        $this->source['priority'] = 1;

        parent::__construct($identifier, $column, $meta_enabled);
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
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'notification';
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueField(): ?string
    {
        return null;
    }


    /**
     * Sets the exception for this notification
     *
     * @param Throwable $e
     * @return static
     */
    public function setException(Throwable $e): static
    {
        if ($e instanceof Exception) {
            if ($e->isWarning()) {
                $mode = DisplayMode::warning;
            } else {
                $mode = DisplayMode::exception;
            }

        } else {
            $mode = DisplayMode::exception;
        }

        $this
            ->setUrl('/development/incidents.html')
            ->setMode($mode)
            ->setFile($e->getFile())
            ->setLine($e->getLine())
            ->setTrace($e->getTraceAsString())
            ->setCode('E-' . $e->getCode())
            ->setTitle(tr('Phoundation encountered an exception'))
            ->setMessage($e->getMessage())
            ->add('developer')
            ->setDetails([
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
     * @return array
     */
    public function getRoles(): array
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
     * @param IteratorInterface|array|string $roles
     * @return static
     */
    public function setRoles(IteratorInterface|array|string $roles): static
    {
        if (!$roles) {
            throw new OutOfBoundsException('No roles specified for this notification');
        }

        return $this
            ->clearRoles()
            ->addRoles($roles);
    }


    /**
     * Will send this notification to the specified roles
     *
     * @param IteratorInterface|array|string $roles
     * @return static
     */
    public function addRoles(IteratorInterface|array|string $roles): static
    {
        if (!$roles) {
            throw new OutOfBoundsException('No roles specified for this notification');
        }

        foreach (Arrays::force($roles) as $role) {
            $this->add($role);
        }

        return $this;
    }


    /**
     * Will send this notification to the specified role
     *
     * @param string|null $role
     * @return static
     */
    public function add(?string $role): static
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
                ]))->addData($this->source);
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
                throw new OutOfBoundsException(tr('Cannot send notification, no title specified'));
            }

            if (!$this->getMessage()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no message specified'));
            }

            if (!$this->getRoles() and !$this->getUsersId()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no roles or target users id specified'));
            }

            // Save and send this notification to the assigned user
            if ($this->getUsersId()) {
                $this
                    ->saveFor($this->getUsersId())
                    ->sendTo($this->getUsersId());
            }

            // Save and send this notification to all users that are members of the specified roles
            foreach ($this->getRoles() as $role) {
                $users = Role::get($role)->getUsers();

                foreach ($users as $user) {
                    try {
                        $this
                            ->saveFor($user->getId())
                            ->sendTo($user->getId());

                    } catch (Throwable $e) {
                        Log::error(tr('Failed to save notification for user ":user" because of the following exception', [
                            ':user' => $user->getId()
                        ]));
                        Log::error($e);
                    }
                }
            }

            $sending = false;

        } catch (Throwable $e) {
            Log::error(tr('Failed to send the following notification with the following exception'));
            Log::write(tr('Code    : ":code"'   , [':code' => $this->getCode()])      , 'debug', 10, false);
            Log::write(tr('Title   : ":title"'  , [':title' => $this->getTitle()])    , 'debug', 10, false);
            Log::write(tr('Message : ":message"', [':message' => $this->getMessage()]), 'debug', 10, false);
            Log::write(tr('Details :')                                                , 'debug', 10, false);

            try {
                Log::write(print_r($this->getDetails(), true), 'debug', 10, false);

            } catch (Throwable $f) {
                Log::error(tr('Failed to display notifications detail due to the following exception. Details following after exception'));
                Log::error($f);

                Log::write(print_r($this->getSourceFieldValue('string', 'details'), true), 'debug', 10, false);
            }

            Log::error(tr('Notification sending exception:'));
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
        Log::information(tr('Notification:'));

        switch ($this->getMode()) {
            case DisplayMode::danger:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, newline: false);
                Log::error($this->getTitle(), use_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, newline: false);
                Log::error($this->getMessage(), use_prefix: false);
                break;

            case DisplayMode::warning:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, newline: false);
                Log::warning($this->getTitle(), use_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, newline: false);
                Log::warning($this->getMessage(), use_prefix: false);
                break;

            case DisplayMode::success:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, newline: false);
                Log::success($this->getTitle(), use_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, newline: false);
                Log::success($this->getMessage(), use_prefix: false);
                break;

            case DisplayMode::info:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, newline: false);
                Log::information($this->getTitle(), use_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, newline: false);
                Log::information($this->getMessage(), use_prefix: false);
                break;

            default:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, newline: false);
                Log::notice($this->getTitle(), use_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, newline: false);
                Log::notice($this->getMessage(), use_prefix: false);
                break;
        }

        $details = $this->getDetails();

        if ($details) {
            Log::write(Strings::size('Details', 12) . ': ', 'debug', clean: false);

            foreach (Arrays::force($details) as $key => $value) {
                if (is_scalar($value)) {
                    Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false, newline: false);
                    Log::write(Strings::log($value), use_prefix: false);

                } else {
                    switch ($key) {
                        case 'trace':
                            Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false);
                            Log::backtrace(backtrace: $value);
                            break;

                        default:
                            Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false, newline: false);
                            Log::printr($value, echo_header: false, use_prefix: false);
                    }
                }
            }
        }

        Log::information(tr('End notification'));

        static::$logged = true;

        return $this;
    }


    /**
     * Save this notification for the specified user
     *
     * @param UserInterface|int|null $user
     * @return $this
     */
    protected function saveFor(UserInterface|int|null $user): static
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
            ->setSourceValue('id', null)
            ->setUsersId($user);

        return parent::save();
    }


    /**
     * Send this notification to the specified user
     *
     * @param UserInterface|int|null $user
     * @return $this
     */
    protected function sendTo(UserInterface|int|null $user): static
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
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'users_id')
                ->setVisible(false)
                ->setInputType(InputTypeExtended::dbid)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isDbId()->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id', [':id' => '$users_id']);
                }))
            ->addDefinition(Definition::new($this, 'code')
                ->setOptional(true)
                ->setReadonly(true)
                ->setLabel(tr('Code'))
                ->setDefault(tr('-'))
                ->addClasses('text-center')
                ->setSize(6)
                ->setMaxlength(16)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isPrintable();
                }))
            ->addDefinition(Definition::new($this, 'mode')
                ->setLabel(tr('Mode'))
                ->setReadonly(true)
                ->setOptional(true, DisplayMode::notice)
                ->addClasses('text-center')
                ->setSize(3)
                ->setMaxlength(16)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isDisplayMode();
                }))
            ->addDefinition(Definition::new($this, 'icon')
                ->setVisible(false)
                ->setOptional(true)
                ->setInputType(InputType::url))
            ->addDefinition(Definition::new($this, 'priority')
                ->setReadonly(true)
                ->setInputType(InputTypeExtended::integer)
                ->setLabel(tr('Priority'))
                ->setDefault(5)
                ->addClasses('text-center')
                ->setMin(1)
                ->setMax(9)
                ->setSize(3))
            ->addDefinition(Definition::new($this, 'title')
                ->setReadonly(true)
                ->setLabel(tr('Title'))
                ->setMaxlength(255)
                ->setSize(12)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isPrintable();
                }))
            ->addDefinition(Definition::new($this, 'message')
                ->setReadonly(true)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Message'))
                ->setMaxlength(65_535)
                ->setSize(12)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isPrintable();
                }))
            ->addDefinition(Definition::new($this, 'url')
                ->setReadonly(true)
                ->setOptional(true)
                ->setInputType(InputType::url)
                ->setLabel(tr('URL'))
                ->setMaxlength(2048)
                ->setSize(12))
            ->addDefinition(Definition::new($this, 'details')
                ->setReadonly(true)
                ->setOptional(true)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Details'))
                ->setMaxlength(65_535)
                ->setRows(10)
                ->setSize(12)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isJson();
                })
                ->setDisplayCallback(function(mixed $value, array $source) {
                    // Since the details almost always have an array encoded in JSON, decode it and display it using
                    // print_r
                    if (!$value) {
                        return null;
                    }

                    try {
                        $return  = '';
                        $details = Json::decode($value);
                        $largest = Arrays::getLongestKeyLength($details);

                        foreach ($details as $key => $value) {
                            if ($value and !is_scalar($value)) {
                                $value = print_r($value, true);
                            }

                            $return .= Strings::size($key, $largest) . ' : ' . $value . PHP_EOL;
                        }

                        return $return;

                    } catch (JsonException) {
                        // Likely this wasn't JSON encoded
                        return $value;
                    }
                }))
            ->addDefinition(Definition::new($this, 'file')
                ->setReadonly(true)
                ->setOptional(true)
                ->setVisible(false)
                ->setInputType(InputType::text)
                ->setLabel(tr('File'))
                ->setMaxlength(255)
                ->setSize(8))
            ->addDefinition(Definition::new($this, 'line')
                ->setReadonly(true)
                ->setOptional(true)
                ->setVisible(false)
                ->setInputType(InputTypeExtended::natural)
                ->setLabel(tr('Line'))
                ->setMin(1)
                ->setSize(4))
            ->addDefinition(Definition::new($this, 'trace')
                ->setReadonly(true)
                ->setOptional(true)
                ->setVisible(false)
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Trace'))
                ->setMaxlength(65_535)
                ->setRows(10)
                ->setSize(12)
                ->addValidationFunction(function (ValidatorInterface $validator) {
                    $validator->isJson();
                }))
            ->get('status')->setDefault('UNREAD');
    }
}
