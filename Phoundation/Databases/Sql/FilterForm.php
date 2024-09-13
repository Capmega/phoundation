<?php

/**
 * Class FilterForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Databases\Sql;

use Phoundation\Data\DataEntry\Definitions\Definition;
use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Enums\EnumElement;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
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
        parent::__construct($content);

        if (empty($this->source)) {
            // Pull all filter data from HTTP GET
            $this->source = PostValidator::new()
                ->select('query')->isOptional()->hasMaxCharacters(8192)->isPrintable()
                ->validate(false);
        }

        // Make sure this is a submittable form with GET method
        $this->setId('filters')
            ->useForm(true)
            ->getForm()
            ->setRequestMethod(EnumHttpRequestMethod::post)
            ->setAction(Url::getWww());

        // Set basic definitions
        $this->definitions = Definitions::new()
            ->add(Definition::new(null, 'query')
                ->setLabel(tr('Query'))
                ->setSize(12)
                ->setOptional(true)
                ->setAutoSubmit(true)
                ->setElement(EnumElement::textarea)
                ->setRows(10));
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
