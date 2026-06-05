<?php 

// cors headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Id");

// avoid caching user lookups
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        include 'getUser.php';
        break;

    case 'POST':
        include 'connexion.php';
        break;

    case 'PUT':
        include 'register.php';
        break;

    case 'DELETE':
        include 'logout.php';
        break;

    case 'OPTIONS':
        break;

    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method not allowed."));
}