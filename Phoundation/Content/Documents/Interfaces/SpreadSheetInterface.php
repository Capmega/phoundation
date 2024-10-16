<?php

namespace Phoundation\Content\Documents\Interfaces;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


interface SpreadSheetInterface
{
    /**
     * Returns the PhpSpreadsheet for this
     *
     * @return Worksheet
     */
    public function getWorkSheet(): Worksheet;
}
