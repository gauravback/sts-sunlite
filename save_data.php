<?php
// Apne Server ki details yahan daalein
$servername = "localhost"; // Ye same rahega
$username = "u578777883_stss"; // Hostinger username (jaise: u578777883_user)
$password = "Sts@#$123456"; // Jo password aapne Hostinger me banaya tha
$dbname = "u578777883_stss"; // ✅ Ye aapke screenshot se confirm ho gaya hai
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($_POST['lat']) && isset($_POST['lng']) && isset($_POST['agent_name'])) {
    
    $agent = $conn->real_escape_string($_POST['agent_name']);
    $lat = $conn->real_escape_string($_POST['lat']);
    $lng = $conn->real_escape_string($_POST['lng']);
    $address = $conn->real_escape_string($_POST['address']);

    $sql = "INSERT INTO sales_location_logs (agent_name, latitude, longitude, address) 
            VALUES ('$agent', '$lat', '$lng', '$address')";

    if ($conn->query($sql) === TRUE) {
        echo "Success";
    } else {
        echo "Error";
    }
}
$conn->close();
?>