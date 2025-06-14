<?php

namespace App\Services;

use Exception;

class UtilService
{
    public static function consume_cpu_with_hashing() {
        $startTime = microtime(true);

        // Determine a random duration between 5 and 10 seconds
        $durationSeconds = rand(5, 10);
        $endTime = $startTime + $durationSeconds;

        // Generate some random data to act as a base for hashing
        // random_bytes() requires PHP 7+. For older versions, consider openssl_random_pseudo_bytes()
        try {
            $baseData = bin2hex(random_bytes(32)); // Creates a 64-character hex string
        } catch (Exception $e) {
            // Fallback for environments where random_bytes might fail or is unavailable
            $baseData = '';
            for ($i = 0; $i < 64; $i++) {
                $baseData .= dechex(rand(0, 15));
            }
        }

        $counter = 0;
        // Loop until the desired duration has passed
        while (microtime(true) < $endTime) {
            // Perform a batch of hashing operations in an inner loop.
            // This makes the work done between time checks more substantial.
            // Adjust 'innerLoopIterations' if needed based on your server's CPU speed.
            $innerLoopIterations = 2000;
            for ($i = 0; $i < $innerLoopIterations; $i++) {
                // SHA256 is a moderately intensive hashing algorithm.
                // Continuously changing the input string by appending counters.
                hash('sha256', $baseData . $counter . $i);
            }
            $counter++;

            // Optional: If you notice the script becoming unresponsive or wish to yield CPU
            // very slightly, you could add a tiny sleep, but this will reduce CPU consumption.
            // For maximum CPU burn, omit this.
            // if ($counter % 100 === 0) { // Example: yield every 100 outer loops
            //     usleep(1); // Sleep for 1 microsecond
            // }
        }

        // You can uncomment this to verify the actual execution time
        // $actualDuration = microtime(true) - $startTime;
        // echo "Hashing function ran for approximately " . round($actualDuration, 2) . " seconds.\n";
    }

    public static function consume_cpu_with_math() {
        $startTime = microtime(true);

        // Determine a random duration between 5 and 10 seconds
        $durationSeconds = rand(5, 10);
        $endTime = $startTime + $durationSeconds;

        // Initialize a value with some randomness for the calculations
        $currentValue = M_PI * (rand(1000, 5000) / 1000.0); // A random-ish float

        $counter = 0;
        // Loop until the desired duration has passed
        while (microtime(true) < $endTime) {
            // Perform a batch of complex math operations in an inner loop.
            // Adjust 'innerLoopIterations' if needed based on your server's CPU speed.
            $innerLoopIterations = 10000;
            for ($i = 0; $i < $innerLoopIterations; $i++) {
                // A series of math operations. abs() is used to avoid errors with sqrt/log.
                // A small constant is added to log arguments to prevent log(0).
                $tempVal = $currentValue + $counter + ($i / 1000.0); // Vary input slightly

                $result = sqrt(abs(sin($tempVal) * cos($tempVal * 1.1) + 0.0001)) *
                          log(abs(tan($tempVal / 1.2) * atan($tempVal) + M_E) + 0.0001);

                // You can add more operations here to increase intensity
                // $result = pow($result, 1.00001);
            }
            $counter++;
            $currentValue += 0.001; // Slightly change the base value for next iteration batch

            // Optional: Tiny sleep as mentioned in the hashing function
            // if ($counter % 100 === 0) {
            //     usleep(1);
            // }
        }

        // You can uncomment this to verify the actual execution time
        // $actualDuration = microtime(true) - $startTime;
        // echo "Math function ran for approximately " . round($actualDuration, 2) . " seconds.\n";
    }
}