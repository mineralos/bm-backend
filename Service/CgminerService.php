<?php
namespace DragonMint\Service;



class CgminerService {


    /*
     * Open a socket connection to localhost and a predefined port and return the response
     * the cgminer API
     */
    public function call($cmd,$parameter=null) {
        global $config;
        $sock=$this->getSock("127.0.0.1",$config["cgminerPort"]);
        $response=null;
        if ($sock!=null) {
                $response=$this->request($sock,$cmd,$parameter);
        }
        return $response;
    }

    /*
     * Create a sock from especified address and port
     */
    public function getSock($addr, $port)
    {
        try {
            $socket = null;
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false || $socket === null) {
                $error = socket_strerror(socket_last_error());
                return null;
            }
            $res = @socket_connect($socket, $addr, $port);
            if ($res === false) {
                $error = socket_strerror(socket_last_error());
                socket_close($socket);
                return null;
            }
            return $socket;
        } catch(\Exception $e) {
            return null;
        }
    }



    /*
     * Read one line from the received buffer of the cgminer API call
     */
    public function readSockLine($socket)
    {
        $line = '';
        while (true)
        {
            $byte = socket_read($socket, 1);
            if ($byte === false || $byte === '')
                break;
            if ($byte === "\0")
                break;
            $line .= $byte;
        }
        return $line;
    }

    /*
     * Send the command(s) to the socket and reply a JSON response from the data obtained
     */
    public function request($socket,$cmd,$parameter=null)
    {
        try {
            if ($socket != null)
            {
                $parameterStr=($parameter==null?"":$parameter);
                $cmd='{"command":"'.$cmd.'","parameter":"'.$parameterStr.'"}';
                socket_write($socket, $cmd, strlen($cmd));
                $line = $this->readsockline($socket);

                socket_close($socket);

                if (strlen($line) == 0)
                {
                    return null;
                }

                if (substr($line,0,1) == '{') {
                    $line=preg_replace('/[^(\x20-\x7F)]*/','', $line);
                    return json_decode($line, true);

                }
            }

        } catch(\Exception $e) {
            return null;
        }
        return null;
    }


}