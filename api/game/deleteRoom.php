<?php

$data = json_decode(file_get_contents("php://input"), true);

// Determine requester id via header/query or server-side lookup (avoid session.json)
$session = function_exists('get_session') ? get_session() : array();
$id = isset($session['id']) ? $session['id'] : null;
// allow ?id= fallback
if (!$id && isset($_GET['id'])) $id = $_GET['id'];
if (!$id) {
    http_response_code(401);
    echo json_encode(array("message" => "Unauthorized. Please provide user id."), JSON_PRETTY_PRINT);
    exit();
}

if (filter_input(INPUT_GET, 'roomID', FILTER_SANITIZE_STRING)) {
    $rooms_file = 'data/rooms.json';
    $rooms = [];

    $data['roomID'] = filter_input(INPUT_GET, 'roomID', FILTER_SANITIZE_STRING);

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    foreach ($rooms as $index => $room) {
        if ($room['id'] === $data['roomID']) {
            if ((string)$room['admin'] !== (string)$id) {
                http_response_code(403);
                echo json_encode(array("message" => "Only the admin can delete the room."), JSON_PRETTY_PRINT);
                exit();
            }
            array_splice($rooms, $index, 1);
            $json = json_encode($rooms, JSON_PRETTY_PRINT);
            $tmp = $rooms_file . '.tmp';
            if (file_put_contents($tmp, $json, LOCK_EX) === false) {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to write rooms file."), JSON_PRETTY_PRINT);
                exit();
            }
            rename($tmp, $rooms_file);
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