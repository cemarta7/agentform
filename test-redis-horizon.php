<?php

/**
 * Redis & Horizon Compatibility Test Script
 *
 * Run this script to test Redis and Horizon functionality:
 * php test-redis-horizon.php
 */

echo "🧪 Redis & Horizon Compatibility Test\n";
echo "=====================================\n\n";

// Test 1: PHP Redis Extension
echo "1. Testing PHP Redis Extension...\n";
if (extension_loaded('redis')) {
    echo "   ✅ Redis extension is loaded\n";
} else {
    echo "   ❌ Redis extension is NOT loaded\n";
    echo "   💡 Install with: pecl install redis\n";
}

if (class_exists('Redis')) {
    echo "   ✅ Redis class is available\n";
} else {
    echo "   ❌ Redis class is NOT available\n";
}

// Test 2: Redis Server Connection
echo "\n2. Testing Redis Server Connection...\n";
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $pong = $redis->ping();
        if ($pong) {
            echo "   ✅ Redis server is running and accessible\n";
            echo "   📊 Redis info: " . $redis->info('server')['redis_version'] . "\n";
        } else {
            echo "   ❌ Redis server ping failed\n";
        }
        $redis->close();
    } else {
        echo "   ⚠️  Cannot test - Redis class not available\n";
    }
} catch (Exception $e) {
    echo "   ❌ Redis connection failed: " . $e->getMessage() . "\n";
    echo "   💡 Start Redis with: redis-server\n";
}

// Test 3: Laravel Configuration
echo "\n3. Testing Laravel Configuration...\n";
if (file_exists(__DIR__ . '/artisan')) {
    echo "   ✅ Laravel project detected\n";

    // Test queue configuration
    $output = shell_exec('php artisan config:show queue.default 2>&1');
    if (strpos($output, 'redis') !== false) {
        echo "   ✅ Queue configured for Redis\n";
    } elseif (strpos($output, 'database') !== false) {
        echo "   ℹ️  Queue configured for Database\n";
    } else {
        echo "   ⚠️  Queue configuration unclear: " . trim($output) . "\n";
    }
} else {
    echo "   ❌ Not in Laravel project root\n";
}

// Test 4: Horizon Installation
echo "\n4. Testing Horizon Installation...\n";
if (file_exists(__DIR__ . '/vendor/laravel/horizon')) {
    echo "   ✅ Horizon package is installed\n";
} else {
    echo "   ❌ Horizon package is NOT installed\n";
    echo "   💡 Install with: composer require laravel/horizon\n";
}

if (file_exists(__DIR__ . '/config/horizon.php')) {
    echo "   ✅ Horizon configuration exists\n";
} else {
    echo "   ❌ Horizon configuration missing\n";
    echo "   💡 Publish with: php artisan horizon:install\n";
}

// Test 5: Queue Tables/Redis Keys
echo "\n5. Testing Queue Storage...\n";
try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);

        // Check for Laravel queue keys
        $keys = $redis->keys('*queue*');
        if (!empty($keys)) {
            echo "   ✅ Redis queue keys found: " . count($keys) . " keys\n";
            foreach (array_slice($keys, 0, 3) as $key) {
                echo "      - " . $key . "\n";
            }
        } else {
            echo "   ℹ️  No Redis queue keys found (normal if no jobs queued)\n";
        }

        $redis->close();
    }
} catch (Exception $e) {
    echo "   ⚠️  Could not check Redis keys: " . $e->getMessage() . "\n";
}

// Test 6: API Endpoints
echo "\n6. Testing API Endpoints...\n";
$endpoints = [
    'http://127.0.0.1:8000/api/metrics/horizon',
    'http://127.0.0.1:8000/api/metrics/agentform'
];

foreach ($endpoints as $endpoint) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);

    $response = @file_get_contents($endpoint, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data) {
            echo "   ✅ " . basename($endpoint) . " endpoint working\n";
            if (isset($data['queue_sizes'])) {
                $total = array_sum($data['queue_sizes']);
                echo "      📊 Total queued jobs: " . $total . "\n";
            }
        } else {
            echo "   ⚠️  " . basename($endpoint) . " returned invalid JSON\n";
        }
    } else {
        echo "   ❌ " . basename($endpoint) . " endpoint not accessible\n";
        echo "      💡 Start Laravel server: php artisan serve\n";
    }
}

// Summary
echo "\n📋 Summary & Recommendations:\n";
echo "=============================\n";

$redisAvailable = extension_loaded('redis') && class_exists('Redis');
$redisRunning = false;

try {
    if ($redisAvailable) {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $redisRunning = $redis->ping();
        $redis->close();
    }
} catch (Exception $e) {
    // Redis not running
}

if ($redisAvailable && $redisRunning) {
    echo "🚀 REDIS READY: Your environment supports Redis/Horizon!\n";
    echo "   Recommended configuration:\n";
    echo "   - QUEUE_CONNECTION=redis\n";
    echo "   - CACHE_STORE=redis\n";
    echo "   - Start Horizon: php artisan horizon\n";
} else {
    echo "🗄️  DATABASE FALLBACK: Use database queues\n";
    echo "   Recommended configuration:\n";
    echo "   - QUEUE_CONNECTION=database\n";
    echo "   - CACHE_STORE=database\n";
    echo "   - Start workers: php artisan queue:work\n";
}

echo "\n✅ Test completed!\n";
echo "📖 See REDIS_HORIZON_COMPATIBILITY.md for detailed setup instructions.\n";
