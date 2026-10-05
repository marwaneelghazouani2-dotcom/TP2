<?php
session_start();

$erreurs = [];
$message = '';   // AJOUT : message de confirmation après VALIDER

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {

    $email = trim($_POST['email'] ?? '');
    $num_telephone = trim($_POST['tel'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Adresse email invalide.";
    }

    if (!preg_match('/^0[567][0-9]{8}$/', $num_telephone)) {
        $erreurs[] = "Numéro de téléphone invalide (format attendu : 06XXXXXXXX).";
    }

    // AJOUT : pour chaque projet/stage, la date de fin ne doit pas être avant la date de début
    foreach ($_POST['date_debut'] ?? [] as $i => $debut) {
        $fin = $_POST['date_fin'][$i] ?? '';
        if ($debut !== '' && $fin !== '' && $fin < $debut) {
            $erreurs[] = "Projet/stage n°" . ($i + 1) . " : la date de fin est avant la date de début.";
        }
    }

    // AJOUT : ne garder que les modules qui appartiennent à la filière choisie
    // (au cas où quelqu'un modifie le formulaire à la main)
    $modulesParFiliere = require 'modules.php';
    $filiere = $_POST['filiere'] ?? '';
    $_POST['modules'] = array_values(array_intersect(
        $_POST['modules'] ?? [],
        $modulesParFiliere[$filiere] ?? []
    ));

    // CORRECTION : garder le fichier déjà envoyé (sinon il est perdu à chaque nouvel envoi)
    $ancienFichier = $_SESSION['data']['fichier_nom'] ?? '';

    if (!empty($erreurs)) {
        $_SESSION['data'] = $_POST;
        $_SESSION['data']['fichier_nom'] = $ancienFichier;
        $_SESSION['erreurs'] = $erreurs;
        header('Location: formulaire.php');
        exit;
    }

    // Données valides
    $_SESSION['data'] = $_POST;
    $_SESSION['data']['fichier_nom'] = $ancienFichier;

    if (isset($_FILES['fichier']) && $_FILES['fichier']['error'] === UPLOAD_ERR_OK) {
        $nomFichier = basename($_FILES['fichier']['name']);

        // CORRECTION (sécurité) : n'accepter que certaines extensions.
        // Sans ça, quelqu'un peut envoyer un fichier .php et l'exécuter sur le serveur.
        $extension = strtolower(pathinfo($nomFichier, PATHINFO_EXTENSION));
        $autorisees = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'txt'];

        if (in_array($extension, $autorisees)) {
            // CORRECTION : ranger les fichiers dans un dossier uploads/
            if (!is_dir('uploads')) {
                mkdir('uploads');
            }
            move_uploaded_file($_FILES['fichier']['tmp_name'], 'uploads/' . $nomFichier);
            $_SESSION['data']['fichier_nom'] = $nomFichier;
        } else {
            $_SESSION['erreurs'] = ["Type de fichier non autorisé (pdf, jpg, png, doc, docx, txt)."];
            header('Location: formulaire.php');
            exit;
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'modifier') {
        header('Location: formulaire.php');
        exit;
    }

    if ($_POST['action'] === 'valider' && !empty($_SESSION['data'])) {
        $d = $_SESSION['data'];
        $modules = implode(', ', $d['modules'] ?? []);

        // CORRECTION : "?? ''" partout, sinon Warning "Undefined array key" si une
        // case n'est pas cochée (ex. aucune filière choisie)
        $contenu = "Nom: " . ($d['nom'] ?? '') . "\n"
                 . "Prenom: " . ($d['prenom'] ?? '') . "\n"
                 . "Age: " . ($d['age'] ?? '') . "\n"
                 . "Tel: " . ($d['tel'] ?? '') . "\n"
                 . "Email: " . ($d['email'] ?? '') . "\n"
                 . "Date de naissance: " . ($d['date_naissance'] ?? '') . "\n"
                 . "Filiere: " . ($d['filiere'] ?? '') . "\n"
                 . "Annee: " . ($d['annee'] ?? '') . "\n"
                 . "Modules: $modules\n"
                 . "Nb projets: " . ($d['nb_projets'] ?? '') . "\n";

        // AJOUT (Question 2) : projets / stages
        $contenu .= "Projets et stages:\n";
        foreach ($d['type'] ?? [] as $i => $type) {
            if (trim($d['lieu'][$i] ?? '') === '' && trim($d['description'][$i] ?? '') === '') {
                continue;   // ligne vide
            }
            $contenu .= "  - $type du " . ($d['date_debut'][$i] ?? '') . " au " . ($d['date_fin'][$i] ?? '')
                      . " à " . ($d['lieu'][$i] ?? '') . " : " . ($d['description'][$i] ?? '') . "\n";
        }

        // AJOUT (Question 2) : centres d'intérêt, compétences, langues
        $contenu .= "Centres d'interet: " . ($d['interets'] ?? '') . "\n"
                  . "Competences: " . ($d['competences'] ?? '') . "\n"
                  . "Langues: " . ($d['langues'] ?? '') . "\n"
                  . "Remarques: " . ($d['remarques'] ?? '') . "\n"
                  . "Fichier: " . ($d['fichier_nom'] ?? '') . "\n";

        // CORRECTION : nettoyer le nom avant de l'utiliser comme nom de fichier
        // (un nom comme "../index" écrirait ailleurs)
        $nomPropre = preg_replace('/[^A-Za-z0-9_-]/', '_', $d['nom'] ?? 'inconnu');
        file_put_contents('fiche_' . $nomPropre . '.txt', $contenu);

        $message = "Informations enregistrées dans fiche_$nomPropre.txt";
    }
}

$d = $_SESSION['data'] ?? [];

// AJOUT : si on ouvre recap.php directement sans avoir rempli le formulaire
if (empty($d)) {
    header('Location: formulaire.php');
    exit;
}

$nom = $d['nom'] ?? '';
$prenom = $d['prenom'] ?? '';
$age = $d['age'] ?? '';
$num_telephone = $d['tel'] ?? '';
$email = $d['email'] ?? '';
$date_naissance = $d['date_naissance'] ?? '';
$filiere = $d['filiere'] ?? '';
$annee = $d['annee'] ?? '';
$modules = implode(', ', $d['modules'] ?? []);
$nb_projets = $d['nb_projets'] ?? '';
$remarques = $d['remarques'] ?? '';
// AJOUT
$interets = $d['interets'] ?? '';
$competences = $d['competences'] ?? '';
$langues = $d['langues'] ?? '';
$fichier_nom = $d['fichier_nom'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif</title>
</head>
<body>
    <h2>Récapitulatif</h2>

    <!-- AJOUT : confirmation après VALIDER -->
    <?php if ($message): ?>
        <p style="color:green;"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>

    <p><strong>Nom :</strong> <?= htmlspecialchars($nom) ?></p>
    <p><strong>Prénom :</strong> <?= htmlspecialchars($prenom) ?></p>
    <p><strong>Âge :</strong> <?= htmlspecialchars($age) ?></p>
    <p><strong>Téléphone :</strong> <?= htmlspecialchars($num_telephone) ?></p>
    <p><strong>Email :</strong> <?= htmlspecialchars($email) ?></p>
    <p><strong>Date de naissance :</strong> <?= htmlspecialchars($date_naissance) ?></p>
    <p><strong>Filière :</strong> <?= htmlspecialchars($filiere) ?></p>
    <p><strong>Année :</strong> <?= htmlspecialchars($annee) ?></p>
    <p><strong>Modules :</strong> <?= htmlspecialchars($modules) ?></p>
    <p><strong>Nombre de projets :</strong> <?= htmlspecialchars($nb_projets) ?></p>

    <!-- AJOUT (Question 2) : projets et stages -->
    <p><strong>Projets et stages :</strong></p>
    <table border="1">
        <tr><th>Type</th><th>Début</th><th>Fin</th><th>Lieu</th><th>Description</th></tr>
        <?php foreach ($d['type'] ?? [] as $i => $type): ?>
            <?php if (trim($d['lieu'][$i] ?? '') === '' && trim($d['description'][$i] ?? '') === '') continue; ?>
            <tr>
                <td><?= htmlspecialchars($type) ?></td>
                <td><?= htmlspecialchars($d['date_debut'][$i] ?? '') ?></td>
                <td><?= htmlspecialchars($d['date_fin'][$i] ?? '') ?></td>
                <td><?= htmlspecialchars($d['lieu'][$i] ?? '') ?></td>
                <td><?= htmlspecialchars($d['description'][$i] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <!-- AJOUT (Question 2) -->
    <p><strong>Centres d'intérêt :</strong> <?= nl2br(htmlspecialchars($interets)) ?></p>
    <p><strong>Compétences :</strong> <?= nl2br(htmlspecialchars($competences)) ?></p>
    <p><strong>Langues :</strong> <?= nl2br(htmlspecialchars($langues)) ?></p>

    <p><strong>Remarques :</strong> <?= htmlspecialchars($remarques) ?></p>
    <!-- AJOUT : fichier envoyé -->
    <p><strong>Fichier :</strong> <?= $fichier_nom !== '' ? htmlspecialchars($fichier_nom) : 'Aucun' ?></p>

    <form method="POST">
        <button type="submit" name="action" value="valider">VALIDER</button>
        <button type="submit" name="action" value="modifier">MODIFIER</button>
    </form>
</body>
</html>