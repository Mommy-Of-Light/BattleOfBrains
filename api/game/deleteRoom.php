<?php

$data = json_decode(file_get_contents("php://input"), true);

$session_file = '../users/data/session.json';

if (file_exists($session_file)) {
    $session = json_decode(file_get_contents($session_file), true);
} else {
    $session = [];
}

if (isset($session['username'])) {
    $username = $session['username'];
} else {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized. Please log in."), JSON_PRETTY_PRINT);
    exit();
}

if (isset($data['roomID'])) {
    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    foreach ($rooms as $index => $room) {
        if ($room['id'] === $data['roomID']) {
            if ($room['admin'] !== $username) {
                http_response_code(403);
                echo json_encode(array("message" => "Only the admin can delete the room."), JSON_PRETTY_PRINT);
                exit();
            }
            array_splice($rooms, $index, 1);
            file_put_contents($rooms_file, json_encode($rooms, JSON_PRETTY_PRINT));
            http_response_code(200);
            echo json_encode(array("message" => "Room deleted successfully."), JSON_PRETTY_PRINT);
            exit();
        }
    }

    http_response_code(404);
    echo json_encode(array("message" => "Room not found."), JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Room ID is required."), JSON_PRETTY_PRINT);
}