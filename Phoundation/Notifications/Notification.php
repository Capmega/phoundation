<?php

/**
 * Class Notification
 *
 *
 * @todo      Change the Notification::roles to a Data\Iterator class instead of a plain array
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Notification
 */


declare(strict_types=1);

namespace Phoundation\Notifications;

use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDetails;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryIcon;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryLine;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryMessage;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryMode;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPriority;
use Phoundation\Data\DataEntry\Traits\TraitDataEntrySetCreatedBy;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryTitle;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryTrace;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUrl;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUser;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Exception\NotificationBusyException;
use Phoundation\Notifications\Exception\NotificationsException;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Config;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Url;
use Throwable;


class Notification extends DataEntry implements NotificationInterface
{
    use TraitDataEntryUrl;
    use TraitDataEntryCode;
    use TraitDataEntryIcon;
    use TraitDataEntryMode;
    use TraitDataEntryFile;
    use TraitDataEntryLine;
    use TraitDataEntryPriority;
    use TraitDataEntryUser;
    use TraitDataEntryTitle;
    use TraitDataEntryMessage;
    use TraitDataEntryDetails;
    use TraitDataEntrySetCreatedBy;
    use TraitDataEntryTrace;


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
     * Tracks if this notification has been logged or not
     *
     * @var bool $is_logged
     */
    protected bool $is_logged = false;

    /**
     * Tracks if this notification has been sent or not
     *
     * @var bool $is_sent
     */
    protected bool $is_sent = false;


    /**
     * Notification class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        static::$auto_log = Config::getBoolean('notifications.auto-log', false);

        $this->source['mode']     = 'notice';
        $this->source['priority'] = 1;

//                EnumDisplayMode::warning, EnumDisplayMode::danger => 'exclamation-circle',
//                EnumDisplayMode::success                          => 'check-circle',
//                EnumDisplayMode::info, EnumDisplayMode::notice    => 'info-circle',
//                default                                           => 'question-circle',

        if (!isset($this->meta_columns)) {
            // By default, the Notification object has created_by NOT meta so that it can set it manually
            $this->meta_columns = [
                'id',
                'created_on',
                'meta_id',
                'status',
                'meta_state',
            ];
        }

        parent::__construct($identifier, $meta_enabled, $init);

        if ($this->isNew()) {
            if (Session::isInitialized()) {
                // By default, the object is created by the current user
                $this->setCreatedBy(Session::getUserObject()->getId());
            }
        }
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
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
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Sets the exception for this notification
     *
     * @param Throwable $e
     *
     * @return static
     */
    public function setException(Throwable $e): static
    {
        if ($e instanceof Exception) {
            if ($e->isWarning()) {
                $mode = EnumDisplayMode::warning;

            } else {
                $mode = EnumDisplayMode::exception;
            }

        } else {
            $e    = new Exception($e);
            $mode = EnumDisplayMode::exception;
        }

        $details = Core::getProcessDetails();

        $this->setUrl(Url::getCurrent())
             ->setMode($mode)
             ->setFile($e->getFile())
             ->setLine($e->getLine())
             ->setTrace($e->getTraceAsJson())
             ->setCode('E-' . $e->getCode())
             ->addRole('developer')
             ->setTitle(tr('Phoundation project ":project" encountered an exception', [
                 ':project' => $details['project'],
             ]))
             ->setMessage(tr('<html>
<body>
<pre style="font: monospace">
Phoundation project ":project" encountered the following :class class exception:

Project                : :project
Project version        : :version_project
Database version       : :version_database
Environment            : :environment
Platform               : :platform
:request: :url
Command line arguments : :_argv
Exception location     : :file@:line
Exception class        : :class
Exception code         : :code
Message                : :message

Additional messages:
:all_messages

Trace:
:trace

Data:
:data

User:
:user

Session:
:_session

Environment variables:
:_env

GET variables:
:_get

POST variables:
:_post

FILES variables:
:_files
</pre>
</body>
</html>', [
                 ':url'              =>  (($details['platform'] === 'web') ? '[' . $details['method'] . '] ' . $this->getUrl() : $details['command']),
                 ':request'          =>  (($details['platform'] === 'web') ? Strings::size('Requested URL', 23) : Strings::size('Executed command', 23)),
                 ':file'             =>  Strings::from($e->getFile(), DIRECTORY_ROOT),
                 ':line'             =>  $e->getLine(),
                 ':code'             =>  $e->getCode(),
                 ':class'            =>  get_class($e),
                 ':trace'            =>  $e->getTraceAsFormattedString(),
                 ':message'          =>  $e->getMessage(),
                 ':all_messages'     =>  $e->getMessages() ? Strings::force($e->getMessages(), PHP_EOL) : '-',
                 ':data'             =>  ($e->getData() ? print_r($e->getData(), true) : '-'),
                 ':project'          =>  $details['project'],
                 ':version_project'  =>  $details['project_version'],
                 ':version_database' =>  $details['database_version'],
                 ':environment'      =>  $details['environment'],
                 ':platform'         =>  $details['platform'],
                 ':user'             =>  $details['user'],
                 ':_session'         =>  Json::encode($details['session']),
                 ':_env'             => ($details['environment_variables'] ? print_r($details['environment_variables'], true) : '-'),
                 ':_argv'            =>  $details['argv']  ? Strings::force($details['argv'], ' ') : '-',
                 ':_get'             =>  $details['get']   ? Json::encode($details['get'])         : '-',
                 ':_post'            =>  $details['post']  ? Json::encode($details['post'])        : '-',
                 ':_files'           =>  $details['files'] ? Json::encode($details['files'])       : '-',
             ], clean: false))->e = $e;

        return $this;
    }


    /**
     * Will send this notification to the specified role
     *
     * @param string|null $role
     *
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
     * Returns the exception for this notification
     *
     * @return Throwable|null
     */
    public function getException(): ?Throwable
    {
        return $this->e;
    }


    /**
     * Send the notification
     *
     * @param bool|null $log
     *
     * @return static
     * @todo Implement!
     */
    public function send(?bool $log = null): static
    {
        try {
            static $sending = false;

            if ($sending) {
                // Avoid endless looping!
                throw NotificationBusyException::new(tr('Cannot send notification ":title" notification with message ":message", the notifications system is already busy sending another notification', [
                    ':title'   => $this->getTitle(),
                    ':message' => $this->getMessage(),
                ]))->addData($this->source);
            }

            if (!sql(connect: false)->isConnected()) {
                throw new NotificationsException(tr('Cannot send notification ":title" notification with message ":message", the system database is not available', [
                    ':title'   => $this->getTitle(),
                    ':message' => $this->getMessage(),
                ]));
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

            if (!$this->getRolesObject() and !$this->getUsersId()) {
                $sending = false;
                throw new OutOfBoundsException(tr('Cannot send notification, no roles or target users id specified'));
            }

            // Save and send this notification to the assigned user
            if ($this->getUsersId()) {
                $this->saveFor($this->getUsersId())
                     ->sendTo($this->getUsersId());
            }

            // Save and send this notification to all users that are members of the specified roles
            foreach ($this->getRolesObject() as $role) {
                $users = Role::load($role)
                             ->getUsersObject();
                foreach ($users as $user) {
                    try {
                        $this->saveFor($user->getId())
                             ->sendTo($user->getId());

                    } catch (Throwable $e) {
                        Log::error(tr('Failed to save notification for user ":user" because of the following exception', [
                            ':user' => $user->getId(),
                        ]));

                        Log::error($e);
                    }
                }
            }

            $sending = false;

        } catch (Throwable $e) {
            Log::error(tr('Failed to send the following notification with the following exception'));
            Log::write(tr('Code    : ":code"', [':code' => $this->getCode()]), 'debug', 10, false);
            Log::write(tr('Title   : ":title"', [':title' => $this->getTitle()]), 'debug', 10, false);
            Log::write(tr('Message : ":message"', [':message' => $this->getMessage()]), 'debug', 10, false);
            Log::write(tr('Data :'), 'debug', 10, false);

            try {
                $details = $this->getDetails();

                if ($details) {
                    Log::write(print_r($details, true), 'debug', 10, false);
                }

            } catch (Throwable $f) {
                Log::error(tr('Failed to display notifications detail due to the following exception. Details following after exception'));
                Log::error($f);
                Log::write(print_r($this->getTypesafe('string', 'details'), true), 'debug', 10, false);
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

        // Remove HTML from the message for logging
        $message = $this->getMessage();
        $message = strip_tags($message);
        $message = trim($message);

        switch ($this->getMode()) {
            case EnumDisplayMode::danger:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'error', echo_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($message, 'error', clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::warning:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'warning', echo_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($message, 'warning', clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::success:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'success', echo_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($message, 'success', clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::info:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'information', echo_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($message, 'information', clean: false, echo_prefix: false);
                break;

            default:
                Log::write(Strings::size('Title', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'notice', echo_prefix: false);
                Log::write(Strings::size('Message', 12) . ': ', 'debug', clean: false, echo_newline: false);
                Log::write($message, 'notice', clean: false, echo_prefix: false);
                break;
        }

        $details = $this->getDetails();

        if ($details) {
            Log::write(Strings::size('Details', 12) . ': ', 'debug', clean: false);

            foreach (Arrays::force($details) as $key => $value) {
                if (is_scalar($value)) {
                    Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false, echo_newline: false);
                    Log::write(Strings::log($value), echo_prefix: false);

                } else {
                    switch ($key) {
                        case 'trace':
                            Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false);
                            Log::backtrace(backtrace: $value);
                            break;

                        default:
                            Log::write(Strings::size(Strings::capitalize($key), 12) . ': ', 'debug', clean: false, echo_newline: false);
                            Log::printr($value, echo_prefix: false, echo_header: false);
                    }
                }
            }
        }

        Log::information(tr('End notification'));
        static::$logged = true;

        return $this;
    }


    /**
     * Returns the roles for this notification
     *
     * @return array
     */
    public function getRolesObject(): array
    {
        return $this->roles;
    }


    /**
     * Sets the message for this notification
     *
     * @note: This will reset the current already registered roles
     *
     * @param IteratorInterface|array|string|int $roles
     *
     * @return static
     */
    public function setRoles(IteratorInterface|array|string|int $roles): static
    {
        if (!$roles) {
            throw new OutOfBoundsException('No roles specified for this notification');
        }

        return $this->clearRoles()
                    ->addRoles($roles);
    }


    /**
     * Will send this notification to the specified roles
     *
     * @param IteratorInterface|array|string|int $roles
     *
     * @return static
     */
    public function addRoles(IteratorInterface|array|string|int $roles): static
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
     * Send this notification to the specified user
     *
     * @param UserInterface|int|null $user
     *
     * @return static
     */
    protected function sendTo(UserInterface|int|null $user): static
    {
        if (!$user) {
            // No user specified, save nothing
            return $this;
        }

        $user = User::load($user);

        if (!Core::isProductionEnvironment()) {
            if ($user->hasAllRights('developer,test,admin')) {
                // We're not in production environment, don't send any notifications!
                Log::warning(tr('Not sending notification ":title" to user ":user" because we are not in production environment', [
                    ':title' => $this->getTitle(),
                    ':user'  => $user->getEmail()
                ]));

                return $this;
            }
        }

        Pho::new()
           ->setPhoCommands('email send')
           ->addArgument('-h')
           ->addArguments(['-t', $user->getEmail()])
           ->addArguments(['-s', $this->getTitle()])
           ->addArguments(['-b', $this->getMessage()])
           ->executeBackground();

        return $this;
    }


    /**
     * Save this notification for the specified user
     *
     * @param UserInterface|int|null $user
     *
     * @return static
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
        $this->set(null, 'id')
             ->setUsersId($user);

        // Notifications are stored in the system database. Do we have the system database available?
        if (sql()->isConnected()) {
            return parent::save();
        }

        // Nope, no system DB!
        if ($this->is_logged) {
            // Log the entire notification
            Log::error(tr('Not saving next notification, the system database is not available', [
                ':id' => $this->getId()
            ]));

            $this->log();

        } else {
            // Notification was already logged, don't log again
            Log::error(tr('Not saving previous notification ":title", there is no system database available', [
                ':title' => $this->getTitle()
            ]));
        }

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newCreatedBy($this))

                    ->add(Definition::new($this, 'users_id')
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::dbid)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDbId()
                                                  ->isQueryResult('SELECT `id`
                                                                   FROM   `accounts_users`
                                                                   WHERE  `id` = :id', [':id' => '$users_id']);
                                    }))

                    ->add(Definition::new($this, 'code')
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

                    ->add(Definition::new($this, 'mode')
                                    ->setLabel(tr('Mode'))
                                    ->setReadonly(true)
                                    ->setOptional(true, EnumDisplayMode::notice)
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setMaxlength(16)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDisplayMode();
                                    }))

                    ->add(Definition::new($this, 'icon')
                                    ->setRender(false)
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::url))

                    ->add(Definition::new($this, 'priority')
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::integer)
                                    ->setLabel(tr('Priority'))
                                    ->setDefault(5)
                                    ->addClasses('text-center')
                                    ->setMin(1)
                                    ->setMax(9)
                                    ->setSize(3))

                    ->add(Definition::new($this, 'title')
                                    ->setReadonly(true)
                                    ->setLabel(tr('Title'))
                                    ->setMaxlength(255)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                    }))

                    ->add(Definition::new($this, 'message')
                                    ->setReadonly(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Message'))
                                    ->setMaxlength(65_535)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                    }))

                    ->add(Definition::new($this, 'url')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::url)
                                    ->setLabel(tr('URL'))
                                    ->setMaxlength(2048)
                                    ->setSize(12))

                    ->add(Definition::new($this, 'details')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Details'))
                                    ->setMaxlength(65_535)
                                    ->setRows(10)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isJson();
                                    })
                                    ->setDisplayCallback(function (mixed $value, array $source) {
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

                    ->add(Definition::new($this, 'file')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel(tr('File'))
                                    ->setMaxlength(255)
                                    ->setSize(8))

                    ->add(Definition::new($this, 'line')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::natural)
                                    ->setLabel(tr('Line'))
                                    ->setMin(1)
                                    ->setSize(4))

                    ->add(Definition::new($this, 'trace')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Trace'))
                                    ->setMaxlength(65_535)
                                    ->setRows(10)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isJson();
                                    }))

                    ->get('status')->setDefault('UNREAD');
    }


    /**
     * Returns true if this notification has been logged
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        return $this->is_logged;
    }


    /**
     * Returns true if this notification has been sent to its recipients
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->is_sent;
    }
}
