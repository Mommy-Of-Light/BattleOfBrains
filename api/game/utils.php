<?php

// Helper function to get session-like user info without using server-side session.json
function get_session() {
    // Accept user id via HTTP header X-User-Id, or via query param 'userID' or 'id'
    $users_file = '../users/data/users.json';
    $id = null;
    if (!empty($_SERVER['HTTP_X_USER_ID'])) {
        $id = $_SERVER['HTTP_X_USER_ID'];
    } elseif (!empty($_GET['userID'])) {
        $id = $_GET['userID'];
    } elseif (!empty($_GET['id'])) {
        $id = $_GET['id'];
    }

    if ($id !== null) {
        if (file_exists($users_file)) {
            $users = json_decode(file_get_contents($users_file), true);
            if (is_array($users)) {
                foreach ($users as $u) {
                    if ((string)($u['id'] ?? '') === (string)$id) {
                        return $u;
                    }
                }
            }
        }
    }

    return [];
}

// Helper function to get rooms
function get_rooms() {
    $rooms_file = 'data/rooms.json';
    if (file_exists($rooms_file)) {
        return json_decode(file_get_contents($rooms_file), true);
    }
    return [];
}

// Helper function to save rooms
function save_rooms($rooms) {
    $rooms_file = 'data/rooms.json';
    $json = json_encode($rooms, JSON_PRETTY_PRINT);
    $tmp = $rooms_file . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    rename($tmp, $rooms_file);
    return true;
}
