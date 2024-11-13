let products = JSON.parse(localStorage.getItem('products'));

let currentCategory = '';
const cart = [];

// Méthode qui génère les boutons de catégorie
function generateNavbar() {
    const navbar = document.getElementById('navbar');
    const categories = [...new Set(products.map(product => product.category))];
    
    categories.forEach(category => {
        const categoryDiv = document.createElement('div');
        categoryDiv.className = 'category';
        categoryDiv.textContent = capitalizeFirstLetter(category);
        categoryDiv.onclick = () => filterCategory(category);
        navbar.appendChild(categoryDiv);
    });

    // Ajoute une option "Tous" pour afficher tous les produits
    const allDiv = document.createElement('div');
    allDiv.className = 'category';
    allDiv.textContent = 'Tous';
    allDiv.onclick = () => filterCategory('');
    navbar.appendChild(allDiv);
}

// Simple méthode pour mettre la première lettre d'un texte en majuscule
function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Méthode qui gère le changement de catégorie
function filterCategory(category) {
    currentCategory = category;
    searchProducts();

    // Supprime la classe active de tous les boutons
    const categoryButtons = document.querySelectorAll('.category');
    categoryButtons.forEach(button => {
        button.classList.remove('active');
    });

    // Ajoute la classe active au bouton correspondant
    const activeButton = Array.from(categoryButtons).find(button => button.textContent.toLowerCase() === (category || 'tous').toLowerCase());
    if (activeButton) {
        activeButton.classList.add('active');
    }
}

// Méthode qui recherche les produits selon la catégorie actuelle et le texte dans la barre de recherche
function searchProducts() {
    const searchInput = document.getElementById('search-input').value.toLowerCase();
    const productList = document.getElementById('product-list');
    productList.innerHTML = '';

    // Filtre les produits selon la catégorie et le texte de recherche
    const filteredProducts = products.filter(product => {
        return (product.french_name.toLowerCase().includes(searchInput) || product.group.toLowerCase().includes(searchInput)) && 
               (currentCategory === '' || product.category === currentCategory);
    });
    
    // Ordonne les produits filtrés
    filteredProducts.sort((a, b) => {
        // Ordonnées par ordre alphabétique (français)
        return a.french_name.localeCompare(b.french_name);
    });

    // Crée la division pour chaque produit à afficher
    filteredProducts.forEach(product => {
        const productDiv = document.createElement('div');
        productDiv.className = 'product';

        const productName = document.createElement('div');
        productName.className = 'product-name';
        productName.textContent = product.french_name;

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.className = 'quantity-input';
        quantityInput.min = 1;
        quantityInput.value = 1;

        const insideText = document.createElement('span');
        insideText.className = 'text';
        insideText.textContent = 'Ajouter au panier';

        const addButton = document.createElement('button');
        addButton.appendChild(insideText);
        addButton.onclick = () => addToCart(product, parseInt(quantityInput.value));

        productDiv.appendChild(productName);
        productDiv.appendChild(quantityInput);
        productDiv.appendChild(addButton);
        productList.appendChild(productDiv);
    });
}

// Ajoute 'quantity' instances de 'product' dans le panier
function addToCart(product, quantity) {
    // Si le nombre n'est pas valide, annuler
    if (isNaN(quantity) || quantity < 1) return;

    // Utiliser .find() pour chercher si le produit est déjà dans le panier
    const existingProduct = cart.find(item => item.name === product.name);

    if (existingProduct) {
        // Si le produit existe déjà ajouter le bon nombre
        existingProduct.count += quantity;
    } else {
        // Sinon, ajouter l'objet de ce produit dans la panier
        cart.push({
            name: product.name,
            french_name : product.french_name,
            category: product.category,
            group: product.group,
            count: quantity, // Le 'count' est initialisé au nombre de ce produit demandé
            price: product.price,
            monthly_price: product.monthly_price,
            service_per_product: product.service_per_product,
            cat_type: product.cat_type
        });
    }

    updateCartDisplay();
}

// Méthode qui regénère l'affichage du panier
function updateCartDisplay() {
    const cartItems = document.getElementById('cart-items');
    cartItems.innerHTML = '';

    // Parcourir le tableau 'cart' pour afficher les articles
    cart.forEach(({ name, count, french_name }) => {
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';

        const itemText = document.createElement('span');
        itemText.textContent = `${french_name} - ${count}`;

        const removeButton = document.createElement('button');
        removeButton.textContent = 'Supprimer';
        removeButton.onclick = () => removeFromCart(name);

        cartItem.appendChild(itemText);
        cartItem.appendChild(removeButton);
        cartItems.appendChild(cartItem);

        document.getElementById('validate-button').scrollIntoView({ behavior : 'smooth', block: 'end'});
    });
}

// Supprimer un produit du panier
function removeFromCart(productName) {
    // Trouver l'index du produit dans le panier
    const productIndex = cart.findIndex(item => item.name === productName);
    if (productIndex > -1) {
        cart.splice(productIndex, 1); // Supprimer le produit du panier
    }
    updateCartDisplay();
}

// Méthode simple qui recherche les produits si la touche 'Enter' est pressée
function checkEnter(event) {
    if (event.key === 'Enter') {
        searchProducts();
    }
}

// Méthode qui met les informations 'Données compressées' dans le panier
function putInfos() {
    // Récupère et décode les infos
    const jsonData = document.getElementById('infos').value;
    const simplifiedCart = JSON.parse(jsonData);

    // Ajoute les produits au panier
    simplifiedCart.forEach(cartItem => {
        const product = products.find(prod => prod.name === cartItem.name);

        if(product) {
            addToCart(product, cartItem.count);
        }
    })

    document.getElementById('infos').value = '';
}

// Méthode qui récupère les infos du panier pour les copier dans le presse-papier
function getInfos() {
    // Récupère et encode les infos
    const simplifiedCart = cart.map(product => {
        return {
            name: product.name,
            count: product.count
        };
    });
    const jsonData = JSON.stringify(simplifiedCart);

    // Appel à la méthode de copie
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

// Méthode qui génère la date de début de contrat (de base)
function startDate() {
    // Date actuelle
    const today = new Date();

    // Mois et année
    let month = today.getMonth();
    let year = today.getFullYear();

    // Le mois après celui après celui actuel
    month += 2;

    // Si le mois dépasse décembre (11), incrémenter l'année et décrémenter de 12 le mois
    if (month > 11) {
        month -= 12;
        year += 1;
    }

    // Retourne le texte sous la forme DD/MM/YYYY
    return `01/${String(month + 1).padStart(2, '0')}/${year}`;
}

// Méthode qui génère le numéro d'offre (de base)
function getOfferNumber() {
    const today = new Date();

    // Récupère les données sous forme YYYYMMDD
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');

    // suffixe générique (modifiable)
    const suffix = '-003';

    return `${year}${month}${day}${suffix}`;
}

// Méthode qui génère la date de validité de l'offre (de base)
function getValidityDate() {
    const today = new Date();

    let month = today.getMonth();
    let year = today.getFullYear();

    month += 1;

    // Si le mois dépasse décembre, redescendre
    if(month > 11) {
        month = 0;
        year += 1;
    }

    const lastDayOfNextMonth = new Date(year, month + 1, 0);

    // Définit les tableaux avec les jours de la semaine et les mois de l'année en français
    const daysOfWeek = ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi"];
    const monthsOfYear = [
        "Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet",
        "Août", "Septembre", "Octobre", "Novembre", "Décembre"
    ];

    // Extrait le jour de la semaine, le jour, le mois et l'année
    const dayOfWeek = daysOfWeek[lastDayOfNextMonth.getDay()];
    const day = String(lastDayOfNextMonth.getDate());
    const monthName = monthsOfYear[lastDayOfNextMonth.getMonth()];
    const yearOfLastDay = lastDayOfNextMonth.getFullYear();

    return `${dayOfWeek}, ${day} ${monthName} ${yearOfLastDay}`;
}

// Valide les entrées du panier et envoit à la page suivante
function validateCart() {
    let radios = document.getElementsByName('options');
    let contractLength = 0;
    for(let i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            contractLength = radios[i].value;
        }
    }

    // Cas qui ne devrait pas arriver puisque '12 mois' est 'checked' originalement
    if(contractLength == 0) {
        alert('Veuillez cocher une durée de contrat !');
        exit;
    }

    const contractStart = document.getElementById('contract-start').value;
    const offerNumber = document.getElementById('offer-number').value;
    const offerValidity = document.getElementById('offer-validity').value;

    // Enregistre les données dans le stockage de la session
    sessionStorage.setItem('cartData', JSON.stringify(cart));
    sessionStorage.setItem('contractLength', JSON.stringify(contractLength));
    sessionStorage.setItem('contractStart', JSON.stringify(contractStart));
    sessionStorage.setItem('offerNumber', JSON.stringify(offerNumber));
    sessionStorage.setItem('offerValidity', JSON.stringify(offerValidity));
    
    // Navigue à la page de visualisation
    window.location.href = '../visualisation/visualisation.php';
}

document.addEventListener('DOMContentLoaded', generateNavbar);

// Initialise les inputs de contrat et d'offre
document.getElementById('contract-start').value = startDate();
document.getElementById('offer-number').value = getOfferNumber();
document.getElementById('offer-validity').value = getValidityDate();