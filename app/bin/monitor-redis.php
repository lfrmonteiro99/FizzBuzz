<?php

require_once __DIR__.'/../../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/../../.env.docker');

// Redis connection parameters
$redisHost = $_ENV['REDIS_HOST'] ?? 'redis';
$redisPort = $_ENV['REDIS_PORT'] ?? 6379;
$redisPassword = $_ENV['REDIS_PASSWORD'] ?? null;

// Connect to Redis
$redis = new Redis();
$redis->connect($redisHost, $redisPort);
if ($redisPassword) {
    $redis->auth($redisPassword);
}

echo "Connected to Redis at {$redisHost}:{$redisPort}\n";
echo "Monitoring for messages...\n";

// Subscribe to the message queue
$redis->subscribe(['fizzbuzz_messages'], function($redis, $channel, $message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] Received message on channel '{$channel}':\n";
    echo json_encode(json_decode($message, true), JSON_PRETTY_PRINT) . "\n\n";
}); 