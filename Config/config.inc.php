<?php

$config["cgminerPort"]=4028;
$config["configDirectory"]="/config/";
$config["configFile"]="/config/cgminer.conf";
$config["profileFile"]="/config/profile.conf";
$config["backupConfigFile"]="/config/.cgminer.conf";
$config["usersFile"]="/config/web-users.json";
$config["buildFile"]="/etc/os-release";
$config["jwtKeyFile"]="/tmp/jwtK";
$config["jwtTokenExpire"]=21600; //6 hours
$config["serverName"]="DragonMint";
$config["salt"]="8985dee0f2bc1b7a895d58bc4811e373";

$config["interfacesDirectory"]="/config/network/";
$config["interfacesFile"]="25-wired.network";
$config["resolvFile"]="/etc/resolv.conf";
$config["logsDumpedFile"]="/tmp/miner.log";

//Default Passwords in Case Missing or Corrupted Users File
$config["userAdmin"]="admin";
$config["userGuest"]="guest";
$config["passwordAdmin"]="dragonadmin";
$config["passwordGuest"]="dragonguest";

//Versions
$config["hardwareVersionFile"]="/etc/hwrevision";
$config["minerTypeFile"]="/tmp/type";
$config["urlFirmwareVersions"]="https://download.halongmining.com/v1/update";
$config["gitHashes"]="/etc/git_hashes";

//Self Test
$config["selfTestCmd"]="/bin/dm-selftest --production";
$config["selfTestLockFile"]="/tmp/ageing_lock";
$config["selfTestLogFile"]="/tmp/ageing.log";
$config["selfTestProgressFile"]="/tmp/ageing_progress.log";

//Stats
$config["statsJsonFile"]="/tmp/stats.json";
