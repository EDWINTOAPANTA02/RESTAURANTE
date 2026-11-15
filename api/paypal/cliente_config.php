<?php
include_once __DIR__ . "/../encabezado.php";
include_once __DIR__ . "/config.php";

echo json_encode([
    "client_id" => PAYPAL_SDK_CLIENT_ID
]);
