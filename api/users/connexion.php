<?php
 
$data = json_decode(file_get_contents("php://input"), true);
if (isset($data['username']) && isset($data['password'])) {
    $username = $data['username'];
    $password = $data['password'];

    $db_file = 'data/users.json';
    $users = [];

    if (file_exists($db_file)) {
        $users = json_decode(file_get_contents($db_file), true);
    } else {
        $json = json_encode(array(), JSON_PRETTY_PRINT);
        $tmp = $db_file . '.tmp';
        file_put_contents($tmp, $json, LOCK_EX);
        rename($tmp, $db_file);
    }

    foreach ($users as $user) {
        if ($user['username'] === $username) {
            if (password_verify($password, $user['password'])) {
                http_response_code(200);
                echo json_encode(array("message" => "Login successful.", "user" => array("id" => $user['id'], "username" => $user['username'], "role" => $user['role'])), JSON_PRETTY_PRINT);
            } else {
                http_response_code(401);
                echo json_encode(array("message" => "Invalid password."), JSON_PRETTY_PRINT);
            }
            exit();
        }
    }
    exit();
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid input. Username and password are required."), JSON_PRETTY_PRINT);
}
