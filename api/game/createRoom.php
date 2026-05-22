<?php

// add the creator as the admin of the room
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && !empty($data['name'])) {
    $roomID = uniqid();

    $session_file = '../users/data/session.json';
    $session = [];

    if (file_exists($session_file)) {
        $session = json_decode(file_get_contents($session_file), true);
    }

    if (isset($session['id'])) {
        $adminId = $session['id'];
    } else {
        $adminId = "admin";
    }

    $newRoom = [
        "id" => $roomID,
        "name" => $data['name'],
        "admin" => $adminId,
        "players" => [],
        "questions" => []
    ];

    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    $rooms[] = $newRoom;
    file_put_contents($rooms_file, json_encode($rooms, JSON_PRETTY_PRINT));

    http_response_code(201);
    echo json_encode($newRoom, JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Room name is required."), JSON_PRETTY_PRINT);
}
