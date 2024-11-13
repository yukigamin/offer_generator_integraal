<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pages A4 avec Navigation</title>
    <link rel="stylesheet" href="visualisation.css">
    <link rel="icon" href="../images/favicon_white.png" type="image/png">
</head>
<body>
    <!-- Logo -->
    <a class="logo-container" href="https://integraal-it.ch/" target="_blank">
        <img src="../images/integraal_it_logo_removed.png" alt="Logo">
    </a>
    <div class="container">
        <aside id="left_aside"><!-- Les boutons pour accéder aux pages seront générés ici --></aside>
        <div class="sheet" id="pageSheet">
            <!-- Les pages seront générées ici -->
        </div>
        <!-- Différents boutons de gestion -->
        <aside id="top">
            <button id="get-all" onclick="getAll()">Sauver l'offre</button><br><br>
            <input type="text" id="set-content">
            <button id="set-all" onclick="setAll()">Insérer une offre</button><br><br>
            <input type="text" id="intro-content">
            <button id="add-intro" onclick="addIntro()">Ajouter à l'introduction</button><br><br>
            <input type="text" id="proposition-content">
            <button id="add-proposition" onclick="addProposition()">Ajouter à la proposition</button><br><br>
            <button id="add-product" onclick="addProduct()">Ajouter un produit</button><br><br>
            <button id="validate-button" onclick="validate()">Valider</button>
        </aside>
        <footer class="footer">
            Ceci est une prévisualisation pour modifier les éléments.<br>
            Le pdf rendu ne sera que similaire à ceci.
        </footer>
    </div>
    <script src="visualisation.js"></script>
</body>
</html>