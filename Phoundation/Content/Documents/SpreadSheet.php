<?php

/**
 * Class SpreadSheet
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Content
 */


declare(strict_types=1);

namespace Phoundation\Content\Documents;

use Phoundation\Content\Documents\Interfaces\SpreadSheetInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataSourceArray;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class SpreadSheet implements SpreadSheetInterface
{
    use TraitDataSourceArray;


    /**
     * The PhpSpreadsheet work sheet
     *
     * @var Worksheet $sheet
     */
    protected Worksheet $sheet;


    /**
     * SpreadSheet class constructor
     *
     * @param DataEntryInterface|IteratorInterface|array $source
     */
    public function __construct(DataEntryInterface|IteratorInterface|array $source)
    {
        if (is_object($source)) {
            // Extract the source data array from the specified source object
            $source = $source->getSource();
        }

        $this->source = $source;
    }


    /**
     * Returns the PhpSpreadsheet for this
     *
     * @return Worksheet
     */
    public function getWorkSheet(): Worksheet
    {
        return $this->sheet;
    }
}
