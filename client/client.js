let clients = [];

document.addEventListener('DOMContentLoaded', function() {
    clients = JSON.parse(localStorage.getItem('clients'));

    const form = document.getElementById('clientForm');
    form.addEventListener('submit', validate);
});

// Méthode qui prend les données de l'input 'infos', les décode et les insère dans le form
function putInfos() {
    const jsonData = document.getElementById('infos').value;

    const clientData = JSON.parse(jsonData);

    // Tableau des entrées du formulaire
    const fields = ['company', 'address', 'postal', 'fullname', 'phone', 'email'];

    fields.forEach(field => {
        document.getElementById(field).value = clientData[field];
    });

    document.getElementById('infos').value = '';
}

// Méthode qui récupère les infos du form et les copie dans le presse-papier
function getInfos() {
    const clientData = createClientData();

    // Encodage des données
    const jsonData = JSON.stringify(clientData);

    // Utilise la méthode copyToClipboard créée
    copyToClipboard(jsonData);
}

// Méthode qui copie un texte (string) dans le clipboard (presse-papier)
function copyToClipboard(text) {
    // Créer un élément textarea temporaire
    const tempTextArea = document.createElement('textarea');
    
    // Définir la valeur du textarea au texte que vous souhaitez copier
    tempTextArea.value = text;

    // Placer l'élément textarea hors de l'écran pour éviter de perturber l'interface utilisateur
    tempTextArea.style.position = 'fixed';  // Éviter de faire défiler vers le bas de la page
    tempTextArea.style.left = '-9999px';

    // Ajouter le textarea au corps du document
    document.body.appendChild(tempTextArea);

    // Sélectionner le texte à l'intérieur du textarea
    tempTextArea.select();

    // Exécuter la commande de copie
    try {
        const successful = document.execCommand('copy');
        const msg = successful ? 'Informations client stockées dans le presse-papier.' : 'Échec de la copie des informations client.';
        alert(msg);
    } catch (err) {
        alert('Erreur lors de la copie de données : ' + err.message);
    }

    // Supprimer le textarea du document
    document.body.removeChild(tempTextArea);
}

// Méthode qui lance la validation et envoie les données et l'utilisateur à la page suivante
function validate(event) {
    event.preventDefault();

    // Récupère les données et les convertit
    const clientData = createClientData();
    const jsonData = JSON.stringify({ clientData });

    // Envoyer les données à panier.php via AJAX
    fetch('../pdf/pdf.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: jsonData
    })
    .then(response => response.text())
    .then(data => {
        console.log('Données envoyées avec succès à pdf.php:', data);
        // Une fois que les données sont envoyées, vous pouvez rediriger vers client.php
        window.location.href = '../panier/panier.php';
    })
    .catch(error => {
        console.error('Erreur lors de l\'envoi des données à pdf.php:', error);
    });
}

// Méthode de factorisation utilisée pour 'créer' le tableau de données selon les entrés du formulaire
function createClientData() {
    // Entrées du formulaire
    const fields = ['company', 'address', 'postal', 'fullname', 'phone', 'email'];

    // Récupérer les infos et les mettre dans le tableau
    const clientData = fields.reduce((data, field) => {
        data[field] = document.getElementById(field).value;
        return data;
    }, {});

    // Retourner le tableau
    return clientData;
}

// Méthode qui cherche les clients selon le texte de l'input 'searchInput'
function searchClients() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    // Filtre les clients avec uniquement ceux qui contiennent le terme de recherche dans le nom de l'entreprise
    const filteredClients = clients.filter(client => 
        client.company.toLowerCase().includes(searchTerm)
    );
    updateClientList(filteredClients);
}

// Méthode qui actualise la liste de clients possibles 
function updateClientList(clients) {
    const clientList = document.getElementById('clientList');
    clientList.innerHTML = '';
    // Crée un élément ligne pour chaque client possible
    clients.forEach(client => {
        const li = document.createElement('li');
        li.textContent = client.company;
        // Au clic, la ligne remplit le formulaire
        li.onclick = () => displayClientDetails(client);
        clientList.appendChild(li);
    });
}

// Méthode qui affiche les données du client donné dans le formulaire
function displayClientDetails(client) {
    document.getElementById('company').value = client.company;
    document.getElementById('address').value = client.address;
    document.getElementById('fullname').value = client.fullname;
    document.getElementById('postal').value = client.postal;
    document.getElementById('phone').value = client.phone;
    document.getElementById('email').value = client.email;
}