<?php

require 'vendor/autoload.php';
require 'RedisDistributedLock.php';

use Predis\Client;

// Initialize Redis client
$redis = new Client(['host' => '127.0.0.1', 'port' => 6379]);

// Create the lock instance
$lock = new RedisDistributedLock($redis, 'my_lock', 10);

$isComplete = false;

while(!$isComplete) {
    echo "Trying to acquire lock\n";

    if ($lock->acquire()) {
        $isComplete = true;
        echo "Lock acquired! Processing...\n";
    
        // Simulate work
        sleep(seconds: 10);

        // Release the lock
        if ($lock->release()) {
            echo "Lock released!\n";
        } else {
            echo "Failed to release lock!\n";
        }
    } else {
        echo "Lock is already taken. Try again later.\n";
    }    
}
