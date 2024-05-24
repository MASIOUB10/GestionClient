<?php
session_start();
require_once 'dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit();
}

// Get logged-in user ID
$user_id = $_SESSION['user_id'];

// Database connection
$database = new Database();
$db = $database->getConnection();

// Functions for CRUD operations

// Function to fetch clients for the logged-in user
function getClientsForUser($user_id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM clients WHERE created_by = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to add a new client
function addClient($name, $email, $phone, $address, $user_id) {
    global $db;
    $stmt = $db->prepare("INSERT INTO clients (name, email, phone, address, created_by) VALUES (:name, :email, :phone, :address, :created_by)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':created_by', $user_id);
    return $stmt->execute();
}

// Function to update an existing client
function updateClient($id, $name, $email, $phone, $address) {
    global $db;
    $stmt = $db->prepare("UPDATE clients SET name = :name, email = :email, phone = :phone, address = :address WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    return $stmt->execute();
}

// Function to delete a client
function deleteClient($id) {
    global $db;
    $stmt = $db->prepare("DELETE FROM clients WHERE id = :id");
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}
//import data
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['import_clients'])) {
    if (isset($_FILES["file"]) && $_FILES["file"]["error"] == UPLOAD_ERR_OK) {
        $file = $_FILES["file"]["tmp_name"];
        $handle = fopen($file, "r");
        // Skip the header row
        fgetcsv($handle);
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $name = $data[0];
            $email = $data[1];
            $phone = $data[2];
            $address = $data[3];
            addClient($name, $email, $phone, $address, $user_id);
        }
        fclose($handle);
        echo "Import successful!";
    } else {
        echo "Error uploading file.";
    }
}
// Export client data to CSV
$clients = getClientsForUser($user_id);

if (isset($_GET['export_clients'])) {
    if ($clients) {
        $filename = 'clients.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Name', 'Email', 'Phone', 'Address'));
        foreach ($clients as $client) {
            fputcsv($output, array($client['name'], $client['email'], $client['phone'], $client['address']));
        }
        fclose($output);
        exit();
    } else {
        // Handle case where no clients are found
        echo "No clients found to export.";
        exit();
    }
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add a new client
    if (isset($_POST['add_client'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        addClient($name, $email, $phone, $address, $user_id);
    }

    // Update an existing client
    if (isset($_POST['edit_client'])) {
        $id = $_POST['client_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        updateClient($id, $name, $email, $phone, $address);
    }

    // Delete a client
    if (isset($_POST['delete_client'])) {
        $id = $_POST['client_id'];
        deleteClient($id);
    }
}

// Fetch clients for the logged-in user
$clients = getClientsForUser($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,            0, 0.5);
        }
        .modal-content {
            background-color: #fff;
            width: 50%;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-gray-200">
    <div class="container mx-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">User Panel</h1>
            <a href="logout.php" class="px-4 py-2 bg-red-600 text-white rounded-md">Logout</a>
        </div>

        <!-- Add Client Form -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Client</h2>
            <form method="post" class="space-y-4">
                <div>
                    <label for="name" class="block mb-1">Name:</label>
                    <input type="text" id="name" name="name" class="block w-full px-4 py-2 border rounded-md" required>
                </div>
                <div>
                    <label for="email" class="block mb-1">Email:</label>
                    <input type="email" id="email" name="email" class="block w-full px-4 py-2 border rounded-md" required>
                </div>
                <div>
                    <label for="phone" class="block mb-1">Phone:</label>
                    <input type="text" id="phone" name="phone" class="block w-full px-4 py-2 border rounded-md" required>
                </div>
                <div>
                    <label for="address" class="block mb-1">Address:</label>
                    <textarea id="address" name="address" class="block w-full px-4 py-2 border rounded-md" required></textarea>
                </div>
                <div>
                    <button type="submit" name="add_client" class="px-6 py-2 bg-green-600 text-white rounded-md">Add Client</button>
                </div>
            </form>
        </div>

        <!-- Import/Export CSV -->

       <!-- Import/Export CSV -->
<!-- Import/Export CSV -->
<div class="mb-8">
    <h2 class="text-xl font-semibold mb-4">Import/Export Clients</h2>
    <!-- Export Clients Button -->
    <a href="?export_clients=true" class="px-6 py-2 bg-blue-600 text-white rounded-md">Export Clients (CSV)</a>
    <!-- Import Clients Form -->
    <form  method="post" enctype="multipart/form-data" class="mt-4">
        <label for="file" class="block mb-2">Import Clients (CSV):</label>
        <input type="file" name="file" id="file" accept=".csv" class="mb-2">
        <button type="submit" name="import_clients" class="px-6 py-2 bg-green-600 text-white rounded-md">Import Clients</button>
    </form>
</div>



        <!-- Client List -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Client List</h2>
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr>
                        <th class="border border-gray-300 px-4 py-2">Name</th>
                        <th class="border border-gray-300 px-4 py-2">Email</th>
                        <th class="border border-gray-300 px-4 py-2">Phone</th>
                        <th class="border border-gray-300 px-4 py-2">Address</th>
                        <th class="border border-gray-300 px-4 py-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($client['name']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($client['email']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($client['phone']) ?></td>
                            <td class="border border-gray-300 px-4 py-2"><?= htmlspecialchars($client['address']) ?></td>
                            <td class="border border-gray-300 px-4 py-2">
                                <button onclick="openEditModal('<?= $client['id'] ?>', '<?= htmlspecialchars($client['name']) ?>', '<?= htmlspecialchars($client['email']) ?>', '<?= htmlspecialchars($client['phone']) ?>', '<?= htmlspecialchars($client['address']) ?>')" class="px-4 py-1 bg-blue-600 text-white rounded-md">Edit</button>
                                <form method="post" class="inline">
                                    <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
                                    <button type="submit" name="delete_client" class="px-4 py-1 bg-red-600 text-white rounded-md">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Edit Client Modal -->
        <div id="editClientModal" class="modal">
            <div class="modal-content">
                <h2 class="text-xl font-semibold mb-4">Edit Client</h2>
                <form id="editClientForm" method="post">
                    <input type="hidden" name="client_id" id="edit_client_id">
                    <div>
                        <label for="edit_name" class="block mb-1">Name:</label>
                        <input type="text" id="edit_name" name="name" class="block w-full px-4 py-2 border rounded-md mb-4" required>
                    </div>
                    <div>
                        <label for="edit_email" class="block mb-1">Email:</label>
                        <input type="email" id="edit_email" name="email" class="block w-full px-4 py-2 border rounded-md mb-4" required>
                    </div>
                    <div>
                        <label for="edit_phone" class="block mb-1">Phone:</label>
                        <input type="text" id="edit_phone" name="phone" class="block w-full px-4 py-2 border rounded-md mb-4" required>
                    </div>
                    <div>
                        <label for="edit_address" class="block mb-1">Address:</label>
                        <textarea id="edit_address" name="address" class="block w-full px-4 py-2 border rounded-md mb-4" required></textarea>
                    </div>
                    <div>
                        <button type="submit" name="edit_client" class="px-6 py-2 bg-blue-600 text-white rounded-md cursor-pointer">Update Client</button>
                    </div>
                </form>
                <button onclick="closeModal()" class="px-6 py-2 bg-gray-500 text-white rounded-md cursor-pointer mt-4">Close</button>
            </div>
        </div>

        <!-- JavaScript for modal functionality -->
        <script>
            function openEditModal(id, name, email, phone, address) {
                document.getElementById('edit_client_id').value = id;
                document.getElementById('edit_name').value = name;
                document.getElementById('edit_email').value = email;
                document.getElementById('edit_phone').value = phone;
                document.getElementById('edit_address').value = address;
                document.getElementById('editClientModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('editClientModal').style.display = 'none';
            }
        </script>
    </div>
</body>
</html>


