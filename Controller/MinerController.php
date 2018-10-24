<?php


class MinerController {



    public function __construct(){

    }


    /*
     * Dump logs to a file and send it
     */
    public function getLogsAction() {
        global $config;
        exec("journalctl > ".$config["logsDumpedFile"]);
        if (file_exists($config["logsDumpedFile"])) {
            header('Content-Description: File Transfer');
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="'.basename($config["logsDumpedFile"]).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($config["logsDumpedFile"]));
            readfile($config["logsDumpedFile"]);
        }
    }


    /*
     * Return the result of getType() function in JSON format
     */
    public function getTypeAction() {
        header('Content-Type: application/json');
        echo json_encode(array("success"=>true,"type"=>getMinerType()));
    }


    /*
     * Sends reboot system call to the miner
     */
    public function rebootAction() {
        header('Content-Type: application/json');
        shell_exec('sleep 4;/bin/systemctl reboot >/dev/null 2>/dev/null &');
        echo json_encode(array("success"=>true));
    }

    /*
     * Sends poweroff system call to the miner
     */
    public function poweroffAction() {
        header('Content-Type: application/json');
        shell_exec('sleep 4;/usr/sbin/poweroff >/dev/null 2>/dev/null &');
        echo json_encode(array("success"=>true));
    }

    /*
     * Restart cgminer
     */
    public function restartCgMinerAction() {
        header('Content-Type: application/json');
        shell_exec('/usr/bin/systemctl restart cgminer >/dev/null 2>/dev/null &');
        echo json_encode(array("success"=>true));
    }

    /*
     * Reset to factory reset deleting all the content of /config and then reboot the miner
     */
    public function factoryResetAction() {
        global $config;
        header('Content-Type: application/json');
        shell_exec('sleep 4; rm -rf '.$config["configDirectory"].'/*;/bin/systemctl reboot >/dev/null 2>/dev/null &');
        echo json_encode(array("success"=>true));
    }

    /*
     * Get Memory Values
     */
    public function getMemory() {
        $memTotal=0;
        $memFree=0;
        $memCached=0;
        $memCachedFree=0;
        $memArray=@file('/proc/meminfo');
        foreach ($memArray as $item) {
            $parts=preg_split( "/[ ]{1,}/", $item );
            switch ($parts[0]) {
                case "MemTotal:":
                    $memTotal=intval($parts[1]);
                    break;
                case "MemFree:":
                    $memFree=intval($parts[1]);
                    break;
                case "Cached:":
                    $memCached=intval($parts[1]);
                    break;
                case "MemAvailable:":
                    $memCachedFree=intval($parts[1]);
                    break;
            }
        }
        return array("memTotal"=>$memTotal,"memFree"=>$memFree,"memCached"=>$memCached,"memCachedFree"=>$memCachedFree);
    }




    /*
     * Obtains info from the network, cgminer and system and generate
     * a JSON output with all information obtained
     */
    public function getOverviewAction() {
        global $config;
        header('Content-Type: application/json');
        $uptime=trim(exec("uptime"));

        $memory=$this->getMemory();

        $versions=getVersions();

        //Network
        $network=getNetwork();

        echo json_encode(array(
            "success"=>true,
            "type"=>getMinerType(),
            "hardware"=>array(
                "status"=>$uptime,
                "memUsed"=>$memory["memTotal"]-$memory["memFree"],
                "memFree"=>$memory["memFree"],
                "memTotal"=>$memory["memTotal"],
                "cacheUsed"=>$memory["memCached"],
                "cacheFree"=>$memory["memCachedFree"],
                "cacheTotal"=>$memory["memCachedFree"]+$memory["memCached"]),
            "network"=>$network,
            "version"=>$versions
        ));

    }

}