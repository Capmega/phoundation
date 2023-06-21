<?php

declare(strict_types=1);

namespace Phoundation\Security\Incidents;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryDetails;
use Phoundation\Data\DataEntry\Traits\DataEntryTitle;
use Phoundation\Data\DataEntry\Traits\DataEntryType;
use Phoundation\Security\Incidents\Exception\IncidentsException;
use Phoundation\Web\Http\Html\Enums\InputElement;


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
     * Incident class constructor
     *
     * @param DataEntryInterface|string|int|null $identifier
     * @param bool $init
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, bool $init = false)
    {
        $this->table        = 'security_incidents';
        $this->entry_name   = 'incident';
        $this->unique_field = 'id';

        parent::__construct($identifier, $init);
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
     * Returns the severity for this object
     *
     * @return string
     */
    public function getSeverity(): string
    {
        return $this->getDataValue('string', 'severity', Severity::unknown->value);
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

        return $this->setDataValue('severity', $severity->value);
    }


    /**
     * Saves the incident to database
     *
     * @param string|null $comments
     * @return bool
     */
    public function save(?string $comments = null): bool
    {
        if ($this->log) {
            $severity = strtolower($this->getSeverity());

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

        return parent::save();
    }


    /**
     * Throw an incidents exception
     *
     * @return never
     */
    #[NoReturn] public function throw(): never
    {
        throw IncidentsException::new($this->getTitle(), $this->getDetails());
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function initDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->add(Definition::new('type')
                ->setLabel(tr('Incident type'))
                ->setDisabled(true)
                ->setDefault(tr('Unknown'))
                ->setSize(6)
                ->setMaxlength(6))
            ->add(Definition::new('severity')
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
            ->add(Definition::new('title')
                ->setLabel(tr('Title'))
                ->setDisabled(true)
                ->setSize(12)
                ->setMaxlength(4)
                ->setMaxlength(255))
            ->add(Definition::new('details')
                ->setElement(InputElement::textarea)
                ->setLabel(tr('Details'))
                ->setDisabled(true)
                ->setSize(12)
                ->setMaxlength(65_535));

//        ->select($this->getAlternateValidationField('type'), true)->isOptional()->hasMaxCharacters(64)->isPrintable()
//        ->select($this->getAlternateValidationField('severity'), true)->hasMaxCharacters(6)->isInArray(['notice', 'low', 'medium', 'high', 'severe'])
//        ->select($this->getAlternateValidationField('title'), true)->hasMaxCharacters(255)->isPrintable()
//        ->select($this->getAlternateValidationField('details'), true)->isOptional()->hasMaxCharacters(65535)->isPrintable()
//        ->noArgumentsLeft($no_arguments_left)

    }
}