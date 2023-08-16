<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Arrays;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Strings;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Notifications\Notification;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\InputElement;
use Throwable;


/**
 * Class Incident
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 * @todo Incidents should be able to throw exceptions depending on type. AuthenticationFailureExceptions, for example, should be thrown from here so that it is no longer required for the developer to both register the incident AND throw the exception
 */
class Incident extends DataEntry
{
    use DataEntryType;
    use DataEntryTitle;
    use DataEntryDetails;


    /**
     * Sets if this incident will be logged in the text log
     *
     * @var bool
     */
    protected bool $log = true;


    /**
     * Sets if this incident will cause a notification to the specified groups
     *
     * @var IteratorInterface $notify_roles
     */
    protected IteratorInterface $notify_roles;


    /**
     * Returns the table name used by this object
     *
     * @return string
     */
    public static function getTable(): string
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
    public static function getUniqueField(): ?string
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
     * @return static
     */
    public function setNotifyRoles(IteratorInterface|array $notify_roles): static
    {
        $this->notify_roles = $notify_roles;
        return $this;
    }


    /**
     * Returns the severity for this object
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->getSourceValue('string', 'severity', Severity::unknown->value);
    }


    /**
     * Sets the severity for this object
     *
     * @param Severity|string $severity
     * @return static
     */
    public function setSeverity(Severity|string $severity): static
    {
        if (is_string($severity)) {
            $severity = Severity::from($severity);
        }

        return $this->setSourceValue('severity', $severity->value);
    }


    /**
     * Saves the incident to database
     *
     * @param bool $force
     * @param string|null $comments
     * @return static
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        $severity = strtolower($this->getSeverity());

        if ($this->log) {
            switch ($severity){
                case 'notice':
                    Log::warning(tr('Security notice: :message', [
                        ':message'  => $this->getTitle()
                    ]));

                    break;

                case 'high':
                    // no break
                case 'severe':
                    Log::error(tr('Security incident (:severity): :message', [
                        ':severity' => $severity,
                        ':message'  => $this->getTitle()
                    ]));

                    break;

                default:
                    Log::warning(tr('Security incident (:severity): :message', [
                        ':severity' => $severity,
                        ':message'  => $this->getTitle()
                    ]));
            }
        }

        // Save the incident
        $incident = parent::save($force, $comments);

        // Notify anybody?
        if (isset($this->notify_roles)) {
            // Notify the specified roles
            $notification = Notification::new();

            switch ($severity) {
                case 'notice':
                    // no break
                case 'low':
                    $notification->setMode(DisplayMode::notice);
                    break;

                case 'medium':
                    $notification->setMode(DisplayMode::warning);
                    break;

                default:
                    $notification->setMode(DisplayMode::danger);
                    break;
            }

            $notification
                ->setUrl('security/incident-' . $this->getId() . '.html')
                ->setRoles($this->notify_roles)
                ->setTitle($this->getType())
                ->setMessage($this->getTitle())
                ->setDetails($this->getDetails())
                ->send();
        }

        return $incident;
    }


    /**
     * Throw an incidents exception
     *
     * @return never
     */
    #[NoReturn] public function throw(): never
    {
        throw IncidentsException::new($this->getTitle())->setData(['details' => $this->getDetails()]);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(Definition::new($this, 'type')
                ->setLabel(tr('Incident type'))
                ->setDisabled(true)
                ->setDefault(tr('Unknown'))
                ->setSize(6)
                ->setMaxlength(6))
            ->addDefinition(Definition::new($this, 'severity')
                ->setElement(InputElement::select)
                ->setLabel(tr('Severity'))
                ->setDisabled(true)
                ->setSize(6)
                ->setMaxlength(6)
                ->setSource([
                    Severity::notice->value => tr('Notice'),
                    Severity::low->value    => tr('Low'),
                    Severity::medium->value => tr('Medium'),
                    Severity::high->value   => tr('High'),
                    Severity::severe->value => tr('Severe')
                ]))
            ->addDefinition(Definition::new($this, 'title')
                ->setLabel(tr('Title'))
                ->setDisabled(true)
                ->setSize(12)
                ->setMaxlength(4)
                ->setMaxlength(255))
            ->addDefinition(Definition::new($this, 'details')
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Details'))
                ->setDisabled(true)
                ->setSize(12)
                ->setMaxlength(65_535)
                ->setDisplayCallback(function(mixed $value, array $source) {
                    // Since the details almost always have an array encoded in JSON, decode it and display it using
                    // print_r
                    if (!$value) {
                        return null;
                    }

                    try {
                        $return  = '';
                        $details = Json::decode($value);
                        $largest = Arrays::getLongestKeySize($details);

                        foreach ($details as $key => $value) {
                            $return .= Strings::size($key, $largest) . ' : ' . $value . PHP_EOL;
                        }

                        return $return;

                    } catch (JsonException $e) {
                        // We couldn't decode it! Why? No idea, but lets just move on, its not THAT important.. yet.
                        Log::warning($e);
                        return $value;
                    }
                }));
    }
}