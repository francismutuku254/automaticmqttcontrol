<?php
// MQTT Broker and Topic
$broker = 'localhost';
$topic = 'sensor/moisture';

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
} else {
    echo "Connected to database.\n";
}

// Full path to mosquitto_sub (adjust the path if necessary)
$mosquittoSubPath = '"C:\Program Files\mosquitto\mosquitto_sub.exe"';

// Construct the command to subscribe to the MQTT topic and listen indefinitely
$command = $mosquittoSubPath . ' -h ' . $broker . ' -t ' . $topic;

// Execute the command and capture real-time output
$process = popen($command, 'r');

if (!$process) {
    die("Error subscribing to MQTT broker.\n");
}

echo "Listening for moisture data on MQTT topic: " . $topic . "\n";

while (!feof($process)) {
    // Read the output (the published moisture value) from the MQTT broker
    $line = fgets($process);
    if ($line !== false) {
        // Convert the received message (moisture value) to a float
        $moisture_value = floatval($line);
        echo "Received moisture value: " . $moisture_value . "\n";

        // Insert the moisture value into the database
        $sql = "INSERT INTO soil_moisture (moisture_level) VALUES ('$moisture_value')";
        if ($conn->query($sql) === TRUE) {
            echo "Moisture value inserted into database: " . $moisture_value . "\n";
        } else {
            echo "Error inserting data: " . $conn->error . "\n";
        }
    }
}

pclose($process);

// Close the database connection
$conn->close();
?>
