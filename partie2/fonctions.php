<?php
// ================== fonctions.php ==================
// Fonctions utilisées par plusieurs pages de la partie 2.

require_once __DIR__ . '/config.php';

// Niveaux proposés dans les listes déroulantes (identiques aux ENUM de la base)
const NIVEAUX_COMPETENCE = ['Débutant', 'Intermédiaire', 'Avancé', 'Expert'];
const NIVEAUX_LANGUE     = ['Débutant', 'Intermédiaire', 'Courant', 'Bilingue', 'Langue maternelle'];

// Afficher un texte sans risque dans une page HTML (protection XSS)
function e($texte) {
    return htmlspecialchars($texte ?? '', ENT_QUOTES, 'UTF-8');
}

// Est-ce qu'un étudiant avec cet email existe déjà dans la base ?
function emailExiste(PDO $pdo, $email) {
    $req = $pdo->prepare('SELECT COUNT(*) FROM etudiant WHERE email = ?');
    $req->execute([$email]);
    return $req->fetchColumn() > 0;
}

// Charger tout le CV d'un étudiant à partir de son email (null si introuvable)
function chargerCV(PDO $pdo, $email) {
    $req = $pdo->prepare('SELECT * FROM etudiant WHERE email = ?');
    $req->execute([$email]);
    $etudiant = $req->fetch(PDO::FETCH_ASSOC);

    if (!$etudiant) {
        return null;
    }

    // Une requête par table liée ; les plus récents en premier
    $requetes = [
        'formations'  => 'SELECT * FROM formation WHERE email = ? ORDER BY date_debut DESC',
        'stages'      => 'SELECT * FROM stage WHERE email = ? ORDER BY date_debut DESC',
        'competences' => 'SELECT * FROM competence WHERE email = ? ORDER BY id_competence',
        'langues'     => 'SELECT * FROM langue WHERE email = ? ORDER BY id_langue',
        'interets'    => 'SELECT * FROM centre_interet WHERE email = ? ORDER BY id_interet',
    ];

    $cv = ['etudiant' => $etudiant];
    foreach ($requetes as $cle => $sql) {
        $req = $pdo->prepare($sql);
        $req->execute([$email]);
        $cv[$cle] = $req->fetchAll(PDO::FETCH_ASSOC);
    }
    return $cv;
}

// Transformer les tableaux envoyés par le formulaire en une liste de lignes.
// Exemple : $_POST['langue'] = ['libelle' => ['Arabe', 'Anglais'], 'niveau' => ['Langue maternelle', 'Courant']]
// devient   [ ['libelle' => 'Arabe', 'niveau' => 'Langue maternelle'], ['libelle' => 'Anglais', 'niveau' => 'Courant'] ]
function lignes($tableau, array $champs) {
    $lignes = [];
    $nb = count($tableau[$champs[0]] ?? []);
    for ($i = 0; $i < $nb; $i++) {
        $ligne = [];
        foreach ($champs as $champ) {
            $ligne[$champ] = trim($tableau[$champ][$i] ?? '');
        }
        // on ignore les lignes complètement vides
        if (implode('', $ligne) !== '') {
            $lignes[] = $ligne;
        }
    }
    return $lignes;
}

// Afficher une période : "09/2023 - 06/2025" ou "09/2023 - aujourd'hui"
function periode($debut, $fin) {
    $texte = $debut ? date('m/Y', strtotime($debut)) : '';
    $texte .= ' - ' . ($fin ? date('m/Y', strtotime($fin)) : "aujourd'hui");
    return $texte;
}

// Enregistrer la photo envoyée. Renvoie le chemin du fichier, ou null en cas d'erreur
// (le message d'erreur est alors placé dans $erreur).
function enregistrerPhoto(array $fichier, &$erreur) {
    if ($fichier['error'] !== UPLOAD_ERR_OK) {
        $erreur = "Erreur pendant l'envoi de la photo.";
        return null;
    }
    if ($fichier['size'] > 2 * 1024 * 1024) {
        $erreur = "La photo ne doit pas dépasser 2 Mo.";
        return null;
    }

    // On vérifie le VRAI contenu du fichier (pas seulement son extension)
    $info = @getimagesize($fichier['tmp_name']);
    if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
        $erreur = "La photo doit être une image JPG ou PNG.";
        return null;
    }

    $dossier = __DIR__ . '/uploads/photos';
    if (!is_dir($dossier)) {
        mkdir($dossier, 0777, true);
    }

    // Nom aléatoire : deux étudiants qui envoient "photo.jpg" ne s'écrasent pas
    $nom = 'uploads/photos/' . bin2hex(random_bytes(8));

    // Si l'extension GD est disponible : on réduit l'image (400 px de large max)
    // et on la convertit en JPEG, un format que FPDF lit toujours sans problème.
    if (function_exists('imagecreatefromstring')) {
        $source = imagecreatefromstring(file_get_contents($fichier['tmp_name']));
        if ($source) {
            if (imagesx($source) > 400) {
                $source = imagescale($source, 400);
            }
            $largeur = imagesx($source);
            $hauteur = imagesy($source);
            $image = imagecreatetruecolor($largeur, $hauteur);
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255)); // fond blanc (PNG transparents)
            imagecopy($image, $source, 0, 0, 0, 0, $largeur, $hauteur);
            imagejpeg($image, __DIR__ . '/' . $nom . '.jpg', 85);
            return $nom . '.jpg';
        }
    }

    // Sans GD : on garde le fichier tel quel
    $extension = $info[2] === IMAGETYPE_PNG ? '.png' : '.jpg';
    move_uploaded_file($fichier['tmp_name'], __DIR__ . '/' . $nom . $extension);
    return $nom . $extension;
}
