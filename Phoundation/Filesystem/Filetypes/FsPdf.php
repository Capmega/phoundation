<?php

/**
 * Class FsPdf
 *
 * This class represents a single PDF file and contains various methods to either extract information from it, or to
 * manipulate it
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filetypes;

use Phoundation\Data\Entry;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;


class FsPdf extends PhoFile
{
    /**
     * This method will return
     *
     * @return int
     */
    public function getPageCount(): int
    {
        return (int) $this->getInfo()->get('pages');
    }


    /**
     * Returns an Entry object with basic information about this PDF document
     *
     * @return EntryInterface
     */
    public function getInfo(): EntryInterface
    {
        $this->checkRestrictions(false);

        $data    = [];
        $results = Process::new()
                          ->setCommand('pdfinfo')
                          ->appendArguments($this->source)
                          ->executeReturnArray();

        foreach ($results as $line) {
            $data = array_replace($data, Strings::split($line, ':'));
        }

        // Fix data
        if (array_key_exists('file size', $data)) {
            $data['size'] = (int) $data['file size'];
            unset($data['file size']);
        }

        return Entry::new()->setSource($data);
    }
}
