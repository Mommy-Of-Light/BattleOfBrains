<?php

require_once 'utils.php';

// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: *, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Id");
// prevent caching of API responses so clients always receive fresh state
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$session = get_session();

// Allow unauthenticated GET (listing/fetching rooms) and OPTIONS (CORS preflight).
// Require auth for mutating requests (POST/PUT/DELETE).
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS' && !isset($session['username'])) {
    http_response_code(401);
    echo json_encode(["message" => "Unauthorized. Please log in."]);
    exit();
}

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