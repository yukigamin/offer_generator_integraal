<?php

session_start();
require('fpdf186/fpdf.php');

// Données générales pour le pdf
$products = [];
$categories = [];
$categoriesMap = [];
$contractLength = 0;
$contractStart = '';
$offerNumber = '';
$offerValidity = '';
$introduction = '';
$proposition = '';

// Récupération des données produit
if (isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    
    if ($data === null) {
        echo "Erreur de décodage JSON";
        exit;
    }
    
    $products = $data['products'];
    $categories = $data['categories'];
    $categoriesMap = $data['categoriesMap'];
    $contractLength = $data['contractLength'];
    $contractStart = $data['contractStart'];
    $offerNumber = $data['offerNumber'];
    $offerValidity = $data['offerValidity'];
    $introduction = $data['introduction'];
    $proposition = $data['proposition'];
}

// Récupération des données client
$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);

// Vérifier que le décodage est réussi
if (isset($data['clientData'])) {
    $_SESSION['clientData'] = $data['clientData'];

    echo "<script type='text/javascript'>alert('Ceci est un pop-up généré par PHP !');</script>";
}

// Prendre les données en variables
$company = $_SESSION['clientData']['company'];
$address = $_SESSION['clientData']['address'];
$postal = $_SESSION['clientData']['postal'];
$fullname = $_SESSION['clientData']['fullname'];
$phone = $_SESSION['clientData']['phone'];
$email = $_SESSION['clientData']['email'];

class PDF extends FPDF {

    function Header()
    {
        // Sauver la position y actuelle
        $currentY = $this->GetY();

        // Ajout du logo Integraal IT
        $this->Image('image/integraal_it_logo.png', 10, 5, 50);

        $this->AddFont('Calibri','','calibri.php');

        // Ajout de la date du jour (optionel)
        /*$this->SetFont('Calibri','',11);
        $date = date('d.m.Y');
        $this->SetXY(-30, 10);
        $this->Cell(0, 5, $date, 0, 0, 'R');*/

        // Ajuster la position Y sous l'en-tête
        $this->SetY(max(30, $currentY)); // 20 est ajustable
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo() . ' sur {nb}', 0, 0, 'C');

        // Bulles de texte en bas à droite
        $this->SetY(-20); 
        $this->SetX($this->w - 40); 
        $this->FooterTextBubble("Integraal IT SA");
        $this->SetY(-15);
        $this->FooterTextBubble("Rue Eugène-Marziano 15,1227 Carouge");
        $this->SetY(-10);
        $this->FooterTextBubble("+41 22 310 50 40");
    }

    function FooterTextBubble($text)
    {
        $this->SetFont('Calibri', '', 8);
        $this->SetFillColor(255, 255, 255); // Fond blanc
        $this->SetTextColor(0); // Texte noir
        $this->SetLineWidth(0.1);

        // Calcul de la largeur de la bulle
        $textWidth = $this->GetStringWidth($text);
        $bubbleWidth = $textWidth + 4; // Padding
        $bubbleHeight = 5;

        $this->SetX($this->w - $bubbleWidth - 5); // Position X à 5 unités de padding
        $this->MultiCell($bubbleWidth, $bubbleHeight, utf8_decode($text), 0, 'R', true);
    }

    // Création d'une méthode pour placer du texte librement
    // Les paramètres sont la position horizontale, verticale, la largeur de la bulle, la hauteur,
    // le texte à afficher, la police, sa taille et le style (B pour gras, I pour italique...), l'alignement horizontal
    function TextBubble($x, $y, $w, $h, $text, $font, $size, $style = '', $align = 'L', $lineHeight = 1)
    {
        // L'alignement doit être L pour left ou R pour right
        $validAlignments = ['L', 'R', 'C'];
        if (!in_array($align, $validAlignments)) {
            $align = 'L';
        }

        $this->SetXY($x, $y);
        $this->AddFont($font, $style, strtolower($font) . $style . '.php');
        $this->SetFont($font, $style, $size);

        // Centrer le texte horizontalement dans la bulle
        if ($align == 'C') {
            // Obtenir la largeur du texte
            $textWidth = $this->GetStringWidth($text);
            // Calculer le décalage pour centrer le texte
            $x = $x + ($w - $textWidth) / 2;
            $this->SetX($x);
            $align = 'L'; // Pour que MultiCell ne change pas l'alignement
        }

        $this->MultiCell($w, $h * $lineHeight, utf8_decode($text), 0, $align, false);
    }

    function AddPage($orientation = '', $size = '', $rotation = 0)
    {
        parent::AddPage($orientation, $size, $rotation);
        $this->SetTopMargin(30);
    }

    function generateProductPage($category, $products, $categoriesMap, $contractLength, $offerNumber) {
        // Regarder s'il y a au moins un produit avec un prix mensuel non-nul
        $hasMonthlyPrice = false;
        foreach ($products as $product) {
            if ($product['category'] === $category && $product['monthly_price'] > 0) {
                $hasMonthlyPrice = true;
                break;
            }
        }

        // Création de la page
        $this->AddPage();
        $this->SetY(30);
        $this->SetX(10);
        $this->SetFont('MicrosoftYaHeiUI', '', 18);
        $this->Cell(140, 10, utf8_decode(strtoupper($category)), 0, $hasMonthlyPrice ? 0 : 1, 'L');
        if($hasMonthlyPrice) {
            $this->SetFont('MicrosoftYaHeiUILight', '', 10);
            $this->Cell(25, 10, utf8_decode('Durée contrat : '), 0, 0, 'L');
            $this->SetFont('MicrosoftYaHeiUIBold', '', 10);
            $this->Cell(25, 10, utf8_decode($contractLength . ' mois'), 0, 1, 'L');
        }
        $this->Ln(2);

        // Définitions des couleurs
        $headerColor = [60, 127, 214]; // Bleu foncé
        $rowColor = [198, 224, 247]; // Bleu très clair
        $textColor = [0, 0, 0]; // Noir
        $borderColor = [255, 255, 255]; // Blanc

        // Définir la couleur des bordures
        $this->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);

        // Définition des en-têtes du tableau
        $this->SetFont('MicrosoftYaHeiUIBold', '', 9);
        $this->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $this->SetTextColor(255, 255, 255); // Texte blanc

        // Colonnes du tableau
        if($hasMonthlyPrice) {
            $this->Cell(95, 10, utf8_decode('Description'), 1, 0, 'L', true);
            $this->Cell(15, 10, utf8_decode('Quantité'), 1, 0, 'C', true);
            $this->Cell(25, 10, utf8_decode('Prix unitaire'), 1, 0, 'C', true);
            $this->Cell(25, 10, utf8_decode('Coût mensuel'), 1, 0, 'C', true);
            $this->Cell(30, 10, utf8_decode('Mise en service'), 1, 1, 'C', true);
        } else {
            $this->Cell(100, 10, utf8_decode('Description'), 1, 0, 'L', true);
            $this->Cell(20, 10, utf8_decode('Quantité'), 1, 0, 'C', true);
            $this->Cell(30, 10, utf8_decode('Prix unitaire'), 1, 0, 'C', true);
            $this->Cell(40, 10, utf8_decode('Coûts uniques'), 1, 1, 'C', true);
        }

        $totalCost = 0;
        $totalMonthlyCost = 0;
        $printedGroups = []; // Pour suivre les groupes déjà saisis
        $currentY = $this->GetY(); // Suivre la position y pour le tableau

        // Affichage des lignes du tableau
        $this->SetFont('MicrosoftYaHeiUILight', '', 9);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

        // Utiliser categoriesMap pour accéder aux groupes de cette catégorie
        if (isset($categoriesMap[$category])) {
            foreach ($categoriesMap[$category]['groups'] as $group => $groupProducts) {

                $hasElements = false;
                foreach ($products as $product) {
                    if($product['group'] === $groupProducts) {
                        $hasElements = true;
                    }
                }
                if ($hasElements) {
                    $groupCost = 0;

                    if ($this->GetY() + 10 > $this->GetPageHeight() - 20) { // 20 is bottom margin
                        $this->AddPage();
                        $this->SetY(30);
                    }

                    if (!isset($printedGroups[$group])) {
                        // Imprimer le nom du groupe
                        $this->SetFont('MicrosoftYaHeiUIBold', '', 9);
                        $this->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]); // Light blue
                        $this->Cell(0, 5, utf8_decode(ucfirst($groupProducts)), 1, 1, 'L', true);
                        $this->SetFont('MicrosoftYaHeiUILight', '', 8);
                        $printedGroups[$group] = true;
                    }

                    // Affichage des produits
                    foreach ($products as $product) {
                        if($product['group'] === $groupProducts && $product['category'] === $category) {

                            if ($this->GetY() + 10 > $this->GetPageHeight() - 20) { // 15 est la marge du bas
                                $this->AddPage();
                                $this->SetY(30);
                            }

                            $quantity = $product['count'];
                            $unitPrice = $product['price'];
                            $monthlyPrice = $product['monthly_price'];
                            $cost = $monthlyPrice > 0 ? $quantity * $monthlyPrice : $quantity * $unitPrice;
                            $totalCost += $monthlyPrice > 0 ? $unitPrice : $cost;
                            $totalMonthlyCost += $monthlyPrice > 0 ? $cost : 0;

                            $groupCost += $cost;

                            // Détermine la hauteur de la cellule en fonction de la description
                            $this->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);
                            $this->SetX(10);
                            $cellHeight = $this->GetY();

                            // Création des cases pour le produit selon prix mensuel
                            if($hasMonthlyPrice) {
                                if($monthlyPrice > 0) {
                                    $this->MultiCell(95, 4, utf8_decode($product['french_name']), 1, 'L', true);
                                    $cellHeight = $this->GetY() - $cellHeight;
                                    $this->SetXY(105, $this->GetY() - $cellHeight);
                                    $this->Cell(15, $cellHeight, utf8_decode($quantity), 1, 0, 'C', true);
                                    $this->Cell(25, $cellHeight, utf8_decode(number_format($monthlyPrice, 2) . ' CHF'), 1, 0, 'R', true);
                                    $this->Cell(25, $cellHeight, utf8_decode(number_format($cost, 2) . ' CHF'), 1, 0, 'R', true);
                                    $this->Cell(30, $cellHeight, utf8_decode(number_format($product['service_per_product'] ? $unitPrice * $quantity : $unitPrice, 2) . ' CHF'), 1, 1, 'R', true);
                                } else {
                                    $this->MultiCell(95, 4, utf8_decode($product['french_name']), 1, 'L', true);
                                    $cellHeight = $this->GetY() - $cellHeight;
                                    $this->SetXY(105, $this->GetY() - $cellHeight);
                                    $this->Cell(15, $cellHeight, utf8_decode($quantity), 1, 0, 'C', true);
                                    $this->Cell(25, $cellHeight, utf8_decode(number_format($unitPrice, 2) . ' CHF'), 1, 0, 'R', true);
                                    $this->Cell(25, $cellHeight, utf8_decode(number_format(0, 2) . ' CHF'), 1, 0, 'R', true);
                                    $this->Cell(30, $cellHeight, utf8_decode(number_format($cost, 2) . ' CHF'), 1, 1, 'R', true);
                                }
                            } else {
                                $this->MultiCell(100, 4, utf8_decode($product['french_name']), 1, 'L', true);
                                $cellHeight = $this->GetY() - $cellHeight;
                                $this->SetXY(110, $this->GetY() - $cellHeight);
                                $this->Cell(20, $cellHeight, utf8_decode($quantity), 1, 0, 'C', true);
                                $this->Cell(30, $cellHeight, utf8_decode(number_format($unitPrice, 2) . ' CHF'), 1, 0, 'R', true);
                                $this->Cell(40, $cellHeight, utf8_decode(number_format($cost, 2) . ' CHF'), 1, 1, 'R', true);
                            }
                        }
                    }

                    // Prix total du groupe
                    $this->SetFont('MicrosoftYaHeiUI', 'U', 9);
                    if (!$hasMonthlyPrice) {
                        $this->Cell(100, 5, '', 1, 0, 'C', true);
                        $this->Cell(20, 5, '', 1, 0, 'C', true);
                        $this->Cell(30, 5, 'Total', 1, 0, 'R', true);
                        $this->Cell(40, 5, utf8_decode(number_format($groupCost, 2) . ' CHF'), 1, 1, 'R', true);
                    }
                }
            }
        }

        $this->SetFont('MicrosoftYaHeiUIBold', '', 10);
        $this->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
        $this->SetTextColor($textColor[0], $textColor[1], $textColor[2]); // Texte noir

        $monthly_discount = $categoriesMap[$category]['monthly_discount'];
        $direct_discount = $categoriesMap[$category]['direct_discount'];
        $discount_description = $categoriesMap[$category]['discount'];

        // Ligne sans le rabais
        if($hasMonthlyPrice) {
            $this->Cell(135, 8, utf8_decode('Total mensuel et mise en service en CHF (hors TVA)'), 1, 0, 'R', true);
            $this->Cell(25, 8, utf8_decode(number_format(round($totalMonthlyCost), 0) . ' CHF'), 1, 0, 'R', true);
            $this->Cell(30, 8, utf8_decode(number_format(round($totalCost), 0) . ' CHF'), 1, 1, 'R', true);

            // Souligner deux fois s'il n'y a pas de rabais
            if($monthly_discount === 0 && $direct_discount === 0) {
                $x = $this->GetX();
                $y = $this->GetY();
                $this->SetXY(146, $y - 2);
                $newX = $this->GetX();
                $newY = $this->GetY();
                $this->SetDrawColor(0, 0, 0);
                $this->Line($newX, $newY, $newX + 23, $newY);
                $this->Line($newX, $newY + 0.7, $newX + 23, $newY + 0.7);
                $this->Line($newX + 25, $newY, $newX + 53, $newY);
                $this->Line($newX + 25, $newY + 0.7, $newX + 53, $newY + 0.7);
                $this->SetXY($x, $y);
            }
        } else {
            $this->Cell(150, 8, utf8_decode('Total en CHF (hors TVA)'), 1, 0, 'R', true);
            $this->Cell(40, 8, utf8_decode(number_format(round($totalCost), 0) . ' CHF'), 1, 1, 'R', true);

            // Souligner deux fois s'il n'y a pas de rabais
            if($monthly_discount === 0 && $direct_discount === 0) {
                $x = $this->GetX();
                $y = $this->GetY();
                $this->SetXY(161, $y - 2);
                $newX = $this->GetX();
                $newY = $this->GetY();
                $this->SetDrawColor(0, 0, 0);
                $this->Line($newX, $newY, $newX + 38, $newY);
                $this->Line($newX, $newY + 0.7, $newX + 38, $newY + 0.7);
                $this->SetXY($x, $y);
            }
        }

        // Ligne pour le rabais, s'il y en a un
        if($monthly_discount > 0 || $direct_discount > 0) {
            if($hasMonthlyPrice) {
                $this->Cell(135, 7, utf8_decode(($monthly_discount > 0 || $direct_discount > 0) ? $discount_description : ''), 1, 0, 'R', true);
                $this->Cell(25, 7, utf8_decode($monthly_discount > 0 ? '- ' . number_format(round($monthly_discount), 0) . ' CHF' : ''), 1, 0, 'R', true);
                $this->Cell(30, 7, utf8_decode($direct_discount > 0 ? '- ' . number_format(round($direct_discount), 0) . ' CHF' : ''), 1, 1, 'R', true);
            } else {
                $this->Cell(150, 7, utf8_decode($direct_discount > 0 ? $discount_description : ''), 1, 0, 'R', true);
                $this->Cell(40, 7, utf8_decode($direct_discount > 0 ? '- ' . number_format(round($direct_discount), 0) . ' CHF' : ''), 1, 1, 'R', true);
            }
    
            $totalCost -= $direct_discount;
            $totalMonthlyCost -= $monthly_discount;
    
            // Ligne finale pour le coût total
            if($hasMonthlyPrice) {
                $this->Cell(135, 8, utf8_decode('Total mensuel et mise en service avec rabais en CHF (hors TVA)'), 1, 0, 'R', true);
                $this->SetFont('MicrosoftYaHeiUIBold', 'U', 10);
                $this->Cell(25, 8, utf8_decode(number_format(round($totalMonthlyCost), 0) . ' CHF'), 1, 0, 'R', true);
                $this->Cell(30, 8, utf8_decode(number_format(round($totalCost), 0) . ' CHF'), 1, 1, 'R', true);
            } else {
                $this->Cell(150, 8, utf8_decode('Total avec rabais (hors TVA)'), 1, 0, 'R', true);
                $this->Cell(40, 8, utf8_decode(number_format(round($totalCost), 0) . ' CHF'), 1, 1, 'R', true);

                // Souligner deux fois le prix total
                $x = $this->GetX();
                $y = $this->GetY();
                $this->SetXY(161, $y - 2);
                $newX = $this->GetX();
                $newY = $this->GetY();
                $this->SetDrawColor(0, 0, 0);
                $this->Line($newX, $newY, $newX + 38, $newY);
                $this->Line($newX, $newY + 0.7, $newX + 38, $newY + 0.7);
                $this->SetXY($x, $y);
            }
        }

        $this->Ln(12);
        $this->SetFont('MicrosoftYaHeiUI', '', 18);
        $this->Cell(125, 10, utf8_decode('DESCRIPTION SERVICES'), 0, 0, 'L');

        $this->SetFont('MicrosoftYaHeiUI', '', 10);
        $this->Cell(35, 10, utf8_decode("NUMÉRO D'OFFRE"), 0, 0, 'L');
        $this->SetFont('MicrosoftYaHeiUILight', '', 10);
        $this->Cell(30, 10, utf8_decode($offerNumber), 0, 1, 'L');

        $this->Ln(2);
        $this->SetFont('MicrosoftYaHeiUILight', '', 10);
        $this->Cell(0, 5, utf8_decode('Sont inclus les services et conditions suivantes'), 0, 1, 'L');
        $this->Ln(3);

        // Positionnement du fond coloré pour le texte
        $startY = $this->GetY(); // Récupère la position Y après le titre
        $this->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]); // Couleur du fond pour la MultiCell

        // Texte à insérer
        $text = '';
        $save_ext = false;
        foreach($products as $product) {
            if($product['category'] === $category && $product['cat_type'] === 'sauv_ext') {
                $save_ext = true;
            }
        }

        if($save_ext) {
            $text = $text . "Sauvegarde
            - Licences, stocakge et supervision
            - Rétention 7D/5W/12M/5Y (selon espace total)";
        }

        $text = $text . $categoriesMap[$category]['description'];

        $this->SetFont('MicrosoftYaHeiUILight', '', 8);

        // Calculer la hauteur nécessaire pour le texte
        $lineHeight = 4; // Hauteur de ligne
        $numLines = count(explode("\n", $text)); // Nombre de lignes
        $rectangleWidth = 190; // Largeur du fond coloré
        $rectangleHeight = $lineHeight * $numLines + 10; // Hauteur du fond coloré (ajuster selon les marges)

        // Dessiner la MultiCell avec la couleur de fond
        $this->SetXY(10, $startY); // Positionnement du début du texte
        $this->MultiCell($rectangleWidth, $lineHeight, utf8_decode($text), 0, 'L', true);

        // Insertion des conditions 
        $this->Ln(3);
        $this->SetFont('MicrosoftYaHeiUILight', '', 8);
        $this->Cell(0, 5, utf8_decode('CONDITIONS'), 0, 1, 'L');
        $this->Ln(0);

        // Positionnement du fond coloré pour le texte
        $startY = $this->GetY(); // Récupère la position Y après le titre
        $this->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]); // Couleur du fond pour la MultiCell

        $info = false;
        $reseau = false;
        $office = false;

        foreach($products as $product) {
            if($product['category'] === $category) {
                if($product['cat_type'] === 'infogerance' || $product['cat_type'] === 'info_server') {
                    $info = true;
                } else if($product['cat_type'] === 'reseau') {
                    $reseau = true;
                } else if($product['cat_type'] === 'off_365') {
                    $office = true;
                }
            }
        }

        // Texte générique selon produits
        $text = '';
        if($info) {
            $text = $text . "90% des interventions se font à distance, toutes les interventions à distance sont en forfait, en cas d'intervention sur site, les déplacements hors forfait sont facturables en régie. 
L'horaire de travail normal est compris entre 06h00 et 20h00. Les heures de travail en dehors de cette période sont majorées de :
        +25% pour les heures effectuées du lundi au vendredi entre 20h00 et 22h00, ainsi que le samedi entre 06h00 et 22h00, 
        +50% pour les heures effectuées de 22h00 à 06h00 les jours ouvrables et le samedi, 
        +100% pour toutes les heures effectuées le dimanche ou les jours fériés.";
        }
        if($reseau) {
            $text = $text . "Les prix de nos produits sont indiqués en CHF hors taxes, hors participation aux frais de traitement et d'expédition. Toutes les commandes quelle que soit leur origine sont payables en Francs Suisses. Integraal IT SA se réserve le droit de modifier ses prix à tout moment mais les produits seront facturés sur la base des tarifs en vigueur au moment de l'enregistrement des commandes sous réserve de disponibilité (sauf erreur ou omission). Les marchandises restent la propriété d'Integraal IT SA jusqu'au paiement intégral. Sauf accord contraire, 100% du paiement à la commande pour le matériel et logiciel se fait lors de la commande.";
        }
        if($office) {
            $text = $text . "Les licences Microsoft sont des engagements annuelles. La réduction des licences doit être communiquée 31 jours avant l'échéance annuel. Les licences Microsoft ne sont pas liées à un compte";
        }

        $text = $text . $categoriesMap[$category]['conditions'];

        // Texte à insérer
        $this->SetFont('MicrosoftYaHeiUILight', '', 8);

        // Calculer la hauteur nécessaire pour le texte
        $lineHeight = 4; // Hauteur de ligne
        $numLines = count(explode("\n", $text)); // Nombre de lignes
        $rectangleWidth = 190; // Largeur du fond coloré
        $rectangleHeight = $lineHeight * $numLines + 10; // Hauteur du fond coloré (ajuster selon les marges)

        // Dessiner la MultiCell avec la couleur de fond
        $this->SetXY(10, $startY); // Positionnement du début du texte
        $this->MultiCell($rectangleWidth, $lineHeight, utf8_decode($text), 0, 'L', true);
    }

    // Méthode de création de table SLA
    function CreateSlaTable($contractLength, $products, $contractStart)
        {
            // Police pour le contenu de la table
            $this->SetFont('MicrosoftYaHeiUIBold', '', 7);

            // Titres des colonnes
            $header = array(
                'Type de service', 
                'Début contrat', 
                'Durée contrat', 
                'Fin de contrat', 
                'Criticité', 
                'Temps de réponse', 
                'Disponibilité support', 
                'Support garanti', 
                'Responsabilités'
            );

            // Regarder s'il y a des produits qui nécessitent SLA
            $info_antivirus = false;
            $info_server = false;
            $sauv_ext = false;
            $hosting = false;

            foreach($products as $product) {
                if($product['cat_type'] === 'info_server') {
                    $info_server = true;
                } else if ($product['cat_type'] === 'info_antivirus') {
                    $info_antivirus = true;
                } else if ($product['cat_type'] === 'hebergement') {
                    $hosting = true;
                } else if ($product['cat_type'] === 'sauv_ext') {
                    $sauv_ext = true;
                }
            }

            // Lignes de données
            $data = array();

            if ($info_antivirus) {
                $data[] = array('Infogérance Antivirus', $contractStart, $contractLength, $this->getEndDate($contractStart, $contractLength), 'P1', '4 hrs NBD', '10x5', 'N1, N2, N3', 'ÉDITEUR/INTEGRAAL IT');
            }

            if ($info_server) {
                $data[] = array('Infogérance serveur, équipement et bureautique', $contractStart, $contractLength, $this->getEndDate($contractStart, $contractLength), 'P2', '8 hrs NBD', '10x5', 'N1, N2, N3', 'INTEGRAAL IT');
            }

            if ($hosting) {
                $data[] = array('Hébergement', $contractStart, $contractLength, $this->getEndDate($contractStart, $contractLength), 'P1', '4 hrs NBD', '10x5', 'N1, N2, N3', 'INTEGRAAL IT');
            }

            if ($sauv_ext) {
                $data[] = array('Sauvegarde externalisée', $contractStart, $contractLength, $this->getEndDate($contractStart, $contractLength), 'P1', '4 hrs NBD', '10x5', 'N1, N2, N3', 'ÉDITEUR/INTEGRAAL IT');
            }

            // Largeurs des colonnes
            $w = array(39, 18, 15, 18, 13, 17, 18, 17, 35);

            // Alignements du texte pour chaque colonne
            $a = array('L', 'R', 'C', 'R', 'C', 'C', 'C', 'C', 'C');

            $rowHeight = 14;

            $this->SetFillColor(173, 216, 230); // Bleu très clair

            // Afficher les lignes
            for ($i = 0; $i < count($header); $i++) {
                $x = $this->GetX(); // Position x actuelle
                $y = $this->GetY(); // Position y actuelle

                // Dessiner le fond de chaque cellule
                $this->Rect($x, $y, $w[$i], $rowHeight, true);

                // Créer une MultiCell avec une hauteur fixe
                $this->MultiCell($w[$i], 5, utf8_decode($header[$i]), 0, 'C', true);

                // Dessiner une bordure autout de la cellule
                $this->Rect($x, $y, $w[$i], $rowHeight, false);

                // Déplacer le positon pour la création de la prochaine cellule
                $this->SetXY($x + $w[$i], $y);
            }
            $this->Ln();
            $this->Ln(5);

            $this->SetFont('MicrosoftYaHeiUILight', '', 7);

            // Imprimer les données
            foreach ($data as $index => $row) {
                $x = $this->GetX();
                $y = $this->GetY();

                // Couleur de fond alternée
                if ($index % 2 == 1) {
                    $this->SetFillColor(240, 240, 240); // Gris très clair
                } else {
                    $this->SetFillColor(255, 255, 255); // Blanc
                }

                $this->MultiCell($w[0], 6, utf8_decode($row[0]), 1, 'L', true);

                $cellHeight = $this->GetY() - $y;

                $this->SetXY($x + $w[0], $y);

                for ($i = 1; $i < count($row); $i++) {
                    $this->Cell($w[$i], $cellHeight, utf8_decode($row[$i]), 1, 0, $a[$i], true);
                }
                $this->Ln();
            }
        }

    // Méthode pour avoir la date de fin de contrat selon la date de débur et la durée
    function getEndDate($contractStart, $contractLength)
    {
        $contractStartFormatted = DateTime::createFromFormat('d/m/Y', $contractStart)->format('Y-m-d');
        
        $endDateTimestamp = strtotime("-1 day", strtotime("+$contractLength months", strtotime($contractStartFormatted)));
        return date("d/m/Y", $endDateTimestamp);
    }

    // Méthode pour créer un rectangle avec du texte
    function AddTextBox($text, $x = null, $y = null, $width = 190)
    {
        // Ajouter une nouvelle page si nécessaire
        if ($y + 30 > $this->GetPageHeight()) {
            $this->AddPage();
            $y = 10;
        }

        // Ajuster la position du rectangle
        if ($x === null) {
            $x = $this->GetX();
        }
        if ($y === null) {
            $y = $this->GetY();
        }

        // Calculer la hauteur du rectangle en fonction du texte
        $height = ceil($this->GetStringWidth($text) / ($width - 6)) * 4 + 6; // Ajouter 6 pixels pour le padding

        // Dessiner le rectangle
        $this->Rect($x, $y, $width, $height, 'F');

        // Écrire le texte à l'intérieur du rectangle
        $this->SetXY($x + 3, $y + 3);
        $this->MultiCell($width - 6, 4, utf8_decode($text), 0, 'L');

        // Déplacer la position actuelle au bas du rectangle
        $this->SetXY($x, $y + $height);
        $this->Ln(3);
    }

    function DoubleLine($width = 85) {
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + $width, $this->GetY());
        $this->Line($this->GetX() + (190 - $width), $this->GetY(), $this->GetX() + 190, $this->GetY());
    }

    function DoubleText($text1 = '', $text2 = '', $width = 85) {
        $this->Cell(190 - $width, 5, utf8_decode($text1), 0, 0, false);
        $this->Cell($width, 5, utf8_decode($text2), 0, 1, false);
    }
}

$pdf = new PDF();

// PAGE DE COUVERTURE

$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Offre
$pdf->TextBubble(50,25,100,10, 'NUMÉRO D\'OFFRE', 'MicrosoftYaHeiUIBold', 10, '', 'R');
$pdf->TextBubble(50,33,100,10, 'VALIDITÉ OFFRE', 'MicrosoftYaHeiUIBold', 10, '', 'R');

$pdf->TextBubble(100,25,100,10, $offerNumber, 'MicrosoftYaHeiUILight', 9, '', 'R');
$pdf->TextBubble(100,33,100,10, $offerValidity, 'MicrosoftYaHeiUILight', 9, '', 'R');

// Titre
$pdf->TextBubble(0,50,208,10, 'CONTRAT DE SERVICES', 'MicrosoftYaHeiUI', 18, '', 'C');
$pdf->TextBubble(0,57,208,10, 'Proposition', 'MicrosoftYaHeiUI', 14, '', 'C');
$pdf->TextBubble(0,70,208,10, 'Entre', 'MicrosoftYaHeiUILight', 10, '', 'C');

// Informations Integraal
$pdf->TextBubble(0,80,208,10, 'INTEGRAAL IT SA', 'MicrosoftYaHeiUIBold', 11, '', 'C');
$pdf->TextBubble(0,85,208,10, 'Rue Eugène-Marziano 15', 'MicrosoftYaHeiUILight', 10, '', 'C');
$pdf->TextBubble(0,90,208,10, '1227 Les Acacias / GE', 'MicrosoftYaHeiUILight', 10, '', 'C');
$pdf->TextBubble(0,95,218,10, '(Désigné ci-après par le PRESTATAIRE)', 'MicrosoftYaHeiUILight', 10, '', 'C');

$pdf->TextBubble(0,120,208,7, 'et', 'MicrosoftYaHeiUILight', 10, '', 'C');

// Informations Client
$pdf->TextBubble(0,140,208,10, $company, 'MicrosoftYaHeiUIBold', 11, '', 'C');
$pdf->TextBubble(0,145,208,10, $address, 'MicrosoftYaHeiUILight', 10, '', 'C');
$pdf->TextBubble(0,150,208,10, $postal, 'MicrosoftYaHeiUILight', 10, '', 'C');
$pdf->TextBubble(0,155,218,10, '(Désigné ci-après par le CLIENT)', 'MicrosoftYaHeiUILight', 10, '', 'C');

// Contact client
$pdf->TextBubble(0,165,100,7, 'CONTACT CLIENT', 'MicrosoftYaHeiUIBold', 9, '', 'R');
$pdf->TextBubble(116,165,90,7, $fullname, 'MicrosoftYaHeiUI', 8, '', 'L');

$pdf->TextBubble(0,169,100,7, 'NUMÉRO DE TÉLÉPHONE', 'MicrosoftYaHeiUIBold', 9, '', 'R');
$pdf->TextBubble(116,169,90,7, $phone, 'MicrosoftYaHeiUILight', 8, '', 'L');

$pdf->TextBubble(0,173,100,7, 'ADRESSE E-MAIL', 'MicrosoftYaHeiUIBold', 9, '', 'R');
$pdf->TextBubble(116,173,90,7, $email, 'MicrosoftYaHeiUILight', 8, '', 'L');

// Logo
$pdf->Image('image/integraal_it_logo.png', 40, 190, 130);

// FIN DE LA PAGE DE COUVERTURE
// PAGE 1 : RÉSUMÉ

$pdf->AddPage();

$pdf->TextBubble(80,30,100,10, 'NUMÉRO D\'OFFRE', 'MicrosoftYaHeiUIBold', 9, '', 'R');
$pdf->TextBubble(100,30,100,10, $offerNumber, 'MicrosoftYaHeiUILight', 8, '', 'R');

$pdf->SetX(0);
$pdf->SetY(30);
$pdf->SetFont('MicrosoftYaHeiUI', '', 16);
$pdf->Cell(0, 10, utf8_decode(strtoupper('RÉSUMÉ POUR LA DIRECTION')), 0, 1, 'L');
$pdf->Ln(2);

// Définitions des couleurs
$headerColor = [60, 127, 214]; // Bleu foncé
$rowColor = [198, 224, 247]; // Bleu très clair
$textColor = [0, 0, 0]; // Noir
$borderColor = [255, 255, 255]; // Blanc

if($introduction != '') {
    // Introduction et proposition Integraal IT
    $pdf->Ln(6);
    $pdf->SetFont('MicrosoftYaHeiUILight', '', 9);
    $pdf->Cell(0, 5, utf8_decode('Introduction'), 0, 1, 'L');

    // Positionnement du fond coloré pour le texte
    $startY = $pdf->GetY(); // Récupère la position Y après le titre
    $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]); // Couleur du fond pour la MultiCell

    // Calculer la hauteur nécessaire pour le texte
    $lineHeight = 4; // Hauteur de ligne
    $numLines = count(explode("\n", $introduction)); // Nombre de lignes
    $rectangleWidth = 190; // Largeur du fond coloré
    $rectangleHeight = $lineHeight * $numLines + 10; // Hauteur du fond coloré (ajuster selon les marges)

    // Dessiner la MultiCell avec la couleur de fond
    $pdf->SetXY(10, $startY); // Positionnement du début du texte
    $pdf->MultiCell($rectangleWidth, $lineHeight, utf8_decode($introduction), 0, 'L', true);
}

if($proposition != '') {
    $pdf->Ln(2);
    $pdf->SetFont('MicrosoftYaHeiUILight', '', 9);
    $pdf->Cell(0, 5, utf8_decode('Proposition Integraal IT'), 0, 1, 'L');

    // Positionnement du fond coloré pour le texte
    $startY = $pdf->GetY(); // Récupère la position Y après le titre
    $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]); // Couleur du fond pour la MultiCell

    // Calculer la hauteur nécessaire pour le texte
    $lineHeight = 4; // Hauteur de ligne
    $numLines = count(explode("\n", $proposition)); // Nombre de lignes
    $rectangleWidth = 190; // Largeur du fond coloré
    $rectangleHeight = $lineHeight * $numLines + 10; // Hauteur du fond coloré (ajuster selon les marges)

    // Dessiner la MultiCell avec la couleur de fond
    $pdf->SetXY(10, $startY); // Positionnement du début du texte
    $pdf->MultiCell($rectangleWidth, $lineHeight, utf8_decode($proposition), 0, 'L', true);
}

$pdf->Ln(5);
// Création tableau
// Définir la couleur des bordures
$pdf->SetDrawColor($borderColor[0], $borderColor[1], $borderColor[2]);

$pdf->SetFont('MicrosoftYaHeiUI', '', 10);
$pdf->Cell(100, 7, utf8_decode('RÉSUMÉ DES COÛTS (HORS TAXES)'), 0, 1, 'L', false);

// Définition des en-têtes du tableau
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 10);
$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->Cell(110, 10, utf8_decode('Description'), 1, 0, 'L', true);
$pdf->Cell(40, 10, utf8_decode('Mensualités (OPEX)'), 1, 0, 'C', true);
$pdf->Cell(40, 10, utf8_decode('Frais uniques (CAPEX)'), 1, 1, 'C', true);


$totalCost = 0;
$totalMonthlyCost = 0;

// Affichage des lignes du tableau
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 11);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);

foreach ($categories as $category) {
    $cost = 0;
    $monthlyCost = 0;
    foreach ($products as $key => $product) {
        if ($product['category'] == $category) {
            $productHasMonthlyPrice = $product['monthly_price'] > 0;
            $cost += $productHasMonthlyPrice ? $product['price'] : $product['price'] * $product['count'];
            $monthlyCost += $product['count'] * $product['monthly_price'];
        }
    }

    // Prendre en compte les rabais
    $discount = $categoriesMap[$category]['direct_discount'];
    $monthly_discount = $categoriesMap[$category]['monthly_discount'];

    $cost -= $discount;
    $monthlyCost -= $monthly_discount;

    $totalCost += $cost;
    $totalMonthlyCost += $monthlyCost;

    // Détermine la hauteur de la cellule en fonction de la description
    $pdf->SetFont('MicrosoftYaHeiUIBold', '', 10);
    $pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);
    $pdf->SetX(10);
    $cellHeight = $pdf->GetY();
    $pdf->MultiCell(110, 21, utf8_decode(strtoupper($category)), 1, 'L', true);
    $cellHeight = $pdf->GetY() - $cellHeight;

    $pdf->SetFont('MicrosoftYaHeiUILight', '', 11);
    $pdf->SetXY(120, $pdf->GetY() - $cellHeight);
    $pdf->Cell(40, $cellHeight, utf8_decode(number_format(round($monthlyCost, 0)) . ' CHF'), 1, 0, 'R', true);
    $pdf->Cell(40, $cellHeight, utf8_decode(number_format(round($cost), 0) . ' CHF'), 1, 1, 'R', true);
}

// Ligne finale pour le coût total
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 12);
$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetTextColor(255, 255, 255); // Texte blanc
$pdf->Cell(110, 10, utf8_decode('GRAND TOTAL'), 1, 0, 'R', true);
$pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]); // Texte noir
$pdf->Cell(40, 10, utf8_decode(number_format(round($totalMonthlyCost), 0) . ' CHF'), 1, 0, 'R', true);
$pdf->Cell(40, 10, utf8_decode(number_format(round($totalCost), 0) . ' CHF'), 1, 1, 'R', true);

// FIN DE LA PAGE DE RÉSUMÉ

foreach ($categories as &$category) {
    $pdf->generateProductPage($category, $products, $categoriesMap, $contractLength, $offerNumber);
}

// PAGE SLA
$slaNeeded = false;
$slaCategories = ['info_antivirus', 'info_server', 'hebergement', 'sauv_ext'];

foreach($products as $product) {
    if(in_array($product['cat_type'], $slaCategories)) {
        $slaNeeded = true;
    }
}

if($slaNeeded) {
    $pdf->AddPage();

    $pdf->SetFont('MicrosoftYaHeiUI', '', 20);
    $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(190, 20, 'CONTRAT DE GESTION', 1, 1, 'C', true);

    $pdf->SetFont('MicrosoftYaHeiUI', '', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(190, 10, 'Service Level Objectives / Operating Level Objectives', 1, 1, 'C', false);

    $pdf->Ln(7);

    $pdf->CreateSlaTable($contractLength, $products, $contractStart);

    $pdf->Ln(4);
    $pdf->Cell(57, 4, utf8_decode('P1 = Criticité Élevée'), 0, 0, 'L', false);
    $pdf->Cell(70, 4, utf8_decode('RPO = Recovery Point Objective / perte de données maximale admissible'), 0, 1, 'L', false);
    $pdf->Cell(57, 4, utf8_decode('P2 = Criticité Normale'), 0, 0, 'L', false);
    $pdf->Cell(70, 4, utf8_decode('N1/N2/N3 = Degré de support niveau 1 / 2 ou 3'), 0, 1, 'L', false);
    $pdf->Cell(57, 4, utf8_decode('P1 = Criticité Basse'), 0, 0, 'L', false);
    $pdf->Cell(70, 4, utf8_decode('10x5 = Disponibilité de 10 heures sur 5 jours'), 0, 1, 'L', false);
}

// FIN DE LA PAGE SLA
// PAGE LISTE D'INCLUSION

$info_server = false;
$info_antivirus = false;
$sauv_ext = false;
$office = false;
$network = false;

foreach($products as $product) {
    if($product['cat_type'] === 'info_server') {
        $info_server = true;
    } else if ($product['cat_type'] === 'info_antivirus') {
        $info_antivirus = true;
    } else if ($product['cat_type'] === 'off_365') {
        $office = true;
    } else if ($product['cat_type'] === 'sauv_ext') {
        $sauv_ext = true;
    } else if ($product['cat_type'] === 'reseau') {
        $network = true;
    }
}

$firstNote = false;
$secondNote = false;
$thirdNote = false;

// Beaucoup d'informations concernant la liste d'inclusion est écrite ci-dessous en brut, il serait possible d'optimiser le code
// avec un lien sur une base de données, mais pas frocément nécessaire s'il n'y a pas de changements réguliers dans la création des pdfs.
if($info_server || $info_antivirus || $office || $sauv_ext || $network) {
    $pdf->AddPage();

    $pdf->SetFont('MicrosoftYaHeiUI', '', 20);
    $pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(190, 20, "LISTE D'INCLUSION", 1, 1, 'C', true);

    $pdf->SetFont('MicrosoftYaHeiUI', '', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(190, 10, utf8_decode('Actions couvertes par les services proposés'), 1, 1, 'C', false);

    $pdf->Ln(7);

    $pdf->SetDrawColor(255, 255, 255);
    $pdf->SetFont('MicrosoftYaHeiUI', '', 10);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(130, 5, 'DESCRIPTION', 1, 0, 'L', true);
    $pdf->Cell(30, 5, 'FREQUENCE', 1, 0, 'L', true);
    $pdf->Cell(30, 5, 'INCLUS', 1, 1, 'L', true);

    if($info_server) {
        $pdf->SetFillColor(227, 139, 98);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('MicrosoftYaHeiUI', '', 10);

        $pdf->Cell(190, 5, utf8_decode('INFOGERANCE SERVEUR'), 1, 1, 'C', true);

        $data1 = array( 
            array('Gestion des correctifs (Service Packs et mises à jour)', 'Au besoin', 'OUI'),
            array('Nettoyage du disque dur (suppression des fichiers temporaires et inutiles)', 'Au besoin', 'OUI'),
            array('Ajout / Modification / Suppression partage fichier', 'Au besoin', 'OUI'),
            array('Ajout / Modification / Suppression droits de sécurité fichier', 'Au besoin', 'OUI'),
            array('Redémarrage des serveurs', 'Au besoin', 'OUI'),
            array('Déploiement des meilleures pratiques en matière de politiques de sécurité', 'Au besoin', 'OUI'),
            array('Gestion des cas de garanties ²', 'Au besoin', 'OUI'),
        );

        $data2 = array(
            array('Surveillance du fonctionnement et état de santé des serveurs', '5x7 ¹', 'OUI'),
            array('Surveillance des évènements de sécurités', '5x7 ¹', 'OUI'),
            array('Surveillance des services critiques et correction', '5x7 ¹', 'OUI'),
        );

        $size1 = 4 * count($data1);
        $size2 = 4 * count($data2);
        $size = $size1 + $size2;

        $pdf->SetFont('MicrosoftYaHeiUILight', '', 8);
        $pdf->SetFillColor(195, 211, 235);
        $pdf->SetTextColor(0,0,0);

        foreach($data1 as $row1) {
            $pdf->Cell(130, 4, utf8_decode($row1[0]), 1, 1, 'L', true);
        }

        $pdf->SetXY(140, $pdf->GetY() - $size1);
        $pdf->Cell(30, $size1, utf8_decode($row1[1]), 1, 1, 'C', true);

        foreach($data2 as $row2) {
            $pdf->Cell(130, 4, utf8_decode($row2[0]), 1, 1, 'L', true);
        }

        $pdf->SetXY(140, $pdf->GetY() - $size2);
        $pdf->Cell(30, $size2, utf8_decode($row2[1]), 1, 1, 'C', true);
        $pdf->SetXY(170, $pdf->GetY() - $size);
        $pdf->Cell(30, $size, $row1[2], 1, 1, 'C', true);

        $firstNote = true;
        $secondNote = true;
    }
    if($info_antivirus) {
        $pdf->SetFillColor(227, 139, 98);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('MicrosoftYaHeiUI', '', 10);

        $pdf->Cell(190, 5, utf8_decode('INFOGERANCE ANTIVIRUS'), 1, 1, 'C', true);

        $data = array( 
            array("Surveillance de l'exécution antivirus et de l'activation de la protection", '5x7 ¹', 'OUI'),
            array("Surveillance des définitions antivirus + mise à jour correcte", '5x7 ¹', 'OUI'),
            array("Surveillance des alertes antivirus et qualification de la menace", '5x7 ¹', 'OUI'),
        );

        $size = 4 * count($data);

        $pdf->SetFont('MicrosoftYaHeiUILight', '', 8);
        $pdf->SetFillColor(195, 211, 235);
        $pdf->SetTextColor(0,0,0);

        foreach($data as $row) {
            $pdf->Cell(130, 4, utf8_decode($row[0]), 1, 1, 'L', true);
        }

        $pdf->SetXY(140, $pdf->GetY() - $size);
        $pdf->Cell(30, $size, utf8_decode($row[1]), 1, 0, 'C', true);
        $pdf->Cell(30, $size, $row[2], 1, 1, 'C', true);

        $firstNote = true;
    }
    if($sauv_ext) {
        $pdf->SetFillColor(227, 139, 98);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('MicrosoftYaHeiUI', '', 10);

        $pdf->Cell(190, 5, utf8_decode('SAUVEGARDE AS A SERVICE'), 1, 1, 'C', true);

        $pdf->SetFont('MicrosoftYaHeiUILight', '', 8);
        $pdf->SetFillColor(195, 211, 235);
        $pdf->SetTextColor(0,0,0);

        $pdf->Cell(130, 4, utf8_decode('Surveillance des sauvegardes serveurs'), 1, 1, 'L', true);
        $pdf->Cell(130, 4, utf8_decode('Surveillance des sauvegardes 365'), 1, 1, 'L', true);
        $pdf->SetXY(140, $pdf->GetY() - 8);
        $pdf->Cell(30, 8, utf8_decode('5x7 ¹'), 1, 1, 'C', true);
        $pdf->Cell(130, 4, utf8_decode('Dépannage problème de sauvegarde'), 1, 0, 'L', true);
        $pdf->Cell(30, 4, 'Au besoin', 1, 1, 'C', true);
        $pdf->Cell(130, 4, utf8_decode('Test de restoration manuel granulaire'), 1, 0, 'L', true);
        $pdf->Cell(30, 4, 'Mensuel', 1, 1, 'C', true);
        $pdf->SetXY(170, $pdf->GetY() - 16);
        $pdf->Cell(30, 16, 'OUI', 1, 1, 'C', true);

        $firstNote = true;
    }
    if($office) {
        $pdf->SetFillColor(227, 139, 98);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('MicrosoftYaHeiUI', '', 10);

        $pdf->Cell(190, 5, utf8_decode('OFFICE 365'), 1, 1, 'C', true);

        $data = array( 
            array('Ajout / Modification / Suppression compte utilisateur ', 'Au besoin', 'OUI'),
            array('Ajout / Modification / Suppression groupe de sécurité ', 'Au besoin', 'OUI'),
            array('Ajout / Modification / Suppression boite partagée ', 'Au besoin', 'OUI'),
            array('Ajout / Modification / Suppression groupe de distribution', 'Au besoin', 'OUI'),
            array('Réinitialisation des mots des passe', 'Au besoin', 'OUI'),
            array('Archivage ancienne boites aux lettres', 'Au besoin', 'OUI'),
        );

        $pdf->SetFont('MicrosoftYaHeiUILight', '', 8);
        $pdf->SetFillColor(195, 211, 235);
        $pdf->SetTextColor(0,0,0);

        foreach($data as $index => $row) {
            $pdf->Cell(130, 4, utf8_decode($row[0]), 1, 1, 'L', true);
        }

        $size = 4 * count($data);

        $pdf->SetXY(140, $pdf->GetY() - $size);
        $pdf->Cell(30, $size, utf8_decode($row[1]), 1, 0, 'C', true);
        $pdf->Cell(30, $size, $row[2], 1, 1, 'C', true);
    }
    if($network) {
        $pdf->SetFillColor(227, 139, 98);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('MicrosoftYaHeiUI', '', 10);

        $pdf->Cell(190, 5, utf8_decode('RESEAU'), 1, 1, 'C', true);

        $pdf->SetFont('MicrosoftYaHeiUILight', '', 8);
        $pdf->SetFillColor(195, 211, 235);
        $pdf->SetTextColor(0,0,0);

        $pdf->Cell(130, 4, utf8_decode('Ajout / Modification / Supression règle de routage'), 1, 1, 'L', true);
        $pdf->Cell(130, 4, utf8_decode('Ajout / Modification / Suppresion utilisateur'), 1, 1, 'L', true);
        $pdf->Cell(130, 4, utf8_decode('Ajout / Modification / Suppresion message vocal garanties ³'), 1, 1, 'L', true);
        $pdf->Cell(130, 4, utf8_decode('Application licences'), 1, 1, 'L', true);
        $pdf->SetXY(140, $pdf->GetY() - 16);
        $pdf->Cell(30, 16, utf8_decode('5x7 ¹'), 1, 1, 'C', true);
        $pdf->SetXY(170, $pdf->GetY() - 16);
        $pdf->Cell(30, 16, 'OUI', 1, 1, 'C', true);

        $firstNote = true;
        $thirdNote = true;
    }
}

$pdf->Ln(5);
if ($firstNote) {
    $pdf->Cell(100, 4, utf8_decode('¹ Remontées des informations et alertes 24x7x365 traitement des alertes 8-18h lundi-vendredi'), 0, 1, 'L', false);
    $pdf->Ln(2);
}
if ($secondNote) {
    $pdf->Cell(100, 4, utf8_decode('² Pour le matériel sous garantie founi Integraal IT'), 0, 1, 'L', false);
    $pdf->Ln(2);
}
if ($thirdNote) {
    $pdf->Cell(100, 4, utf8_decode('³ Fichier .wave transmis par le client'), 0, 1, 'L', false);
    $pdf->Ln(2);
}

// FIN DE LA PAGE LISTE D'INCLUSION
// PAGE SIGNATURE

$pdf->AddPage();

// Titre
$pdf->SetXY(10,30);
$pdf->SetFillColor($headerColor[0], $headerColor[1], $headerColor[2]);
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 18);
$pdf->Cell(190, 15, 'CONTRAT DE VENTE', 1, 1, 'C', true);
$pdf->Ln(10);

// Conditions spécifiques, numéro d'offre
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 9);
$pdf->Cell(100, 7, utf8_decode('CONDITIONS SPÉCIFIQUES'), 0, 0, 'L', false);
$pdf->Cell(39, 7, utf8_decode("NUMÉRO D'OFFRE"), 0, 0, 'L', false);
$pdf->SetFont('MicrosoftYaHeiUI', '', 9);
$pdf->Cell(40, 7, utf8_decode($offerNumber), 0, 1, 'L', false);

$pdf->Ln(4);
$pdf->SetFont('MicrosoftYaHeiUILight', '', 7);
$pdf->Cell(50, 5, utf8_decode('Sont inclus conditions suivantes'), 0, 1, 'L', false);
$pdf->Ln(2);

$pdf->SetFillColor($rowColor[0], $rowColor[1], $rowColor[2]);
$text = "Le service d'assistance Integraal IT SA est disponible de 8h00 à 18h00 du Lundi au Vendredi pendant les jours ouvrables dans le canton de Genève.";
$pdf->AddTextBox($text);
$text = "L'horaire de travail normal est compris entre 06h00 et 20h00. Les heures de travail en dehors de cette période sont majorées de ; +25% pour les heures effectuées du lundi au vendredi entre 20h00 et 22h00, ainsi que le samedi entre 06h00 et 22h00, +50% pour les heures effectuées de 22h00 à 06h00 les jours ouvrables et le samedi, +100% pour toutes les heures effectuées le dimanche ou les jours fériés.";
$pdf->AddTextBox($text);
$text = "Les prix de nos produits sont indiqués en CHF hors taxes, hors participation aux frais de traitement et d'expédition. Toutes les commandes quelle que soit leur origine sont payables en Francs Suisses. Integraal IT se réserve le droit de modifier ses prix à tout moment mais les produits seront facturés sur la base des tarifs en vigueur au moment de l'enregistrement des commandes sous réserve de disponibilité (sauf erreur ou omission). Les marchandises restent la propriété d'Integraal IT jusqu'au paiement intégral.";
$pdf->AddTextBox($text);

$pdf->Ln(4);

// Conditions générales
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 9);
$pdf->Cell(100, 9, utf8_decode('CONDITIONS GÉNÉRALES'), 0, 1, 'L', false);

$pdf->SetFont('MicrosoftYaHeiUILight', '', 7);
$pdf->SetFillColor(255, 255, 255);
$text = "La résiliation peut être faite 30 jours avant la date de fin du contrat / Tous les prix s'entendent sans TVA en CHF / Sauf contre-indication stipulée dans la commande, le paiement se fait à 20 jours nets à partir de la date de la facture / Le prix de la location des licences et de l'hébergement peut évoluer selon les modifications de prix des éditeurs ou hébergeurs. Integraal IT SA peut faire des évolutions / Les conditions générales de Integraal IT SA font partie intégrante de cette proposition. / SLA standard fournis est un service en 10 par 5 du lundi au vendredi de 8h00 à 18h pendant les jours ouvrés avec un taux de disponibilité de 99.5% pour tous les services avec un RTO et un RPO de 24h.";
$pdf->MultiCell(190, 4, utf8_decode($text), 0, 'L', false);

$pdf->SetFont('MicrosoftYaHeiUILight', '', 6);
$pdf->Ln(5);
$text = "Par la signature de ce document le client reconnait que les conditions générales (CG), les SLA et la matrice RACI font partie intégrante du contrat : ";
$pdf->Cell(140, 4, utf8_decode($text), 0, 0, 'L', false);
// Lien à la page avec les conditions générales complètes
$url = 'https://portal.integraal-it.ch/CG.pdf';
$pdf->Cell(50, 4, utf8_decode('https://portal.integraal-it.ch/CG.pdf'), 0, 1, 'L', false, $url);

$pdf->Ln(5);
$pdf->SetFont('MicrosoftYaHeiUIBold', '', 7);
$text = "Si les conditions présentées dans cette offre vous conviennent, vous pouvez signer cette proposition et la transmettre à votre contact commercial.";
$pdf->Cell(190, 5, utf8_decode($text), 0, 1, 'L', false);

$pdf->Ln(4);
$pdf->Cell(50, 5, utf8_decode('ACCEPTÉ ET VALIDÉ'), 0, 1, false);
$pdf->Line($pdf->GetX(), $pdf->GetY(), $pdf->GetX() + 190, $pdf->GetY());

// Données Integraal IT et client
$pdf->Ln(4);
$pdf->DoubleText('Integraal IT SA', $company);

$pdf->DoubleLine();

$pdf->SetFont('MicrosoftYaHeiUILight', '', 7);
$pdf->DoubleText('Date', 'Date');

$pdf->Ln(5);
$pdf->DoubleLine();

$pdf->DoubleText('Nom et prénom en lettres majuscules', 'Nom et prénom en lettres majuscules');

$pdf->Ln(4);

$pdf->SetFont('MicrosoftYaHeiUIBold', '', 7);
$pdf->DoubleText('Nicolas SCHEUNER');

$pdf->DoubleLine();

$pdf->SetFont('MicrosoftYaHeiUILight', '', 7);
$pdf->DoubleText('Fonction', 'Fonction');

$pdf->Ln(4);

$pdf->SetFont('MicrosoftYaHeiUIBold', '', 7);
$pdf->DoubleText('DIRECTEUR');

$pdf->DoubleLine();

$pdf->SetFont('MicrosoftYaHeiUILight', '', 7);
$pdf->DoubleText('Signature', "Signature et timbre de l'entreprise");

// FIN DE LA PAGE SIGNATURE

$pdf->AliasNbPages();
// Sortie du PDF
$pdf->Output("facture.pdf", "I");

session_abort();