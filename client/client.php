<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informations client</title>
    <link rel="stylesheet" href="client.css">
    <link rel="icon" href="../images/favicon_white.png" type="image/png">
</head>
<body>
    <!-- Logo de l'entreprise avec le lien -->
    <a class="logo-container" href="https://integraal-it.ch/" target="_blank">
        <img src="../images/integraal_it_logo_removed.png" alt="Logo">
    </a>
    <!-- Aside de recherche client -->
    <aside id="left-aside">
        <div class="client-search-container">
            <h1 id="client-search-title">Recherche client</h1>
            <input type="text" id="searchInput" placeholder="Rechercher par nom..." oninput="searchClients()">
            <ul id="clientList" class="client-list"></ul>
        </div>
    </aside>
    <header>
        <div class="nine">
            <h1>Projection coûts<span>Informations client</span></h1>
        </div>
    </header>
    <!-- Formulaire à remplir (required) pour les informations client -->
    <form id="clientForm" action="../panier/panier.php" method="post">
        <label for="company">Nom de l'entreprise :</label>
        <input type="text" id="company" name="company" placeholder="SA, SàRL..." required><br><br>
        
        <label for="address">Adresse :</label>
        <input type="text" id="address" name="address" placeholder="Rue, numéro..." required><br>
        <input type="text" id="postal" name="postal" placeholder="Localité, code postal..." required><br><br>
        
        <label for="fullname">Nom complet :</label>
        <input type="text" id="fullname" name="fullname" placeholder="Nom, Prénom..." required><br><br>
        
        <label for="phone">Numéro de téléphone :</label>
        <input type="text" id="phone" name="phone" placeholder="+** (0)..." required><br><br>
        
        <label for="email">Adresse mail :</label>
        <input type="email" id="email" name="email" placeholder="exemple@integraal.ch..." required><br><br>
        
        <button type="submit" id="submit">Valider</button>
    </form>
    <!-- Récupération / Insertion des données du presse-papier -->
    <aside id="right-aside">
        <input type="text" id="infos" placeholder="Données compressées...">
        <button onclick="putInfos()" id="put_infos">Insérer les informations</button><br><br><br><br>
        <button onclick="getInfos()" id="get_infos">Récupérer les informations</button>
    </aside>
    <script src="client.js"></script>
</body>
</html>