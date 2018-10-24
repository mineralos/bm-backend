<?php
include __DIR__.'/Common/common.inc.php';
include __DIR__.'/Config/config.inc.php';
require_once('vendor/autoload.php');
$loggedUser=getLoggedUser();
if ($loggedUser=="admin") {
    echo "success";
    exit();
}
header("HTTP/1.1 401 Unauthorized");