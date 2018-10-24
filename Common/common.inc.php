<?php

use Firebase\JWT\JWT;

/*
 * Obtains the JWT from a predefined file
 * if the file doesn't exists, generate random
 * key and write that key to a file.
 * This file is being generated on every reboot.
 */
function get_jwt_key() {
    global $config;
    $content=null;
    if (file_exists($config["jwtKeyFile"]))
        $content=@file_get_contents($config["jwtKeyFile"]);

    if ($content==null||$content=="") {
        $key=rand_string(20);
        file_put_contents($config["jwtKeyFile"],$key);
        return $key;
    }

    return $content;
}

/*
 * Generate random string with a desired length
 */
function rand_string( $length ) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    return substr(str_shuffle($chars),0,$length);
}

/*
 * Common function to obtain current network setting
 */
function getNetwork() {
    global $config;
    $networkFileParsed=@parse_ini_file($config["interfacesDirectory"].$config["interfacesFile"]);

    $dhcp="dhcp";
    if ($networkFileParsed!=null) {
        if (array_key_exists("DHCP", $networkFileParsed) && $networkFileParsed["DHCP"] == 1) {
            $dhcp = "dhcp";
        } else {
            $dhcp = "static";
        }
    }
    $ip   = exec("ifconfig | grep inet | sed -n '1p' | awk '{print $2}' | awk -F ':' '{print $2}'");
    $netmask= exec("ifconfig |grep inet| sed -n '1p'|awk '{print $4}'|awk -F ':' '{print $2}'");
    $gw = exec("route -n | grep eth0 | grep UG | awk '{print $2}'");
    $dns[0]="";
    $dns[1]="";
    if (file_exists($config["resolvFile"])) {
        $dnsContent = file($config["resolvFile"], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $i=0;
        foreach ($dnsContent as $item) {
            $dnsParts=explode("nameserver ",$item);
            if(isset($dnsParts[1])) {
                $dns[$i]=trim($dnsParts[1]);
                $i++;
            }
        }
    }
    return array(
        "dhcp"=>$dhcp,
        "ipaddress"=>$ip,
        "netmask"=>$netmask,
        "gateway"=>$gw,
        "dns1"=>$dns[0],
        "dns2"=>$dns[1]);
}

/*
 * It returns the name of the user (admin/guest) if valid credentials are provided
 * through JWT or Basic Auth. returns null if the authentication fail or "expired" if the
 * JWT is expired
 */
function getLoggedUser() {
    global $config;
    $token=getBearerToken();
    if ($token!=null||(isset($_SERVER['PHP_AUTH_USER'])&&isset($_SERVER['PHP_AUTH_USER'])&&isset($_SERVER['PHP_AUTH_PW']))) {

        if ($token == null&&isset($_SERVER['PHP_AUTH_USER'])) {
            if (isset($_SERVER['PHP_AUTH_USER'])&&isset($_SERVER['PHP_AUTH_PW'])) {
                $username=preg_replace("/[^a-zA-Z0-9_\-]+/","",$_SERVER['PHP_AUTH_USER']);
                $users=array();
                if (file_exists($config["usersFile"])) {
                    $configContent=@file_get_contents($config["usersFile"]);
                } else {
                    if ($username == $config["userAdmin"] && $_SERVER['PHP_AUTH_PW'] == $config["passwordAdmin"]) {
                        return $config["userAdmin"];
                    } elseif ($username == $config["userGuest"] && $_SERVER['PHP_AUTH_PW'] == $config["passwordGuest"]) {
                        return $config["userGuest"];
                    } else {
                        return null;
                    }
                }
                if (isset($configContent)&&$configContent!=null&&$configContent!="") {
                    $users = json_decode($configContent, true);
                }

                foreach ($users as $user) {
                    if ($username == $user["username"]) {
                        if (generatePasswordHash($_SERVER['PHP_AUTH_PW'])==$user["password"]) {
                            return $user["username"];
                        } else {
                            return null;
                        }
                    }
                }
            }
        } else {
            try {
                $decoded = JWT::decode($token, get_jwt_key(), array('HS256'));
                return $decoded->user;
            }catch(\Exception $e) {
                return "expired";
            }
        }
    }
    return null;
}

/*
 * Validate an IP address
 */
function isValidIp($ip) {
    if (long2ip(ip2long($ip)) == $ip) {
        return true;
    }
    return false;
}

/*
 * Convert IP Mask Address format to Cidr, needed for systemd network
 * configuration file
 */
function mask2cidr($mask){
    $long = ip2long($mask);
    $base = ip2long('255.255.255.255');
    return 32-log(($long ^ $base)+1,2);
}

/*
 * Generate a hash using a predefined salt
 */
function generatePasswordHash($password) {
    global $config;
    return hash('sha256', $config["salt"].$password);
}

/*
 * Remove special characters from the worker, not implemented yet
 */
function cleanWorker($string) {
    $string = str_replace(' ', '-', $string);
    return preg_replace('/[^A-Za-z0-9\-\.\_]/', '', $string);
}

/*
 * Multi server function to obtain the Headers Autorization
 */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/*
 * Get HardWare version from
 */
function getHardwareVersion() {
    global $config;
    $fileContent=@file_get_contents($config["hardwareVersionFile"]);
    $hwVersion="";
    if ($fileContent!=null&&strlen($fileContent)>0) {
        $hwVersionParts=explode(" ",trim($fileContent));
        $hwVersion=$hwVersionParts[0];
    }
    return $hwVersion;
}

/*
 * Get Hardware Type
 */
function getMinerType() {
    global $config;
    $fileContent=@file_get_contents($config["hardwareVersionFile"]);
    $typeVersion="";
    if ($fileContent!=null&&strlen($fileContent)>0) {
        $hwVersionParts=explode(" ",trim($fileContent));
        $typeVersionParts=explode(".",trim($hwVersionParts[1]));
        $typeVersion=strtoupper($typeVersionParts[0]);
    }
    return $typeVersion;
}

/*
 * Get Versions Array
 */
function getVersions() {
    global $config;
    //Version
    $version="";
    $buildDate="";
    $hardwareVersion="";

    $fileContent=@file_get_contents($config["hardwareVersionFile"]);
    if ($fileContent!=null&&$fileContent!="") {
        $hwVersionParts=explode(" ",trim($fileContent));
        if (count($hwVersionParts)>0) {
            $hardwareVersion=$hwVersionParts[0];
        }
    }


    $flag=trim(exec('/usr/sbin/fw_printenv -n image_flag'));
    if ($flag!="") {
        if ($flag=="0") {
            $version=trim(exec('/usr/sbin/fw_printenv -n version_0'));
        } else if ($flag=="1") {
            $version=trim(exec('/usr/sbin/fw_printenv -n version_1'));
        }
    }
    if ($version!=""){
        $date=getDateFromVersion($version);
        if (!is_null($date)) {
            $buildDate=date_format($date,'jS \of F Y h:i A');
        }
    }

    $macAddress=exec('cat /sys/class/net/eth0/address');


    return array("hwver"=>$hardwareVersion,
        "ethaddr"=>$macAddress,
        "build_date"=>$buildDate,
        "platform_v"=>$version);
}

/*
 * Get Data from File or Version
 */
function getDateFromVersion($version) {
    $versionParts=explode("_",$version);
    $date=null;
    if (count($versionParts)>=3) { //Well formatted
        $date = date_create_from_format('Ymd His', $versionParts[1] . " " . $versionParts[2]);
    }
    return $date;
}

/*
 * Get Timestamp from File or Version
 */
function getTimestampFromVersion($version) {
    $versionParts=explode("_",$version);
    $date=null;
    if (count($versionParts)>=3) { //Well formatted
        $date = date_create_from_format('Ymd His', $versionParts[1] . " " . $versionParts[2]);
        $timestamp = date_timestamp_get($date);
    }
    return $timestamp;
}

/*
 * Parses the authorization header of a request and obtain the Baerer Token
 */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}
// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

/*
 * Get Data using PHP curl with the defined $params
 */
function getUrlData($url,$params=null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (is_null($params)) {
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        $query=http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url."?".$query);
    }
    $result=curl_exec($ch);
    curl_close($ch);

    if ($result!=null&&$result!="") {
        return $result;
    }
    return null;
}

/*
 * Check if Auto Tune is Enabled
 */
function isAutoTuneEnabled() {
    global $config;
    $configContent=@file_get_contents($config["configFile"]);
    if ($configContent!=null&&$configContent!="") {
        $configArray = json_decode($configContent, true);
        if (is_array($configArray)&&array_key_exists(getMinerType()."noauto",$configArray)) {
            return false;
        }

    }
    return true;
}

/*
 * Returns cgminer autotune mode
 */
function getAutoTuneConfig() {
    global $config;
    header('Content-Type: application/json');

    $mode="balanced"; //default
    $configContent=@file_get_contents($config["profileFile"]);
    if ($configContent!=null&&$configContent!="") {
        $profileFile = json_decode($configContent, true);
        if (!is_null($profileFile)&&isset($profileFile["mode"])) {
            $mode=$profileFile["mode"];
        }
    }
    return $mode;
}