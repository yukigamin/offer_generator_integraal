let products = [];
let orderedCategories = [];
let orderedCategoriesWithCancellations = [];
let categoriesMap = {};

const sheet = document.getElementById('pageSheet');
let currentPage = 0;

let isGroupDragged = false;
let draggedGroup = null;
let draggedProduct = null;

let contractLength = 0;
let contractStart = '';
let offerNumber = '';
let offerValidity = '';

let introduction = '';
let proposition = '';

let addedProductsCount = 0;

// Méthode qui récupère les infos à la création de la page et initialise la page
function getInfo() {
    products = JSON.parse(sessionStorage.getItem('cartData'));
    contractLength = parseInt(JSON.parse(sessionStorage.getItem('contractLength')));
    contractStart = JSON.parse(sessionStorage.getItem('contractStart'));
    offerNumber = JSON.parse(sessionStorage.getItem('offerNumber'));
    offerValidity = JSON.parse(sessionStorage.getItem('offerValidity'));

    if (products) {

        products.forEach(product => {
            let currentCategory = product.category;

            if (!orderedCategories.includes(currentCategory)) {
                orderedCategories.push(currentCategory);
                orderedCategoriesWithCancellations.push(currentCategory);
            }

            const { category, group } = product;
            
            // Créer la catégorie si elle n'existe pas
            if (!categoriesMap[category]) {
                categoriesMap[category] = {
                   groups: new Array(),
                   monthly_discount: 0,
                   direct_discount: 0,
                   discount: '',
                   description: '',
                   conditions: ''
                }
            }

            // Ajouter le groupe à la catégorie
            categoriesMap[category].groups.push(group);
        });

    } else {
        console.error("Aucune donnée de panier trouvée dans l'URL.");
    }
}

// Méthode qui récupère les infos de la visu pour les copier dans le presse-papier
function getAll() {
    // Mettre les données dans le tableau
    const data = {
        products: products,
        orderedCategories: orderedCategories,
        orderedCategoriesWithCancellations: orderedCategoriesWithCancellations,
        categoriesMap: Object.fromEntries(
            Object.entries(categoriesMap).map(([category, { groups, direct_discount, monthly_discount, discount, description, conditions }]) => [
                category,
                {
                    groups: groups,
                    direct_discount,
                    monthly_discount,
                    discount,
                    description,
                    conditions
                }
            ])
        ),
        contractLength: contractLength,
        contractStart: contractStart,
        offerNumber: offerNumber,
        offerValidity: offerValidity,
        currentPage: currentPage,
        introduction: introduction,
        proposition: proposition
    };

    // Encoder les données
    const jsonData = JSON.stringify(data);

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

// Méthode qui met les informations saisies dans la visualisation
function setAll() {
    const data = JSON.parse(document.getElementById('set-content').value);

    products = data.products || [];
    orderedCategories = data.orderedCategories || [];
    orderedCategoriesWithCancellations = data.orderedCategoriesWithCancellations || [];

    console.log(data.categoriesMap);
    console.log(data.orderedCategories);

    categoriesMap = Object.fromEntries(
        Object.entries(data.categoriesMap || {}).map(([category, { groups, direct_discount, monthly_discount, discount, description, conditions }]) => [
            category,
            {
                groups: groups,  // Convertir à nouveau en Set
                direct_discount,
                monthly_discount,
                discount,
                description,
                conditions
            }
        ])
    );
    
    contractLength = data.contractLength || 0;
    contractStart = data.contractStart || '';
    offerNumber = data.offerNumber || '';
    offerValidity = data.offerValidity || '';
    currentPage = data.currentPage || 0;
    introduction = data.introduction || '';
    proposition = data.proposition || '';

    categories = createCategories();

    createPages();
    createAside();
    showPage(currentPage);

    document.getElementById('set-content').value = '';
}

// Méthode qui crée l'objet categories, utilisé généralement dans le fichier
function createCategories() {
    // Crée l'objet categories selon 'products'
    const categories = products.reduce((acc, product) => {
        if (!acc[product.category]) {
            acc[product.category] = {};
        }
        if (!acc[product.category][product.group]) {
            acc[product.category][product.group] = [];
        }
        acc[product.category][product.group].push(product.name);
        return acc;
    }, {});

    // S'assure que toutes les catégories soient inclues 
    orderedCategories.forEach(category => {
        if (!categories[category]) {
            categories[category] = {}; // Ajouter une catégorie avec un objet groupe vide
        }
    });

    return categories;
}

// Méthode qui crée les boutons sur la gauche
function createAside() {
    const aside = document.getElementById('left_aside');
    aside.innerHTML = '';

    // Parcourir les clés de orderedCategories
    Object.keys(orderedCategories).forEach(index => {
        const button = document.createElement('button');
        button.textContent = orderedCategories[index].charAt(0).toUpperCase() + orderedCategories[index].slice(1);
        button.dataset.pageIndex = index;

        // Permettre aux boutons d'accepter les événements de dépôt
        button.addEventListener('dragover', handleDragOver);
        button.addEventListener('drop', handleDrop);

        // Ajouter des flèches pour réorganiser
        const upArrow = document.createElement('span');
        upArrow.classList.add('arrow-up');
        upArrow.textContent = '↑';

        const downArrow = document.createElement('span');
        downArrow.classList.add('arrow-down');
        downArrow.textContent = '↓';

        // Ajouter un événement de clic pour déplacer le bouton vers le haut
        upArrow.addEventListener('click', (event) => {
            event.stopPropagation();
            moveButtonUp(button);
        });

        // Ajouter un événement de clic pour déplacer le bouton vers le bas
        downArrow.addEventListener('click', (event) => {
            event.stopPropagation();
            moveButtonDown(button);
        });

        button.appendChild(upArrow);
        button.appendChild(downArrow);

        // Ajouter un événement de clic pour afficher la page correspondante
        button.addEventListener('click', () => showPage(parseInt(button.dataset.pageIndex)));
        aside.appendChild(button);

        // Déterminer si la catégorie doit afficher la croix
        const hideButton = !orderedCategoriesWithCancellations.includes(orderedCategories[index]);
        // Mettre à jour le bouton aside pour cette catégorie
        const asideButton = document.querySelector(`#left_aside button[data-page-index="${index}"]`);
        if (asideButton) {
            asideButton.style.backgroundColor = hideButton ? 'c8c8c8' : 'f0f0f0';
            asideButton.style.opacity = hideButton ? 0.5 : 1;
        }
    });
}

// Méthode qui permet de déplacer le-dit bouton vers le haut
function moveButtonUp(button) {
    const previousButton = button.previousElementSibling;
    if (previousButton) {
        // Insérer le bouton avant le bouton précédent dans le DOM
        button.parentNode.insertBefore(button, previousButton);

        // Trouver les indices dans orderedCategories
        const currentIndex = orderedCategories.indexOf(button.childNodes[0].textContent.toLowerCase());
        const previousIndex = orderedCategories.indexOf(previousButton.childNodes[0].textContent.toLowerCase());

        // Échanger les catégories dans le tableau
        if (currentIndex > -1 && previousIndex > -1) {
            [orderedCategories[currentIndex], orderedCategories[previousIndex]] = 
                [orderedCategories[previousIndex], orderedCategories[currentIndex]];  
        }
    }
}

// Méthode qui permet de déplacer le-dit bouton vers le bas
function moveButtonDown(button) {
    const nextButton = button.nextElementSibling;
    if (nextButton) {
        // Insérer le bouton après le bouton suivant dans le DOM
        button.parentNode.insertBefore(nextButton, button);

        // Trouver les indices dans orderedCategories
        const currentIndex = orderedCategories.indexOf(button.childNodes[0].textContent.toLowerCase());
        const nextIndex = orderedCategories.indexOf(nextButton.childNodes[0].textContent.toLowerCase());

        // Échanger les catégories dans le tableau
        if (currentIndex > -1 && nextIndex > -1) {
            [orderedCategories[currentIndex], orderedCategories[nextIndex]] = [orderedCategories[nextIndex], orderedCategories[currentIndex]];   
        }
    }
}

function createPage(categoryName, groups, pageIndex) {
    const page = document.createElement('div');
    page.classList.add('page');
    page.id = `page${pageIndex + 1}`;

    // Savoir si cette page devrait posséder la colonne 'prix mensuel' ou pas
    let hasMonthlyPrice = false;
    products.forEach(product => {
        if(product.category === categoryName) {
            if(product.monthly_price && product.monthly_price > 0) {
                hasMonthlyPrice = true;
            }
        }
    })

    const costHeader = hasMonthlyPrice ? 'Coût mensuel' : 'Coûts uniques';
    const serviceHeader = hasMonthlyPrice ? '<th>Mise en service</th>' : '';

    // Création de la page et de tous les objets à l'intérieur
    page.innerHTML = `
        <div id="popup" class="popup">
            <label for="input1">Rabais prix ponctuel : </label>
            <input type="number" id="input1">
            <button onclick="fillDiscount(1)">100%</button><br><br>

            ${hasMonthlyPrice ? 
                `<label for="input2">Rabais prix mensuel : </label>
                <input type="number" id="input2">
                <button onclick="fillDiscount(2)">100%</button><br><br>` :
                ''
            }

            <label for="text_input">Description : </label>
            <input type="text" id="text_input"><br><br></br>

            <button id="submitButton" onclick="addDiscount('${categoryName}')">Valider</button>
        </div>
        <div id="big-cross" class="cross" onclick="deletePage()">✕</div>
        <h1>
            <input id="title" type="text" value="${categoryName.charAt(0).toUpperCase() + categoryName.slice(1)}" />
        </h1>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>${costHeader}</th>
                        ${serviceHeader}
                        <th>Supprimer</th>
                    </tr>
                </thead>
                <tbody>
                    ${Object.keys(groups).map(group => `
                        <tr class="group-header" draggable="true" data-group="${group}">
                            <td colspan="4">
                                <input class="group_title" value="${group}"></input>
                                <button class="drag-handle" title="Move Group">☰</button>
                            </td>
                        </tr>
                        ${groups[group].map(item => {
                            const product = products.find(p => p.name === item);
                            const unitPrice = product.monthly_price && product.monthly_price > 0 ? product.monthly_price : product.price;
                            const cost = unitPrice * product.count;

                            const serviceCost = product.monthly_price && product.monthly_price > 0 ? 
                            (product.service_per_product ? product.price * product.count : product.price) : cost;

                            const noMoney = hasMonthlyPrice && product.monthly_price == 0;

                            return `
                                <tr draggable="true" class="product-row" data-product="${item}" data-group="${group}">
                                    <td><input class="french_name" data-product="${item}" type="text" value="${product.french_name}" /></td>
                                    <td><input type="number" value="${product.count}" data-product="${item}" class="quantity" /></td>
                                    <td><input type="number" step="0.01" value="${unitPrice.toFixed(2)}" data-product="${item}" class="${product.monthly_price > 0 ? 'monthly_price' : 'price'}" /></td>
                                    <td class=${noMoney ? 'no-money' : 'cost'}>${(hasMonthlyPrice ? (product.monthly_price ? cost.toFixed(2) : 0) : cost.toFixed(2))} CHF</td>
                                    ${hasMonthlyPrice
                                        ? (product.monthly_price > 0
                                            ? `<td><input type="number" value="${serviceCost.toFixed(2)}" data-product="${item}" class="price" /></td>`
                                            : `<td class="cost">${cost.toFixed(2)} CHF</td>`
                                        )
                                        : ''
                                    }
                                    <td style="width: 50px;"><button onclick="deleteProduct('${product.name}')">X</button></td>
                                </tr>
                            `;
                        }).join('')}
                    `).join('')}
                </tbody>
            </table>
        </div>
        <div id="down-buttons">
            <button id="delete-page" onclick="deletePage()">Supprimer cette page</button>
            <button id="add-discount" onclick="openPopup()">Ajouter un rabais</button>
            <div>
                <input class="desc_input" id="description-input" value=""></input>
                <input class="desc_input" id="condition-input" value=""></input><br>
                <button id="add-description" onclick="addDescription('${categoryName}')">Ajouter à la description</button>
                <button id="add-condition" onclick="addCondition('${categoryName}')">Ajouter aux conditions</button>
            </div>
            <div id="discount-space"></div>
        </div>
    `;

    // Ajout d'écouteurs d'évènements pour le déplacement les groupes et des produits
    page.querySelectorAll('.group-header').forEach(groupHeader => {
        groupHeader.addEventListener('dragstart', handleDragStart);
        groupHeader.addEventListener('dragover', handleDragOver);
        groupHeader.addEventListener('drop', handleDrop);
    });
    page.querySelectorAll('.product-row').forEach(productHeader => {
        productHeader.addEventListener('dragstart', handleProductDragStart);
        productHeader.addEventListener('dragover', handleProductDragOver);
        productHeader.addEventListener('drop', handleProductDrop);
    }) 

    // Ajouter les écouteurs d'événements pour les inputs
    const input = page.querySelector('h1 input');
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            updateCategory(pageIndex, event.target.value);
        }
    });
    input.addEventListener('blur', (event) => {
        updateCategory(pageIndex, event.target.value);
    });

    page.querySelectorAll('.french_name, .quantity, .price, .monthly_price').forEach(inputField => {
        inputField.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                updateProduct(inputField);
            }
        });

        inputField.addEventListener('blur', () => {
            updateProduct(inputField);
        });
        inputField.addEventListener('input', () => {
            updateProduct(inputField);
        });
        inputField.addEventListener('change', () => {
            updateProduct(inputField);
        });
    });

    const selector = page.querySelectorAll('.group_title');
    if(selector) {
        selector.forEach(inputField => {
            inputField.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    updateGroup(event.target.value, pageIndex);
                }
            });
            inputField.addEventListener('blur', (event) => {
                updateGroup(event.target.value, pageIndex);
            });
        })
    }

    // Ajouter l'enfant 'page' au contenant
    sheet.appendChild(page);
}

// Méthode qui permet de supprimer une page (catégorie)
function deletePage() {
    const pages = document.querySelectorAll('.page');
    const selectedPage = pages[currentPage];
    const cross = selectedPage.querySelector('#big-cross');
    const deleteButton = selectedPage.querySelector('#delete-page');
    const categoryName = selectedPage.querySelector('h1 input').value.toLowerCase();

    // Trouver le bouton dans le menu latéral qui correspond au nom de la catégorie
    const asideButtons = document.querySelectorAll('#left_aside button');
    let correspondingButton = null;

    // Parcourir tous les boutons pour trouver celui qui correspond à la catégorie
    asideButtons.forEach(button => {
        if (button.childNodes[0].textContent.trim().toLowerCase() === categoryName) {
            correspondingButton = button;
        }
    });

    if (correspondingButton) {
        if (cross.style.display === 'block') {
            // Ajouter la catégorie à la liste des annulations
            orderedCategoriesWithCancellations.push(categoryName);
            cross.style.display = 'none';
            deleteButton.textContent = 'Supprimer cette page';
            correspondingButton.style.backgroundColor = "#f0f0f0";
            correspondingButton.style.opacity = 1;
        } else {
            // Retirer la catégorie de la liste des annulations
            orderedCategoriesWithCancellations = orderedCategoriesWithCancellations.filter(category => category !== categoryName);
            cross.style.display = 'block';
            deleteButton.textContent = 'Récupérer cette page';
            correspondingButton.style.backgroundColor = "#c8c8c8";
            correspondingButton.style.opacity = 0.5;
        }
    } else {
        console.error(`No button found for category: ${categoryName}`);
    }
}

// Méthode qui permet d'ajouter un rabais à la page demandée
function addDiscount(categoryName) {
    const pages = document.querySelectorAll('.page');
    const selectedPage = pages[currentPage];
    const division = selectedPage.querySelector('#discount-space');
    const popup = selectedPage.querySelector('#popup');
    const input1Value = parseFloat(popup.querySelector('#input1').value);
    input2 = popup.querySelector('#input2');
    const input2Value = input2 ? parseFloat(input2.value) : 0;
    const text_input = popup.querySelector('#text_input').value;

    let descriptionText = '';
    let text = '';

    // Mettre à jour les rabais dans le categoriesMap
    categoriesMap[categoryName].direct_discount = input1Value;
    categoriesMap[categoryName].monthly_discount = input2Value;

    // Vérifier et ajouter le rabais sur les prix directs
    if(input1Value && input1Value > 0) {
        descriptionText = text_input;
        text += 'Rabais sur prix directs : ' + input1Value + ' CHF.\n';
    } 

    // Vérifier et ajouter le rabais sur les prix mensuels
    if(input2Value && input2Value > 0) {
        descriptionText = text_input;
        text += 'Rabais sur prix mensuels : ' + input2Value + ' CHF.\n';
    }

    // Ajouter la description si au moins un des rabais est supérieur à 0
    if((input1Value && input1Value > 0) || (input2Value && input2Value > 0)) {
        text += 'Description : ' + descriptionText;
    } else {
        // Réinitialiser les rabais si aucun n'est valide
        categoriesMap[categoryName].direct_discount = 0;
        categoriesMap[categoryName].monthly_discount = 0;
    }

    // Mettre à jour la description dans le categoriesMap
    categoriesMap[categoryName].discount = descriptionText;
    
    // Afficher le texte dans la division appropriée
    division.textContent = text;

    // Masquer le popup
    popup.style.display = 'none';
}

// Méthode qui permet d'afficher le popup pour le rabais
function openPopup() {
    const pages = document.querySelectorAll('.page');
    const selectedPage = pages[currentPage];
    const popup = selectedPage.querySelector('#popup');
    popup.style.display = 'block';
}

// Méthdoe qui remplit le rabais
function fillDiscount(inputNumber) {
    const pages = document.querySelectorAll('.page');
    const selectedPage = pages[currentPage];
    const popup = selectedPage.querySelector('#popup');
    const input = popup.querySelector('#input' + inputNumber);
    
    const categoryName = selectedPage.querySelector('h1 input').value.toLowerCase();
    let total = 0;
    let hasMonthlyPrice = false;

    // Vérifier si un produit dans la catégorie a un prix mensuel
    products.forEach(product => {
        if(product.category === categoryName) {
            if(product.monthly_price && product.monthly_price > 0) {
                hasMonthlyPrice = true;
            }
        }
    });

    // Calculer le total en fonction du type d'entrée et de la catégorie du produit
    products.forEach(product => {
        if(product.category === categoryName) {
            if(hasMonthlyPrice) {
                if(inputNumber == 1) {
                    // Additionner les prix des produits avec ou sans prix mensuel
                    if(product.monthly_price && product.monthly_price > 0) {
                        total += product.price;
                    } else {
                        total += product.price * product.count;
                    }
                } else if (inputNumber == 2) {
                    // Additionner les prix mensuels des produits
                    if(product.monthly_price && product.monthly_price > 0) {
                        total += product.monthly_price * product.count;
                    }
                }
            } else {
                // Additionner les prix des produits sans prix mensuel
                total += product.price * product.count;
            }
        }
    });

    // Remplir le champ d'entrée avec le total calculé
    input.value = total;
}

// Méthode qui permet d'ajouter la valeur du champ de saisie à la description de la catégorie
function addDescription(categoryName) {
    categoriesMap[categoryName].description += document.getElementById('description-input').value + '\n';
    document.getElementById('description-input').value = '';
}

// Méthode qui permet d'ajouter la valeur du champ de saisie aux conditions de la catégorie
function addCondition(categoryName) {
    categoriesMap[categoryName].conditions += document.getElementById('condition-input').value + '\n';
    document.getElementById('condition-input').value = '';
}

// Méthode qui permet d'ajouter la valeur du champ de saisie à l'introduction du pdf'
function addIntro() {
    introduction += document.getElementById('intro-content').value + '\n';
    document.getElementById('intro-content').value = '';
}

// Méthode qui permet d'ajouter la valeur du champ de saisie à la proposition du pdf'
function addProposition() {
    proposition += document.getElementById('proposition-content').value + '\n';
    document.getElementById('proposition-content').value = '';
}

// Méthode qui permet de mettre à jour l'affichage des produits (selon ordre)
function updateProduct(inputField) {
    const productName = inputField.dataset.product;
    const newValue = inputField.value.trim();
    const product = products.find(p => p.name === productName);

    // Mettre à jour les propriétés du produit en fonction du champ d'entrée
    if (inputField.classList.contains('french_name')) {
        product.french_name = newValue;
    } else if (inputField.classList.contains('quantity')) {
        product.count = parseFloat(newValue);
    } else if (inputField.classList.contains('price')) {
        product.price = parseFloat(newValue);
    } else if (inputField.classList.contains('monthly_price')) {
        product.monthly_price = parseFloat(newValue);
    }

    const row = inputField.closest('tr');
    const costCell = row.querySelector('.cost');
    const quantity = row.querySelector('.quantity').value;
    const price = row.querySelector('.price').value;

    // Calculer et mettre à jour le coût total dans la cellule correspondante
    if (costCell) {
        if(product.monthly_price > 0) {
            costCell.textContent = (quantity * row.querySelector('.monthly_price').value).toFixed(2) + 'CHF';
        } else {
            costCell.textContent = (quantity * price).toFixed(2) + ' CHF';
        }
    }

    // Recréer les catégories après mise à jour
    categories = createCategories();
}

// Méthode qui permet de mettre à jour l'affichage des catégories et des boutons (selon ordre)
function updateCategory(pageIndex, newCategory) {
    const oldCategory = Object.keys(categories)[pageIndex];
    const formattedNewCategory = newCategory.trim().toLowerCase();

    // Vérifier si le nom de la catégorie est identique, sinon procéder à la mise à jour
    if (oldCategory === formattedNewCategory) return;

    // Mettre à jour les produits dans le tableau
    products.forEach(product => {
        if (product.category === oldCategory) {
            product.category = formattedNewCategory;
        }
    });

    // Ajouter la nouvelle catégorie à categoriesMap
    if (!categoriesMap[formattedNewCategory]) {
        categoriesMap[formattedNewCategory] = {
            groups: categoriesMap[oldCategory].groups,
            monthly_discount: categoriesMap[oldCategory].monthly_discount,
            direct_discount: categoriesMap[oldCategory].direct_discount,
            discount: categoriesMap[oldCategory].discount,
            description: categoriesMap[oldCategory].description,
            conditions: categoriesMap[oldCategory].conditions
        }
    }

    // Mettre à jour categoriesMap
    // Supprimer l'entrée de l'ancienne catégorie si elle existe
    if (categoriesMap[oldCategory]) {
        delete categoriesMap[oldCategory];
    }

    // Mettre à jour les tableaux orderedCategories et orderedCategoriesWithCancellations
    orderedCategoriesWithCancellations.forEach((category, index) => {
        if (category === oldCategory) {
            orderedCategories[index] = formattedNewCategory;
            orderedCategoriesWithCancellations[index] = formattedNewCategory;
        }
    });

    // Recréer les catégories
    categories = createCategories();

    // Réinitialiser les pages et le menu latéral
    createPages();
    createAside();

    // Afficher la page mise à jour
    showPage(pageIndex);
}

// Méthode qui permet de mettre à jour l'affichage des groupes (selon ordre)
function updateGroup(newGroup, pageIndex) {
    const categoryName = Object.keys(categories)[pageIndex];
    const groupTitleInput = event.target; // L'élément d'entrée qui a déclenché l'événement
    const groupHeader = groupTitleInput.closest('.group-header');
    const oldGroup = groupHeader.dataset.group;

    // Vérifier si le nom du groupe n'a pas changé, sinon sortir
    if (oldGroup === newGroup) return;

    // Mettre à jour les produits
    products.forEach(product => {
        if (product.category === categoryName && product.group === oldGroup) {
            product.group = newGroup;
        }
    });

    // Mettre à jour categoriesMap
    if (categoriesMap[categoryName]) {
        const groups = categoriesMap[categoryName].groups;
    
        const oldGroupIndex = groups.indexOf(oldGroup);
        if (oldGroupIndex !== -1) {
            // Remove the old group
            groups.splice(oldGroupIndex, 1);
            
            // Add the new group
            groups.push(newGroup);
        }
    }

    // Mettre à jour categories
    if (categories[categoryName] && categories[categoryName][oldGroup]) {
        categories[categoryName][newGroup] = categories[categoryName][oldGroup];
        delete categories[categoryName][oldGroup];
    }

    // Mettre à jour le dataset du header du groupe
    groupHeader.dataset.group = newGroup;

    // Recréer les pages pour refléter les changements
    createPages();
    showPage(parseInt(pageIndex));
}

// Méthode pour créer (ou recréer) les pages
function createPages() {
    sheet.innerHTML = '';

    // Créer les pages en fonction des catégories
    Object.keys(orderedCategories).forEach(index => {
        // Déterminer si la catégorie doit afficher la croix
        const showCross = !orderedCategoriesWithCancellations.includes(orderedCategories[index]);

        // Non-sense code pour afficher les groupes dans le bon ordre
        let temp = categories[orderedCategories[index]]
        let mOrdered = categoriesMap[orderedCategories[index]].groups.reduce((acc, key) => {
            if (temp[key] !== undefined) {
              acc[key] = temp[key];
            }
            return acc;
          }, {});

        // Créer la page pour cette catégorie
        createPage(orderedCategories[index], mOrdered, index);

        // Mettre à jour la grande croix dans la page
        const pageElement = document.querySelector(`#page${index + 1}`);
        if (pageElement) {
            const bigCross = pageElement.querySelector('#big-cross');
            if (bigCross) {
                bigCross.style.display = showCross ? 'block' : 'none';
            }
        }
    });
}

// Fonction pour afficher la page courante
function showPage(index) {
    const pages = document.querySelectorAll('.page');
    
    // Afficher uniquement la page correspondant à l'index et masquer les autres
    pages.forEach((page, i) => {
        page.classList.toggle('active', i === index);
    });

    // Mettre à jour la variable pour suivre la page courante
    currentPage = parseInt(index);
}

// Fonction pour gérer le début du glissement d'un groupe
function handleDragStart(event) {
    isGroupDragged = true;
    draggedGroup = event.target;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', draggedGroup.dataset.group);

    // Ajouter une classe au groupe glissé pour le retour visuel
    setTimeout(() => draggedGroup.classList.add('dragged'), 0);
}

// Fonction pour gérer le début du glissement d'un produit
function handleProductDragStart(event) {
    isGroupDragged = false;
    draggedProduct = event.target;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', draggedProduct.dataset.product);
    
    // Ajouter une classe au produit glissé pour le retour visuel
    setTimeout(() => draggedProduct.classList.add('dragged'), 0);
}

// Fonction pour gérer le survol au-dessus d'un groupe pendant le glissement d'un élément
function handleDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';

    const target = event.target.closest('.group-header');
    const asideButton = event.target.closest('#left_aside button');


    if (target && (!isGroupDragged || target !== draggedGroup)) {
        // Autoriser le dépôt à l'intérieur d'une page
        const bounding = target.getBoundingClientRect();
        const offset = bounding.y + bounding.height / 2;

        if (event.clientY - offset > 0) {
            target.parentNode.insertBefore(isGroupDragged ? draggedGroup : draggedProduct, target.nextSibling);
        } else {
            target.parentNode.insertBefore(isGroupDragged ? draggedGroup : draggedProduct, target);
        }
    } else if (asideButton) {
        // Autoriser le dépôt sur les boutons du menu latéral
        event.dataTransfer.dropEffect = 'move';
    }
}

// Fonction pour gérer le survol au-dessus d'un produit pendant le glissement d'un élément
function handleProductDragOver(event) {
    event.preventDefault();
    event.dataTransfer.dropEffect = 'move';

    const target = event.target.closest('.product-row');

    if(target && !isGroupDragged && draggedGroup !== target) {
        // Autoriser le dépôt à l'intérieur d'une page
        const bounding = target.getBoundingClientRect();
        const offset = bounding.y + bounding.height / 2;

        if (event.clientY - offset > 0) {
            target.parentNode.insertBefore(draggedProduct, target.nextSibling);
        } else {
            target.parentNode.insertBefore(draggedProduct, target);
        }
    }
}

// Fonction pour gérer le dépôt d'un élément glissé sur un groupe
function handleDrop(event) {
    event.stopPropagation();
    event.preventDefault();

    if(isGroupDragged) {
        const page = draggedGroup.closest('.page');
        const asideButton = event.target.closest('#left_aside button');

        if (asideButton) {
            // Gérer le dépôt sur un bouton du menu latéral
            const targetCategory = asideButton.childNodes[0].textContent.trim().toLowerCase();
            const sourceCategory = page.querySelector('h1 input').value.toLowerCase();
    
            if (targetCategory !== sourceCategory) {
                const groupName = draggedGroup.dataset.group;
                const sourceGroups = categories[sourceCategory];
                const targetGroups = categories[targetCategory];
    
                if (sourceGroups && sourceGroups[groupName]) {
                    // Déplacer le groupe et ses produits de la catégorie source à la catégorie cible
                    if (!targetGroups) {
                        categories[targetCategory] = {};
                    }
                    categories[targetCategory][groupName] = sourceGroups[groupName];
    
                    // Supprimer le groupe de la catégorie source
                    delete sourceGroups[groupName];
                    if (Object.keys(sourceGroups).length === 0) {
                        delete categories[sourceCategory];
                    }
    
                    // Mettre à jour categoriesMap (supprimer les groupes et catégories)
                    if (categoriesMap[sourceCategory]) {
                        const groups = categoriesMap[sourceCategory].groups;
                    
                        // Find the index of the group to remove
                        const groupIndex = groups.indexOf(groupName);
                        if (groupIndex !== -1) {
                            // Remove the group from the array
                            groups.splice(groupIndex, 1);
                    
                            // Check if the array is now empty
                            if (groups.length === 0) {
                                delete categoriesMap[sourceCategory];
                            }
                        }
                    }
    
                    // Créer la catégorie si elle n'existe pas encore
                    if (!categoriesMap[targetCategory]) {
                        categoriesMap[targetCategory] = {
                            groups: new Array(),
                            monthly_discount: 0,
                            direct_discount: 0,
                            discount: 0,
                            description: '',
                            conditions: ''
                        }
                    }            
    
                    // Mettre à jour le tableau des produits
                    products.forEach(product => {
                        if (product.group === groupName && product.category === sourceCategory) {
                            product.category = targetCategory;
                        }
                    });
    
                    // Mettre à jour categoriesMap avec le bon ordre
                    let newOrderedArray = [];
                    products.forEach(product => {
                        if (product.category === targetCategory) {
                            // Add the group only if it's not already in the array
                            if (!newOrderedArray.includes(product.group)) {
                                newOrderedArray.push(product.group);
                            }
                        }
                    });
                    categoriesMap[targetCategory].groups = newOrderedArray;
    
                    categories = createCategories();
    
                    // Recréer les pages et mettre à jour le menu latéral
                    createPages();
                    createAside();
                    showPage(parseInt(currentPage));
                }
            }
        } else {
            // Gérer le dépôt à l'intérieur d'une page
            const newGroupsOrder = Array.from(page.querySelectorAll('.group-header')).map(header => header.dataset.group);

            // Mettre à jour categoriesMap et le tableau des produits en fonction du nouvel ordre des groupes
            const categoryName = page.querySelector('h1 input').value.toLowerCase();
            const oldGroupsOrder = Array.from(page.querySelectorAll('.group-header')).map(header => header.dataset.group);

            // Assurer que oldGroupsOrder correspond à categoriesMap
            if (categoriesMap[categoryName]) {
                oldGroupsOrder.forEach(group => {
                    const groupIndex = categoriesMap[categoryName].groups.indexOf(group);
                    if (groupIndex !== -1) {
                        categoriesMap[categoryName].groups.splice(groupIndex, 1);
                    }
                });
            }

            // Update groups order with newGroupsOrder
            if (!categoriesMap[categoryName]) {
                categoriesMap[categoryName] = { groups: [] }; // Initialize if it doesn't exist
            }
            newGroupsOrder.forEach(groupName => {
                if (!categoriesMap[categoryName].groups.includes(groupName)) {
                    categoriesMap[categoryName].groups.push(groupName);
                }
            });
    
            // Mettre à jour le tableau des produits pour refléter le nouvel ordre des groupes
            const reorderedGroups = {};
            newGroupsOrder.forEach(groupName => {
                reorderedGroups[groupName] = categories[categoryName][groupName];
            });
    
            categories[categoryName] = reorderedGroups;
            createPages();
            showPage(parseInt(currentPage));
        }

        // Supprimer la classe de glissement
        draggedGroup.classList.remove('dragged');
        draggedGroup = null;
        isGroupDragged = false;
    } else {
        if(asideButton) {
            // Cas produit déplacé sur une autre page
            // TODO MOVE PRODUCT TO OTHER PAGE (ASIDE BUTTON)
            //     -> delete product from page
            //     -> delete group if necessary
            //     -> delete page if necessary 
            // !!! If the product is alone in the group, can't we call handleDrop with the group directly ? 
        } else {
            // Cas produit déplacé sur la page
            const targetGroup = event.target.closest('.group-header');
            const sourceName = draggedProduct.dataset.group;

            if(targetGroup && targetGroup.querySelector('.group_title').value !== sourceName) {
                const targetName = targetGroup.querySelector('.group_title').value;

                // Changer le nom du groupe dans les produits
                products.forEach(product => {
                    if (product.name === draggedProduct.dataset.product) {
                        product.group = targetName;
                        categories = createCategories();
                        
                        if (!categories[product.category][sourceName]) {
                            const groupsArray = categoriesMap[product.category].groups;
                            const groupIndex = groupsArray.indexOf(sourceName);
                            if (groupIndex !== -1) {
                                // Remove the sourceName from the array
                                groupsArray.splice(groupIndex, 1);
                            }
                        }
                    }
                });
            }

            draggedProduct.classList.remove('dragged');
            draggedProduct = null;

            createPages();
            showPage(parseInt(currentPage));
        }
    }
}

// Fonction pour gérer le dépôt d'un produit glissé sur un produit
function handleProductDrop(event) {
    event.stopPropagation();
    event.preventDefault();

    if(isGroupDragged) {
        // rien ne se passe
        draggedGroup.classList.remove('dragged');
        draggedGroup = null;
        isGroupDragged = false;
    } else {
        const targetName = event.target.closest('.product-row').dataset.group;
        const sourceName = draggedProduct.dataset.group;

        if(sourceName !== targetName) {
            // Changer le nom du groupe dans les produits
            products.forEach(product => {
                if (product.name === draggedProduct.dataset.product) {
                    product.group = targetName;
                    categories = createCategories();
                    
                    if (!categories[product.category][sourceName]) {
                        const groupsArray = categoriesMap[product.category].groups;
                        const groupIndex = groupsArray.indexOf(sourceName);
                        if (groupIndex !== -1) {
                            // Remove the sourceName from the array
                            groupsArray.splice(groupIndex, 1);
                        }
                    }
                }
            });
        }

        // Dans le groupe cible, réorganiser l'ordre des produits (produits et catégories)
        const page = draggedProduct.closest('.page');
        const newOrder = Array.from(page.querySelectorAll('.product-row')).map(prod => prod.dataset.product);
        const categoryName = page.querySelector('h1 input').value.toLowerCase();

        const reorderedProducts = [];
        newOrder.forEach(prod => {
            const product = products.find(p => p.name === prod);
            reorderedProducts.push(product);
        })
        
        products.forEach(prod => {
            if(prod.category !== categoryName) {
                reorderedProducts.push(prod);
            }
        });
        products = reorderedProducts;

        categories = createCategories();

        draggedProduct.classList.remove('dragged');
        draggedProduct = null;
    }

    createPages();
    showPage(parseInt(currentPage));
}

// Fonction pour ajouter un produit à la page actuelle
function addProduct() {
    const currentCategoryName = document.querySelectorAll('.page')[currentPage].querySelector('h1 input').value.toLowerCase();

    addedProductsCount += 1;
    var ctStr = addedProductsCount.toString();

    const newProduct = {
        "name":"random_item_" + ctStr,
        "french_name":"Nouvel élément " + ctStr,
        "category":currentCategoryName,
        "group":"Nouveau groupe",
        "count":1,
        "price":0,
        "monthly_price":0.00,
        "service_per_product":false,
        "cat_type":""
    };

    console.log(products);
    console.log(categoriesMap);
    console.log(categories);

    products.push(newProduct);

    if (!categoriesMap[currentCategoryName].groups.includes(newProduct.group)) {
        categoriesMap[currentCategoryName].groups.push(newProduct.group);
    }
    
    categories = createCategories();

    createPages();
    showPage(currentPage);
}

// Fonction pour supprimer un produit définitivement
function deleteProduct(prodName) {
    console.log(products);

    const product = products.find(p => p.name == prodName);
    const categoryName = product.category;
    const groupName = product.group;

    // Remove the product from products array
    const index = products.findIndex(p => p.name === prodName);
    if (index !== -1) {
        products.splice(index, 1);
    }

    // Do we need to remove the group or the category as well ?
    const removeGroup = products.filter(p => p.group == groupName).length == 0;
    const removeCategory = products.filter(p => p.category == categoryName).length == 0;

    // If yes, remove group (and category if necessary too)
    if(removeGroup) {
        const groupIndex = categoriesMap[categoryName].groups.findIndex(g => g == groupName);
        categoriesMap[categoryName].groups.splice(groupIndex, 1);
    }
    if(removeCategory) {
        const indexBis = orderedCategoriesWithCancellations.findIndex(c => c == categoryName);
        orderedCategoriesWithCancellations.splice(indexBis, 1);
    }

    categories = createCategories();

    // Recreate the pages and display the new state of the visualisator
    createPages();
    createAside();
    showPage(currentPage);
}

// Fonction pour valider les données et les envoyer au serveur pour générer le PDF
function validate() {
    // Filtrer les catégories annulées pour ne conserver que celles qui sont encore présentes
    orderedCategoriesWithCancellations = orderedCategories.filter(cat =>
        orderedCategoriesWithCancellations.includes(cat)
    );

    // Préparer les données à envoyer
    const data = {
        products: products,
        categories: orderedCategoriesWithCancellations,
        categoriesMap: Object.fromEntries(
            Object.entries(categoriesMap).map(([category, { groups, direct_discount, monthly_discount, discount, description, conditions }]) => [
                category,
                {
                    groups: groups,
                    direct_discount,
                    monthly_discount,
                    discount,
                    description,
                    conditions
                }
            ])
        ),
        contractLength: contractLength,
        contractStart: contractStart,
        offerNumber: offerNumber,
        offerValidity: offerValidity,
        introduction: introduction,
        proposition: proposition
    };

    // Créer un formulaire pour soumettre les données
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../pdf/pdf.php';

    // Ajouter un champ caché contenant les données au format JSON
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'data';
    input.value = JSON.stringify(data);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// Initialiser l'affichage avec la première page
getInfo();
let categories = createCategories();
createPages();
createAside();
showPage(currentPage);