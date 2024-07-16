<?php

declare(strict_types=1);

namespace Phoundation\Web\Routing\Interfaces;

interface MapInterface
{
    public function apply($url): string;
}
