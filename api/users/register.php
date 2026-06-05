<?php

$db_file = 'data/users.json';
$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['username']) && isset($data['password'])) {
    $username = $data['username'];
    $password = password_hash($data['password'], PASSWORD_DEFAULT);

    $users = [];
    if (file_exists($db_file)) {
        $users = json_decode(file_get_contents($db_file), true);
    }

    foreach ($users as $user) {
        if ($user['username'] === $username) {
            http_response_code(400);
            echo json_encode(array("message" => "Username not valid."), JSON_PRETTY_PRINT);
            exit();
        }
    }

    $users[] = array("id" => count($users) + 1, "username" => $username, "password" => $password, "role" => "student", "score" => 0);

    $json = json_encode($users, JSON_PRETTY_PRINT);
    $tmp = $db_file . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        http_response_code(500);
        echo json_encode(array("message" => "Failed to write users file."), JSON_PRETTY_PRINT);
        exit();
    }
    rename($tmp, $db_file);

    http_response_code(201);
    echo json_encode(array("message" => "User registered successfully."), JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid input. Username and password are required."), JSON_PRETTY_PRINT);
}
