<?php

include __DIR__.'/NetworkController.php';


class NetworkApiV1Controller extends NetworkController {

    /*
     * Returns JSON with the current network settings of the miner old API
     */
    public function getNetworkPyAction() {
        global $config;
        header('Content-Type: application/json');

        $network=getNetwork();
        $network["dns"]=array($network["dns1"],$network["dns2"]);
        unset($network["dns1"]);
        unset($network["dns2"]);
        echo json_encode($network);

    }


}