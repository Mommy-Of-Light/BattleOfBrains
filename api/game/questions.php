<?php

require_once 'utils.php';

// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Id");

$method = $_SERVER['REQUEST_METHOD'];

// Allow preflight CORS requests to pass without authentication
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$session = get_session();

// Allow GET requests without authentication. For mutating requests require a user id/role.
if ($method !== 'GET') {
    if (!isset($session['id'])) {
        http_response_code(401);
        echo json_encode(["message" => "Unauthorized. Please log in."]);
        exit();
    }
    $id = $session['id'];
    $role = $session['role'];
} else {
    $id = isset($session['id']) ? $session['id'] : null;
    $role = isset($session['role']) ? $session['role'] : null;
}

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
    if (!isset($data['question'], $data['options'], $data['answer']) || !isset($_GET['roomID'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID, question, options and answer are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $_GET['roomID']) {
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
    if (!isset($data['question'], $data['options'], $data['answer']) && !isset($_GET['roomID']) && !isset($_GET['questionID'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID, question ID, question, options and answer are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $_GET['roomID']) {
            foreach ($room['questions'] as &$question) {
                if ($question['id'] === $_GET['questionID']) {
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
    if (!isset($_GET['roomID'], $_GET['questionID'])) {
        http_response_code(400);
        echo json_encode(["message" => "Room ID and question ID are required."]);
        exit();
    }
    $rooms = get_rooms();
    foreach ($rooms as &$room) {
        if ($room['id'] === $_GET['roomID']) {
            foreach ($room['questions'] as $key => $question) {
                if ($question['id'] === $_GET['questionID']) {
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
