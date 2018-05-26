<?php
/*
 * Route library
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Capmega <copyright@capmega.com>
 */



 /*
 * Initialize the library
 * Automatically executed by libs_load()
 */
function iptables_library_init(){
    try{
        load_libs('servers');
        define('IPTABLES_BUFFER', '__BUFFER__');

    }catch(Exception $e){
        throw new bException('iptables_library_init(): Failed', $e);
    }
}



/*
 * Execute iptables with the specified parameters
 *
 * @param string|integer $server The unique name or id of the host where to execute the iptables command
 * @param string $parameters
 * @return mixed The output of servers_exec() for the specified host with the specified parameters
 */
function iptables_exec($server, $parameters = null){
    static $commands = array();

    try{
        if($server === IPTABLES_BUFFER){
            /*
             * Buffer the commands
             */
            $commands[] = 'sudo iptables '.$entry['parameters'];
            return false;
        }

        if($commands){
            /*
             * Execute all commands from the
             */
            $command  = implode(';', $commands);
            $commands = array();

            servers_exec($server, $command);
        }

        if(!$parameters){
            /*
             * Don't do anything, this is usually used to flush the command
             * buffer
             */
            return false;
        }

        return servers_exec($server, 'sudo iptables '.$parameters);

    }catch(Exception $e){
        throw new bException('iptables_exec(): Failed', $e);
    }
}



/*
 * @param mixed $server The unique name or id of the host where to execute the iptables command
 */
function iptables_set_forward($server, $value = 1){
    try{
        servers_exec($server, 'echo '.$value.' > /proc/sys/net/ipv4/ip_forward');

    }catch(Exception $e){
        throw new bException('iptables_set_forward(): Failed', $e);
    }
}



/*
 * @param mixed $server The unique name or id of the host where to execute the iptables command
 */
function iptables_flush_nat_rules($server){
    try{
        servers_exec($server, 'iptables -t nat -F');

    }catch(Exception $e){
        throw new bException('iptables_flush_nat_rules(): Failed', $e);
    }
}



/*
 * Adds a prerouting rule on iptables
 *
 * @example usage iptables -t nat -A PREROUTING -p tcp --dport 80 -j DNAT --to-destination 255.255.255.255:401
 * Sets a new iptables rulte for port forwarding
 * @param string|integer $server The unique name or id of the host where to execute the iptables command
 * @param string $protocol
 * @param integer $origin_port
 * @param integer $destination_port
 * @param string $destination_ip
 */
function iptables_add_prerouting($server, $protocol, $origin_port, $destination_port, $destination_ip){
    try{
        $protocol         = iptables_validate_protocol($protocol);
        $origin_port      = iptables_validate_port($origin_port);
        $destination_port = iptables_validate_port($destination_port);
        $destination_ip   = iptables_validate_ip($destination_ip);

        iptables_exec($server, '-t nat -A PREROUTING -p tcp --dport '.$origin_port.' -j DNAT --to-destination '.$destination_ip.':'.$destination_port);

    }catch(Exception $e){
        throw new bException('iptables_add_prerouting(): Failed', $e);
    }
}



/*
 * Adds a postrouting rule on iptables
 *
 * @example usage iptables -t nat -A POSTROUTING -p tcp -d 255.210.102.105 --dport 40001 -j SNAT --to-source 255.255.255.255
 * @param string|integer $server The unique name or id of the host where to execute the iptables command
 * @param string $protocol
 * @param integer $port
 * @param string $destination_ip
 */
function iptables_add_postrouting($server, $protocol, $port, $destination_ip){
    try{
        $protocol       = iptables_validate_protocol($protocol);
        $port           = iptables_validate_port($port);
        $destination_ip = iptables_validate_ip($destination_ip);
        $public_ip      = servers_get_public_ip($server);

        iptables_exec($server, '-t nat -A POSTROUTING -p tcp -d '.$destination_ip.' --dport '.$port.' -j SNAT --to-source '.$public_ip);

    }catch(Exception $e){
        throw new bException('iptables_add_postrouting(): Failed', $e);
    }
}



/*
 * Flush all iptables rules
 *
 * @param string|integer $server The unique name or id of the host where to execute the iptables command
 */
function iptables_flush_all($server){
    try{
        iptables_exec($server, '-F');

    }catch(Exception $e){
        throw new bException('iptables_flush_all(): Failed', $e);
    }
}



/*
 * Flush all nat rules on iptables
 *
 * @example usage iptables -t nat -F
 * @param string|integer $server The unique name or id of the host where to execute the iptables command
 * @see iptables_clean_nat_chain()
 */
function iptables_clean_chain_nat($server){
    try{
        iptables_exec($server, '-t nat -F');

    }catch(Exception $e){
        throw new bException('iptables_clean_chain_nat(): Failed', $e);
    }
}



/*
 * Deletes all iptables rules
 *
 * @param string $server The unique name or id of the host where to execute the iptables command
 * @see iptables_delete_all()
 */
function iptables_delete_all($server){
    try{
        iptables_exec($server, '-X');

    }catch(Exception $e){
        throw new bException('iptables_delete_all(): Failed', $e);
    }
}



/*
 * Validates an specific IP
 *
 * @param string $ip
 * @return string $ip
 * @see iptables_validate_port()
 * @see iptables_validate_protocol()
 * @see iptables_validate_chain_type()
 */
 function iptables_validate_ip($ip){
    try{
        if(filter_var($ip, FILTER_VALIDATE_IP) === false){
            throw new bException(tr('iptables_validate_ip(): Specified ip ":ip" is not valid', array(':ip' => $ip)), 'invalid');
        }

        return $ip;

    }catch(Exception $e){
        throw new bException('iptables_validate_ip(): Failed', $e);
    }
}



/*
 * Validates a protocol in order to add a iptables rule
 *
 * @param string $protocol
 * @return string $protocol
 * @see iptables_validate_protocol()
 */
function iptables_validate_protocol($protocol){
    try{
        if(empty($protocol)){
            throw new bException(tr('iptables_validate_protocol(): No protocol specified'), 'not-specified');
        }

        $protocol = strtolower($protocol);

        switch($protocol){
            case 'tcp':
                // FALLTHROUGH
            case 'udp':
                // FALLTHROUGH
            case 'icmp':
                // FALLTHROUGH
                break;

            default:
                throw new bException(tr('iptables_validate_protocol(): Unknown protocol ":protocol" specified', array(':protocol' => $protocol)), 'unknown');
        }

        return $protocol;

    }catch(Exception $e){
        throw new bException('iptables_validate_protocol(): Failed', $e);
    }
}



/*
 * Validates a specific port, it must be in a range to be added on iptables rules
 *
 * @param integer $port
 * @return integer $port
 * @see iptables_validate_protocol()
 * @see iptables_validate_chain_type()
 */
function iptables_validate_port($port){
    try{
        if(empty($port)){
            throw new bException(tr('iptables_validate_port(): No port specified'), 'not-specified');
        }

        if(!is_natural($port) or ($port > 65535)){
            throw new bException(tr('iptables_validate_port(): Invalid port ":port" specified', array(':port' => $port)), 'invalid');
        }

        return $port;

    }catch(Exception $e){
        throw new bException('iptables_validate_port(): Failed', $e);
    }
}



/*
 * Validates a chain type, valid chain type: prerouting, postrouting
 *
 * @param string $chain_type, available type = prerouting, postrouting
 * @return string $chain_type
 * @see iptables_validate_chain_port()
 * @see iptables_validate_chain_protocol()
 */
function iptables_validate_chain_type($chain_type){
    try{
        if(empty($chain_type)){
            throw new bException(tr('iptables_validate_chain_type(): No chain type specified'), 'not-specified');
        }

        $chain_type = strtolower($chain_type);

        switch($chain_type){
            case 'prerouting':
                // FALLTHROUGH
            case 'postrouting':
                //valid chaing types
                break;

            default:
                throw new bException(tr('iptables_validate_chain_type(): Unknown chain type ":chaintype" specified', array(':chaintype' => $chain_type)), 'unknown');
        }

        return $chain_type;

    }catch(Exception $e){
        throw new bException('iptables_validate_chain_type(): Failed', $e);
    }
}
?>