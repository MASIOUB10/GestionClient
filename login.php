<?php
session_start();
require_once 'dbconnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Create new Database instance
    $database = new Database();
    $db = $database->getConnection();

    // Prepare select statement
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password
    if ($user && password_verify($password, $user['password'])) {
        // Store user ID in session
        $_SESSION['user_id'] = $user['id'];
        // Redirect to user page after successful login
        header("location: user.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-200 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md">
        <h2 class="text-2xl mb-4">User Login</h2>
        <form method="post">
            <?php if (isset($error)): ?>
                <p class="text-red-500 mb-4"><?= $error ?></p>
            <?php endif; ?>
            <div class="mb-4">
                <label for="username" class="block mb-2">Username:</label>
                <input type="text" id="username" name="username" required class="block w-full px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
                <label for="password" class="block mb-2">Password:</label>
                <input type="password" id="password" name="password" required class="block w-full px-4 py-2 border rounded-md">
            </div>
            <div class="mb-4">
                <a href="register.php" class="text-blue-600 hover:underline">Don't have an account? Register here.</a>
            </div>
            <div>
                <input type="submit" value="Login" class="px-6 py-2 bg-blue-600 text-white rounded-md cursor-pointer">
            </div>
        </form>
    </div>
</body>
</html>
