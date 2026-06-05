<?php

// Simple leaderboard API: GET returns top N users, POST updates a user's best score
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-User-Id");

// prevent caching of leaderboard responses
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Use absolute paths to avoid working-directory issues and ensure
// directories exist before attempting atomic writes.
$base = __DIR__ . '/';

function atomic_write($filePath, $jsonData) {
    $dir = dirname($filePath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $tmp = $filePath . '.tmp';
    $written = @file_put_contents($tmp, $jsonData, LOCK_EX);
    if ($written === false) {
        // cleanup and fail
        @unlink($tmp);
        return false;
    }
    // prefer rename, but fallback to copy if rename fails
    if (!@rename($tmp, $filePath)) {
        if (!@copy($tmp, $filePath)) {
            @unlink($tmp);
            return false;
        }
        @unlink($tmp);
    }
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

$users_file = $base . 'users/data/users.json';
if (!file_exists($users_file)) {
    $json = json_encode(array(), JSON_PRETTY_PRINT);
    atomic_write($users_file, $json);
}

$users = json_decode(@file_get_contents($users_file), true);
if (!is_array($users)) $users = array();

$sessions_file = $base . 'leaderboard_sessions.json';
if (!file_exists($sessions_file)) {
    $json = json_encode(array(), JSON_PRETTY_PRINT);
    atomic_write($sessions_file, $json);
}
$sessions = json_decode(@file_get_contents($sessions_file), true);
if (!is_array($sessions)) $sessions = array();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // If a roomID is provided, return the session leaderboard for that room
    if (isset($_GET['roomID'])) {
        $roomID = $_GET['roomID'];
        $list = isset($sessions[$roomID]) ? $sessions[$roomID] : array();
        usort($list, function($a, $b) {
            $sa = isset($a['score']) ? intval($a['score']) : 0;
            $sb = isset($b['score']) ? intval($b['score']) : 0;
            if ($sb === $sa) {
                return ($a['finishedAt'] ?? 0) <=> ($b['finishedAt'] ?? 0);
            }
            return $sb <=> $sa;
        });
        http_response_code(200);
        echo json_encode($list, JSON_PRETTY_PRINT);
        exit();
    }

    $top = isset($_GET['top']) ? intval($_GET['top']) : 10;
    usort($users, function($a, $b) {
        $sa = isset($a['score']) ? intval($a['score']) : 0;
        $sb = isset($b['score']) ? intval($b['score']) : 0;
        return $sb <=> $sa;
    });
    $rankings = array_slice($users, 0, $top);
    http_response_code(200);
    echo json_encode($rankings, JSON_PRETTY_PRINT);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(array("message" => "id is required."), JSON_PRETTY_PRINT);
        exit();
    }

    $id = $data['id'];
    $score = isset($data['score']) ? intval($data['score']) : null;
    $roomID = isset($data['roomID']) ? $data['roomID'] : null;
    $progress = isset($data['progress']) ? $data['progress'] : null; // arbitrary progress payload
    $finished = isset($data['finished']) ? (bool)$data['finished'] : false;

    $found = false;
    $username = null;

    foreach ($users as &$user) {
        if ((string)$user['id'] === (string)$id) {
            $current = isset($user['score']) ? intval($user['score']) : 0;
            // update global best only when finished or when score exceeds current best
            if ($score !== null && ($finished || $score > $current)) {
                $user['score'] = $score;
            }
            $found = true;
            $username = isset($user['username']) ? $user['username'] : null;
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(array("message" => "User not found."), JSON_PRETTY_PRINT);
        exit();
    }

    // persist users only if we updated a best score
    if ($score !== null && $finished) {
        $json = json_encode($users, JSON_PRETTY_PRINT);
        atomic_write($users_file, $json);
    }

    // If roomID provided, update or add session entry for that room
    if ($roomID) {
        if (!isset($sessions[$roomID]) || !is_array($sessions[$roomID])) $sessions[$roomID] = array();
        $updated = false;
        foreach ($sessions[$roomID] as &$entry) {
            if ((string)$entry['id'] === (string)$id) {
                if ($score !== null) $entry['score'] = $score;
                if ($progress !== null) $entry['progress'] = $progress;
                $entry['username'] = $username;
                $entry['lastUpdate'] = time();
                if ($finished) $entry['finishedAt'] = time();
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $new = array('id' => $id, 'username' => $username, 'score' => ($score !== null ? $score : 0), 'lastUpdate' => time());
            if ($progress !== null) $new['progress'] = $progress;
            if ($finished) $new['finishedAt'] = time();
            $sessions[$roomID][] = $new;
        }

        // sort session entries (finished entries prioritized by score and finish time)
        usort($sessions[$roomID], function($a, $b) {
            $sa = isset($a['score']) ? intval($a['score']) : 0;
            $sb = isset($b['score']) ? intval($b['score']) : 0;
            if ($sb === $sa) {
                return (isset($a['finishedAt']) ? $a['finishedAt'] : 0) <=> (isset($b['finishedAt']) ? $b['finishedAt'] : 0);
            }
            return $sb <=> $sa;
        });

        $json = json_encode($sessions, JSON_PRETTY_PRINT);
        atomic_write($sessions_file, $json);
    }

    http_response_code(200);
    echo json_encode(array("message" => ($finished ? "Score updated." : "Progress updated.")), JSON_PRETTY_PRINT);
    exit();
}

http_response_code(405);
echo json_encode(array("message" => "Method not allowed."), JSON_PRETTY_PRINT);
