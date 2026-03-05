<?php
// config.php

class Config {
    // Replace 'your_secure_api_key_here' with a strong random string
    const API_KEY = '4f9a2b8c5e1d7f3a9b0c2e4d6f8a1b3c5e7d9f0a2b4c6d8e0f1a3b5c7d9e1f';
}

function validateApiKey() {
    // Get all headers from the request
    $headers = getallheaders();
    
    // Check if the 'X-API-KEY' header exists and matches our constant
    $receivedKey = isset($headers['X-API-KEY']) ? $headers['X-API-KEY'] : null;

    if ($receivedKey !== Config::API_KEY) {
        http_response_code(401); // Unauthorized
        echo json_encode([
            "success" => false, 
            "error" => "Invalid or missing API Key"
        ]);
        exit; // Stop further execution
    }
}