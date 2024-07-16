<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing\Interfaces;

interface MappingInterface
{
    public function apply($url): string;
}
