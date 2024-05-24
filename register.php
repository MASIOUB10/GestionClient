<?php
require_once 'dbconnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Create new Database instance
    $database = new Database();
    $db = $database->getConnection();

    // Prepare insert statement
    $stmt = $db->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashed_password);

    // Execute the statement
    if ($stmt->execute()) {
        // Redirect to login page after successful registration
        header("location: login.php");
        exit();
    } else {
        echo "Registration failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-200 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md">
        <h2 class="text-2xl mb-4">User Registration</h2>
        <form method="post">
            <div class="mb-4">
                <label for="username" class="block mb-2">Username:</label>
                <input type="text" id="username" name="username" required class="block w-full px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
                <label for="password" class="block mb-2">Password:</label>
                <input type="password" id="password" name="password" required class="block w-full px-4 py-2 border rounded-md">
            </div>
            <div>
                <input type="submit" value="Register" class="px-6 py-2 bg-blue-600 text-white rounded-md cursor-pointer">
            </div>
        </form>
    </div>
</body>
</html>
