<?php

namespace Phoundation\Processes;

use Phoundation\Data\DataEntry\DataEntryFieldDefinition;
use Phoundation\Data\DataEntry\DataList;
use Phoundation\Web\Http\Html\Enums\InputType;


/**
 * Class Tasks
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Data
 */
class Tasks extends DataList
{
    /**
     * @inheritDoc
     */
    protected function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }

    /**
     * @inheritDoc
     */
    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }

    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // TODO: Implement save() method.
    }
}