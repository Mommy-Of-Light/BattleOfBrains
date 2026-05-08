<?php
// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (isset($data['username']) && isset($data['password'])) {
            $username = $data['username'];
            $password = $data['password'];

            $db_file = 'data/users.json';
            $session_file = 'data/session.json';
            $users = [];

            if (file_exists($db_file)) {
                $users = json_decode(file_get_contents($db_file), true);
            }

            foreach ($users as $user) {
                if ($user['username'] === $username) {
                    if (password_verify($password, $user['password'])) {
                        http_response_code(200);
                        echo json_encode(array("message" => "Login successful."));
                        $session_data = array("username" => $username);

                        if (!file_exists($session_file)) {
                            file_put_contents($session_file, json_encode(array()));
                        }

                        file_put_contents($session_file, json_encode($session_data));
                    } else {
                        http_response_code(401);
                        echo json_encode(array("message" => "Invalid password."));
                    }
                    exit();
                }
            }
            exit();
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Invalid input. Username and password are required."));
        }
        break;

    case 'OPTIONS':
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
}