<?php

declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Secrets;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\File;
use Phoundation\Virtualization\Kubernetes\ObjectFile;

/**
 * Class SecretFile
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Virtualization
 *
 * @example
    apiVersion: v1
    kind: Secret
    metadata:
      name: mysecret
    type: Opaque
    data:
      username: YWRtaW4=
        password: MWYyZDFlMmU2N2Rm
 *
 * @note The values must be BASE64 encoded!
 */
class SecretFile extends ObjectFile
{
    /**
     * SecretFile class constructor
     */
    public function __construct(Secret $secret)
    {
        parent::__construct($secret);
    }


    /**
     * Returns the deployment data object for this deployment file
     *
     * @return Secret
     */
    public function getSecret(): Secret
    {
        return $this->object;
    }


    /**
     * Builds the data string for this deployment file from the Secret object
     *
     * @param array|null $configuration
     * @return array
     */
    protected function buildConfiguration(?array $configuration = null): array
    {
        // Encode all data values in base64
        $data        = [];
        $object_data = $this->object->getData();

        if ($object_data) {
            foreach ($object_data as $key => &$value) {
                $data[$key] = base64_encode($value);
            }

            unset($value);
        }

        return parent::buildConfiguration([
            'type' => 'Opaque',
            'data' => $data
        ]);
    }
}