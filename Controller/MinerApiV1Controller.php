<?php

include __DIR__.'/MinerController.php';


class MinerApiV1Controller extends MinerController {

    /*
     * Return the result of getType() function in JSON format with old api
     */
    public function getTypePyAction() {
        header('Content-Type: application/json');
        echo json_encode(array("type"=>$this->getType()));
    }

    /*
     * Return memory and uptime in JSON format with old api
     */
    public function getSystemPyAction() {
        //Memory and Uptime
        header('Content-Type: application/json');
        $uptime=trim(exec("uptime"));
        $memory=$this->getMemory();

        echo json_encode(array(
            "status"=>$uptime,
            "memUsed"=>$memory["memTotal"]-$memory["memFree"],
            "memFree"=>$memory["memFree"],
            "memTotal"=>$memory["memTotal"],
            "cacheUsed"=>$memory["memCached"],
            "cacheFree"=>$memory["memCachedFree"],
            "cacheTotal"=>$memory["memCachedFree"]+$memory["memCached"]
        ));
    }

    /*
     * Return versions of the system with old api
     */
    public function getVersionPyAction() {
        header('Content-Type: application/json');
        $versions=$this->getVersions();
        $versions["rootfs_v"]=$versions["platform_v"];
        echo json_encode($versions);
    }

}
