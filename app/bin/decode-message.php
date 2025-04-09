<?php

require_once '/var/www/vendor/autoload.php';

$input = stream_get_contents(STDIN);
if (preg_match('/^1\) 1\) "messages"/', $input)) {
    // Extract the message value from Redis XREAD output
    if (preg_match('/2\) "([^"]+)"/', $input, $matches)) {
        $message = $matches[1];
        // Remove the serialization prefix (s:XXXX:) if present
        $message = preg_replace('/^s:\d+:"(.*)"$/', '$1', $message);
        // Unescape the JSON string
        $message = stripcslashes($message);
        $data = json_decode($message, true);
        
        if (isset($data['body'])) {
            $envelope = unserialize($data['body']);
            echo json_encode([
                'class' => get_class($envelope->getMessage()),
                'message' => $envelope->getMessage(),
                'stamps' => array_map(fn($stamp) => get_class($stamp), $envelope->all())
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }
    }
} else {
    echo "No valid message found in input\n";
} 