<?php

/**
 * Class FsPdf
 *
 * This class represents a single PDF file and contains various methods to either extract information from it, or to
 * manipulate it
 *
 * @author    Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license   This plugin is developed by Medinet and may only be used by others with explicit written authorization
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Filetypes;

use Phoundation\Data\Entry;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Filesystem\FsFile;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;


class FsPdf extends FsFile
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
                          ->addArguments($this->source)
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
