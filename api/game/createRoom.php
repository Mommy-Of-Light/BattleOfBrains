<?php

// add the creator as the admin of the room
$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['name']) && !empty($data['name']) && isset($data['capacity']) && is_int($data['capacity'])) {
    $roomID = uniqid();

    // Determine admin id from request header/body or server-side lookup (no session.json)
    $session = function_exists('get_session') ? get_session() : array();
    if (isset($session['id'])) {
        $adminId = $session['id'];
    } elseif (isset($data['id'])) {
        $adminId = $data['id'];
    } elseif (isset($data['adminId'])) {
        $adminId = $data['adminId'];
    } else {
        $adminId = "admin";
    }

    $newRoom = [
        "id" => $roomID,
        "name" => $data['name'],
        "admin" => $adminId,
        "capacity" => $data['capacity'],
        "players" => [],
        "started" => false,
        "questions" => []
    ];

    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    $rooms[] = $newRoom;
    $json = json_encode($rooms, JSON_PRETTY_PRINT);
    $tmp = $rooms_file . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to write rooms file."), JSON_PRETTY_PRINT);
        exit();
    }
    rename($tmp, $rooms_file);

    http_response_code(201);
    echo json_encode($newRoom, JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Room name is required."), JSON_PRETTY_PRINT);
}
