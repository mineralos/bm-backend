<?php

include __DIR__.'/StatusController.php';

use DragonMint\Service\CgminerService;

class StatusApiV1Controller extends StatusController {

    /*
     * Consumes pools, devs, stats from cgminer API
     * and create a JSON response with all the information
     * obtained. A new hardware option was created to include
     * the fans speed
     */
    public function getApiPyAction() {
        header('Content-Type: application/json');

        $service = new CgminerService();
        $response=$service->call("pools+devs+stats");

        $devs=@$response["devs"][0]["DEVS"];
        $pools=@$response["pools"][0]["POOLS"];
        $status=@$response["devs"][0]["STATUS"];
        $stats=@$response["stats"][0]["STATS"];

        if (is_array($stats)) {

            for ($i = 0; array_key_exists($i,$stats); $i++) {

                $statsChain = $stats[$i];

                if (isset($statsChain["Fan duty"])&&array_key_exists($i,$devs)) {
                    $devs[$i]["DUTY"]=$statsChain["Fan duty"];
                }
            }
        }

        echo json_encode(array("DEVS" => $devs, "POOLS" => $pools,"STATUS"=>$status));
    }

}