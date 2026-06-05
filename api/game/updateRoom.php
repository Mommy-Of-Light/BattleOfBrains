<?php

$data = json_decode(file_get_contents("php://input"), true);

// get actor from request body or from header-based lookup (no session.json)
$session = function_exists('get_session') ? get_session() : array();
$sessionId = isset($session['id']) ? $session['id'] : null;
$actorId = isset($data['id']) ? $data['id'] : $sessionId;

// normalize actor id to string for safe comparisons
$actorId = $actorId !== null ? (string)$actorId : null;

if (isset($data['id']) && isset($data['action']) && isset($data['roomID'])) {
    if (!in_array($data['action'], ['join', 'leave', 'start', 'stop'])) {
        http_response_code(400);
        echo json_encode(array("message" => "Invalid action. Must be 'join', 'leave', 'start' or 'stop'."), JSON_PRETTY_PRINT);
        exit();
    }

    $rooms_file = 'data/rooms.json';
    $rooms = [];

    if (file_exists($rooms_file)) {
        $rooms = json_decode(file_get_contents($rooms_file), true);
    }

    foreach ($rooms as &$room) {
        if ($room['id'] === $data['roomID']) {
            // actor is the requester id (from body) or header-based lookup
            if (!$actorId) {
                http_response_code(401);
                echo json_encode(array("message" => "Unauthorized. Please provide user id."), JSON_PRETTY_PRINT);
                exit();
            }

            // normalize admin id and request id for comparison
            $roomAdmin = isset($room['admin']) ? (string)$room['admin'] : null;
            $requester = $actorId;

            // Admins spectate by default; prevent admin from joining as a player but allow them to 'leave' (spectate)
            if ($roomAdmin === $requester && $data['action'] === 'join') {
                http_response_code(400);
                echo json_encode(array("message" => "Admin spectates and cannot join as a player."), JSON_PRETTY_PRINT);
                exit();
            }
            // enforce admin-only actions for start/stop
            if (in_array($data['action'], ['start', 'stop']) && $requester !== $roomAdmin) {
                http_response_code(403);
                echo json_encode(array("message" => "Only the admin can perform this action."), JSON_PRETTY_PRINT);
                exit();
            }
            
            if ($data['action'] === 'join') {
                $joinId = isset($data['id']) ? (string)$data['id'] : null;
                // ensure players are stored as strings
                $room['players'] = array_map('strval', $room['players'] ?? []);
                if ($joinId !== null && !in_array($joinId, $room['players'], true)) {
                    $room['players'][] = $joinId;
                    // ensure numeric indexes are sequential so json_encode() emits a JSON array
                    $room['players'] = array_values($room['players']);
                }
            } elseif ($data['action'] === 'start') {
                // mark the room as started so all clients can detect it
                $room['started'] = true;
            } elseif ($data['action'] === 'stop') {
                // stop the room (only admin can do this)
                $room['started'] = false;
            } elseif ($data['action'] === 'leave') {
                $leaveId = isset($data['id']) ? (string)$data['id'] : null;
                // If the admin is leaving the room, consider the room inactive:
                // stop the room and remove all players (auto-kick).
                if ($requester !== null && $roomAdmin !== null && (string)$requester === (string)$roomAdmin) {
                    $room['players'] = [];
                    $room['started'] = false;
                    @file_put_contents('data/updateRoom.log', date('[Y-m-d H:i:s]') . " admin_left_clear_players\n", FILE_APPEND);
                } else {
                    $room['players'] = array_values(array_filter($room['players'] ?? [], function ($p) use ($leaveId) {
                        return (string)$p !== (string)$leaveId;
                    }));
                }
            }

            $json = json_encode($rooms, JSON_PRETTY_PRINT);
            $tmp = $rooms_file . '.tmp';
            $logFile = 'data/updateRoom.log';
            $action = isset($data['action']) ? $data['action'] : 'unknown';
            $roomIdLog = isset($data['roomID']) ? $data['roomID'] : 'unknown';
            $actorLog = $actorId ?? 'unknown';

            $written = file_put_contents($tmp, $json, LOCK_EX);
            $logEntry = date('[Y-m-d H:i:s]') . " action={$action} room={$roomIdLog} actor={$actorLog} write=" . ($written === false ? 'fail' : 'ok') . "\n";
            @file_put_contents($logFile, $logEntry, FILE_APPEND);

            $renameSuccess = false;
            if ($written !== false) {
                // try rename first
                if (@rename($tmp, $rooms_file)) {
                    $renameSuccess = true;
                    @file_put_contents($logFile, date('[Y-m-d H:i:s]') . " rename=ok\n", FILE_APPEND);
                } else {
                    // fallback: try copy then unlink
                    if (@copy($tmp, $rooms_file)) {
                        @unlink($tmp);
                        $renameSuccess = true;
                        @file_put_contents($logFile, date('[Y-m-d H:i:s]') . " copy_fallback=ok\n", FILE_APPEND);
                    } else {
                        // final fallback: direct write to target file (not atomic)
                        if (@file_put_contents($rooms_file, $json, LOCK_EX) !== false) {
                            @unlink($tmp);
                            $renameSuccess = true;
                            @file_put_contents($logFile, date('[Y-m-d H:i:s]') . " direct_write_fallback=ok\n", FILE_APPEND);
                        } else {
                            @file_put_contents($logFile, date('[Y-m-d H:i:s]') . " all_write_methods_failed\n", FILE_APPEND);
                        }
                    }
                }
            }

            if (!$renameSuccess) {
                http_response_code(500);
                echo json_encode(array("message" => "Failed to persist rooms file."), JSON_PRETTY_PRINT);
                exit();
            }

            http_response_code(200);
            echo json_encode($room, JSON_PRETTY_PRINT);
            exit();
        }
    }

    http_response_code(404);
    echo json_encode(array("message" => "Room not found."), JSON_PRETTY_PRINT);
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Invalid request data."), JSON_PRETTY_PRINT);
}