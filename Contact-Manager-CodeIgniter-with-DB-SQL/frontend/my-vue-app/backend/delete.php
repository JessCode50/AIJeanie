<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$file = 'contacts.json';

$incomingData = file_get_contents("php://input");
$data = json_decode($incomingData); 

if (!isset($data) || !is_numeric($data)){
    echo json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
    exit;
}

if (file_exists($file)){
    $contacts = file_get_contents($file);
    $contacts = json_decode($contacts, true);

    if (!is_array($contacts)){
        echo json_encode(["status" => "error", "message" => "File is corrupted"]);
        exit;
    }
}

else {
    echo json_encode(["status" => "error", "message" => "File not found"]);
    exit; 
}

unset($contacts[$data]);
$contacts = array_values($contacts);

file_put_contents($file, json_encode($contacts, JSON_PRETTY_PRINT));

echo json_encode(["status" => "deleted"]);
?>
