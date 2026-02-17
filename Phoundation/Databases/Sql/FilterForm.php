<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Databases
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Definitions;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
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
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        $this->setRequestMethod(EnumHttpRequestMethod::post);

        parent::__construct($source);

        // Set basic definitions
        $this->_definitions->setDefinitionRender('date_range', false)
                            ->setDefinitionRender('users_id'  , false)
                            ->setDefinitionRender('status'    , false);

        $this->_definitions->add(DefinitionFactory::newDescription('query')
                                                   ->setLabel(tr('Query'))
                                                   ->setSize(12)
                                                   ->setOptional(true)
                                                   ->setAutoSubmit(true)
                                                   ->setCliColumn('-q,--query "QUERY"')
                                                   ->setRows(10));

        $this->_definitions->addButtons(Buttons::new()->addButton(tr('Execute')));
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
