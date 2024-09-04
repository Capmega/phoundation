<?php

/**
 * Class Incident
 *
 *
 *
 * @see       DataEntry
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 * @todo      Incidents should be able to throw exceptions depending on type. AuthenticationFailureExceptions, for
 *            example, should be thrown from here so that it is no longer required for the developer to both register
 *            the incident AND throw the exception
 */


declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Cli\CliCommand;
use Phoundation\Core\Exception\SessionException;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryBody;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDetails;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryTitle;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryType;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Security\Incidents\Interfaces\IncidentInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Uploads\UploadHandler;


class Incident extends DataEntry implements IncidentInterface
{
    use TraitDataEntryType;
    use TraitDataEntryTitle;
    use TraitDataEntryBody;
    use TraitDataEntryDetails;


    /**
     * Sets if this incident is logged in the text log
     *
     * @var bool
     */
    protected bool $log = true;

    /**
     * Sets if this incident causes a notification to the specified groups
     *
     * @var IteratorInterface $notify_roles
     */
    protected IteratorInterface $notify_roles;


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'security_incidents';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return 'security incident';
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
     * Returns if this incident will be logged in the text log
     *
     * @return bool
     */
    public function getLog(): bool
    {
        return $this->log;
    }


    /**
     * Sets if this incident will be logged in the text log
     *
     * @param bool $log
     *
     * @return static
     */
    public function setLog(bool $log): static
    {
        $this->log = $log;

        return $this;
    }


    /**
     * Sets who will be notified about this incident directly without accessing the roles object
     *
     * @param IteratorInterface|array|string|null $roles
     *
     * @return Incident
     */
    public function notifyRoles(IteratorInterface|array|string|null $roles): static
    {
        if (is_string($roles)) {
            // Ensure the source is not a string, at least an array
            $roles = Arrays::force($roles);
        }

        $this->getNotifyRoles()->addSource($roles);

        return $this;
    }


    /**
     * Returns the roles iterator containing who will be notified about this incident
     *
     * @return IteratorInterface
     */
    public function getNotifyRoles(): IteratorInterface
    {
        if (empty($this->notify_roles)) {
            $this->notify_roles = new Iterator();
        }

        return $this->notify_roles;
    }


    /**
     * Sets the roles iterator containing who will be notified about this incident
     *
     * @param IteratorInterface|array $notify_roles
     *
     * @return static
     */
    public function setNotifyRoles(IteratorInterface|array $notify_roles): static
    {
        $this->notify_roles = $notify_roles;

        return $this;
    }


    /**
     * Sets the severity for this object
     *
     * @param EnumSeverity|string $severity
     *
     * @return static
     */
    public function setSeverity(EnumSeverity|string $severity): static
    {
        if (is_string($severity)) {
            $severity = EnumSeverity::from($severity);
        }

        return $this->set($severity->value, 'severity');
    }


    /**
     * Saves the incident to the database
     *
     * @param bool        $force
     * @param bool        $skip_validation
     * @param string|null $comments
     *
     * @return static
     */
    public function save(bool $force = false, bool $skip_validation = false, ?string $comments = null): static
    {
        // Save the incident
        $severity = strtolower($this->getSeverity());
        $incident = parent::save($force, $skip_validation, $comments);

        // Default details added to security incidents medium or higher
        if ($this->severityIsEqualOrHigherThan(EnumSeverity::medium)) {
            $this->addDetails([
                'platform'    => PLATFORM,
                'environment' => ENVIRONMENT,
                'url'         => Url::getCurrent(),
                'command'     => CliCommand::getExecutedPath(),
                'user'        => Session::getUserObject()->getLogId(),
                '$_GET'       => GetValidator::new()->getSource(),
                '$_POST'      => PostValidator::new()->getSource(),
                '$_SERVER'    => $_SERVER,
                '$_SESSION'   => Session::getSource(),
                '$argv'       => ArgvValidator::new()->getSource(),
            ]);
        }


        if ($this->log) {
            switch ($severity) {
                case 'notice':
                    Log::warning(tr('Security notice (:id): :message', [
                        ':id'      => $this->getId(),
                        ':message' => $this->getTitle(),
                    ]));
                    break;

                case 'high':
                    // no break

                case 'severe':
                    Log::error(tr('Security incident (:id / :severity): :message', [
                        ':id'       => $this->getId(),
                        ':severity' => $severity,
                        ':message'  => $this->getTitle(),
                    ]));
                    break;

                default:
                    Log::warning(tr('Security incident (:id / :severity): :message', [
                        ':id'       => $this->getId(),
                        ':severity' => $severity,
                        ':message'  => $this->getTitle(),
                    ]));
            }
        }

        // Notify anybody?
        if (isset($this->notify_roles)) {
            // Notify the specified roles
            $notification = Notification::new();

            switch ($severity) {
                case 'notice':
                    // no break

                case 'low':
                    $notification->setMode(EnumDisplayMode::notice);
                    break;

                case 'medium':
                    $notification->setMode(EnumDisplayMode::warning);
                    break;

                default:
                    $notification->setMode(EnumDisplayMode::danger);
                    break;
            }

            $notification->setUrl('security/incident+' . $this->getId() . '.html')
                         ->setRoles($this->notify_roles)
                         ->setTitle($this->getType())
                         ->setMessage($this->getTitle())
                         ->setDetails($this->getDetails())
                         ->send();
        }

        return $incident;
    }


    /**
     * Returns the severity for this object
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->getTypesafe('string', 'severity', EnumSeverity::unknown->value);
    }


    /**
     * Returns true if the current severity is higher than the specified severity
     *
     * @param EnumSeverity $severity
     *
     * @return bool
     */
    public function severityIsEqualOrHigherThan(EnumSeverity $severity): bool
    {
        switch ($severity) {
            case EnumSeverity::severe:
                if ($this->getSeverity() === EnumSeverity::severe->value) {
                    return true;
                }

                return false;

            case EnumSeverity::high:
                return match ($this->getSeverity()) {
                    EnumSeverity::severe->value,
                    EnumSeverity::high->value => true,
                    default => false,
                };

            case EnumSeverity::medium:
                return match ($this->getSeverity()) {
                    EnumSeverity::low->value,
                    EnumSeverity::notice->value => false,
                    default => true,
                };

            case EnumSeverity::low:
                if ($this->getSeverity() === EnumSeverity::notice->value) {
                    return false;
                }

                return true;

            case EnumSeverity::notice:
                if ($this->getSeverity() === EnumSeverity::notice->value) {
                    return true;
                }

                return false;


            case EnumSeverity::unknown:
                // Don't know, assume its severe?
                return true;
        }

        throw new OutOfBoundsException(tr('Unknown severity ":severity" specified', [
            ':severity' => $severity
        ]));
    }


    /**
     * Throw an incidents exception
     *
     * @param string|null $exception
     *
     * @return never
     */
    #[NoReturn] public function throw(?string $exception = null): never
    {
        if ($exception) {
            throw $exception::new($this->getTitle())
                            ->addData(['details' => $this->getDetails()]);
        }

        throw IncidentsException::new($this->getTitle())
                                ->addData(['details' => $this->getDetails()]);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(Definition::new($this, 'type')
                                    ->setLabel(tr('Incident type'))
                                    ->setDisabled(true)
                                    ->setDefault(tr('Unknown'))
                                    ->setSize(6)
                                    ->setMaxlength(64))

                    ->add(Definition::new($this, 'severity')
                                    ->setElement(EnumElement::select)
                                    ->setLabel(tr('Severity'))
                                    ->setDisabled(true)
                                    ->setSize(6)
                                    ->setMaxlength(6)
                                    ->setDataSource([
                                        EnumSeverity::notice->value => tr('Notice'),
                                        EnumSeverity::low->value    => tr('Low'),
                                        EnumSeverity::medium->value => tr('Medium'),
                                        EnumSeverity::high->value   => tr('High'),
                                        EnumSeverity::severe->value => tr('Severe'),
                                    ]))

                    ->add(Definition::new($this, 'title')
                                    ->setLabel(tr('Title'))
                                    ->setDisabled(true)
                                    ->setSize(12)
                                    ->setMinlength(4)
                                    ->setMaxlength(255))

                    ->add(Definition::new($this, 'body')
                                    ->setLabel(tr('Body'))
                                    ->setDisabled(true)
                                    ->setOptional(true)
                                    ->setSize(12)
                                    ->setMaxlength(65_535))

                    ->add(Definition::new($this, 'details')
                                    ->setElement(EnumElement::textarea)
                                    ->setLabel(tr('Details'))
                                    ->setDisabled(true)
                                    ->setSize(12)
                                    ->setMaxlength(65_535)
                                    ->setDisplayCallback(function (mixed $value, array $source) {
                                        // Since the details almost always have an array encoded in JSON, decode it and
                                        // display it using print_r
                                        if (!$value) {
                                            return null;
                                        }

                                        try {
                                            $return  = '';
                                            $details = Json::decode($value);
                                            $largest = Arrays::getLongestKeyLength($details);
                                            foreach ($details as $key => $value) {
                                                $return .= Strings::size($key, $largest) . ' : ' . $value . PHP_EOL;
                                            }

                                            return $return;

                                        } catch (JsonException $e) {
                                            // We couldn't decode it! Why? Lets move on, its not THAT important.. yet.
                                            Log::warning($e);

                                            return $value;
                                        }
                                    }));
    }
}
