<?php

// Helper function to get session
function get_session() {
    $session_file = '../users/data/session.json';
    if (file_exists($session_file)) {
        return json_decode(file_get_contents($session_file), true);
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
    file_put_contents($rooms_file, json_encode($rooms, JSON_PRETTY_PRINT));
}
