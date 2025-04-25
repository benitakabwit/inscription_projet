<?php
// api/functions.php

function get_json_data() {
    $data = json_decode(file_get_contents('php://input'), true);
    return $data ?? [];
}

function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function handle_error($message, $status_code = 500) {
    send_json_response(['erreur' => $message], $status_code);
}