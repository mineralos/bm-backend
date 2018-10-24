<?php


class NetworkController {

    /*
     * Returns JSON with the current network settings of the miner
     */
    public function getNetworkAction() {
        global $config;
        header('Content-Type: application/json');

        $network=getNetwork();
        $network["success"]=true;
        echo json_encode($network);


    }

    /*
     * Update networks settings and restart the network
     */
    public function updateNetworkAction() {
        global $config;
        header('Content-Type: application/json');

        if (!isset($_POST["dhcp"])||($_POST["dhcp"]!="dhcp"&&$_POST["dhcp"]!="static")) {
            echo json_encode(array("success"=>false,"message"=>"invalid dhcp setting"));
            return;
        }

        if ($_POST["dhcp"]=="dhcp") {
            $fileContent="[Match]\n";
            $fileContent.="Name=eth0\n";
            $fileContent.="[Network]\n";
            $fileContent.="DHCP=yes\n";
            $fileContent.="[DHCP]\n";
            $fileContent.="ClientIdentifier=mac\n";
            file_put_contents($config["interfacesDirectory"].$config["interfacesFile"],$fileContent);
            echo json_encode(array("success"=>true));
        } else if ($_POST["dhcp"]=="static"){
            if (!isset($_POST["ipaddress"])||$_POST["ipaddress"]==""||!isValidIp($_POST["ipaddress"])) {
                echo json_encode(array("success"=>false,"message"=>"invalid ip address"));
                return;
            }
            if (!isset($_POST["netmask"])||$_POST["netmask"]==""||!isValidIp($_POST["netmask"])) {
                echo json_encode(array("success"=>false,"message"=>"invalid netmask "));
                return;
            }
            if (!isset($_POST["gateway"])||$_POST["gateway"]==""||!isValidIp($_POST["gateway"])) {
                echo json_encode(array("success"=>false,"message"=>"invalid gateway "));
                return;
            }
            if (!isset($_POST["dns"][0])||$_POST["dns"][0]==""||!isValidIp($_POST["dns"][0])) {
                echo json_encode(array("success"=>false,"message"=>"invalid dns 1 "));
                return;
            }
            if (!isset($_POST["dns"][1])||$_POST["dns"][1]==""||!isValidIp($_POST["dns"][1])) {
                echo json_encode(array("success"=>false,"message"=>"invalid dns 2 "));
                return;
            }
            $fileContent="[Match]\n";
            $fileContent.="Name=eth0\n";
            $fileContent.="[Network]\n";
            $fileContent.="Address=".$_POST["ipaddress"]."/".mask2cidr($_POST["netmask"])."\n";
            $fileContent.="Gateway=".$_POST["gateway"]."\n";
            $fileContent.="DNS=".$_POST["dns"][0]."\n";
            $fileContent.="DNS=".$_POST["dns"][1]."\n";

            if (!file_exists($config["interfacesDirectory"]))
                mkdir($config["interfacesDirectory"]);

            file_put_contents($config["interfacesDirectory"].$config["interfacesFile"],$fileContent);


            echo json_encode(array("success"=>true));

        }
        $this->restartNetwork();

    }

    /*
     * Private function to restart the network
     */
    private function restartNetwork() {
        exec('systemctl restart systemd-networkd');
    }


    /*
     * Response success true when this action is executed,
     * used to the network GUI to know when the miner is back online
     * after a network settings update
     */
    public function pingAction() {
        header('Content-Type: application/json');
        echo json_encode(array("success"=>true));
    }


}