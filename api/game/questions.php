<?php

// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


// Helper function to get session
function get_session() {
    $session_file = '../users/data/session.json';
    if (file_exists($session_file)) {
        return json_decode(file_get_contents($session_file), true);
    }
    return [];
}

// Helper function to get rooms
function get_rooms() {
    $rooms_file = 'data/rooms.json';
    if (file_exists($rooms_file)) {
        return json_decode(file_get_contents($rooms_file), true);
    }
    return [];
}

// Helper function to save rooms
function save_rooms($rooms) {
    $rooms_file = 'data/rooms.json';
    file_put_contents($rooms_file, json_encode($rooms, JSON_PRETTY_PRINT));
}

$method = $_SERVER['REQUEST_METHOD'];
$session = get_session();

if (!isset($session['username'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized. Please log in."]);
    exit();
}

$username = $session['username'];
$role = $session['role'];

if ($method === 'GET') {
    // View questions: /questions.php?roomID=xxx
    if (!isset($_GET['roomID'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID is required."]);
        exit();
    }
    $roomID = $_GET['roomID'];
    $rooms = get_rooms();
    foreach ($rooms as $room) {
        if ($room['id'] === $roomID) {
            http_response_code(200);
            echo json_encode($room['questions'], JSON_PRETTY_PRINT);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(["message" => "Room not found."]);
    exit();
}

if ($method === 'POST') {
    // Add question
    $data = json_decode(file_get_contents("php://input"), true);
    if ($role === 'student') {
        http_response_code(403);
        echo json_encode(["message" => "Students can only view questions."]);
        exit();
    }
    if (!isset($data['roomID'], $data['question'], $data['options'], $data['answer'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID, question, options and answer are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $data['roomID']) {
            $newQuestion = [
                "id" => uniqid(),
                "question" => $data['question'],
                "options" => $data['options'],
                "answer" => $data['answer']
            ];
            $room['questions'][] = $newQuestion;
            save_rooms($rooms);
            http_response_code(201);
            echo json_encode($newQuestion, JSON_PRETTY_PRINT);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(["message" => "Room not found."]);
    exit();
}

if ($method === 'PUT') {
    // Update question
    $data = json_decode(file_get_contents("php://input"), true);
    if ($role === 'student') {
        http_response_code(403);
        echo json_encode(["message" => "Students can only view questions."]);
        exit();
    }
    if (!isset($data['roomID'], $data['questionID'], $data['question'], $data['options'], $data['answer'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID, question ID, question, options and answer are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $data['roomID']) {
            foreach ($room['questions'] as &$question) {
                if ($question['id'] === $data['questionID']) {
                    $question['question'] = $data['question'];
                    $question['options'] = $data['options'];
                    $question['answer'] = $data['answer'];
                    save_rooms($rooms);
                    http_response_code(200);
                    echo json_encode($question, JSON_PRETTY_PRINT);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Question not found."]);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(["message" => "Room not found."]);
    exit();
}

if ($method === 'DELETE') {
    // Delete question
    $data = json_decode(file_get_contents("php://input"), true);
    if ($role === 'student') {
        http_response_code(403);
        echo json_encode(["message" => "Students can only view questions."]);
        exit();
    }
    if (!isset($data['roomID'], $data['questionID'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID and question ID are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $data['roomID']) {
            foreach ($room['questions'] as $key => $question) {
                if ($question['id'] === $data['questionID']) {
                    unset($room['questions'][$key]);
                    $room['questions'] = array_values($room['questions']);
                    save_rooms($rooms);
                    http_response_code(200);
                    echo json_encode(["message" => "Question deleted successfully."]);
                    exit();
                }
            }
            http_response_code(404);
            echo json_encode(["message" => "Question not found."]);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(["message" => "Room not found."]);
    exit();
}

if ($method === 'OPTIONS') {
    // Preflight request for CORS
    http_response_code(204);
    exit();
}

// Method not allowed
http_response_code(405);
echo json_encode(["message" => "Method not allowed."]);
