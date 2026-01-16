<?php

/**
 * Class Notification
 *
 *
 * @todo      Change the Notification::roles to a Data\Iterator class instead of a plain array
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Notification
 */


declare(strict_types=1);

namespace Phoundation\Notifications;

use Phoundation\Accounts\Roles\Exception\RoleNotExistsException;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Core;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCreatedBy;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDetails;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryIcon;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLine;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMessage;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPriority;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTitle;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTrace;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUser;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataOverrideNonProductionLockout;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Developer\Debug\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\PhoException;
use Phoundation\Notifications\Exception\NotificationBusyException;
use Phoundation\Notifications\Exception\NotificationsException;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Os\Processes\Commands\Pho;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Arrays;
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
    use TraitDataEntryCreatedBy;
    use TraitDataEntryTrace;
    use TraitDataOverrideNonProductionLockout;


    /**
     * Keeps track of if this notification was logged or not
     *
     * @var bool
     */
    protected static bool $logged = false;

    /**
     * Keeps track of if notifications should abe automatically logged or not
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
     * @param Throwable|IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(Throwable|IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->initializeVirtualConfiguration([
            'users' => ['id'],
        ]);

        static::$auto_log = config()->getBoolean('notifications.auto-log', false);

        // By default, the Notification object has created_by NOT meta so that it can set it manually
        $this->meta_columns = [
            'id',
            'created_on',
            'meta_id',
            'status',
            'meta_state',
        ];

//                EnumDisplayMode::warning, EnumDisplayMode::danger => 'exclamation-circle',
//                EnumDisplayMode::success                          => 'check-circle',
//                EnumDisplayMode::info, EnumDisplayMode::notice    => 'info-circle',
//                default                                           => 'question-circle',

        if ($identifier instanceof Throwable) {
            parent::__construct();
            $this->setException($identifier);

        } else {
            parent::__construct($identifier);
        }

        if ($this->isNew()) {
            if (Session::isInitialized()) {
                // By default, the object is created by the current user
                $this->setCreatedBy(Session::getUserObject()->getId())
                     ->ready();
            }
        }

        $this->setMode(EnumDisplayMode::notice)
             ->setPriority(1);
    }


    /**
     * Returns a new Notification object
     *
     * @param Throwable|IdentifierInterface|array|string|int|false|null $identifier
     *
     * @return static
     */
    public static function new(Throwable|IdentifierInterface|array|string|int|false|null $identifier = false): static
    {
        return new static($identifier);
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
    public static function getEntryName(): string
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
     * Returns id for this database entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        return $this->getTypesafe('int', static::getIdColumn()) . ' / ' . $this->getTitle();
    }


    /**
     * Sets the exception for this notification
     *
     * @param Throwable|null $exception
     *
     * @return static
     */
    public function setException(?Throwable $exception): static
    {
        if ($exception instanceof PhoException) {
            if ($exception->isWarning()) {
                $mode = EnumDisplayMode::warning;

            } else {
                $mode = EnumDisplayMode::exception;
            }

        } else {
            $e    = new PhoException($exception);
            $mode = EnumDisplayMode::exception;
        }

        $details = Core::getProcessDetails();

        $this->setUrl(Url::newCurrent())
             ->setMode($mode)
             ->setFile($exception->getFile())
             ->setLine($exception->getLine())
             ->setTrace($exception->getTrace())
             ->setCode('E-' . $exception->getCode())
             ->addRole('developer')
             ->setTitle(tr('Phoundation project ":project (:environment)" encountered an exception', [
                 ':project'     => $details['project'],
                 ':environment' => ENVIRONMENT,
             ]))
             ->setMessage($this->generateExceptionHtmlMessage($exception, $details))
             ->e = $exception;

        return $this;
    }


    /**
     * Generates an HTML message for the given exception
     *
     * @param Throwable $e
     * @param array     $details
     *
     * @return string
     */
    protected function generateExceptionHtmlMessage(Throwable $e, array $details): string
    {
        return tr('<html>
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
:exception

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
            ':class'            =>   get_class($e),
            ':url'              => (($details['platform'] === 'web') ? '[' . $details['method'] . '] ' . $this->getUrl() : $details['command']),
            ':request'          => (($details['platform'] === 'web') ? Strings::size('Requested URL', 23) : Strings::size('Executed command', 23)),
            ':exception'        =>   $this->generateExceptionSection($e, tr('Exception:')),
            ':project'          =>   $details['project'],
            ':version_project'  =>   $details['project_version'],
            ':version_database' =>   $details['database_version'],
            ':environment'      =>   $details['environment'],
            ':platform'         =>   $details['platform'],
            ':user'             =>   $details['user'],
            ':_session'         =>   Json::encode($details['session']),
            ':_env'             =>   $details['environment_variables'] ? print_r($details['environment_variables'], true) : '-',
            ':_argv'            =>   $details['argv']                  ? Strings::force($details['argv'], ' ')            : '-',
            ':_get'             =>   $details['get']                   ? Json::encode($details['get'])                    : '-',
            ':_post'            =>   $details['post']                  ? Json::encode($details['post'])                   : '-',
            ':_files'           =>   $details['files']                 ? Json::encode($details['files'])                  : '-',
        ], clean: false);
    }


    /**
     * Generates a message section for the given exception
     *
     * @param Throwable $e
     * @param string    $title
     * @param int       $indent
     *
     * @return string
     */
    protected function generateExceptionSection(Throwable $e, string $title, int $indent = 0): string
    {
        $indent_string = str_repeat(' ', $indent);

        // Fetch data from exception, be it either a PHP exception or Phoundation exception
        if ($e instanceof PhoException) {
            $data     = ($e->getData() ? print_r($e->getData(), true) : '-');
            $trace    = $e->getTraceAsFormattedString($indent + 4);
            $messages = $e->getMessages() ? Strings::force($e->getMessages(), PHP_EOL) : '-';

        } else {
            $data     = '-';
            $trace    = implode(PHP_EOL, Debug::formatBackTrace((array) $this->getTrace(), $indent));
            $messages = '-';
        }

        return PHP_EOL . $indent_string . $title . PHP_EOL . tr(':indent    Exception location     : :file@:line
:indent    Exception class        : :class
:indent    Exception code         : :code
:indent    Message                : :message

:indent    Additional exception messages:
:indent    :all_messages

:indent    Trace:
:trace

:indent    Exception data:
:indent    :data :additional', [
                ':indent'       =>  $indent_string,
                ':file'         =>  Strings::from($e->getFile(), DIRECTORY_ROOT),
                ':line'         =>  $e->getLine(),
                ':code'         =>  $e->getCode(),
                ':class'        =>  get_class($e),
                ':trace'        =>  $trace,
                ':message'      =>  $e->getMessage(),
                ':all_messages' =>  $messages,
                ':data'         =>  $data,
                ':additional'   => ($e->getPrevious() ? PHP_EOL . $this->generateExceptionSection($e->getPrevious(), tr('Previous exception:'), $indent + 4) : null)
        ], clean: false);
    }


    /**
     * Set the status for this Notification
     *
     * @param string|null $status
     * @param string|null $comments
     * @param bool        $auto_save
     *
     * @return static
     */
    public function setStatus(?string $status, ?string $comments = null, bool $auto_save = true): static
    {
        // Setting Notification status is only allowed when not impersonating
        if (!Session::isImpersonated()) {
            return parent::setStatus($status, $comments, $auto_save);
        }

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
            if ($log === null) {
                $log = static::$auto_log;
            }

            if (!static::$logged and $log) {
                // Automatically log this notification
                static::log();
            }

            if (!$this->getTitle()) {
                throw new OutOfBoundsException(tr('Cannot send notification, no title specified'));
            }

            if (!$this->getMessage()) {
                throw new OutOfBoundsException(tr('Cannot send notification, no message specified'));
            }

            if (!$this->getRolesObject() and !$this->getUsersId()) {
                throw new OutOfBoundsException(tr('Cannot send notification, no roles or target users id specified'));
            }

            // Save and send this notification to the assigned user
            if ($this->getUsersId()) {
                $this->saveFor($this->getUsersId())
                     ->sendTo($this->getUsersId());
            }

            // Save and send this notification to all users that are members of the specified roles
            foreach ($this->getRolesObject() as $role) {
                try {
                    $users = Role::new()->load($role)->getUsersObject()->load();

                    foreach ($users as $user) {
                        try {
                            $this->saveFor($user->getId())
                                 ->sendTo($user->getId());

                        } catch (Throwable $e) {
                            Log::error(ts('Failed to save notification for user ":user" because of the following exception', [
                                ':user' => $user->getId(),
                            ]));

                            Log::error($e);
                        }
                    }

                } catch (RoleNotExistsException $e) {
                    Incident::new()
                            ->setException($e)
                            ->setTitle(tr('Role does not exist'))
                            ->setBody(tr('Will not send notification ":notification" to role ":role" because the role does not exist', [
                                ':role'         => $role,
                                ':notification' => $this->getLogId(),
                            ]))
                            ->setNotifyRoles(Role::exists(['seo_name' => 'developer']) ? 'developer' : null)
                            ->save(true);
                }
            }

        } catch (Throwable $e) {
            Log::error(ts('Failed to send the following notification with the following exception'));
            Log::write(ts('Code    : ":code"'   , [':code'    => $this->getCode()])   , 'debug', 10, false);
            Log::write(ts('Title   : ":title"'  , [':title'   => $this->getTitle()])  , 'debug', 10, false);
            Log::write(ts('Message : ":message"', [':message' => $this->getMessage()]), 'debug', 10, false);
            Log::write(ts('Data    :'), 'debug', 10, false);

            try {
                $details = $this->getDetails();

                if ($details) {
                    Log::write(var_export($details, true), 'debug', 10, false, true, false);
                }

            } catch (Throwable $f) {
                Log::error(ts('Failed to display notifications detail due to the following exception. Details following after exception'));
                Log::error($f);
                Log::write(print_r($this->getTypesafe('string', 'details'), true), 'debug', 10, false, true, false);
            }

            Log::error(ts('Notification sending exception:'));
            Log::error($e);
        }

        return $this;
    }


    /**
     * Log this notification to the system logs as well
     *
     * @param bool $log
     *
     * @return static
     */
    public function log(int|bool $log = true): static
    {
        if ($log === false) {
            // We are asked not to log
            return $this;
        }

        if ($log === true) {
            $log = 8;
        }

        Log::information(ts('Notification:'));

        // Remove HTML from the message for logging
        $message = (string) $this->getMessage();
        $message = strip_tags($message);
        $message = get_null(trim($message)) ?? 'N/A';

        switch ($this->getMode()) {
            case EnumDisplayMode::danger:
                Log::write(Strings::size('Type', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getMode()->value, 'error', $log, echo_prefix: false);
                Log::write(Strings::size('Title', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'error', $log, echo_prefix: false);
                Log::write(Strings::size('Message', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($message, 'error', $log, clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::warning:
                Log::write(Strings::size('Type', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getMode()->value, 'warning', $log, echo_prefix: false);
                Log::write(Strings::size('Title', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'warning', $log, echo_prefix: false);
                Log::write(Strings::size('Message', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($message, 'warning', $log, clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::success:
                Log::write(Strings::size('Type', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getMode()->value, 'success', $log, echo_prefix: false);
                Log::write(Strings::size('Title', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'success', $log, echo_prefix: false);
                Log::write(Strings::size('Message', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($message, 'success', $log, clean: false, echo_prefix: false);
                break;

            case EnumDisplayMode::info:
                Log::write(Strings::size('Type', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getMode()->value, 'information', $log, echo_prefix: false);
                Log::write(Strings::size('Title', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'information', $log, echo_prefix: false);
                Log::write(Strings::size('Message', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($message, 'information', $log, clean: false, echo_prefix: false);
                break;

            default:
                Log::write(Strings::size('Type', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write(get_null($this->getMode()->value) ?? tr('No mode'), 'notice', $log, echo_prefix: false);
                Log::write(Strings::size('Title', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($this->getTitle(), 'notice', $log, echo_prefix: false);
                Log::write(Strings::size('Message', 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                Log::write($message, 'notice', $log, clean: false, echo_prefix: false);
                break;
        }

        $details = $this->getDetails();

        if ($details) {
            Log::write(Strings::size('Details', 10) . ': ', 'debug', $log, clean: false);

            foreach (Arrays::force($details, null) as $key => $value) {
                switch ($key) {
                    case 'trace':
                        Log::write(Strings::size(Strings::capitalize((string) $key), 10) . ': ', 'debug', $log, clean: false);
                        Log::backtrace(backtrace: $value, threshold: $log);
                        break;

                    case 'exception':
                        if (!$value instanceof Throwable) {
                            $value = PhoException::newFromSource($value);
                        }

                        Log::exception($value);
                        break;

                    default:
                        Log::write(Strings::size(Strings::capitalize((string) $key), 10) . ': ', 'debug', $log, clean: false, echo_newline: false);
                        Log::printr($value, $log, echo_prefix: false, echo_header: false);
                }
            }
        }

        Log::information(ts('End notification'));
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
        static $sending = false;

        if (!$user) {
            // No user specified, save nothing
            return $this;
        }

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
        $user    = User::new()->load($user);

        if (!config()->getBoolean('notifications.send.enabled', true) and !$this->override_non_production_lockout) {
            // We're not in production environment, don't send any notifications!
            Log::warning(ts('Not sending notification ":title" to user ":user" because notifications sending has been disabled', [
                ':title' => $this->getTitle(),
                ':user'  => $user->getEmail()
            ]), 3);

            $sending = false;
            return $this;
        }

        if ($user->getEmail()) {
            $message = $this->getMessage();

            if ($this->getDetails()) {
                $message .= PHP_EOL . PHP_EOL . tr('Details:') . PHP_EOL . print_r($this->getDetails(), true);
                $message  = '<pre>' . $message . '</pre>';
            }

            Pho::new()
               ->setPhoCommands('email send')
               ->addArgument('-h')
               ->addArguments(['-t', $this->getOverrideEmail() ?? $user->getEmail()])
               ->addArguments(['-s', $this->getTitle()])
               ->addArguments(['-b', $message])
               ->executeBackground();

        } else {
            // WTF? This user has no email address? Is it a system user?
            Incident::new()
                    ->setSeverity(EnumSeverity::high)
                    ->setNotifyRoles('developer,security')
                    ->setTitle(tr('Notification for user without email address'))
                    ->setBody(tr('An attempt was made to send a notification to user ":user" but the user has no email address', [
                        ':user' => $user->getLogId()
                    ]))
                    ->setData([
                        'user' => $user->getSource()
                    ])
                    ->save();
        }

        $sending = false;
        return $this;
    }


    /**
     * Returns a configured email address that (if configured) will always be used for all notifications
     *
     * @return string|null
     */
    public function getOverrideEmail(): ?string
    {
        return get_null(config()->getString('notifications.send.override.email', ''));
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
            Log::error(ts('Not saving next notification, the system database is not available', [
                ':id' => $this->getId()
            ]));

            $this->log();

        } else {
            // Notification was already logged, don't log again
            Log::error(ts('Not saving previous notification ":title", there is no system database available', [
                ':title' => $this->getTitle()
            ]));
        }

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return Notification
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newCreatedBy())

                      ->add(Definition::new('users_id')
                                      ->setRender(false)
                                      ->setInputType(EnumInputType::dbid)
                                      ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                          $o_validator->isDbId()
                                                      ->isQueryResult('SELECT `id`
                                                                       FROM   `accounts_users`
                                                                       WHERE  `id` = :id
                                                                       AND   (`status` IS NULL OR `status` != "deleted")', [
                                                                           ':id' => '$users_id'
                                                      ]);
                                    }))

                    ->add(Definition::new('code')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setLabel(tr('Code'))
                                    ->setDefault(tr('-'))
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setMaxlength(16)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isPrintable();
                                    }))

                    ->add(Definition::new('mode')
                                    ->setLabel(tr('Mode'))
                                    ->setReadonly(true)
                                    ->setOptional(true, EnumDisplayMode::notice->value)
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setMaxLength(16)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isDisplayMode();
                                    }))

                    ->add(Definition::new('icon')
                                    ->setRender(false)
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::url))

                    ->add(Definition::new('priority')
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::integer)
                                    ->setLabel(tr('Priority'))
                                    ->setDefault(5)
                                    ->addClasses('text-center')
                                    ->setMin(1, true)
                                    ->setMax(9)
                                    ->setSize(3))

                    ->add(Definition::new('title')
                                    ->setReadonly(true)
                                    ->setLabel(tr('Title'))
                                    ->setMaxlength(255)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isDescription();
                                    }))

                    ->add(Definition::new('message')
                                    ->setReadonly(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Message'))
                                    ->setMaxlength(16_777_215)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        // TODO ADD MORE VALIDATIONS HERE?!
                                        $o_validator->isPrintable()->setContentTestDone();
                                    }))

                    ->add(Definition::new('url')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::url)
                                    ->setLabel(tr('URL'))
                                    ->setMaxlength(2048)
                                    ->setSize(12))

                    ->add(Definition::new('details')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Details'))
                                    ->setMaxlength(16_777_215)
                                    ->setRows(10)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isJson();
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
                                                if (!is_scalar($value)) {
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

                    ->add(Definition::new('file')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::text)
                                    ->setLabel(tr('File'))
                                    ->setMaxlength(255)
                                    ->setSize(8))

                    ->add(Definition::new('line')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setInputType(EnumInputType::natural)
                                    ->setLabel(tr('Line'))
                                    ->setMin(1)
                                    ->setSize(4))

                    ->add(Definition::new('trace')
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setRender(false)
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Trace'))
                                    ->setMaxlength(65_535)
                                    ->setRows(10)
                                    ->setSize(12)
                                    ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                        $o_validator->isJson();
                                    }));

        $o_definitions->get('status')->setDefault('UNREAD');
        $o_definitions->get('created_by')->setSize(3);

        return $this;
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
