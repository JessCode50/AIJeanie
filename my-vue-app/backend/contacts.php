<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
$file = 'contacts.json';

if ($_SERVER['REQUEST_METHOD'] == 'GET'){
    if (!file_exists($file)){
        echo json_encode(["status" => "error", "message" => "File not found"]);
    }

    $jsonString = file_get_contents($file);
    echo $jsonString;
}

else if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $incomingData = file_get_contents("php://input");
    $data = json_decode($incomingData, true);

    if (!isset($data)){
        echo json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
        exit;
    }

    if (!file_exists($file)){
        echo json_encode(["status" => "error", "message" => "File not found"]);
        exit;
    }

    $contacts = file_get_contents($file);
    $contacts = json_decode($contacts, true);

    if (!is_array($contacts)){
        echo json_encode(["status" => "error", "message" => "File is corrupted"]);
        exit;
    }

    if ($data['edit'] == true){
        if (!is_numeric($data['index'])){
            echo json_encode(["status" => "error", "message" => "Invalid contact index provided"]);
            exit;
        }
        $contacts[$data['index']] = $data['contact'];

        echo json_encode(["status" => "edited"]);
    }

    else {
        array_push($contacts, $data);
        echo json_encode(["status" => "added"]);
    }

    file_put_contents($file, json_encode($contacts, JSON_PRETTY_PRINT));
}
?>