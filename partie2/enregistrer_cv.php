<?php
// ================== enregistrer_cv.php ==================
// Vérifie les données du formulaire et enregistre le CV dans la base tp_cv.
session_start();
require 'fonctions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$erreurs = [];

// ---------- 1) Récupérer les données ----------
$nom            = trim($_POST['nom'] ?? '');
$prenom         = trim($_POST['prenom'] ?? '');
$email          = strtolower(trim($_POST['email'] ?? ''));
$telephone      = str_replace(' ', '', $_POST['telephone'] ?? '');
$adresse        = trim($_POST['adresse'] ?? '');
$emailOriginal  = trim($_POST['email_original'] ?? '');

$formations  = lignes($_POST['formation'] ?? [], ['diplome', 'etablissement', 'date_debut', 'date_fin', 'description']);
$stages      = lignes($_POST['stage'] ?? [], ['intitule', 'entreprise', 'lieu', 'date_debut', 'date_fin', 'description']);
$competences = lignes($_POST['competence'] ?? [], ['libelle', 'niveau']);
$langues     = lignes($_POST['langue'] ?? [], ['libelle', 'niveau']);
$interets    = array_values(array_filter(array_map('trim', $_POST['interet'] ?? [])));

// Ce qui a été saisi, pour pouvoir le réafficher dans le formulaire en cas d'erreur
$saisie = [
    'email_original' => $emailOriginal,
    'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
    'telephone' => $telephone, 'adresse' => $adresse,
    'formations' => $formations, 'stages' => $stages,
    'competences' => $competences, 'langues' => $langues, 'interets' => $interets,
];

// Renvoyer vers le formulaire avec les erreurs et les données saisies
function retourFormulaire(array $erreurs, array $saisie) {
    $_SESSION['cv_erreurs'] = $erreurs;
    $_SESSION['cv_form'] = $saisie;
    header('Location: formulaire_cv.php');
    exit;
}

// ---------- 2) Vérifier les données ----------
if ($nom === '' || $prenom === '') {
    $erreurs[] = "Le nom et le prénom sont obligatoires.";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreurs[] = "Adresse email invalide.";
}
if (!preg_match('/^(0|\+212)[5-7][0-9]{8}$/', $telephone)) {
    $erreurs[] = "Numéro de téléphone invalide (ex : 0612345678 ou +212612345678).";
}
if ($adresse === '') {
    $erreurs[] = "L'adresse est obligatoire.";
}

foreach ($formations as $i => $x) {
    if ($x['diplome'] === '' || $x['etablissement'] === '' || $x['date_debut'] === '') {
        $erreurs[] = "Formation n°" . ($i + 1) . " : diplôme, établissement et date de début sont obligatoires.";
    } elseif ($x['date_fin'] !== '' && $x['date_fin'] < $x['date_debut']) {
        $erreurs[] = "Formation n°" . ($i + 1) . " : la date de fin est avant la date de début.";
    }
}
foreach ($stages as $i => $x) {
    if ($x['intitule'] === '' || $x['entreprise'] === '' || $x['date_debut'] === '') {
        $erreurs[] = "Stage n°" . ($i + 1) . " : intitulé, entreprise et date de début sont obligatoires.";
    } elseif ($x['date_fin'] !== '' && $x['date_fin'] < $x['date_debut']) {
        $erreurs[] = "Stage n°" . ($i + 1) . " : la date de fin est avant la date de début.";
    }
}
foreach ($competences as $i => $x) {
    if ($x['libelle'] === '' || ($x['niveau'] !== '' && !in_array($x['niveau'], NIVEAUX_COMPETENCE))) {
        $erreurs[] = "Compétence n°" . ($i + 1) . " : nom manquant ou niveau invalide.";
    }
}
foreach ($langues as $i => $x) {
    if ($x['libelle'] === '' || !in_array($x['niveau'], NIVEAUX_LANGUE)) {
        $erreurs[] = "Langue n°" . ($i + 1) . " : la langue et son niveau sont obligatoires.";
    }
}

// Rubriques obligatoires du CV
if (count($formations) === 0)  $erreurs[] = "Ajoutez au moins une formation.";
if (count($competences) === 0) $erreurs[] = "Ajoutez au moins une compétence.";
if (count($langues) === 0)     $erreurs[] = "Ajoutez au moins une langue.";
if (count($interets) === 0)    $erreurs[] = "Ajoutez au moins un centre d'intérêt.";

// ---------- 3) L'email sert d'identifiant : on le valide ----------
$modification = ($emailOriginal !== '' && emailExiste($pdo, $emailOriginal));

if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    if (!$modification && emailExiste($pdo, $email)) {
        // Création d'un CV avec un email déjà présent dans la base
        $erreurs[] = "Un CV existe déjà avec l'email $email. Utilisez « Retrouver mon CV » sur l'accueil pour le modifier.";
    } elseif ($modification && $email !== $emailOriginal && emailExiste($pdo, $email)) {
        // Changement d'email vers un email déjà utilisé par quelqu'un d'autre
        $erreurs[] = "L'email $email est déjà utilisé par un autre CV.";
    }
}

// ---------- 4) La photo ----------
$anciennePhoto = null;
if ($modification) {
    $req = $pdo->prepare('SELECT photo FROM etudiant WHERE email = ?');
    $req->execute([$emailOriginal]);
    $anciennePhoto = $req->fetchColumn() ?: null;
}

$photoEnvoyee = isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE;

if (!$photoEnvoyee && !$anciennePhoto) {
    $erreurs[] = "La photo est obligatoire.";
}

// ---------- 5) En cas d'erreur : retour au formulaire ----------
if (!empty($erreurs)) {
    if ($photoEnvoyee) {
        $erreurs[] = "(Pour des raisons de sécurité, la photo doit être choisie à nouveau.)";
    }
    retourFormulaire($erreurs, $saisie);
}

// La photo n'est enregistrée que si tout le reste est correct
$photo = $anciennePhoto;
if ($photoEnvoyee) {
    $erreurPhoto = '';
    $photo = enregistrerPhoto($_FILES['photo'], $erreurPhoto);
    if ($photo === null) {
        retourFormulaire([$erreurPhoto], $saisie);
    }
}

// ---------- 6) Enregistrement dans la base ----------
// Transaction : soit TOUT est enregistré, soit RIEN (en cas d'erreur au milieu)
try {
    $pdo->beginTransaction();

    if ($modification) {
        // Mise à jour. Si l'email change, ON UPDATE CASCADE met à jour les autres tables.
        $req = $pdo->prepare('UPDATE etudiant
                              SET email = ?, nom = ?, prenom = ?, telephone = ?, adresse = ?, photo = ?
                              WHERE email = ?');
        $req->execute([$email, $nom, $prenom, $telephone, $adresse, $photo, $emailOriginal]);
    } else {
        $req = $pdo->prepare('INSERT INTO etudiant (email, nom, prenom, telephone, adresse, photo)
                              VALUES (?, ?, ?, ?, ?, ?)');
        $req->execute([$email, $nom, $prenom, $telephone, $adresse, $photo]);
    }

    // On remplace toutes les lignes liées : on supprime les anciennes puis on insère les nouvelles
    foreach (['formation', 'stage', 'competence', 'langue', 'centre_interet'] as $table) {
        $pdo->prepare("DELETE FROM $table WHERE email = ?")->execute([$email]);
    }

    $req = $pdo->prepare('INSERT INTO formation (email, diplome, etablissement, date_debut, date_fin, description)
                          VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($formations as $x) {
        $req->execute([$email, $x['diplome'], $x['etablissement'], $x['date_debut'],
                       $x['date_fin'] ?: null, $x['description'] ?: null]);
    }

    $req = $pdo->prepare('INSERT INTO stage (email, intitule, entreprise, lieu, date_debut, date_fin, description)
                          VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($stages as $x) {
        $req->execute([$email, $x['intitule'], $x['entreprise'], $x['lieu'] ?: null, $x['date_debut'],
                       $x['date_fin'] ?: null, $x['description'] ?: null]);
    }

    $req = $pdo->prepare('INSERT INTO competence (email, libelle, niveau) VALUES (?, ?, ?)');
    foreach ($competences as $x) {
        $req->execute([$email, $x['libelle'], $x['niveau'] ?: null]);
    }

    $req = $pdo->prepare('INSERT INTO langue (email, libelle, niveau) VALUES (?, ?, ?)');
    foreach ($langues as $x) {
        $req->execute([$email, $x['libelle'], $x['niveau']]);
    }

    $req = $pdo->prepare('INSERT INTO centre_interet (email, libelle) VALUES (?, ?)');
    foreach ($interets as $libelle) {
        $req->execute([$email, $libelle]);
    }

    $pdo->commit();

} catch (PDOException $ex) {
    $pdo->rollBack();
    // La nouvelle photo ne sert plus : on la supprime
    if ($photoEnvoyee && $photo && $photo !== $anciennePhoto) {
        @unlink(__DIR__ . '/' . $photo);
    }
    die("Erreur lors de l'enregistrement : " . e($ex->getMessage()));
}

// Une nouvelle photo remplace l'ancienne : on supprime l'ancien fichier
if ($photoEnvoyee && $anciennePhoto && $anciennePhoto !== $photo) {
    @unlink(__DIR__ . '/' . $anciennePhoto);
}

// ---------- 7) Afficher le CV enregistré ----------
header('Location: apercu_cv.php?ok=1&email=' . urlencode($email));
exit;
