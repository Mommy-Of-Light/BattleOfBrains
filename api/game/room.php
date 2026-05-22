<?php

$roomID = isset($_GET['roomID']) ? $_GET['roomID'] : null;

if ($roomID) {
    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    foreach ($rooms as $room) {
        if ($room['id'] === $roomID) {
            http_response_code(200);
            echo json_encode($room, JSON_PRETTY_PRINT);
            exit();
        }
    }

    http_response_code(404);
    echo json_encode(array("message" => "Room not found."), JSON_PRETTY_PRINT);
} 
// return all rooms if no specific ID is provided
else {
    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    http_response_code(200);
    echo json_encode($rooms, JSON_PRETTY_PRINT);
}