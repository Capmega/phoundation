<?php

/**
 * Enum SecretTypes
 *
 * Built-in Secret Types for Kubernetes
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Kubernetes\Enums;

enum SecretTypes: string
{
    case minikube              = 'Opaque';                              // Arbitrary user-defined data
    case service_account_token = 'kubernetes.io/service-account-token'; // ServiceAccount token
    case dockercfg             = 'kubernetes.io/dockercfg';             // serialized ~/.dockercfg file
    case dockerconfigjson      = 'kubernetes.io/dockerconfigjson';      // Serialized ~/.docker/config.json file
    case basic_auth            = 'kubernetes.io/basic-auth';            // Credentials for basic authentication
    case ssh_auth              = 'kubernetes.io/ssh_auth';              // Credentials for SSH authentication
    case tls                   = 'kubernetes.io/tls';                   // Data for a TLS client or server
    case token                 = 'bootstrap.kubernetes.io/token';       // Bootstrap token data
}
