<?php

$data = json_decode(file_get_contents("php://input"), true);
$session_file = '../users/data/session.json';

if (file_exists($session_file)) {
    $session = json_decode(file_get_contents($session_file), true);
} else {
    $session = [];
}

if (isset($session['id'])) {
    $id = $session['id'];
} else {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized. Please log in."), JSON_PRETTY_PRINT);
    exit();
}

if (isset($data['id']) && isset($data['action']) && filter_input(INPUT_GET, 'roomID', FILTER_SANITIZE_STRING)) {
    if (!in_array($data['action'], ['join', 'leave'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid action. Must be 'join' or 'leave'."), JSON_PRETTY_PRINT);
        exit();
    }

    $data['roomID'] = filter_input(INPUT_GET, 'roomID', FILTER_SANITIZE_STRING);
    
    var_dump($data);

    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    foreach ($rooms as &$room) {
        if ($room['roomID'] === $data['roomID']) {
            if ($room['admin'] === $id && $data['id'] === $id && $data['action'] === 'leave') {
                http_response_code(400);
                echo json_encode(array("message" => "Admin cannot leave the room. Please delete the room instead."), JSON_PRETTY_PRINT);
                exit();
            }
            if ($room['admin'] === $id && $data['id'] === $id && $data['action'] === 'join') {
                http_response_code(400);
                echo json_encode(array("message" => "Admin is already in the room."), JSON_PRETTY_PRINT);
                exit();
            }
            if ($id !== $room['admin']) {
                http_response_code(403);
                echo json_encode(array("message" => "Only the admin can perform action on rooms."), JSON_PRETTY_PRINT);
                exit();
            }
            if ($data['action'] === 'join') {
                if (!in_array($data['id'], $room['players'])) {
                    $room['players'][] = $data['id'];
                }
            } elseif ($data['action'] === 'leave') {
                $room['players'] = array_filter($room['players'], function ($p) use ($data) {
                    return $p !== $data['id'];
                });
            }

            file_put_contents($rooms_file, json_encode($rooms, JSON_PRETTY_PRINT));
            http_response_code(200);
            echo json_encode($room, JSON_PRETTY_PRINT);
            exit();
        }
    }

    http_response_code(404);
    echo json_encode(array("message" => "Room not found."), JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request data."), JSON_PRETTY_PRINT);
}