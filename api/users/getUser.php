<?php

// Get the user ID from the request
$userID = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_STRING);

// Load users from the JSON file
$users_file = 'data/users.json';
$users = [];

if (file_exists($users_file)) {
    $users = json_decode(file_get_contents($users_file), true);
} else {
    $json = json_encode(array(), JSON_PRETTY_PRINT);
    $tmp = $users_file . '.tmp';
    file_put_contents($tmp, $json, LOCK_EX);
    rename($tmp, $users_file);
}

if ($userID) {
    foreach ($users as $user) {
        // compare as strings to avoid type-mismatch between ints and strings
        if ((string)$user['id'] === (string)$userID) {
            http_response_code(200);
            echo json_encode($user, JSON_PRETTY_PRINT);
            exit();
        }
    }
    http_response_code(404);
    echo json_encode(array("message" => "User not found."), JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "User ID is required."), JSON_PRETTY_PRINT);
}