<?php
// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
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
                    echo json_encode(array("message" => "Username already exists."));
                    exit();
                }
            }

            $users[] = array("username" => $username, "password" => $password);

            file_put_contents($db_file, json_encode($users));

            http_response_code(201);
            echo json_encode(array("message" => "User registered successfully."));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid input. Username and password are required."));
        }

    case 'OPTIONS':
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
}