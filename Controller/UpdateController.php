<?php


class UpdateController {



    public function __construct(){

    }

    public function getLatestFirmwareVersionAction() {
        global $config;
        header('Content-Type: application/json');

        $versions=null;
        $latestVersion="";
        $latestVersionDate="";
        $latestUrl="";
        $latestInfo="";
        $currentVersionDate="";

        //Get Current Version and Type
        $minerType=getMinerType();
        $hardwareVersion=getHardwareVersion();
        $currentVersions=getVersions();
        $currentVersion=$currentVersions["platform_v"];
        $currentVersionDate=getDateFromVersion($currentVersion);

        //Create GET params
        $params=array("currentVersion"=>$currentVersion);
        $response=getUrlData($config["urlFirmwareVersions"]."/".strtolower($minerType)."/".strtolower($hardwareVersion)."/stable",$params);

        $isUpdated=false;
        if ($response!=null) {
            $versions = json_decode($response,true);
            if ($versions!=null&&is_array($versions)&&array_key_exists("version", $versions)) {
                if (array_key_exists("version", $versions)) {
                    $latestVersion = $versions["version"];
                }
                if (array_key_exists("url", $versions)) {
                    $latestUrl = $versions["url"];
                }
                if (array_key_exists("info", $versions)) {
                    $latestInfo = $versions["info"];
                }
                if (array_key_exists("versionDate", $versions)) {
                    $latestVersionDate=$versions["versionDate"];
                } else {
                    $latestVersionDate=getTimestampFromVersion($latestVersion);
                }

                //Compare Versions Dates
                if (getTimestampFromVersion($currentVersion)>=$latestVersionDate) {
                    $isUpdated = true;
                }

            }
        }

        if ($latestVersion!="")
            echo json_encode(array(
                "success"=>true,
                "version"=>$latestVersion,
                "versionDate"=>date_format(date_create_from_format('U',$latestVersionDate),'jS \of F Y h:i A'),
                "url"=>$latestUrl,
                "info"=>$latestInfo,
                "currentVersion"=>$currentVersion,
                "currentVersionDate"=>date_format($currentVersionDate,'jS \of F Y h:i A'),
                "isUpdated"=>$isUpdated
            ));
        else
            echo json_encode(array("success"=>false,"message"=>"can't fetch firmware versions"));
    }



}