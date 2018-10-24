<?php

use DragonMint\Service\CgminerService;
use Firebase\JWT\JWT;

class UserController {

    private $users=null;

    /*
     * Parse the users configuration file into the property users,
     * if the file doesn't exists the file is created with the predefined
     * username and password
     */
    public function __construct(){
        global $config;
        $configContent=null;
        if (file_exists($config["usersFile"]))
        $configContent=file_get_contents($config["usersFile"]);
        if ($configContent!=null&&$configContent!="") {
            $this->users = json_decode($configContent, true);
        }
        if ($this->users === null || json_last_error() !== JSON_ERROR_NONE) {

            $this->users=array(array("username"=>$config["userAdmin"],"password"=>generatePasswordHash($config["passwordAdmin"])),array("username"=>$config["userGuest"],"password"=>generatePasswordHash($config["passwordGuest"])));
            $this->save();
        }

    }

    /*
     * Action that receive a username and password from POST and return a JWT
     */
    public function authAction() {
        global $config;
        header('Content-Type: application/json');
        if (isset($_POST["username"])&&$_POST["username"]!=""&&isset($_POST["password"])&&$_POST["password"]!="") {
            $username=preg_replace("/[^a-zA-Z0-9_\-]+/","",$_POST["username"]);
            foreach ($this->users as $user) {
                if ($username==$user["username"]) {
                    if (generatePasswordHash($_POST["password"])==$user["password"]) {
                        //Valid Password
                        $key = get_jwt_key();
                        $now=time();
                        $token = array(
                            "iss" => $config["serverName"],
                            "iat" => $now,
                            "exp" => $now+intval($config["jwtTokenExpire"]),
                            "user"=>$user["username"]
                        );
                        $jwt = JWT::encode($token, $key);
                        echo json_encode(array("success"=>true,"jwt"=>$jwt));
                        return;
                    } else {
                        echo json_encode(array("success"=>false,"message"=>"invalid password"));
                        return;
                    }
                }
            }
            echo json_encode(array("success"=>false,"message"=>"user not found"));
            return;
        } else {
            echo json_encode(array("success"=>false,"message"=>"missing username/password fields"));
            return;
        }
    }

    /*
     * Change the password of a user and update the config gile
     */
    public function updatePasswordAction() {
        global $config;
        header('Content-Type: application/json');
        $validPassword=false;
        if (isset($_POST["currentPassword"]) &&$_POST["currentPassword"]!="") {
            foreach ($this->users as $user) {
                if ($config["userAdmin"] == $user["username"]) {
                    if (generatePasswordHash($_POST["currentPassword"]) == $user["password"]) {
                        $validPassword=true;
                    }
                    break;
                }
            }
        }
        if (!$validPassword) {
            echo json_encode(array("success"=>false,"message"=>"wrong current password"));
            return;
        }
        if (!isset($_POST["user"])||($_POST["user"]!=$config["userAdmin"]&&$_POST["user"]!=$config["userGuest"])) {
            echo json_encode(array("success"=>false,"message"=>"invalid user"));
            return;
        }
        if (!isset($_POST["newPassword"])||$_POST["newPassword"]=="") {
            echo json_encode(array("success"=>false,"message"=>"invalid new password"));
            return;
        }
        $updatedPassword=false;
        $userPos=0;

        foreach ($this->users as $user) {
            if ($_POST["user"] == $user["username"]) {
                $this->users[$userPos]["password"]=generatePasswordHash($_POST["newPassword"]);
                $updatedPassword=true;
                break;
            }
            $userPos++;
        }
        

        if ($updatedPassword) {
            $this->save();
            echo json_encode(array("success"=>true));
            return;
        } else {
            echo json_encode(array("success"=>false,"message"=>"cant update the password"));
            return;
        }

    }

    /*
     * Dump the $users property into the users configuration file
     */
    private function save() {
        global $config;

        //Save Config
        file_put_contents($config["usersFile"],json_encode($this->users,JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
    }



}