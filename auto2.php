<?php
// Thresholds for controlling the pump
$lower_threshold = 25;
$upper_threshold = 70;
$broker = 'localhost';
$topic = 'pump/control';
$mosquittoPubPath = '"C:\Program Files\mosquitto\mosquitto_pub.exe"';

// MySQL database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mqtt";

// Create MySQL connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Infinite loop to continuously check for new data
while (true) {
    // Retrieve the total number of records
    $sql_count = "SELECT COUNT(*) as count FROM soil_moisture";
    $result_count = $conn->query($sql_count);
    $row_count = $result_count->fetch_assoc();
    $initial_count = $row_count['count'];

    // Wait for new data to be inserted
    sleep(10); // Check for new data every 10 seconds

    // Get the new record count after waiting
    $result_count = $conn->query($sql_count);
    $row_count = $result_count->fetch_assoc();
    $new_count = $row_count['count'];

    if ($new_count > $initial_count) {
        // If a new record has been inserted, calculate the average moisture level
        $sql = "SELECT AVG(moisture_level) as avg_moisture FROM soil_moisture";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $avg_moisture = $row['avg_moisture'];
            echo "Average Moisture Level: " . $avg_moisture . "%\n";

            // Determine pump control based on the average moisture value
            if ($avg_moisture < $lower_threshold) {
                // Turn the pump ON
                $command = $mosquittoPubPath . ' -h ' . $broker . ' -t ' . $topic . ' -m "ON"';
                exec($command);
                echo "Pump turned ON (Moisture: " . $avg_moisture . "%)\n";
            } elseif ($avg_moisture > $upper_threshold) {
                // Turn the pump OFF
                $command = $mosquittoPubPath . ' -h ' . $broker . ' -t ' . $topic . ' -m "OFF"';
                exec($command);
                echo "Pump turned OFF (Moisture: " . $avg_moisture . "%)\n";
            } else {
                echo "Pump state unchanged (Moisture: " . $avg_moisture . "%)\n";
            }
        } else {
            echo "No data available to calculate average moisture.\n";
        }
    } else {
        echo "No new data detected.\n";
    }
}
$conn->close();
?>
