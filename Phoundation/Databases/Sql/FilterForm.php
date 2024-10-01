<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Input\Buttons\Buttons;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\Url;


class FilterForm extends \Phoundation\Web\Html\Components\Forms\FilterForm
{
    /**
     * FilterForm class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setRequestMethod(EnumHttpRequestMethod::post);

        parent::__construct($content);

        // Set basic definitions
        $this->definitions->setRender('date_range', false)
                          ->setRender('users_id'  , false)
                          ->setRender('status'    , false);

        $this->definitions->add(Definition::new(null, 'query')
                                          ->setLabel(tr('Query'))
                                          ->setSize(12)
                                          ->setOptional(true)
                                          ->setAutoSubmit(true)
                                          ->setElement(EnumElement::textarea)
                                          ->setRows(10));

        $this->definitions->addButtons(Buttons::new()->addButton(tr('Execute')));

        // Auto apply
        $this->applyValidator(self::class);
    }


    /**
     * Returns the query, if specified
     *
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->get('query');
    }
}
