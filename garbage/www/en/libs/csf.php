<?php
/*
 * CSF library
 *
 * This is a front-end library to the Config Server Firewall
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function csf_library_init() {
    try {
        /*
         * On the command line we can only run this as root user
         */
        if (PLATFORM_CLI) {
            cli_root_only();
        }

        load_libs('servers,ssh');

    }catch(Exception $e) {
        throw new CoreException('csf_library_init(): Failed', $e);
    }
}



/*
 * Install Config Server Firewall
 */
function csf_install($server = null) {
    try {
        if ($csf = csf_get_exec($server)) {
            throw new CoreException(tr('csf_install(): CSF has already been installed and is available at ":csf"', array(':csf' => $csf)), 'executable-not-exists');
        }

        $command = 'wget --directory-prefix=/tmp https://download.configserver.com/csf.tgz; cd /tmp/; tar -xf csf.tgz; cd csf/; sh install.sh;';
        csf_exec($server, $command);
        return csf_get_exec($server);

    }catch(Exception $e) {
        throw new CoreException('csf_install(): Failed', $e);
    }
}



/*
 * Start CSF on the specified server. After that, IF required, re-apply
 * programmed forwarding rules, since those are iptable, and CSF will reset all
 * iptable rules
 *
 * @param mixed $server (optional) The server where to start CSF
 * @return void
 */
function csf_start($server = null) {
    try {
        load_libs('forwards');
        csf_exec($server, ':csf -s');
        forwards_apply_server($server);

    }catch(Exception $e) {
        throw new CoreException('csf_start(): Failed', $e);
    }
}



/*
 * Stop CSF on the specified server. After that, IF required, re-apply
 * programmed forwarding rules, since those are iptable, and CSF will reset all
 * iptable rules
 *
 * @param mixed $server (optional) The server where to start CSF
 * @return void
 */
function csf_stop($server = null) {
    try {
        load_libs('forwards');
        csf_exec($server, ':csf -f');
        forwards_apply_server($server);

    }catch(Exception $e) {
        throw new CoreException('csf_stop(): Failed', $e);
    }
}



/*
 * Restart CSF on the specified server. After that, IF required, re-apply
 * programmed forwarding rules, since those are iptable, and CSF will reset all
 * iptable rules
 *
 * @param mixed $server (optional) The server where to start CSF
 * @return void
 */
function csf_restart($server = null) {
    try {
        load_libs('forwards');
        csf_exec($server, ':csf -r');
        forwards_apply_server($server);

    }catch(Exception $e) {
        throw new CoreException('csf_restart(): Failed', $e);
    }
}



/*
 * Return the absolute location of the CSF executable binary
 */
function csf_get_exec($server = null) {
    static $install = false;

    try {
        $csf = trim(servers_exec($server, 'which csf 2> /dev/null'));

        if (!$csf) {
            if (!$install) {
                throw new CoreException('csf_exec(): CSF is not installed, and installation failed', 'failed');
            }

            $install = true;
            csf_install($server);
            return csf_get_exec();
        }

        return $csf;

    }catch(Exception $e) {
        throw new CoreException('csf_get_exec(): Failed', $e);
    }
}



/*
 *
 */
function csf_exec($server, $command) {
    try {
        $csf = csf_get_exec();
        return servers_exec($server, str_replace(':csf', $csf, $command));

    }catch(Exception $e) {
        throw new CoreException('csf_exec(): Failed', $e);
    }
}



/*
 *
 */
function csf_set_restrict_syslog($server, $value) {
    try {
        $value   = csf_validate_restrictsyslog($value);
        $command = 'sed -i -E \'s/^RESTRICT_SYSLOG = \"[0-3]\"/RESTRICT_SYSLOG = "'.$value.'"/g\' /etc/csf/csf.conf';

        return csf_exec($server, $command);

    }catch(Exception $e) {
        throw new CoreException('csf_set_testing(): Failed', $e);
    }
}



/*
 *
 */
function csf_set_testing($server, $value) {
    try {
        $value   = csf_validate_testing($value);
        $command = 'sed -i -E \'s/^TESTING = \"(0|1)\"/TESTING = "'.$value.'"/g\' /etc/csf/csf.conf';

        return csf_exec($server, $command);

    }catch(Exception $e) {
        throw new CoreException('csf_set_testing(): Failed', $e);
    }
}



/*
 * Accepted protocols tcp, udp
 * @param string|array $ports, if $ports is a string it must be separeted by commas example: 12,80,443
 */
function csf_set_ports($server, $protocol, $rule_type, $ports) {
    try {
        $ports     = csf_validate_ports($ports);
        $rule_type = csf_validate_rule_type($rule_type, true);
        $protocol  = csf_validate_protocol($protocol);
        $command   = 'sed -i -E \'s/^'.$protocol.'_'.$rule_type.' = \"([0-9]+,)*([0-9]*)\"/'.$protocol.'_'.$rule_type.' = "'.$ports.'"/g\' /etc/csf/csf.conf';

        return csf_exec($server, $command);

    }catch(Exception $e) {
        throw new CoreException('csf_set_ports(): Failed', $e);
    }
}



/*
 *
 */
function csf_allow_ip($server, $ip=false) {
    try {
        $ip = csf_validate_ip($ip);

        return csf_exec($server, ':csf -dr '.$ip.'; :csf -a '.$ip);

    }catch(Exception $e) {
        throw new CoreException('csf_allow_ip(): Failed', $e);
    }
}



/*
 *
 */
function csf_deny_ip($server, $ip=false) {
    try {
        $ip = csf_validate_ip($ip);
        return csf_exec($server, ':csf -ar '.$ip.'; :csf -d '.$ip);

    }catch(Exception $e) {
        throw new CoreException('csf_deny_ip(): Failed', $e);
    }
}



/*
 * When adding a new rule we need to check if exist on deny rule and remove in
 * order to create the new one
 *
 * @param
 */
function csf_allow_rule($server, $protocol, $rule_type, $port, $ip=false, $comments='') {
    try {
        $port      = csf_validate_ports($port, true);
        $protocol  = csf_validate_protocol($protocol, true);
        $rule_type = csf_validate_rule_type($rule_type);
        $ip        = csf_validate_ip($ip);

        $rule      = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip.' # '.$comments;
        $command   = 'if ! grep "'.$rule.'" /etc/csf/csf.allow; then echo "'.$rule.'" >> /etc/csf/csf.allow; fi;';

        return csf_exec($server, $command);

    }catch(Exception $e) {
        throw new CoreException('csf_allow_rule(): Failed', $e);
    }
}



/*
 * when adding a new rule we need to check if exist on allow rule and remove in
 * order to create the new one
 */
function csf_deny_rule($server, $protocol, $rule_type, $port, $ip=false, $comments='') {
    try {
        $port      = csf_validate_ports($port, true);
        $protocol  = csf_validate_protocol($protocol, true);
        $rule_type = csf_validate_rule_type($rule_type);
        $ip        = csf_validate_ip($ip);
        $rule      = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip.' # '.$comments;
        $command   = 'if ! grep "'.$rule.'" /etc/csf/csf.deny; then echo "'.$rule.'" >> /etc/csf/csf.deny; fi;';

        return csf_exec($server, $command);

    }catch(Exception $e) {
        throw new CoreException('csf_deny_rule(): Failed', $e);
    }
}



/*
 *
 */
function csf_remove_allow_rule($server, $protocol, $connection_type, $port, $ip) {
    try {
        $protocol        = csf_validate_protocol($protocol, true);
        $connection_type = csf_validate_rule_type($connection_type);
        $port            = csf_validate_ports($port, true);

        $result = csf_exec($server, 'sed -i -E \'s/'.$protocol.'\|'.$connection_type.'\|d='.$port.'\|s='.$ip.'//g\' /etc/csf/csf.allow');

        return $result;

    }catch(Exception $e) {
        throw new CoreException('csf_remove_allow_rule(): Failed', $e);
    }
}



/*
 *
 */
function csf_remove_deny_rule($server, $protocol, $connection_type, $port, $ip) {
    try {
        $protocol        = csf_validate_protocol($protocol, true);
        $connection_type = csf_validate_rule_type($connection_type);
        $port            = csf_validate_ports($port);
        $result          = csf_exec($server, 'sed -i -E \'s/'.$protocol.'\|'.$connection_type.'\|d='.$port.'\|s='.$ip.'//g\' /etc/csf/csf.deny');

        return $result;

    }catch(Exception $e) {
        throw new CoreException('csf_deny_rule(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_protocol($protocol, $lower_case = false) {
    try {
        if (empty($protocol)) {
            throw new CoreException(tr('csf_validate_protocol(): No protocol specified'), 'not-specified');
        }

        $protocol = strtoupper($protocol);

        switch ($protocol) {
            case 'TCP':
                // no-break
            case 'UDP':
                // no-break
            case 'TCP6':
                // no-break
            case 'UDP6':
                /*
                 * These are valid
                 */
                break;

            default:
                throw new CoreException(tr('csf_validate_protocol(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'unknown');
        }

        return $lower_case ? strtolower($protocol) : $protocol;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_protocol(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_rule_type($rule_type, $upper_case = false) {
    try {
        if (empty($rule_type)) {
            throw new CoreException(tr('csf_validate_rule_type(): No rule type specified'), 'not-specified');
        }

        $rule_type = strtolower($rule_type);

        switch ($rule_type) {
            case 'in':
                // no-break
            case 'out':
                /*
                 * These are valid
                 */
                break;

            default:
                throw new CoreException(tr('csf_validate_rule_type(): Unknown rule type ":ruletype" specified', array(':ruletype' => $rule_type)), 'unknown');
        }

        return $upper_case ? strtoupper($rule_type) : $rule_type;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_rule_type(): Failed', $e);
    }
}



 /*
  *
  */
 function csf_validate_ip($ip) {
    try {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new CoreException(tr('csf_validate_ip(): Specified ip ":ip" is not valid', array(':ip' => $ip)), 'invalid');
        }

        return $ip;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_ip(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_ports($ports, $single = false) {
    try {
        if (empty($ports)) {
            throw new CoreException(tr('csf_validate_ports(): No ports specified'), 'not-specified');
        }

        $ports = Arrays::force($ports);

        if ($single and (count($ports) > 1)) {
            throw new CoreException(tr('csf_validate_ports(): Multiple ports specified with single port flag'), 'multiple');
        }

        foreach ($ports as $port) {
            if (!is_natural($port) or ($port > 65535)) {
                throw new CoreException(tr('csf_validate_ports(): Invalid port ":port" specified', array(':port' => $port)), 'invalid');
            }
        }

        return $single?$ports[0]:$ports;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_ports(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_testing($value) {
    try {
        if (empty($value)) {
            $value = 0;
        }

        switch ($value) {
            case '0':
                // no-break
            case '1':
                //These are valid
                break;

            default:
                throw new CoreException(tr('csf_validate_testing(): Invalid testing value ":value" specified', array(':value' => $value)), 'invalid');
        }

        return $value;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_testing(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_restrictsyslog($value) {
    try {
        if (empty($value)) {
            $value = 0;
        }

        switch ($value) {
            case '0':
                // no-break
            case '1':
                // no-break
            case '2':
                // no-break
            case '3':
                //These are valid
                break;

            default:
                throw new CoreException(tr('csf_validate_restrictsyslog(): Invalid restrict syslog value ":value" specified', array(':value' => $value)), 'invalid');
        }

        return $value;

    }catch(Exception $e) {
        throw new CoreException('csf_validate_restrictsyslog(): Failed', $e);
    }
}
?>