<?php
// ================== cv_pdf.php ==================
// Génère le CV au format PDF avec la bibliothèque FPDF (dossier fpdf/).
require 'fonctions.php';
require 'fpdf/fpdf.php';

$cv = chargerCV($pdo, strtolower(trim($_GET['email'] ?? '')));
if (!$cv) {
    http_response_code(404);
    exit('CV introuvable.');
}
$et = $cv['etudiant'];

// FPDF ne gère pas l'UTF-8 : on convertit les textes en Windows-1252
// (sinon les accents s'affichent mal : "Ã©" au lieu de "é")
function t($texte) {
    $resultat = @iconv('UTF-8', 'windows-1252//TRANSLIT', $texte ?? '');
    return $resultat !== false ? $resultat : mb_convert_encoding($texte ?? '', 'Windows-1252', 'UTF-8');
}

// On crée notre propre classe à partir de FPDF pour ajouter nos méthodes
class CV extends FPDF {

    // Pied de page (appelé automatiquement par FPDF sur chaque page)
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(140);
        $this->Cell(0, 10, t('CV généré le ' . date('d/m/Y') . ' - page ' . $this->PageNo()), 0, 0, 'C');
    }

    // Titre d'une rubrique, suivi d'un trait horizontal
    function Rubrique($titre) {
        if ($this->GetY() > 250) {
            $this->AddPage();
        }
        $this->Ln(4);
        $this->SetFont('Helvetica', 'B', 12);
        $this->SetTextColor(25, 25, 25);
        $this->Cell(0, 7, t(mb_strtoupper($titre)), 0, 1);
        $this->SetDrawColor(170, 170, 170);
        $this->Line($this->lMargin, $this->GetY(), $this->GetPageWidth() - $this->rMargin, $this->GetY());
        $this->Ln(3);
    }

    // Une formation ou un stage : la période à gauche, les détails à droite
    function Entree($periode, $titre, $sousTitre, $description) {
        if ($this->GetY() > 255) {
            $this->AddPage();
        }
        $y = $this->GetY();
        $xDetails = $this->lMargin + 40;

        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(110, 110, 110);
        $this->Cell(40, 5, t($periode));

        $this->SetXY($xDetails, $y);
        $this->SetFont('Helvetica', 'B', 11);
        $this->SetTextColor(25, 25, 25);
        $this->MultiCell(0, 5, t($titre));

        $this->SetX($xDetails);
        $this->SetFont('Helvetica', 'I', 10);
        $this->SetTextColor(80, 80, 80);
        $this->MultiCell(0, 5, t($sousTitre));

        if ($description) {
            $this->SetX($xDetails);
            $this->SetFont('Helvetica', '', 10);
            $this->SetTextColor(45, 45, 45);
            $this->MultiCell(0, 5, t($description));
        }
        $this->Ln(3);
    }

    // Une liste sur deux colonnes (compétences, langues)
    function ListeDeuxColonnes(array $elements) {
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(45, 45, 45);
        $largeur = ($this->GetPageWidth() - $this->lMargin - $this->rMargin) / 2;

        foreach ($elements as $i => $texte) {
            $finDeLigne = ($i % 2 === 1) ? 1 : 0;   // 2e colonne -> on passe à la ligne
            $this->Cell($largeur, 6, chr(149) . ' ' . t($texte), 0, $finDeLigne);   // chr(149) = puce "•"
        }
        if (count($elements) % 2 === 1) {
            $this->Ln();
        }
    }
}

// ---------- Création du document ----------
$pdf = new CV('P', 'mm', 'A4');
$pdf->SetMargins(18, 18, 18);
$pdf->SetAutoPageBreak(true, 20);
$pdf->SetTitle('CV - ' . $et['prenom'] . ' ' . $et['nom'], true);
$pdf->AddPage();

// ---------- En-tête : photo à droite ----------
$basPhoto = 0;
$cheminPhoto = __DIR__ . '/' . $et['photo'];
if ($et['photo'] && is_file($cheminPhoto)) {
    $taille = getimagesize($cheminPhoto);          // [largeur, hauteur] en pixels
    $l = 34;                                       // largeur en mm
    $h = $l * $taille[1] / $taille[0];             // hauteur proportionnelle
    if ($h > 44) {
        $h = 44;
        $l = $h * $taille[0] / $taille[1];
    }
    try {
        $pdf->Image($cheminPhoto, $pdf->GetPageWidth() - 18 - $l, 18, $l, $h);
        $basPhoto = 18 + $h;
    } catch (Exception $ex) {
        // image illisible : le CV est généré sans photo
    }
}

// ---------- En-tête : nom et coordonnées ----------
$pdf->SetXY(18, 20);
$pdf->SetFont('Helvetica', 'B', 22);
$pdf->SetTextColor(20, 20, 20);
$pdf->Cell(130, 10, t($et['prenom'] . ' ' . mb_strtoupper($et['nom'])), 0, 1);
$pdf->Ln(3);

$coordonnees = [
    'Email'     => $et['email'],
    'Téléphone' => $et['telephone'],
    'Adresse'   => $et['adresse'],
];
foreach ($coordonnees as $libelle => $valeur) {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetTextColor(60, 60, 60);
    $pdf->Cell(24, 6, t($libelle . ' :'));
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->MultiCell(106, 6, t($valeur));
}

// On continue sous la photo si elle est plus haute que le texte
$pdf->SetY(max($pdf->GetY(), $basPhoto) + 2);

// ---------- Rubriques ----------
$pdf->Rubrique('Formations');
foreach ($cv['formations'] as $x) {
    $pdf->Entree(periode($x['date_debut'], $x['date_fin']), $x['diplome'], $x['etablissement'], $x['description']);
}

if ($cv['stages']) {
    $pdf->Rubrique('Stages');
    foreach ($cv['stages'] as $x) {
        $lieu = $x['lieu'] ? ' - ' . $x['lieu'] : '';
        $pdf->Entree(periode($x['date_debut'], $x['date_fin']), $x['intitule'], $x['entreprise'] . $lieu, $x['description']);
    }
}

$pdf->Rubrique('Compétences');
$pdf->ListeDeuxColonnes(array_map(function ($x) {
    return $x['libelle'] . ($x['niveau'] ? ' (' . $x['niveau'] . ')' : '');
}, $cv['competences']));

$pdf->Rubrique('Langues');
$pdf->ListeDeuxColonnes(array_map(function ($x) {
    return $x['libelle'] . ' : ' . $x['niveau'];
}, $cv['langues']));

$pdf->Rubrique("Centres d'intérêt");
$pdf->SetFont('Helvetica', '', 10);
$pdf->SetTextColor(45, 45, 45);
$pdf->MultiCell(0, 6, t(implode(', ', array_column($cv['interets'], 'libelle'))));

// ---------- Envoi au navigateur ----------
// 'I' = afficher dans le navigateur, 'D' = télécharger
$nomFichier = 'CV_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $et['prenom'] . '_' . $et['nom']) . '.pdf';
$pdf->Output(isset($_GET['telecharger']) ? 'D' : 'I', $nomFichier);
