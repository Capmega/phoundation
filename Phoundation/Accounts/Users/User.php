<?php

/**
 * Class User
 *
 * This is the default user class.
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use DateTimeInterface;
use Phoundation\Accounts\Enums\EnumAuthenticationAction;
use Phoundation\Accounts\Exception\AccountsException;
use Phoundation\Accounts\Rights\Interfaces\RightsInterface;
use Phoundation\Accounts\Rights\RightsBySeoName;
use Phoundation\Accounts\Roles\Interfaces\RoleInterface;
use Phoundation\Accounts\Roles\Interfaces\RolesInterface;
use Phoundation\Accounts\Roles\Role;
use Phoundation\Accounts\Roles\Roles;
use Phoundation\Accounts\Roles\RolesBySeoName;
use Phoundation\Accounts\Users\Configuration\Configurations;
use Phoundation\Accounts\Users\Configuration\Interfaces\ConfigurationsInterface;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\UsersException;
use Phoundation\Accounts\Users\Interfaces\AuthenticationInterface;
use Phoundation\Accounts\Users\Interfaces\EmailsInterface;
use Phoundation\Accounts\Users\Interfaces\PasswordInterface;
use Phoundation\Accounts\Users\Interfaces\PhonesInterface;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImageInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImagesInterface;
use Phoundation\Accounts\Users\ProfileImages\ProfileImage;
use Phoundation\Accounts\Users\ProfileImages\ProfileImages;
use Phoundation\Accounts\Users\Sessions\Exception\SessionException;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionInterface;
use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Accounts\Users\Sessions\Sessions;
use Phoundation\Core\Core;
use Phoundation\Core\Hooks\Hook;
use Phoundation\Core\Hooks\Interfaces\HookInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Exception\DataEntryNotExistsException;
use Phoundation\Data\DataEntries\Exception\DataEntryNotSavedException;
use Phoundation\Data\DataEntries\Exception\DataEntryReadonlyException;
use Phoundation\Data\DataEntries\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryAddress;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryComments;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryData;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDomain;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryEmail;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryFirstNames;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryGeo;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLanguage;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryLastNames;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMfaCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryMfaTimeslice;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPhone;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryProfilePictureFile;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTimezone;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryTitle;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryType;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUrl;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerificationCode;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryVerifiedOn;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Databases\Sql\Exception\SqlMultipleResultsException;
use Phoundation\Databases\Sql\QueryBuilder\QueryBuilder;
use Phoundation\Date\PhoDateTime;
use Phoundation\Exception\NotExistsException;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Exception\UnderConstructionException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Notifications\Interfaces\NotificationInterface;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Security\Passwords\Exception\PasswordNotChangedException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Seo;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Forms\DataEntryForm;
use Phoundation\Web\Html\Components\Forms\Interfaces\DataEntryFormInterface;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumInputType;
use Phoundation\Web\Http\Domains;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Json\Users;
use Plugins\Phoundation\MultiFactorAuthentication\Interfaces\MultiFactorAuthenticationInterface;
use Plugins\Phoundation\MultiFactorAuthentication\MultiFactorAuthentication;
use Stringable;
use Throwable;


class User extends DataEntry implements UserInterface
{
    use TraitDataEntryAddress;
    use TraitDataEntryCode;
    use TraitDataEntryComments;
    use TraitDataEntryData;
    use TraitDataEntryDescription;
    use TraitDataEntryDomain;
    use TraitDataEntryEmail;
    use TraitDataEntryFirstNames;
    use TraitDataEntryGeo;
    use TraitDataEntryPhone;
    use TraitDataEntryProfilePictureFile;
    use TraitDataEntryLanguage;
    use TraitDataEntryLastNames;
    use TraitDataEntryTimezone;
    use TraitDataEntryTitle;
    use TraitDataEntryType;
    use TraitDataEntryUrl;
    use TraitDataEntryVerificationCode;
    use TraitDataEntryVerifiedOn;
    use TraitDataEntryMfaCode;
    use TraitDataEntryMfaTimeslice;


    /**
     * The extra email addresses for this user
     *
     * @var EmailsInterface $emails
     */
    protected EmailsInterface $emails;

    /**
     * The extra phones for this user
     *
     * @var PhonesInterface $phones
     */
    protected PhonesInterface $phones;

    /**
     * The roles for this user
     *
     * @var RolesInterface $roles
     */
    protected RolesInterface $roles;

    /**
     * The rights for this user
     *
     * @var RightsInterface $rights
     */
    protected RightsInterface $rights;

    /**
     * Columns that will NOT be inserted
     *
     * @var array $columns_filter_on_insert
     */
    protected array $columns_filter_on_insert = [
        'id',
        'password',
    ];

    /**
     * User from a different authentication system
     *
     * @var UserInterface|null $remote_user
     */
    protected ?UserInterface $remote_user = null;

    /**
     * Tracks the available profile images for this user
     *
     * @var ProfileImagesInterface $profile_images
     */
    protected ProfileImagesInterface $profile_images;

    /**
     * Sets if this User object may receive notifications or if it can simply ignore them.
     *
     * @var bool $notifications_enabled
     */
    protected bool $notifications_enabled = true;


    /**
     * DataEntry class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->initializeVirtualConfiguration([
            'countries' => ['id', 'code', 'name'],
            'states'    => ['id', 'code', 'name'],
            'cities'    => ['id', 'name'],
            'timezones' => ['id', 'name'],
        ]);

        if (empty($this->protected_columns)) {
            $this->protected_columns = [
                'password',
                'key',
            ];
        }

        // Process system users. Possible identifiers for system users are "system" or "guest"
        switch ($identifier) {
            case 'guest':
                $this->initGuestUser();
                break;

            case 'system':
                $this->initSystemUser();
                break;

            default:
                parent::__construct($identifier);
        }
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('User');
    }


    /**
     * Returns a new "guest" static object
     *
     * @return static
     */
    public static function newGuest(): static
    {
        return new static('guest');
    }


    /**
     * Returns a new "system" static object
     *
     * @return static
     */
    public static function newSystem(): static
    {
        return new static('system');
    }


    /**
     * Returns true if this user object is the guest user
     *
     * @return bool
     */
    public function isGuest(): bool
    {
        return array_get_safe($this->source, 'email') === 'guest';
    }


    /**
     * Returns true if this user object is the system user
     *
     * @return bool
     */
    public function isSystem(): bool
    {
        return (array_get_safe($this->source, 'email') === 'system');
    }


    /**
     * Returns true if this user object is the system user
     *
     * @return bool
     */
    public function isSystemUser(): bool
    {
        return $this->isGuest() or $this->isSystem();
    }


    /**
     * Returns a single user object for a single user that has the specified role.
     *
     * @note Will throw a NotExistsException if the specified role does not exist
     *
     * @param RoleInterface|array|string|int $role
     *
     * @return UserInterface
     * @throws SqlMultipleResultsException, NotExistsException
     */
    public static function newForRole(RoleInterface|array|string|int $role): UserInterface
    {
throw new UnderConstructionException('User::newForRole(): This would VERY likely return multiple users!');
        $role     = Role::new()->load($role);
        $users_id = sql()->getColumn('SELECT `accounts_users`.`id`
                                      FROM   `accounts_users`
                                      JOIN   `accounts_users_roles`
                                        ON   `accounts_users_roles`.`users_id` = `accounts_users`.`id`
                                      WHERE  `accounts_users_roles`.`roles_id` = :roles_id
                                        AND  `accounts_users_roles`.`status`   IS NULL', [
                                            ':roles_id' => $role->getId(),
        ]);

        if (empty($users_id)) {
            throw new NotExistsException(tr('No user exists that has the role ":role"', [
                ':role' => $role,
            ]));
        }

        return static::new()->load($users_id);
    }


    /**
     * Returns a single user object for a single user that has the specified alternate email address.
     *
     * @param IdentifierInterface|array|string|int|null $identifier
     * @param EnumLoadParameters|null                   $on_load_null_identifier
     * @param EnumLoadParameters|null                   $on_load_not_exists
     *
     * @return static|null
     */
    public function load(IdentifierInterface|array|string|int|null $identifier = null, ?EnumLoadParameters $on_load_null_identifier = null, ?EnumLoadParameters $on_load_not_exists = null): ?static
    {
        try {
            $user = parent::load($identifier, $on_load_null_identifier, $on_load_not_exists);

        } catch (DataEntryNotExistsException $e) {
            if ($this->identifier === ['email' => 'guest']) {
                // Keep throwing the exception, the "guest" user will automatically initialize
                throw $e;
            }

            $user = $this->loadFromAlternativeEmail();

            if (!$user) {
                // The requested user identifier doesn't exist
                throw $e;
            }
        }

        return $user;
    }


    /**
     * Will attempt to load the user by the alternative email
     *
     * @return static|null
     */
    protected function loadFromAlternativeEmail(): ?static
    {
        if (static::determineColumn($this->identifier) === 'email') {
            if ((static::getDefaultConnector() === 'system') and (static::getTable() === 'accounts_users')) {
                // Try to find the user by alternative email address
                $user = sql()->getRow('SELECT `users_id`, `verified_on`
                                    FROM   `accounts_emails` 
                                    WHERE  `email` = :email 
                                      AND  `status` IS NULL', [
                    ':email' => $this->identifier['email'],
                ]);

                if ($user) {
                    if ($user['verified_on'] or !config()->getBoolean('security.accounts.identify.email.verification.required', true)) {
                        $user = static::new()->setMetaEnabled($this->meta_enabled)
                                             ->setIgnoreDeleted($this->ignore_deleted)
                                             ->load($user['users_id']);

                        Log::warning(ts('Identified user ":user" with alternate email ":email"', [
                            ':user'  => $user->getLogId(),
                            ':email' => $this->identifier,
                        ]));

                        return $user;
                    }

                    Log::warning(ts('Cannot identify user ":user" on alternate email, the email does not have the required verification', [
                        ':user' => $this->identifier,
                    ]));
                }
            }
        }

        // Could not load user from alternative email address
        return null;
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_users';
    }


    /**
     * Returns id for this database entry
     *
     * @param bool $exception
     *
     * @return int|null
     * @throws DataEntryNotSavedException
     */
    public function getId(bool $exception = true): int|null
    {
        if ($this->isSystem()) {
            // System user always returns NULL
            return null;
        }

        return parent::getId($exception);
    }


    /**
     * Returns id for this user entry that can be used in logs
     *
     * @return string
     */
    public function getLogId(): string
    {
        if ($this->hasStatus('system')) {
            // This is a system type user, either system or guest
            return Strings::log(($this->getId(false)) ?? tr('N/A')) . ' / ' . $this->getNickname();
        }

        $id    = $this->getTypesafe('int'       , $this->getIdColumn());
        $label = $this->getTypesafe('string|int', static::getUniqueColumn() ?? 'id');

        if ($label == $id) {
            return Strings::log($id);
        }

        return Strings::log($id . ' / ' . $label);
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return 'email';
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param array                    $identifier
     * @param string                   $password
     * @param EnumAuthenticationAction $action
     * @param string|null              $domain
     *
     * @return UserInterface
     * @throws Throwable
     */
    public static function authenticate(array $identifier, string $password, EnumAuthenticationAction $action, ?string $domain = null): UserInterface
    {
        $hook           = Hook::new('phoundation/accounts/authentication');
        $authentication = Authentication::new()
                                        ->setAccount($identifier)
                                        ->setAction($action);

        // Try authentication through hook
        $user = $hook->execute('authenticate', [
            'identifier'     => $identifier,
            'password'       => $password,
            'domain'         => $domain,
            'action'         => $action,
            'authentication' => $authentication,
        ]);

        return static::processHookAuthentication($hook, $user, $authentication);
    }


    /**
     * Process the results from the authentication hook
     *
     * @param HookInterface           $hook
     * @param UserInterface|null      $user
     * @param AuthenticationInterface $authentication
     *
     * @return UserInterface
     */
    protected static function processHookAuthentication(HookInterface $hook, ?UserInterface $user, AuthenticationInterface $authentication): UserInterface
    {
        if (empty($user)) {
            if ($hook->exists('authenticate')) {
                $authentication->setStatus('no-hook-data')->save();

                Incident::new()
                        ->setSeverity(EnumSeverity::high)
                        ->setType('hook')
                        ->setTitle('Authentication hook returned no data')
                        ->setBody(tr('Cannot perform user authentication, hook script ":hook" returned no data', [
                            ':hook' => $hook->getFile('authenticate')->getRootname(),
                        ]))
                        ->setDetails([
                            'account' => $hook->getArgument('identifier'),
                            'hook'    => $hook->__toArray()
                        ])
                        ->setNotifyRoles('accounts')
                        ->save()
                        ->throw(OutOfBoundsException::class);
            }

            Log::warning(ts('Authentication hook ":hook" does not exist, attempting default internal authentication instead', [
                ':hook' => $hook->getFile('authenticate')->getRootname(),
            ]));

            // The hook file doesn't exist, try internal authentication
            $user = User::doAuthenticate($hook->getArgument('identifier'), $hook->getArgument('password'), $hook->getArgument('authentication'), $hook->getArgument('domain'));

        } elseif (!$user instanceof UserInterface) {
            $authentication->setStatus('bad-hook-data')->save();

            throw new OutOfBoundsException(tr('Cannot perform user authentication, the hook script ":hook" returned a non UserInterface value ":value"', [
                ':hook'  => $hook->getFile('authenticate')->getRootname(),
                ':value' => $user,
            ]));
        }

        // Check user status, only NULL is allowed!
        if ($user->getStatus()) {
            $authentication->setStatus('locked')->save();

            Incident::new()
                    ->setCreatedBy($user->getId())
                    ->setSeverity(EnumSeverity::high)
                    ->setType('User attempted to authenticate locked account')
                    ->setBody(tr('Cannot authenticate user ":user", the user has the status ":status" which is not allowed', [
                        ':user'   => $user->getLogId(),
                        ':status' => $user->getStatus(),
                    ]))
                    ->setDetails(['user' => $user->getLogId()])
                    ->setNotifyRoles('accounts')
                    ->save()
                    ->throw(AuthenticationException::class);
        }

        $authentication->setCreatedBy($user->getId())->save();

        Log::warning(ts('Authenticated user ":user" with account authentication hook ":hook"', [
            ':user' => $user->getLogId(),
            ':hook' => $hook->getFile('authenticate')->getRootname(),
        ]));

        return $user;
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param array                    $identifier
     * @param string                   $password
     * @param EnumAuthenticationAction $action
     * @param string|null              $domain
     *
     * @return UserInterface
     */
    public static function authenticateInternal(array $identifier, string $password, EnumAuthenticationAction $action, ?string $domain = null): UserInterface
    {
        $authentication = Authentication::new()
                                        ->setAction($action)
                                        ->setAccount($identifier);

        return static::doAuthenticate($identifier, $password, $authentication, $domain);
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param array                   $identifier
     * @param string                  $password
     * @param AuthenticationInterface $authentication
     * @param string|null             $domain
     * @param bool                    $test
     *
     * @return static
     */
    protected static function doAuthenticate(array $identifier, string $password, AuthenticationInterface $authentication, ?string $domain, bool $test = false): static
    {
        try {
            $user = static::new()->load($identifier);

            if ($user->passwordMatch($password)) {
                static::doAuthenticateDomain($identifier, $user, $authentication, $domain, $test);

                Hook::new('phoundation/accounts/authentication')
                    ->execute('success', [
                        'user'     => $user,
                        'password' => $password
                    ]);

                return $user;
            }

        } catch (DataEntryNotExistsException $e) {
            $authentication->setStatus('user-not-exist')->save();

            Incident::new()
                    ->setType('security')
                    ->setTitle('User does not exist')
                    ->setSeverity(EnumSeverity::low)
                    ->setBody(tr('Cannot perform ":action" on user ":user", the user does not exist', [
                        ':action' => $authentication->getAction(),
                        ':user'   => Json::encode($identifier, JSON_OBJECT_AS_ARRAY),
                    ]))
                    ->setDetails([
                        'user'               => $identifier,
                        'remote_ip'          => Session::getIpAddress(),
                        'original_remote_ip' => Session::getOriginalIpAddress()
                    ])
                    ->setNotifyRoles('accounts')
                    ->save()
                    ->throw(AuthenticationException::class);
        }

        if ($test) {
            throw AuthenticationException::new(tr('The specified password for user ":user" is incorrect', [
                ':user' => $user->getLogId()
            ]))
            ->setData(['user' => $user->getLogId()])
            ->setStatusFilter('password-incorrect');
        }

        // When not just testing the authentication, execute the failure hook and register an incident
        Hook::new('phoundation/accounts/authentication')
            ->execute('failure', [
                'status'   => 'password-incorrect',
                'user'     => $user,
                'password' => $password
        ]);

        // NOTE: Non User::class objects likely authenticate against different databases and as such, those users will
        // have non-existing user ids which can't be used for "created_by" column
        $authentication->setCreatedBy(($user::class === User::class) ? $user->getId() : null)
                       ->setStatus('password-incorrect')
                       ->save();

        Incident::new()
                ->setSeverity(EnumSeverity::low)
                ->setType('security')
                ->setTitle('Incorrect password for account detected')
                ->setBody(tr('Cannot perform ":action" user ":user", the specified password is incorrect', [
                    ':action' => $authentication->getAction(),
                    ':user'   => $user->getLogId(),
                ]))
                ->setDetails(['user' => $user->getLogId()])
                ->setNotifyRoles('accounts')
                ->save()
                ->throw(AuthenticationException::class);
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param EnumAuthenticationAction $action
     * @param string|null              $domain
     *
     * @return static
     */
    public function authenticateDomain(EnumAuthenticationAction $action, ?string $domain = null): static
    {
        if ($this->isGuest()) {
            throw new AuthenticationException(tr('Cannot authenticate on a domain, the current user is a guest'));
        }

        $identifier       = ['email' => $this->getEmail()];
        $o_authentication = Authentication::new()
                                          ->setAction($action)
                                          ->setAccount(Json::encode($identifier, JSON_OBJECT_AS_ARRAY));

        static::doAuthenticateDomain($identifier, $this, $o_authentication, $domain);
        return $this;
    }


    /**
     * Checks if the user is allowed to authenticate on the current domain
     *
     * @param array                   $identifier
     * @param UserInterface           $user
     * @param AuthenticationInterface $authentication
     * @param string|null             $domain
     * @param bool                    $test
     *
     * @return bool
     */
    protected static function doAuthenticateDomain(array $identifier, UserInterface $user, AuthenticationInterface $authentication, ?string $domain, bool $test = false): bool
    {
        if ($user->getDomain()) {
            // User is limited to a domain!
            if (!$domain) {
                $domain = Domains::getCurrent();
            }

            // Trim spaces but also dots as domain MAY have dots
            $domain       = trim($domain, '. \n\r\t\v\0');
            $user_domains = Arrays::force($user->getDomain());

            foreach ($user_domains as $user_domain) {
                // Trim spaces but also dots as domain MAY have dots
                $user_domain = trim($user_domain, '. \n\r\t\v\0');

                if ($user_domain === $domain) {
                    return true;
                }
            }

            if (!$test) {
                $authentication->setStatus('domain-not-allowed')
                               ->save();

                Incident::new()
                        ->setCreatedBy($user->getId())
                        ->setSeverity(EnumSeverity::medium)
                        ->setType('security')
                        ->setTitle('Domain access disallowed')
                        ->setBody(tr('The user ":user" is not allowed to have access to domain ":domain"', [
                            ':user'   => $user->getLogId(),
                            ':domain' => $domain,
                        ]))
                        ->setDetails([
                            'user'         => $user,
                            'user_domains' => $user_domains,
                            'domain'       => $domain,
                        ])
                        ->setNotifyRoles('accounts')
                        ->save();
            }

            throw new AuthenticationException(tr('The specified user ":user" is not allowed to access the domain ":domain"', [
                ':user'   => $identifier,
                ':domain' => $domain,
            ]));
        }

        return true;
    }


    /**
     * Returns true if the specified password matches the user's password
     *
     * @param string $password
     *
     * @return bool
     */
    public function passwordMatch(string $password): bool
    {
        if ($this->isNew()) {
            Core::delayFromInput($password);
            throw new OutOfBoundsException(tr('Cannot match passwords, this user has not yet been saved in the database'));
        }

        return Password::match($this->source[static::getIdColumn()], $password, (string) $this->source['password']);
    }


    /**
     * Update the MFA code (and optionally the timeslice) for this user
     *
     * @param string   $code
     * @param int|null $timeslice
     *
     * @return static
     */
    public function updateMfaCode(string $code, ?int $timeslice): static
    {
        Log::action(ts('Updating MFA for user ":user"', [':user' => $this->getDisplayName()]));

        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }


        $this->setMfaCode($code)
             ->setMfaTimeslice($timeslice);

        parent::save();

        $this->notify()?->setTitle(tr('The multi-factor authentication for your account has been updated'))
                        ->setMessage(tr('The multi-factor authentication for your account :account on the website :website has been updated. If this was not you, please contact the administrator at :email.', [
                            ':account' => Session::getUserObject()->getEmail(),
                            ':website' => config()->getString('project.name' , 'Phoundation'),
                            ':email'   => config()->getString('project.email', 'webmaster@' . Domains::getPrimaryWeb()),
                        ]))
                        ->save()
                        ->send();

        return $this;
    }


    /**
     * Update only the MFA timeslice for this user
     *
     * @param int|null $timeslice
     *
     * @return static
     */
    public function updateMfaTimeslice(?int $timeslice): static
    {
        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        $this->setMfaTimeslice($timeslice);
        return parent::save();
    }


    /**
     * Save the user to database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        Log::action(ts('Saving user ":user"', [':user' => $this->getDisplayName()]));

        if ($this->readonly or $this->disabled) {
            throw new DataEntryReadonlyException(tr('Cannot save this ":name" object, the object is readonly or disabled', [
                ':name' => static::getEntryName(),
            ]));
        }

        // Can this information be changed? If this user has god right, the executing user MUST have god right as well!
        if (!$this->isNew() and $this->hasAllRights('god')) {
            if (PLATFORM_WEB and !Session::getUserObject()->hasAllRights('god')) {
                // Oops...
                Incident::new()
                        ->setSeverity(EnumSeverity::severe)
                        ->setType('security')
                        ->setTitle('Blocked user update')
                        ->setBody(tr('The user ":user" attempted to modify god level user ":modify" without having the "god" right itself.', [
                            ':modify' => $this->getLogId(),
                            ':user'   => Session::getUserObject()
                                                ->getLogId(),
                        ]))
                        ->setDetails([
                            ':modify' => $this->getLogId(),
                            ':user'   => Session::getUserObject()
                                                ->getSource(),
                        ])
                        ->setNotifyRoles('accounts')
                        ->save()
                        ->throw();
            }
        }

        parent::save($force, $skip_validation, $comments);

        if ($this->isSaved()) {
            // Do we need to apply default rights?
            // Send out Account write notifications, but not during init states.
            $this->applyDefaultRoles()
                 ->notifyUserAboutWrite()
                 ->notifyRoleAccountsAboutWrite();
        }

        // Save was successful! If we're saving the current user, then update the session
        if (Session::iSpecificUser($this)) {
            if (!$this->isSystemUser()) {
                Log::action(ts('Current session user ":user" changed in database, refreshing session user data', [
                    ':user' => $this->getLogId(),
                ]));
                Session::reloadUser();
            }
        }

        return $this;
    }


    /**
     * Ensures that a new user has a number of default rights that apply to all users
     *
     * @return static
     */
    protected function applyDefaultRoles(): static
    {
        if ($this->isCreated()) {
            if ($this->isSystemUser()) {
                // Don't add roles to these users
                return $this;
            }

            // Get user default roles and the user roles object
            $roles   = config()->getArray('security.accounts.roles.default', []);
            $o_roles = $this->getRolesObject();

            if ($roles) {
                // Add all default roles one by one
                foreach (Arrays::force($roles) as $role) {
                    try {
                        $o_roles->add(Role::new()->load($role));

                    } catch (DataEntryNotExistsException $e) {
                        // Oh noes! This default role does not exist!
                        Incident::new()
                                ->setException($e)
                                ->setType('security')
                                ->setTitle(tr('Invalid default role'))
                                ->setBody(tr('The configured default role ":role" could not be added to user ":user" because it does not exist', [
                                    ':role' => $role,
                                    ':user' => $this->getLogId(),
                                ]))
                                ->setNotifyRoles('developer,operations')
                                ->save();
                    }
                }

                // Write the default roles to the database
                $o_roles->save();
            }
        }

        return $this;
    }


    /**
     * Notifies the "accounts" role about a change in this user
     *
     * @return static
     */
    protected function notifyRoleAccountsAboutWrite(): static
    {
        if (Core::inInitState()) {
            // Don't notify for actions executed during INIT state
            return $this;
        }

        if ($this->isCreated()) {
            // An administrator created a new user
            Incident::new()
                    ->setSeverity(EnumSeverity::low)
                    ->setType('security')
                    ->setTitle(tr('User created'))
                    ->setBody(tr('The administrator ":admin" created the user ":user"', [
                        ':admin' => Session::getUserObject()->getLogId(),
                        ':user'  => $this->getLogId(),
                    ]))
                    ->setDetails(['user' => $this->getLogId()])
                    ->setNotifyRoles('accounts')
                    ->save();

            return $this;
        }

        if (Session::getUserObject()->getId() === $this->getId()) {
            // A user updated its own account
            Incident::new()
                    ->setSeverity(EnumSeverity::low)
                    ->setType('security')
                    ->setTitle(tr('User modified'))
                    ->setBody(tr('The user ":user" modified their own account, see audit ":meta_id" for more information', [
                        ':user'    => $this->getLogId(),
                        ':meta_id' => $this->getMetaId(),
                    ]))
                    ->setDetails(['user' => $this->getLogId()])
                    ->setNotifyRoles('accounts')
                    ->save();

            return $this;
        }

        // An administrator updated a user
        Incident::new()
                ->setSeverity(EnumSeverity::low)
                ->setType('security')
                ->setTitle(tr('User modified'))
                ->setBody(tr('The administrator ":admin" modified the user ":user", see audit ":meta_id" for more information', [
                    ':admin'   => Session::getUserObject()->getLogId(),
                    ':user'    => $this->getLogId(),
                    ':meta_id' => $this->getMetaId(),
                ]))
                ->setDetails(['user' => $this->getLogId()])
                ->setNotifyRoles('accounts')
                ->save();

        return $this;
    }


    /**
     * Notifies the user that either this account was created or modified
     *
     * @return static
     */
    protected function notifyUserAboutWrite(): static
    {
        if (!$this->notifications_enabled) {
            return $this;
        }

        if ($this->isSystemUser()) {
            // Yeah, we don't notify the system users
            return $this;
        }

        if ($this->isCreated()) {
            // Notify the user that their account was created, accompanied by a login link
            $key = $this->getSigninKey()
                        ->generate(Url::new('/force-password-update.html')
                                      ->makeWww());

            $this->notify()
                 ?->setTitle(tr('An account has been created for you on :project', [
                     ':project' => config()->getString('project.name', 'Phoundation')
                 ]))
                 ->setMessage(tr('An account has been created on :project by :user. To enter the system, you can click the link :link or copy/paste the :url in your browser. This will immediately take you to your account where you only have to enter your desired password', [
                     ':url'     => $key->getUrl(),
                     ':link'    => '<a href="' . $key->getUrl() . '">' . tr('here') . '</a>',
                     ':user'    => Session::getUserObject()
                                          ->getDisplayName(),
                     ':project' => config()->getString('project.name', 'Phoundation'),
                 ]))
                 ->save()
                 ->send();

            return $this;
        }

        // Notify user that their account was modified
        if (Session::getUserObject()->getId() === $this->getId()) {
            $message = tr('Your account has been updated by you from IP address :ip. If you did not make this change, please notify your IT department immediately', [
                ':ip' => Session::getIpAddress(),
            ]);

        } else {
            $message = tr('Your account has been updated by :user. If this was unexpected, please contact this person to ensure your account is still safe.', [
                ':user' => Session::getUserObject()->getDisplayName(),
            ]);
        }

        $this->notify()?->setTitle(tr('Your :project account has been modified', [
                            ':project' => config()->getString('project.name', 'Phoundation')
                        ]))
                        ->setMessage($message)
                        ->save()
                        ->send();

        return $this;
    }


    /**
     * Returns the initials for this user
     *
     * @param bool $official
     *
     * @return string|null
     */
    public function getInitials(bool $official = false): ?string
    {
        // Nickname is NOT allowed for official information
        if ($this->getNickname()){
            if ($official) {
                return substr($this->getFirstNames(), 0, 1) . substr($this->getLastNames(), 0, 1);
            }

            return substr($this->getNickname(), 0, 2);
        }

        if (trim($this->getFirstNames() . ' ' . $this->getLastNames())) {
            return substr($this->getFirstNames(), 0, 1) . substr($this->getLastNames(), 0, 1);
        }

        if ($this->getUsername()) {
            return substr($this->getUsername(), 0, 2);
        }

        if ($this->getEmail()) {
            $user   = Strings::until($this->getEmail(), '@');
            $domain = Strings::from($this->getEmail(), '@');
            return substr($user, 0, 1) . substr($domain, 0, 1);
        }

        if (!($name = $this->getId(false))) {
            if ($this->getId(false) === -1) {
                // This is the guest user
                $name = tr('G');

            } else {
                // This is a new user
                $name = tr('[N]');
            }
        }

        return $name;
    }


    /**
     * Returns the name for this user that can be displayed
     *
     * @param bool $official
     * @param bool $clean
     * @param bool $reverse
     *
     * @return string|null
     */
    public function getDisplayName(bool $official = false, bool $clean = false, bool $reverse = false): ?string
    {
        if ($clean) {
            $postfix = null;

        } else {
            $postfix = match ($this->getStatus()) {
                'deleted' => ' ' . tr('[DELETED]'),
                'locked'  => ' ' . tr('[LOCKED]'),
                default   => null
            };
        }

        if (!($name = $this->getNickname()) or $official) {
            // Nickname is NOT allowed for official information
            if ($reverse) {
                $name = $this->getLastNames() . ', ' . $this->getFirstNames();

            } else {
                $name = $this->getFirstNames() . ' ' . $this->getLastNames();
            }

            $name = trim($name);

            if (!($name)) {
                if (!($name = $this->getUsername())) {
                    if (!($name = $this->getEmail())) {
                        if (!($name = $this->getId(false))) {
                            if ($this->getId(false) === -1) {
                                // This is the guest user
                                $name = tr('Guest');

                            } else {
                                // This is a new user
                                $name = tr('[NEW]');
                            }
                        }
                    }
                }

            } elseif ($this->getTitle()) {
                $name = $this->getTitle() . ' ' . $name;
            }
        }

        return $name . $postfix;
    }


    /**
     * Returns the nickname for this user
     *
     * @return string|null
     */
    public function getNickname(): ?string
    {
        return $this->getTypesafe('string', 'nickname');
    }


    /**
     * Returns the username for this user
     *
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->getTypesafe('string', 'username');
    }


    /**
     * Returns true if the user has SOME of the specified rights
     *
     * @param array|string $rights
     *
     * @return bool
     */
    public function hasSomeRights(array|string $rights): bool
    {
        if (!$rights) {
            return true;
        }

        if (empty($this->rights) and $this->isNew()) {
            return false;
        }

        $contains = $this->getRightsObject()->containsKeys($rights, false, 'god');

        if (!$contains) {
            if ($this->getRightsObject()->getCount()) {
                $this->getRightsObject()->ensureRightsExist($this->getMissingRights($rights));

            } else {
                if ($this->isSystem()) {
                    // System user has all rights
                    return true;
                }
            }
        }

        return $contains;
    }


    /**
     * Returns true if the user has ALL the specified rights
     *
     * @param array|string $rights
     * @param string|null  $always_match
     *
     * @return bool
     */
    public function hasAllRights(array|string $rights, ?string $always_match = 'god'): bool
    {
        if (!$rights) {
            return true;
        }

        if (empty($this->rights) and $this->isNew()) {
            return false;
        }

        $contains = $this->getRightsObject()->containsKeys($rights, true, $always_match);

        if (!$contains) {
            if ($this->getRightsObject()->getCount()) {
                $this->getRightsObject()
                     ->setAutoCreate(true)
                     ->ensureRightsExist($this->getMissingRights($rights));

            } else {
                if (PLATFORM_CLI and $this->isSystem()) {
                    // System user has all rights
                    return true;
                }
            }
        }

        return $contains;
    }


    /**
     * Returns the roles for this user
     *
     * @param bool $reload
     * @param bool $order
     *
     * @return RightsInterface
     */
    public function getRightsObject(bool $reload = false, bool $order = false): RightsInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access rights for user ":user", the user has not yet been saved', [
                ':user' => $this->getLogId(),
            ]));
        }

        if (empty($this->rights) or $reload) {
            $this->rights = RightsBySeoName::new()
                                           ->setParentObject($this)
                                           ->load($order ? ['$order' => ['right' => 'asc']] : null);
        }

        return $this->rights;
    }


    /**
     * Returns an array of what rights this user misses
     *
     * @param array|string $rights
     *
     * @return array
     */
    public function getMissingRights(array|string $rights): array
    {
        if (!$rights) {
            return [];
        }

        return $this->getRightsObject()->getMissingKeys($rights, 'god');
    }


    /**
     * Authenticates the specified user id / email with its password
     *
     * @param string $key
     *
     * @return static
     */
    public static function authenticateKey(string $key): static
    {
        // Return the user that has this API key
        return Users::getUserFromApiKey($key);
    }


    /**
     * Returns the session for this user
     *
     * @return SessionInterface
     */
    public function getSessionObject(): SessionInterface
    {
        if ($this->getId(false) === Session::getUserObject()->getId()) {
            return Session::getInstance();
        }

        throw new SessionException(tr('Cannot access session data for user ":user", that user is not the current session user ":session"', [
            ':user'    => $this->getLogId(),
            ':session' => Session::getUserObject()->getLogId(),
        ]));
    }


    /**
     * Returns an Iterator containing all the sessions for this user
     *
     * @return IteratorInterface
     */
    public function getActiveSessions(): IteratorInterface
    {
        return Sessions::getActiveForUsersId($this->getId());
    }


    /**
     * Removes the specified roles from this user
     *
     * @param Stringable|array|string|int $keys
     * @param bool                        $strict
     *
     * @return static
     */
    public function removeRoles(Stringable|array|string|int $keys, bool $strict = false): static
    {
        $this->getRolesObject()->removeKeys($keys, $strict);
        return $this;
    }


    /**
     * Easy access to adding a role to this user
     *
     * @param mixed                            $value
     * @param Stringable|string|float|int|null $key
     * @param bool                             $skip_null_values
     *
     * @return static
     */
    public function addRoles(mixed $value, Stringable|string|float|int|null $key = null, bool $skip_null_values = true): static
    {
        $this->getRolesObject()
             ->add($value, $key, $skip_null_values);

        return $this;
    }


    /**
     * Returns the roles for this user
     *
     * @return RolesInterface
     */
    public function getRolesObject(): RolesInterface
    {
        if ($this->isNew()) {
            throw new AccountsException(tr('Cannot access roles for user ":user", the user has not yet been saved', [
                ':user' => $this->getLogId(),
            ]));
        }

        if (!isset($this->roles)) {
            if ($this->getId(false)) {
                $this->roles = RolesBySeoName::new()
                                             ->setParentObject($this)
                                             ->load();

            } else {
                $this->roles = RolesBySeoName::new()->setParentObject($this);
            }
        }

        return $this->roles;
    }


    /**
     * Returns Search Engine Optimized id for this user entry that can be used in logs
     *
     * @return string
     */
    public function getSeoLogId(): string
    {
        return Seo::string($this->getLogId());
    }


    /**
     * Returns id for this user entry that can be used as a variable
     *
     * @return string
     */
    public function getVarLogId(): string
    {
        return str_replace(' ', '', $this->getLogId());
    }


    /**
     * Sets the remote_id for this user
     *
     * @param string|int|null $remote_id
     *
     * @return static
     */
    public function setRemoteId(string|int|null $remote_id): static
    {
        return $this->set((int) $remote_id, 'remote_id');
    }


    /**
     * Returns the remote user for this user
     *
     * @param string      $class
     * @param string|null $column
     *
     * @return UserInterface|null
     */
    public function getRemoteUserObject(string $class, ?string $column = 'user_key'): ?UserInterface
    {
        // Validate
        if (!class_exists($class)) {
            throw new OutOfBoundsException(tr('Cannot create remote user object with class ":class", the specified class does not exist (or has not been loaded yet)', [
                ':class' => $class,
            ]));
        }

        if (!is_a($class, DataEntryInterface::class, true)) {
            throw new OutOfBoundsException(tr('Cannot create remote user object with class ":class", the specified class is not an instance of DataEntryInterface', [
                ':class' => $class,
            ]));
        }

        // If the remote user has already been set, verify the class and return it
        if ($this->remote_user) {
            if ($this->remote_user instanceof $class) {
                // Return the remote user, immediately linked to this user
                return $this->remote_user->setRemoteUserObject($this);
            }

            throw new OutOfBoundsException(tr('The existing remote user object with class ":class" is not an instance of the requested class ":requested"', [
                ':class'     => get_class($this->remote_user),
                ':requested' => $class,
            ]));
        }

        if ($this->getRemoteId()) {
            // There is a remote user, it is just not initialized yet. Instantiate the object and link it to this user
            $this->remote_user = $class::new()
                                       ->load([$column => $this->getRemoteId()])
                                       ->setRemoteUserObject($this);

            return $this->remote_user;
        }

        // There is no remote user
        return null;
    }


    /**
     * Sets the remote user for this user
     *
     * @param UserInterface|null $remote_user
     *
     * @return static
     */
    public function setRemoteUserObject(?UserInterface $remote_user): static
    {
        $this->remote_user = $remote_user;

        return $this;
    }


    /**
     * Returns the remote_id for this user
     *
     * @return int|null
     */
    public function getRemoteId(): ?int
    {
        return $this->getTypesafe('int', 'remote_id');
    }


    /**
     * Sets the username for this user
     *
     * @param string|null $username
     *
     * @return static
     */
    public function setUsername(?string $username): static
    {
        return $this->set($username, 'username');
    }


    /**
     * Sets the nickname for this user
     *
     * @param string|null $nickname
     *
     * @return static
     */
    public function setNickname(?string $nickname): static
    {
        $nickname = trim((string) $nickname);

        // Ensure nickname isn't system or guest
        switch (strtolower($nickname)) {
            case 'guest':
                if ($this->isGuest()) {
                    break;
                }

                $e = 'guest';
                break;

            case 'system':
                if ($this->isSystem()) {
                    break;
                }

                $e = 'system';
                break;
        }

        if (isset($e)) {
            // This is a non system user with a reserved name, this is not allowed!
            throw new ValidationFailedException(tr('The nickname ":name" can only be used for ":e" accounts', [
                ':name' => $nickname,
                ':e'    => $e
            ]));
        }

        // GuestUser or SystemUser object in a User object, this is ok
        return $this->set(get_null($nickname), 'nickname');

    }


    /**
     * Returns the last_sign_in for this user
     *
     * @return string|null
     */
    public function getLastSignin(): ?string
    {
        return $this->getTypesafe('string', 'last_sign_in');
    }


    /**
     * Sets the last_sign_in for this user
     *
     * @param string|null $last_sign_in
     *
     * @return static
     */
    public function setLastSignin(?string $last_sign_in): static
    {
        return $this->set($last_sign_in, 'last_sign_in');
    }


    /**
     * Returns the update_password for this user
     *
     * @return DateTimeInterface|null
     */
    public function getUpdatePassword(): ?DateTimeInterface
    {
        $update_password = $this->getTypesafe('string', 'update_password');

        if ($update_password) {
            return new PhoDateTime($update_password);
        }

        return null;
    }


    /**
     * Sets the update_password for this user
     *
     * @param DateTimeInterface|true|null $date_time
     *
     * @return static
     */
    public function setUpdatePassword(DateTimeInterface|bool|null $date_time): static
    {
        if (is_bool($date_time)) {
            // Update password immediately
            $date_time = new PhoDateTime('1970');

        } elseif ($date_time) {
            $date_time = $date_time->getTimestamp();
        }

        return $this->set($date_time, 'update_password');
    }


    /**
     * Returns the authentication_failures for this user
     *
     * @return int|null
     */
    public function getAuthenticationFailures(): ?int
    {
        return $this->getTypesafe('int', 'authentication_failures');
    }


    /**
     * Sets the authentication_failures for this user
     *
     * @param int|null $authentication_failures
     *
     * @return static
     */
    public function setAuthenticationFailures(?int $authentication_failures): static
    {
        return $this->set((int) $authentication_failures, 'authentication_failures');
    }


    /**
     * Returns the locked_until for this user
     *
     * @return string|null
     */
    public function getLockedUntil(): ?string
    {
        return $this->getTypesafe('string', 'locked_until');
    }


    /**
     * Sets the locked_until for this user
     *
     * @param DateTimeInterface|string|null $locked_until
     *
     * @return static
     */
    public function setLockedUntil(DateTimeInterface|string|null $locked_until): static
    {
        if ($locked_until instanceof DateTimeInterface) {
            $locked_until = $locked_until->format('mysql');
        }

        return $this->set($locked_until, 'locked_until');
    }


    /**
     * Returns the sign_in_count for this user
     *
     * @return int|null
     */
    public function getSigninCount(): ?int
    {
        return $this->getTypesafe('int', 'sign_in_count');
    }


    /**
     * Sets the sign_in_count for this user
     *
     * @param int|null $sign_in_count
     *
     * @return static
     */
    public function setSigninCount(?int $sign_in_count): static
    {
        return $this->set($sign_in_count, 'sign_in_count');
    }


    /**
     * Returns the notifications_hash for this user
     *
     * @return string|null
     */
    public function getNotificationsHash(): ?string
    {
        return $this->getTypesafe('string', 'notifications_hash');
    }


    /**
     * Sets the notifications_hash for this user
     *
     * @param string|null $notifications_hash
     *
     * @return static
     */
    public function setNotificationsHash(?string $notifications_hash): static
    {
        sql()->update('accounts_users', ['notifications_hash' => $notifications_hash], ['id' => $this->getId(false)]);

        return $this->set($notifications_hash, 'notifications_hash');
    }


    /**
     * Returns the fingerprint datetime for this user
     *
     * @return DateTimeInterface|null
     */
    public function getFingerprintObject(): ?DateTimeInterface
    {
        return PhoDateTime::newNull($this->getFingerprint());
    }


    /**
     * Returns the fingerprint datetime for this user
     *
     * @return string|null
     */
    public function getFingerprint(): ?string
    {
        return $this->getTypesafe('string', 'fingerprint');
    }


    /**
     * Sets the fingerprint datetime for this user
     *
     * @param DateTimeInterface|string|int|null $fingerprint
     *
     * @return static
     */
    public function setFingerprint(DateTimeInterface|string|int|null $fingerprint): static
    {
        if ($fingerprint) {
            if (!is_object($fingerprint)) {
                $fingerprint = new PhoDateTime($fingerprint);
            }

            return $this->set($fingerprint->format('Y-m-d H:i:s'), 'fingerprint');
        }

        return $this->set(null, 'fingerprint');
    }


    /**
     * Returns the keywords for this user
     *
     * @return string|null
     */
    public function getKeywords(): ?string
    {
        return $this->getTypesafe('string', 'keywords');
    }


    /**
     * Sets the keywords for this user
     *
     * @param array|string|null $keywords
     *
     * @return static
     */
    public function setKeywords(array|string|null $keywords): static
    {
        return $this->set(Strings::force($keywords, ', '), 'keywords');
    }


    /**
     * Returns the priority for this user
     *
     * @return int|null
     */
    public function getPriority(): ?int
    {
        return $this->getTypesafe('int', 'priority');
    }


    /**
     * Sets the priority for this user
     *
     * @param int|null $priority
     *
     * @return static
     */
    public function setPriority(?int $priority): static
    {
        return $this->set($priority, 'priority');
    }


    /**
     * Returns the is_leader for this user
     *
     * @return bool
     */
    public function getIsLeader(): bool
    {
        return $this->getTypesafe('bool', 'is_leader', false);
    }


    /**
     * Sets the is_leader for this user
     *
     * @param int|bool|null $is_leader
     *
     * @return static
     */
    public function setIsLeader(int|bool|null $is_leader): static
    {
        return $this->set((bool) $is_leader, 'is_leader');
    }


    /**
     * Returns the leader email for this user
     *
     * @return string|null
     */
    public function getLeadersEmail(): ?string
    {
        return $this->getLeader()?->getEmail();
    }


    /**
     * Sets the leader email for this user
     *
     * @param string|null $leaders_email
     *
     * @return static
     */
    public function setLeadersEmail(?string $leaders_email): static
    {
        if ($leaders_email) {
            $leaders_id = User::new()->load(['email' => $leaders_email])->getId();
        }

        return $this->setLeadersId(isset_get($leaders_id));
    }


    /**
     * Returns the leader id for this user
     *
     * @return int|null
     */
    public function getLeadersId(): ?int
    {
        return $this->getTypesafe('int', 'leaders_id');
    }


    /**
     * Sets the leader id for this user
     *
     * @param int|null $leaders_id
     *
     * @return static
     */
    public function setLeadersId(?int $leaders_id): static
    {
        return $this->set($leaders_id, 'leaders_id');
    }


    /**
     * Returns the leader for this user
     *
     * @return UserInterface|null
     */
    public function getLeader(): ?UserInterface
    {
        return static::new()->loadNull($this->getTypesafe('int', 'leaders_id'));
    }


    /**
     * Returns the name for the leader for this user
     *
     * @return string|null
     */
    public function getLeadersName(): ?string
    {
        return $this->getLeader()->getDisplayName();
    }


    /**
     * Returns the latitude for this user
     *
     * @return float|null
     */
    public function getLatitude(): ?float
    {
        return $this->getTypesafe('float', 'latitude');
    }


    /**
     * Sets the latitude for this user
     *
     * @param float|null $latitude
     *
     * @return static
     */
    public function setLatitude(?float $latitude): static
    {
        return $this->set($latitude, 'latitude');
    }


    /**
     * Returns the longitude for this user
     *
     * @return float|null
     */
    public function getLongitude(): ?float
    {
        return $this->getTypesafe('float', 'longitude');
    }


    /**
     * Sets the longitude for this user
     *
     * @param float|null $longitude
     *
     * @return static
     */
    public function setLongitude(?float $longitude): static
    {
        return $this->set($longitude, 'longitude');
    }


    /**
     * Returns the accuracy for this user
     *
     * @return float|null
     */
    public function getAccuracy(): ?float
    {
        return $this->getTypesafe('float', 'accuracy');
    }


    /**
     * Sets the accuracy for this user
     *
     * @param float|null $accuracy
     *
     * @return static
     */
    public function setAccuracy(?float $accuracy): static
    {
        return $this->set($accuracy, 'accuracy');
    }


    /**
     * Returns the offset_latitude for this user
     *
     * @return float|null
     */
    public function getOffsetLatitude(): ?float
    {
        return $this->getTypesafe('float', 'offset_latitude');
    }


    /**
     * Sets the offset_latitude for this user
     *
     * @param float|null $offset_latitude
     *
     * @return static
     */
    public function setOffsetLatitude(?float $offset_latitude): static
    {
        return $this->set($offset_latitude, 'offset_latitude');
    }


    /**
     * Returns the offset_longitude for this user
     *
     * @return float|null
     */
    public function getOffsetLongitude(): ?float
    {
        return $this->getTypesafe('float', 'offset_longitude');
    }


    /**
     * Sets the offset_longitude for this user
     *
     * @param float|null $offset_longitude
     *
     * @return static
     */
    public function setOffsetLongitude(?float $offset_longitude): static
    {
        return $this->set($offset_longitude, 'offset_longitude');
    }


    /**
     * Returns the redirect for this user
     *
     * @return string|null
     */
    public function getRedirect(): ?string
    {
        return $this->getTypesafe('string', 'redirect');
    }


    /**
     * Sets the redirect for this user
     *
     * @param Stringable|string|null $redirect
     *
     * @return static
     */
    public function setRedirect(Stringable|string|null $redirect = null): static
    {
        if ($redirect) {
            // Ensure there is a valid redirect URL
            $redirect = Url::new($redirect)->makeWww();
        }

        return $this->set(get_null((string) $redirect), 'redirect');
    }


    /**
     * Returns the gender for this user
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->getTypesafe('string', 'gender');
    }


    /**
     * Sets the gender for this user
     *
     * @param string|null $gender
     *
     * @return static
     */
    public function setGender(?string $gender): static
    {
        return $this->set($gender, 'gender');
    }


    /**
     * Returns the birthdate for this user
     *
     * @return DateTimeInterface|null
     */
    public function getBirthdate(): ?DateTimeInterface
    {
        $birthdate = $this->getTypesafe('string', 'birthdate');

        if ($birthdate) {
            return new PhoDateTime($birthdate);
        }

        return null;
    }


    /**
     * Sets the birthdate for this user
     *
     * @param DateTimeInterface|string|null $birthdate
     *
     * @return static
     */
    public function setBirthdate(DateTimeInterface|string|null $birthdate): static
    {
        return $this->set($birthdate, 'birthdate');
    }


    /**
     * Sets the password for this user
     *
     * @param string $password
     * @param string $validation
     * @param bool   $permit_same_password
     *
     * @return static
     */
    public function changePassword(string $password, string $validation, bool $permit_same_password = false): static
    {
        $password   = trim($password);
        $validation = trim($validation);

        try {
            $this->validatePassword($password, $validation, $permit_same_password);
            $this->setPassword(Password::hash($password, $this->source['id']));

            Hook::new('phoundation/accounts/password/change/')
                ->execute('success', [
                    'user' => $this,
                    'new'  => $password,
                ]);

        } catch (Throwable $e) {
            Hook::new('phoundation/accounts/password/change/')
                ->execute('failure', [
                    'user' => $this,
                    'new'  => $password,
                ]);

            throw $e;
        }

        return $this->savePassword();
    }


    /**
     * Validates the specified password
     *
     * @param string $password
     * @param string $validation
     * @param bool   $permit_same_password
     *
     * @return static
     */
    public function validatePassword(string $password, string $validation, bool $permit_same_password = false): static
    {
        $password   = trim($password);
        $validation = trim($validation);

        if (!$password) {
            throw new ValidationFailedException(tr('No password specified'));
        }

        if (!$validation) {
            throw new ValidationFailedException(tr('No validation password specified'));
        }

        if ($password !== $validation) {
            throw new ValidationFailedException(tr('The password must match the validation password'));
        }

        if (empty($this->source['id'])) {
            throw new OutOfBoundsException(tr('Cannot set password for user ":user", it has not been saved yet', [
                ':user' => $this->getDisplayName(),
            ]));
        }

        if (empty($this->source['email'])) {
            throw new OutOfBoundsException(tr('Cannot set password for user ":user", it has no email address', [
                ':user' => $this->getLogId(),
            ]));
        }

        // Is the password secure?
        Password::testSecurity($password, $this->source['email'], $this->source['id']);

        // Is the password not the same as the current password?
        if (!$permit_same_password) {
            if ($this->hasPassword($password)) {
                throw new PasswordNotChangedException(tr('The specified password is the same as the current password'));
            }
        }

        return $this;
    }


    /**
     * Returns true if this user has the specified password
     *
     * @param string $password
     *
     * @return bool
     */
    public function hasPassword(string $password): bool
    {
        try {
            static::doAuthenticate(
                ['email' => $this->source['email']],
                $password,
                Authentication::new()->setAction(EnumAuthenticationAction::test),
                isset_get($this->source['domain']),
                true
            );

            return true;

        } catch (AuthenticationException) {
            return false;
        }
    }


    /**
     * Sets the password for this user
     *
     * @param string|null $password
     *
     * @return static
     */
    protected function setPassword(?string $password): static
    {
        $this->source['password'] = $password;

        return $this;
    }


    /**
     * Save the password for this user
     *
     * @return static
     */
    protected function savePassword(): static
    {
        if (empty($this->source['id'])) {
            throw new UsersException(tr('Cannot save password, this user does not have an id'));
        }

        sql()->setDebug($this->debug)
             ->query('UPDATE `accounts_users` SET `password` = :password WHERE `id` = :id', [
            ':id'       => $this->source['id'],
            ':password' => $this->source['password'],
        ]);

        return $this;
    }


    /**
     * Clears the password for this user
     *
     * @return static
     */
    public function clearPassword(): static
    {
        $this->setPassword(null);

        return $this->savePassword();
    }


    /**
     * Returns the name with an id for a user
     *
     * @return string|null
     */
    function getDisplayId(): ?string
    {
        return $this->getTypesafe('int', 'id') . ' / ' . $this->getDisplayName();
    }


    /**
     * Returns a password object for this user
     *
     * @return PasswordInterface
     */
    public function getPasswordObject(): PasswordInterface
    {
        return new Password($this->getId(false));
    }


    /**
     * Returns the password string for this user
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return get_safe_typed('string', $this->source, 'password', null, false);
    }


    /**
     * Returns the extra email addresses for this user
     *
     * @return EmailsInterface
     */
    public function getEmailsObject(): EmailsInterface
    {
        if (!isset($this->emails)) {
            if ($this->isNew()) {
                $this->emails = Emails::new()->setParentObject($this);

            } else {
                $this->emails = Emails::new()
                                      ->setParentObject($this)
                                      ->load();
            }

        }

        return $this->emails;
    }


    /**
     * Returns a sign-in key object that can be used to generate a sign in key for this user
     *
     * @return SignInKeyInterface
     */
    public function getSigninKey(): SignInKeyInterface
    {
        $this->checkNew('getSigninKey');
        return SignInKey::new()->setUsersId($this->getId());
    }


    /**
     * Returns the extra phones for this user
     *
     * @return PhonesInterface
     */
    public function getPhonesObject(): PhonesInterface
    {
        if (!isset($this->phones)) {
            if ($this->isNew()) {
                $this->phones = Phones::new()->setParentObject($this);

            } else {
                $this->phones = Phones::new()
                                      ->setParentObject($this)
                                      ->load();
            }
        }

        return $this->phones;
    }


    /**
     * Creates and returns an HTML DataEntry form
     *
     * @param string $name
     *
     * @return DataEntryFormInterface
     */
    public function getRolesHtmlDataEntryFormObject(string $name = 'roles_id[]'): DataEntryFormInterface
    {
        // Get a list of all roles for this user
        $selected = [];

        foreach ($this->getRolesObject() as $role) {
            $selected[] = $role->getId();
        }

        // Build up the roles select object
        $roles = Roles::new();
        $roles->setQueryBuilder(QueryBuilder::new($roles)
                                            ->setSelect('`accounts_roles`.`id`, 
                                                         CONCAT(
                                                             UPPER(LEFT(`accounts_roles`.`name`, 1)), 
                                                             SUBSTRING(`accounts_roles`.`name`, 2)
                                                         ) AS `name`')
                                            ->setWhere('`accounts_roles`.`status` IS NULL')
                                            ->setOrderBy('`name`'))
                                            ->load();

        $entry  = DataEntryForm::new()->setRenderContentsOnly(true);
        $select = $roles->getHtmlSelectOld()->setCache(true)
                        ->setNotSelectedLabel(null)
                        ->setMultiple(true)
                        ->setName($name)
                        ->setSize($roles->getCount())
                        ->setSelected($selected);

        return $entry->appendContent($select->render());
    }


    /**
     * Erase this user and its data
     *
     * @param bool $secure
     *
     * @return static
     */
    public function erase(bool $secure = false): static
    {
        $this->checkNew('erase');

        // Delete the users data directory, then erase the user from the database
        PhoDirectory::new(DIRECTORY_DATA . 'home/' . $this->getId(), PhoRestrictions::new(DIRECTORY_DATA . 'home/', true))
                    ->delete(DIRECTORY_DATA . 'home/');

        return parent::erase();
    }


    /**
     * Update this session so that it impersonates this person
     *
     * @return void
     */
    public function impersonate(): void
    {
        Session::impersonate($this);
    }


    /**
     * Returns true if the current session user can impersonate this user
     *
     * A user can be impersonated if:
     * - The current session user has the right to impersonate users
     * - The target user does NOT have the "god" right
     * - The target user is not the same as the current user
     * - The current session user is not impersonated itself
     *
     * @return bool
     */
    public function canBeImpersonated(): bool
    {
        if ($this->isNotNew()) {
            // Cannot impersonate while we are already impersonating
            if (!Session::isImpersonated()) {
                // We can only impersonate if we have the right to do so
                if (Session::getUserObject()->hasAllRights('impersonate')) {
                    // We must have the right and we cannot impersonate ourselves
                    if ($this->getId() !== Session::getUserObject()->getId()) {
                        // Cannot impersonate god level users
                        if (!$this->isDeleted() and !$this->isLocked()) {
                            if (!$this->hasAllRights('god')) {
                                // Cannot impersonate readonly users (typically guest and system)
                                if (!$this->readonly) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }


    /**
     * Returns true if this user account is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->isStatus('locked');
    }


    /**
     * Returns true if the current session user can change the status of this user
     *
     * @return bool
     */
    public function canBeStatusChanged(): bool
    {
        // Cannot change status for new users
        if ($this->isNotNew()) {
            // We cannot status change ourselves
            if ($this->getId(false) !== Session::getUserObject()->getId()) {
                // Cannot change status for god right users
                if (!$this->hasAllRights('god')) {
                    // Cannot change status for readonly users (typically guest and system)
                    if (!$this->readonly) {
                        return true;
                    }
                }
            }
        }

        return false;
    }


    /**
     * Lock this user account
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function lock(?string $comments = null): static
    {
        Sessions::new()->drop($this);

        return $this->setLockedUntil(PhoDateTime::new('2999/12/31 23:59:59'))
                    ->save()
                    ->setStatus('locked', $comments);
    }


    /**
     * Unlock this user account
     *
     * @param string|null $comments
     *
     * @return static
     */
    public function unlock(?string $comments = null): static
    {
        Sessions::new()->drop($this);

        return $this->setLockedUntil(null)
                    ->save()
                    ->setStatus(null, $comments);
    }


    /**
     * Returns if this user can receive notifications or if notifications for this user will be dropped
     *
     * @return bool
     */
    public function getNotificationsEnabled(): bool
    {
        return $this->notifications_enabled;
    }


    /**
     * Sets if this user can receive notifications or if notifications for this user will be dropped
     *
     * @param bool $enabled
     *
     * @return static
     */
    public function setNotificationsEnabled(bool $enabled): static
    {
        $this->notifications_enabled = $enabled;
        return $this;
    }


    /**
     * Returns a NotificationInterface object that can be used to send a notification to only this user.
     *
     * Will return NULL if notifications_enabled is false
     *
     * @return NotificationInterface|null
     */
    public function notify(): ?NotificationInterface
    {
        if ($this->notifications_enabled) {
            return Notification::new()->setUserObject($this);
        }

        return null;
    }


    /**
     * Returns true if the user has the specified role
     *
     * @param RolesInterface|RoleInterface|Stringable|string $roles
     * @param string|null                                    $message
     *
     * @return static
     */
    public function checkRoles(RolesInterface|RoleInterface|Stringable|string $roles, ?string $message = null): static
    {
        if (!$this->hasRoles($roles)) {
            throw new NotExistsException($message ?? tr('The user ":user" does not have the required role(s) ":roles"', [
                ':user'  => $this->getLogId(),
                ':roles' => $roles,
            ]));
        }

        return $this;
    }


    /**
     * Returns true if the user has the specified role
     *
     * @param RolesInterface|RoleInterface|Stringable|string $roles
     *
     * @return bool
     */
    public function hasRoles(RolesInterface|RoleInterface|Stringable|string $roles): bool
    {
        $result = true;
        $roles  = Arrays::force($roles);

        foreach ($roles as $role) {
            $exists = $this->getRolesObject()->keyExists($role);

            if ($exists) {
                $result = false;
                break;
            }
        }

        return $result;
    }


    /**
     * Return the user data used for validation.
     *
     * This method strips the basic meta-data but also the password column as that is updated directly
     *
     * @return array
     */
    protected function getDataForValidation(): array
    {
        return Arrays::removeKeys(parent::getDataForValidation(), ['password']);
    }


    /**
     * Returns the name for this user
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return trim($this->getTypesafe('string', 'first_names') . ' ' . $this->getTypesafe('string', 'last_names'));
    }


    /**
     * Returns true if this is a new entry that hasn't been written to the database yet
     *
     * @return bool
     */
    public function isNew(): bool
    {
        $new = $this->getId(false) === null;

        if ($new) {
            // System user is never new!
            if ($this->isSystem()) {
                return false;
            }
        }

        return $new;
    }


    /**
     * Initializes a Guest user object
     *
     * @return void
     */
    protected function initGuestUser(): void
    {
        // Guest user is readonly and also does not register meta requests
        $this->meta_enabled = false;

        parent::__construct(false);

        $this->loadOrThis(['email' => 'guest']);

        $this->source['redirect'] = null;
        $this->source['email']    = 'guest';
        $this->source['status']   = 'system';
        $this->source['nickname'] = tr('Guest');

        if ($this->isNew()) {
            // Guest user does not yet exist, save it now. Since guest user MAY be created automatically by guest itself
            // we will NOT save the created_by column (making created_by the system user)
            $this->setMetaColumns([
                'id',
                'created_on',
                'meta_id',
                'status',
                'meta_state',
            ])->save(skip_validation: true);
        }

        $this->readonly = true;
    }


    /**
     * Initializes a System user object
     *
     * @return void
     */
    protected function initSystemUser(): void
    {
        // System user is readonly and also doesn't register meta-requests
        $this->readonly     = true;
        $this->meta_enabled = false;

        parent::__construct();

        $this->source['id']       = null;
        $this->source['redirect'] = null;
        $this->source['status']   = 'system';
        $this->source['email']    = 'system';
        $this->source['nickname'] = tr('System');

        $this->roles  = RolesBySeoName::new()->load(['name' => 'god']);
        $this->rights = RightsBySeoName::new()->load(['name' => 'god']);
    }


    /**
     * Returns the profile image for this user
     *
     * @return string|null
     */
    public function getProfileImage(): ?string
    {
        return $this->getTypesafe('string', 'profile_image');
    }


    /**
     * Sets the profile image for this user
     *
     * @param string|null $profile_image
     *
     * @return static
     */
    public function setProfileImage(string|null $profile_image): static
    {
        return $this->set($profile_image, 'profile_image');
    }


    /**
     * Returns the profile_images_id for this user
     *
     * @return int|null
     */
    public function getProfileImagesId(): ?int
    {
        return $this->getTypesafe('int', 'profile_images_id');
    }


    /**
     * Sets the profile_images_id for this user
     *
     * @param int|null $profile_images_id
     *
     * @return static
     */
    public function setProfileImagesId(int|null $profile_images_id): static
    {
        return $this->set($profile_images_id, 'profile_images_id');
    }


    /**
     * Returns the profile image for this user
     *
     * @return ProfileImageInterface
     */
    public function getProfileImageObject(): ProfileImageInterface
    {
        // Return the user's profile image
        return ProfileImage::new()->loadThis($this->getTypesafe('int', 'profile_images_id'));
    }


    /**
     * Sets the profile image for this user
     *
     * @param ProfileImageInterface|string|null $profile_image
     *
     * @return static
     */
    public function setProfileImageObject(ProfileImageInterface|string|null $profile_image): static
    {
        return $this->set($profile_image->getId()                          , 'profile_images_id')
                    ->set($profile_image->getImageFileObject()->getSource(), 'profile_image');
    }


    /**
     * Returns the list of profile images for this user
     *
     * @return ProfileImagesInterface
     */
    public function getProfileImagesObject(): ProfileImagesInterface
    {
        if (empty($this->profile_images)) {
            $this->profile_images = new ProfileImages($this);
        }

        return $this->profile_images;
    }


    /**
     * Returns the "configurations" object for this user
     *
     * @return ConfigurationsInterface
     */
    public function getConfigurationsObject(): ConfigurationsInterface
    {
        return Configurations::new($this);
    }


    /**
     * Returns a MultiFactorAuthenticationInterface for this user
     *
     * @return MultiFactorAuthenticationInterface
     */
    public function getMultiFactorAuthenticationObject(): MultiFactorAuthenticationInterface
    {
        return MultiFactorAuthentication::new($this);
    }


    /**
     * Sets the available data keys for the User class
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->get('status')->setNullDisplay(tr('Ok'));
        $o_definitions->add(Definition::new('remote_id')
                                      ->setOptional(true)
                                      ->setRender(false)
                                      ->setInputType(EnumInputType::number))

                      ->add(Definition::new('last_sign_in')
                                    ->setOptional(true)
                                    ->setDisabled(true)
                                    ->setRender(function() { return !$this->isNew(); })
                                    ->setInputType(EnumInputType::datetime_local)
                                    ->setDbNullInputType(EnumInputType::text)
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setNullDisplay(tr('Never signed yet'))
                                    ->setLabel('Last sign in'))

                    ->add(DefinitionFactory::newNumber('sign_in_count')
                                    ->setOptional(true, 0)
                                    ->setDisabled(true)
                                    ->setRender(function() { return !$this->isNew(); })
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setLabel(tr('Sign in count')))

                    ->add(DefinitionFactory::newNumber('authentication_failures')
                                    ->setOptional(true, 0)
                                    ->setDisabled(true)
                                    ->setRender(function() { return !$this->isNew(); })
                                    ->setMin(0)
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setLabel(tr('Authentication failures')))

                    ->add(Definition::new('locked_until')
                                    ->setOptional(true)
                                    ->setDisabled(true)
                                    ->setRender(function() { return !$this->isNew(); })
                                    ->setInputType(EnumInputType::datetime_local)
                                    ->setDbNullInputType(EnumInputType::text)
                                    ->setNullDisplay(tr('Not locked'))
                                    ->addClasses('text-center')
                                    ->setSize(3)
                                    ->setLabel(tr('Locked until')))

                    ->add(DefinitionFactory::newDivider()
                                           ->setRender(function() {
                                               return $this->definitions->getDefinitionRender('last_sign_in')
                                                  and $this->definitions->getDefinitionRender('sign_in_count')
                                                  and $this->definitions->getDefinitionRender('authentication_failures')
                                                  and $this->definitions->getDefinitionRender('locked_until');
                                           }))

                    ->add(DefinitionFactory::newEmail()
                                           ->setOptional(true)
                                           ->setSize(3)
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The email address for this user. This is also the unique identifier for the user'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               // Email address is optional IF remote_id is specified.
                                               // Validate the email address.
                                               $validator->orColumn('remote_id');
                                               $validator->isUnique(tr('already exists as a primary email address'));

                                               $exists = sql()->getRow('SELECT `id` 
                                                                        FROM   `accounts_emails` 
                                                                        WHERE  `email` = :email', [
                                                                            ':email' => $validator->getSelectedValue(),
                                               ]);

                                               if ($exists) {
                                                   $validator->addSoftFailure(tr('already exists as an additional email address'));
                                               }
                                           }))

                    ->add(Definition::new('domain')
                                    ->setOptional(true)
                                    ->setMaxLength(128)
                                    ->setSize(3)
                                    ->setCliColumn('--domain')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Restrict to domain(s)'))
                                    ->setHelpText(tr('The domain(s) where this user will be able to sign in'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isDomain();
                                    }))

                    ->add(Definition::new('username')
                                    ->setOptional(true)
                                    ->setSize(3)
                                    ->setCliColumn('-u,--username')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Username'))
                                    ->setHelpGroup(tr('Personal information'))
                                    ->setHelpText(tr('The unique username for this user.'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isName(64);
                                    }))

                    ->add(DefinitionFactory::newName('nickname')
                                           ->setOptional(true)
                                           ->setLabel(tr('Nickname'))
                                           ->setCliColumn('--nickname NAME')
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The nickname for this user')))

                    ->add(DefinitionFactory::newName('first_names')
                                           ->setOptional(true)
                                           ->setCliColumn('-f,--first-names NAMES')
                                           ->setLabel(tr('First names'))
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The firstnames for this user')))

                    ->add(DefinitionFactory::newName('last_names')
                                           ->setOptional(true)
                                           ->setCliColumn('-n,--last-names')
                                           ->setLabel(tr('Last names'))
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The lastnames / surnames for this user')))

                    ->add(DefinitionFactory::newTitle()
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The title added to this users name')))

                    ->add(Definition::new('gender')
                                    ->setOptional(true)
                                    ->setElement(EnumElement::select)
                                    ->setSize(3)
                                    ->setCliColumn('-g,--gender')
                                    ->setDataSource([
                                        ''       => tr('Select a gender'),
                                        'male'   => tr('Male'),
                                        'female' => tr('Female'),
                                        'other'  => tr('Other'),
                                    ])
                                    ->setCliAutoComplete([
                                        'word'   => function (string $word) {
                                            return Arrays::removeMatchingValues([
                                                tr('Male'),
                                                tr('Female'),
                                                tr('Other'),
                                            ], $word);
                                        },
                                        'noword' => function ($word) {
                                            return [
                                                tr('Male'),
                                                tr('Female'),
                                                tr('Other'),
                                            ];
                                        },
                                    ])
                                    ->setLabel(tr('Gender'))
                                    ->setHelpGroup(tr('Personal information'))
                                    ->setHelpText(tr('The gender for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->hasMaxCharacters(6);
                                    }))

                    ->add(DefinitionFactory::newUsersEmail('leaders_email')
                                           ->setCliColumn('--leader USER-EMAIL')
                                           ->clearValidationFunctions()
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->orColumn('leaders_id')
                                                         ->isEmail()
                                                         ->setColumnFromQuery('leaders_id', 'SELECT `id` FROM `accounts_users` WHERE `email` = :email AND `status` IS NULL', [':email' => '$leaders_email']);
                                           }))

                    ->add(DefinitionFactory::newUsersId('leaders_id')
                                           ->setCliColumn('--leaders-id USERS-DATABASE-ID')
                                           ->setLabel(tr('Leader'))
                                           ->setHelpGroup(tr('Hierarchical information'))
                                           ->setHelpText(tr('The user that is the leader for this user'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->orColumn('leaders_email')
                                                         ->isDbId()
                                                         ->isQueryResult('SELECT `id` FROM `accounts_users` WHERE `id` = :id AND `status` IS NULL', [':id' => '$leaders_id']);
                                           }))

                    ->add(Definition::new('is_leader')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::checkbox)
                                    ->setSize(3)
                                    ->setCliColumn('--is-leader')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Is leader'))
                                    ->setHelpGroup(tr('Hierarchical information'))
                                    ->setHelpText(tr('Sets if this user is a leader itself'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isBoolean();
                                    }))

                    ->add(DefinitionFactory::newCode('code')
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The code associated with this user')))

                    ->add(Definition::new('priority')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setCliColumn('--priority')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Priority'))
                                    ->setMin(1)
                                    ->setMax(9)
                                    ->setHelpText(tr('The priority for this user, between 1 and 9'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isInteger();
                                    }))

                    ->add(DefinitionFactory::newDate('birthdate')
                                           ->setLabel(tr('Birthdate'))
                                           ->setCliColumn('-b,--birthdate')
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('The birthdate for this user'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isDate()
                                                         ->isBefore(PhoDateTime::new(), true);
                                           }))

                    ->add(DefinitionFactory::newPhone()
                                           ->setSize(3)
                                           ->setHelpGroup(tr('Personal information'))
                                           ->setHelpText(tr('Main phone number where this user may be contacted'))
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               // Validate the email address
                                               $validator->isUnique(tr('already exists as a primary phone number'));
                                           }))

                    ->add(DefinitionFactory::newLanguagesName()
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The display language for this user')))

                    ->add(DefinitionFactory::newLanguagesCode()
                                           ->setHelpGroup(tr('Location information')))

                    ->add(DefinitionFactory::newLanguagesId()
                                           ->setHelpGroup(tr('Location information')))

                    ->add(Definition::new('address')
                                    ->setOptional(true)
                                    ->setMaxLength(255)
                                    ->setSize(3)
                                    ->setCliColumn('-a,--address')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Address'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The address where this user resides'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                    }))

                    ->add(Definition::new('zipcode')
                                    ->setOptional(true)
                                    ->setMinLength(4)
                                    ->setMaxLength(8)
                                    ->setSize(1)
                                    ->setCliColumn('-z,--zipcode')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Zip code'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The zip code (postal code) where this user resides'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                    }))

                    ->add(DefinitionFactory::newCountriesName()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The country where this user resides')))

                    ->add(DefinitionFactory::newCountriesCode()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The country code where this user resides')))

                    ->add(DefinitionFactory::newCountriesId()
                                           ->setSize(2))

                    ->add(DefinitionFactory::newStatesName()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The state where this user resides')))

                    ->add(DefinitionFactory::newStatesCode()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The state code where this user resides')))

                    ->add(DefinitionFactory::newStatesId()
                                           ->setSize(2))

                    ->add(DefinitionFactory::newCitiesName()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The city where this user resides')))

                    ->add(DefinitionFactory::newCitiesId()
                                           ->setSize(2))

                    ->add(DefinitionFactory::newTimezonesName()
                                           ->setSize(2)
                                           ->setHelpGroup(tr('Location information'))
                                           ->setHelpText(tr('The timezone name where this user resides')))

                    ->add(DefinitionFactory::newTimezonesId()
                                           ->setSize(2))

                    ->add(Definition::new('latitude')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setCliColumn('--latitude')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Latitude'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The latitude location for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isLatitude();
                                    }))

                    ->add(Definition::new('longitude')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setCliColumn('--longitude')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Longitude'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The longitude location for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isLongitude();
                                    }))

                    ->add(Definition::new('offset_latitude')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Offset latitude'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The latitude location for this user with a random offset within the configured range')))

                    ->add(Definition::new('offset_longitude')
                                    ->setOptional(true)
                                    ->setReadonly(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Offset longitude'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The longitude location for this user with a random offset within the configured range')))

                    ->add(Definition::new('accuracy')
                                    ->setOptional(true)
                                    ->setInputType(EnumInputType::number)
                                    ->setSize(3)
                                    ->setMin(0)
                                    ->setMax(10)
                                    ->setCliColumn('--accuracy')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Accuracy'))
                                    ->setHelpGroup(tr('Location information'))
                                    ->setHelpText(tr('The accuracy of this users location'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isFloat();
                                    }))

                    ->add(Definition::new('type')
                                    ->setOptional(true)
                                    ->setMaxLength(16)
                                    ->setSize(3)
                                    ->setCliColumn('--type')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Type'))
                                    ->setHelpGroup(tr(''))
                                    ->setHelpText(tr('The type classification for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isName();
                                    }))

                    ->add(Definition::new('keywords')
                                    ->setOptional(true)
                                    ->setMaxLength(255)
                                    ->setSize(3)
                                    ->setCliColumn('-k,--keywords')
                                    ->setCliAutoComplete(true)
                                    ->setLabel(tr('Keywords'))
                                    ->setHelpGroup(tr('Account information'))
                                    ->setHelpText(tr('The keywords for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isPrintable();
                                        //$validator->sanitizeForceArray(' ')->forEachField()->isWord()->sanitizeForceString()
                                    }))

                    ->add(DefinitionFactory::newDivider('redirect-divider'))

                    ->add(DefinitionFactory::newUrl('redirect')
                                           // Normal users always start with "/force-password-update.html" URL because they lack a password, but remote users should already have a password.
                                           ->setSize(4)
                                           ->setDataSource(Url::new('system/accounts/users/redirect/autosuggest.json')->makeAjax())
                                           ->setInputType(EnumInputType::auto_suggest)
                                           ->setInitialDefault($this->getRemoteId() ? null : Url::new(config()->getString('security.accounts.users.new.defaults.redirect', '/force-password-update.html'))->makeWww())
                                           ->setLabel(tr('Redirect URL'))
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('The URL where this user will be forcibly redirected to upon sign in')))

                    ->add(Definition::new('url')
                                    ->setSize(4)
                                    ->setOptional(true)
                                    ->setMaxLength(2048)
                                    ->setCliColumn('--url')
                                    ->setLabel(tr('Website URL'))
                                    ->setHelpGroup(tr('Account information'))
                                    ->setHelpText(tr('A URL specified by the user, usually containing more information about the user')))

                    ->add(DefinitionFactory::newDateTime('verified_on')
                                           ->setSize(4)
                                           ->setDisabled(true)
                                           ->setDbNullInputType(EnumInputType::text)
                                           ->setNullDisplay(tr('Not verified'))
                                           ->addClasses('text-center')
                                           ->setLabel(tr('Account verified on'))
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('The date when this user was email verified. Empty if not yet verified')))

                    ->add(DefinitionFactory::newDescription()
                                           ->setSize(6)
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('A public description about this user')))

                    ->add(DefinitionFactory::newComments()
                                           ->setSize(6)
                                           ->setHelpGroup(tr('Account information'))
                                           ->setHelpText(tr('Comments about this user by leaders or administrators that are not visible to the user')))

                    ->add(DefinitionFactory::newCode('verification_code')
                                           ->setOptional(true)
                                           ->setRender(false)
                                           ->setReadonly(true))

                    ->add(Definition::new('fingerprint')
                                    // TODO Implement
                                    ->setNoValidation(true)
                                    ->setOptional(true)
                                    ->setRender(false))

                    ->add(DefinitionFactory::newCode('notifications_hash')
                                    // This hash is set directly so it won't really be touched by DataEntry
                                    ->setOptional(true)
                                    ->setDirectUpdate(true)
                                    ->setRender(false)
                                    ->setReadonly(true))

                    ->add(Definition::new('password')
                                    ->setRender(false)
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxLength(64)
                                    ->setNullDisplay(false)
                                    ->setHelpText(tr('The password for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isStrongPassword();
                                    }))

                    ->add(DefinitionFactory::newCode('mfa_code')
                                    ->setRender(false)
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setInputType(EnumInputType::password)
                                    ->setMaxLength(64)
                                    ->setHelpText(tr('The MFA code for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isCode(max_characters: 64);
                                    }))

                    ->add(DefinitionFactory::newNumber('mfa_timeslice')
                                    ->setRender(false)
                                    ->setReadonly(true)
                                    ->setOptional(true)
                                    ->setCliAutoComplete(true)
                                    ->setInputType(EnumInputType::positiveInteger)
                                    ->setHelpText(tr('The MFA timeslice for this user'))
                                    ->addValidationFunction(function (ValidatorInterface $validator) {
                                        $validator->isInteger()->isPositive();
                                    }))

                    ->add(DefinitionFactory::newId('profile_images_id')
                                           ->setOptional(true)
                                           ->setRender(false)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               if ($validator->getSelectedValue()) {
                                                   $validator->isTrue(ProfileImage::exists($validator->getSelectedValue()), 'profile image must exist');
                                               }
                                            }))

                    ->add(DefinitionFactory::newFile(null, 'profile_image')
                                           ->setLabel('Profile image')
                                           ->setOptional(true)
                                           ->setRender(false)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               if ($validator->getSelectedValue()) {
                                                   if ($this->isNew()) {
                                                       // Can't save a profile image with a user that does not yet exist in the database
                                                       $validator->addSoftFailure(tr('requires that the user is saved first'));

                                                   } else {
                                                       $validator->isFile(
                                                           PhoDirectory::newCdnObject(true, '/img/files/profile/' . $this->getId() . '/'),
                                                       );
                                                   }
                                               }
                                           }))

                    // ???
                    ->add(DefinitionFactory::newData('data')
// No validation for now until we can figure out what this column does, and how to validate it
->setNoValidation(true));

        return $this;
    }
}
