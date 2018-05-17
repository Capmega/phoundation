<?php
/*
 * CSF library
 *
 * This is a front-end library to the Config Server Firewall
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Sven Oostenbrink <support@capmega.com>
 */



/*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function csf_library_init(){
    try{
        /*
         * On the command line we can only run this as root user
         */
        if(PLATFORM_CLI){
            cli_root_only();
        }

        load_libs('servers,ssh');

    }catch(Exception $e){
        throw new bException('csf_library_init(): Failed', $e);
    }
}



/*
 * Return the absolute location of the CSF executable binary
 */
function csf_get_exec(){
    static $install = false;

    try{
        $csf = trim(shell_exec('which csf 2> /dev/null'));

        if(!$csf){
            if($install){
                throw new bException('csf_exec(): CSF is not installed, and installation failed', 'failed');
            }

            $install = true;
            csf_install();
            return csf_get_exec();
        }

        return $csf;

    }catch(Exception $e){
        throw new bException('csf_get_exec(): Failed', $e);
    }
}



/*
 *
 */
function csf_exec($hostname, $command){
    try{
        $csf = csf_get_exec();

        if(empty($hostname)){
            throw new bException(tr('csf_exec(): Unknown hostname ":hostname" specified', array(':hostname' => $hostname)), 'unknown');
        }

        return servers_exec($hostname, str_replace(':csf', $csf, $command));

    }catch(Exception $e){
        throw new bException('csf_exec(): Failed', $e);
    }
}



/*
 * Install Config Server Firewall
 */
function csf_install(){
    try{
        if($csf = csf_get_exec()){
            throw new bException(tr('csf_install(): CSF has already been installed and is available at ":csf"', array(':csf' => $csf)), 'executable-not-found');
        }

        copy('https://download.configserver.com/csf.tgz', TMP.'csf.tgz');
        safe_exec('cd '.TMP.'; tar -xf '.TMP.'csf.tgz; cd '.TMP.'csf/; ./install.sh');

        if(!$csf = csf_get_exec()){
            throw new bException('csf_install(): The CSF executable could not be found after installation', 'executable-not-found');
        }

        /*
         * Cleanup
         */
        ssh_exec('rm '.TMP.'csf/ -rf');

        return $csf;

    }catch(Exception $e){
        throw new bException('csf_install(): Failed', $e);
    }
}



/*
 *
 */
function csf_set_restrict_syslog($value){
    //RESTRICT_SYSLOG
}



/*
 *
 */
function csf_set_testing($value){
    //TESTING
}



/*
 * Accepted protocols tcp, udp
 * @param string|array $ports, if $ports is a string it must be separeted by commas example: 12,80,443
 */
function csf_set_ports($hostname, $protocol, $rule_type, $ports){
    try{
        $ports     = csf_validate_ports($ports);
        $rule_type = csf_validate_rule_type($rule_type);
        $protocol  = csf_validate_protocol($protocol);
        $command   = 'sed -i -E \'s/^'.$protocol.'_'.$rule_type.' = \"([0-9]+,)*([0-9]*)\"/'.$protocol.'_'.$rule_type.' = "'.$ports.'"/g\' /etc/csf/csf.conf';

        return csf_exec($hostname, $command);

    }catch(Exception $e){
        throw new bException('csf_set_ports(): Failed', $e);
    }
}



/*
 *
 */
function csf_allow_ip($hostname, $ip=false){
    try{
        $ip = csf_validate_ip($ip);

        return csf_exec($hostname, ':csf -dr '.$ip.'; :csf -a '.$ip);

    }catch(Exception $e){
        throw new bException('csf_allow_ip(): Failed', $e);
    }
}



/*
 *
 */
function csf_deny_ip($hostname, $ip=false){
    try{
        $ip = csf_validate_ip($ip);

        return csf_exec($hostname, ':csf -ar '.$ip.'; :csf -d '.$ip);

    }catch(Exception $e){
        throw new bException('csf_deny_ip(): Failed', $e);
    }
}



/*
 *
 */
function csf_start($hostname=false){
    try{
        return csf_exec($hostname, ':csf -s');

    }catch(Exception $e){
        throw new bException('csf_start(): Failed', $e);
    }
}



/*
 *
 */
function csf_stop($hostname=false){
    try{
        return csf_exec($hostname, ':csf -f');

    }catch(Exception $e){
        throw new bException('csf_stop(): Failed', $e);
    }
}



/*
 *
 */
function csf_restart($hostname=false){
    try{
        return csf_exec($hostname, ':csf -r');

    }catch(Exception $e){
        throw new bException('csf_restart(): Failed', $e);
    }
}



/*
 * When adding a new rule we need to check if exist on deny rule and remove in
 * order to create the new one
 *
 * @param
 */
function csf_allow_rule($hostname, $protocol, $rule_type, $port, $ip=false){
    try{
        if(empty($port)){
            throw new bException(tr('csf_allow_rule(): Unknown port ":port" specified', array(':port' => $port)), 'unknown');
        }

        $protocol  = csf_validate_protocol($protocol);
        $rule_type = csf_validate_rule_type($rule_type);
        $ip        = csf_validate_ip($ip);
        $rule      = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip;
        $command   = 'if ! grep "'.$rule.'" /etc/csf/csf.allow; then echo "'.$rule.'" >> /etc/csf/csf.allow; fi;';

        return csf_exec($hostname, $command);

    }catch(Exception $e){
        throw new bException('csf_allow_rule(): Failed', $e);
    }
}



/*
 * when adding a new rule we need to check if exist on allow rule and remove in
 * order to create the new one
 */
function csf_deny_rule($hostname, $protocol, $rule_type, $port, $ip=false){
    try{
        if(empty($port)){
            throw new bException(tr('csf_deny_rule(): Unknown port ":port" specified', array(':port' => $port)), 'unknown');
        }

        $protocol  = csf_validate_protocol($protocol);
        $rule_type = csf_validate_rule_type($rule_type);
        $ip        = csf_validate_ip($ip);
        $rule      = $protocol.'|'.$rule_type.'|d='.$port.'|s='.$ip;
        $command   = 'if ! grep "'.$rule.'" /etc/csf/csf.deny; then echo "'.$rule.'" >> /etc/csf/csf.deny; fi;';

        return csf_exec($hostname, $command);

    }catch(Exception $e){
        throw new bException('csf_deny_rule(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_protocol($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('csf_validate_protocol(): No protocol specified'), 'not-specified');
        }

        $protocol = strtoupper($protocol);

        switch($protocol){
            case 'tcp':
                // FALLTHROUGH
            case 'udp':
                // FALLTHROUGH
            case 'tcp6':
                // FALLTHROUGH
            case 'udp6':
                /*
                 * These are valid
                 */
                break;

            default:
                throw new bException(tr('csf_validate_protocol(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'unknown');
        }

        return $protocol;

    }catch(Exception $e){
        throw new bException('csf_validate_protocol(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_rule_type($rule_type){
    try{
        if(empty($rule_type)){
            throw new bException(tr('csf_validate_rule_type(): No rule type specified'), 'not-specified');
        }

        $rule_type = strtolower($rule_type);

        switch($rule_type){
            case 'in':
                // FALLTHROUGH
            case 'out':
                /*
                 * These are valid
                 */
                break;

            default:
                throw new bException(tr('csf_validate_rule_type(): Unknown rule type ":ruletype" specified', array(':ruletype' => $rule_type)), 'unknown');
        }

        return $rule_type;

    }catch(Exception $e){
        throw new bException('csf_validate_rule_type(): Failed', $e);
    }
}



 /*
  *
  */
 function csf_validate_ip($ip){
    try{
        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('csf_validate_ip(): Specified ip ":ip" is not valid', array(':ip' => $ip)), 'invalid');
        }

        return $ip;

    }catch(Exception $e){
        throw new bException('csf_validate_ip(): Failed', $e);
    }
}



/*
 *
 */
function csf_validate_ports($ports){
    try{
        if(empty($ports)){
            throw new bException(tr('csf_validate_ports(): No ports specified'), 'not-specified');
        }

        foreach(array_force($ports) as $port){
            if(!is_natural($port) or ($port > 65535)){
                throw new bException(tr('csf_validate_ports(): Invalid port ":port" specified', array(':port' => $port)), 'invalid');
            }
        }

        return $ports;

    }catch(Exception $e){
        throw new bException('csf_validate_ports(): Failed', $e);
    }
}
?>