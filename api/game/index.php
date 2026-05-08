<?php

// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        include 'room.php';
        break;

    case 'POST':
        include 'createRoom.php';
        break;

    case 'PUT':
        include 'updateRoom.php';
        break;

    case 'DELETE':
        include 'deleteRoom.php';
        break;

    case 'OPTIONS':
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
}