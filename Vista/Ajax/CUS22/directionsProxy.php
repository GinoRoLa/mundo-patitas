<?php
// api/directionsProxy.php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); 

$apiKey = "MIAPI";

if (!isset($_GET["origin"]) || !isset($_GET["destination"])) {
    echo json_encode(["status" => "ERROR", "message" => "Faltan parámetros"]);
    exit;
}

$baseUrl = "https://maps.googleapis.com/maps/api/directions/json";

// Aquí agregamos optimize:true al inicio de los waypoints
$waypoints = isset($_GET["waypoints"]) && !empty($_GET["waypoints"])
    ? "optimize:true|" . $_GET["waypoints"]
    : "";

$query = http_build_query([
    "origin" => $_GET["origin"],
    "destination" => $_GET["destination"],
    "waypoints" => $waypoints,
    "mode" => "driving",
    "key" => $apiKey
]);

$url = "{$baseUrl}?{$query}";

// Llamada a Google Directions API
$response = file_get_contents($url);
if ($response === FALSE) {
    echo json_encode(["status" => "ERROR", "message" => "No se pudo conectar con Google"]);
    exit;
}

echo $response;
