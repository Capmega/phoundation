<?php

namespace Phoundation\Security;

use Phoundation\Data\DataList;



/**
 * Class Incidents
 *
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Security
 */
class Incidents extends DataList
{
    /**
     * Incidents class constructor
     */
    public function __construct()
    {
        $this->entry_class = Incident::class;
        $this->setHtmlQuery('SELECT `id`, `type`, `severity`, `titl` FROM `security_incidents`');
        parent::__construct(null, null);
    }



    protected function load(?string $id_column = null): static
    {
        // TODO: Implement load() method.
    }

    protected function loadDetails(array|string|null $columns, array $filters = []): array
    {
        // TODO: Implement loadDetails() method.
    }

    public function save(): static
    {
        // TODO: Implement save() method.
    }
}