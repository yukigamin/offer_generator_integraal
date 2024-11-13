<?php
// Configuration de la connexion à la base de données
$servername = "localhost";
$username = "root";
$password = /*"Te4mint.00"*/"";
$dbname = "offer generator";

// Créer la connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Requête SQL pour récupérer les données
$products_sql = "SELECT name, french_name, `group`, category, price, monthly_price, service_per_product, cat_type FROM products";
$clients_sql = "SELECT company, address, postal, fullname, phone, email FROM clients";
$products_result = $conn->query($products_sql);
$clients_result = $conn->query($clients_sql);

// Tableau pour stocker les résultats
$products = array();
$clients = array();

// Vérifier si des résultats sont retournés
if ($products_result->num_rows > 0) {
    while($row = $products_result->fetch_assoc()) {
        $products[] = array(
            'name' => $row['name'],
            'french_name' => $row['french_name'],
            'group' => $row['group'],
            'category' => $row['category'],
            'price' => (float)$row['price'],
            'monthly_price' => (float)$row['monthly_price'],
            'service_per_product' => $row['service_per_product'] ? true : false,
            'cat_type' => $row['cat_type']
        );
    }
}
if ($clients_result->num_rows > 0) {
    while($row = $clients_result->fetch_assoc()) {
        $clients[] = array(
            'company' => $row['company'],
            'address' => $row['address'],
            'postal' => $row['postal'],
            'fullname' => $row['fullname'],
            'phone' => $row['phone'],
            'email' => $row['email']
        );
    }
}

// Fermer la connexion
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product List</title>
    <script>
        localStorage.clear();

        document.addEventListener("DOMContentLoaded", function() {
            // Convertir les données PHP en JSON
            const products = <?php echo json_encode($products, JSON_PRETTY_PRINT); ?>;
            const clients = <?php echo json_encode($clients, JSON_PRETTY_PRINT); ?>;

            // Mettre les données dans le stockage local pour transférer aux autres pages
            localStorage.setItem('products', JSON.stringify(products));
            localStorage.setItem('clients', JSON.stringify(clients));

            // Envoyer l'utilisateur à la page client
            window.location.href = 'client/client.php';
        });
    </script>
</head>
<body>
    <h1>Product List</h1>
    <p>Redirection en cours...</p>
</body>
</html>