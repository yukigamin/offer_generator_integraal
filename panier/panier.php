<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier</title>
    <link rel="stylesheet" href="panier.css">
    <link rel="icon" href="../images/favicon_white.png" type="image/png">
</head>
<body>
    <header>
        <div class="header-content">
            <!-- Logo -->
            <a class="logo-container" href="https://integraal-it.ch/" target="_blank">
                <img src="../images/integraal_it_logo_white_removed.png" alt="Logo">
            </a>
            <div class="nav-search-container">
                <div class="navbar" id="navbar">
                    <!-- les catégories seront générées ici-->
                </div>
                <!-- Barre de recherche -->
                <div class="search-bar">
                    <input type="text" id="search-input" placeholder="Rechercher..." onkeypress="checkEnter(event)">
                    <button id="search-products" onclick="searchProducts()">🔍</button>
                </div>
            </div>
        </div>
    </header>
    <!-- Aside avec les informations concernant le contrat et l'offre -->
    <aside id="left-aside">
        <div class="three">
            <h1>Durée de contrat :</h1>
        </div>
        <form>
            <label for="01">12 mois</label>
            <input id="01" type="radio" name="options" value="12" checked>
            <label for="02">24 mois</label>
            <input id="02" type="radio" name="options" value="24">
            <label for="03">36 mois</label>
            <input id="03" type="radio" name="options" value="36">
        </form>
        <div class="offer">
            <h1>Début de contrat :</h1>
        </div>
        <input type="text" id="contract-start" value="tomate">
        <div class="offer">
            <h1>Numéro d'offre :</h1>
        </div>
        <input type="text" id="offer-number">
        <div class="offer">
            <h1>Validité de l'offre :</h1>
        </div>
        <input type="text" id="offer-validity">
    </aside>
    <!-- Aside avec les boutons de récupération / insertion ainsi que le panier -->
    <aside id="cart">
        <div class="three">
            <h1>Panier</h1>
        </div>
        <div id="infos_div">
            <button onclick="getInfos()" id="get_infos">Récupérer articles</button><br><br>
            <input type="text" id="infos" placeholder="Données compressées...">
            <button onclick="putInfos()" id="put_infos">Insérer articles</button>
        </div>
        <div id="cart-items">
            <!-- Les articles du panier seront affichés ici -->
        </div>
        <button id="validate-button" onclick="validateCart()">Valider</button>
    </aside>
    <main>
        <div id="product-list">
            <!-- Les produits seront affichés ici -->
        </div>
    </main>
    <script src="panier.js"></script>
</body>
</html>