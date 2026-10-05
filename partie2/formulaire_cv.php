<?php
// ================== formulaire_cv.php ==================
// Formulaire de création / modification d'un CV.
session_start();
require 'fonctions.php';

// "Créer mon CV" depuis l'accueil : on repart d'un formulaire vide
if (isset($_GET['nouveau'])) {
    unset($_SESSION['cv_form']);
}

$erreurs = $_SESSION['cv_erreurs'] ?? [];
unset($_SESSION['cv_erreurs']);

// D'où viennent les données à afficher ?
if (isset($_SESSION['cv_form'])) {
    // 1) Retour après une erreur : on remet ce que l'utilisateur avait saisi
    $f = $_SESSION['cv_form'];
    unset($_SESSION['cv_form']);

} elseif (!empty($_GET['email'])) {
    // 2) Modification d'un CV existant : on le charge depuis la base
    $cv = chargerCV($pdo, $_GET['email']);
    if (!$cv) {
        header('Location: index.php?introuvable=1');
        exit;
    }
    $f = $cv['etudiant'];
    $f['email_original'] = $cv['etudiant']['email'];
    $f['formations']  = $cv['formations'];
    $f['stages']      = $cv['stages'];
    $f['competences'] = $cv['competences'];
    $f['langues']     = $cv['langues'];
    $f['interets']    = array_column($cv['interets'], 'libelle');

} else {
    // 3) Nouveau CV : une ligne vide dans chaque rubrique (sauf stages)
    $f = [
        'formations'  => [[]],
        'stages'      => [],
        'competences' => [[]],
        'langues'     => [[]],
        'interets'    => [''],
    ];
}

// email_original = l'email du CV qu'on est en train de modifier ('' si nouveau CV)
$emailOriginal = $f['email_original'] ?? '';

// Photo déjà enregistrée dans la base (si on modifie un CV)
$photoActuelle = null;
if ($emailOriginal !== '') {
    $req = $pdo->prepare('SELECT photo FROM etudiant WHERE email = ?');
    $req->execute([$emailOriginal]);
    $photoActuelle = $req->fetchColumn() ?: null;
}

// ---------- Affichage d'une ligne de chaque rubrique ----------
// Chaque fonction sert deux fois : pour les lignes déjà remplies
// et comme modèle (<template>) pour le bouton "+ Ajouter".

function boutonAnnuler() {
    echo '<button type="button" onclick="this.parentElement.remove()">Annuler</button>';
}

function optionsNiveau(array $niveaux, $choisi, $texteVide) {
    echo '<option value="">' . e($texteVide) . '</option>';
    foreach ($niveaux as $n) {
        $sel = ($n === $choisi) ? 'selected' : '';
        echo '<option value="' . e($n) . '" ' . $sel . '>' . e($n) . '</option>';
    }
}

function ligneFormation($x = []) { ?>
    <div>
        <input type="text" name="formation[diplome][]" placeholder="Diplôme (ex : Cycle ingénieur GI)" size="30" value="<?= e($x['diplome'] ?? '') ?>" required>
        <input type="text" name="formation[etablissement][]" placeholder="Établissement" value="<?= e($x['etablissement'] ?? '') ?>" required>
        Début : <input type="date" name="formation[date_debut][]" value="<?= e($x['date_debut'] ?? '') ?>" required>
        Fin : <input type="date" name="formation[date_fin][]" value="<?= e($x['date_fin'] ?? '') ?>">
        <input type="text" name="formation[description][]" placeholder="Description (facultatif)" size="30" value="<?= e($x['description'] ?? '') ?>">
        <?php boutonAnnuler(); ?>
    </div>
<?php }

function ligneStage($x = []) { ?>
    <div>
        <input type="text" name="stage[intitule][]" placeholder="Intitulé du stage" size="25" value="<?= e($x['intitule'] ?? '') ?>" required>
        <input type="text" name="stage[entreprise][]" placeholder="Entreprise" value="<?= e($x['entreprise'] ?? '') ?>" required>
        <input type="text" name="stage[lieu][]" placeholder="Lieu" size="12" value="<?= e($x['lieu'] ?? '') ?>">
        Début : <input type="date" name="stage[date_debut][]" value="<?= e($x['date_debut'] ?? '') ?>" required>
        Fin : <input type="date" name="stage[date_fin][]" value="<?= e($x['date_fin'] ?? '') ?>">
        <input type="text" name="stage[description][]" placeholder="Missions réalisées" size="30" value="<?= e($x['description'] ?? '') ?>">
        <?php boutonAnnuler(); ?>
    </div>
<?php }

function ligneCompetence($x = []) { ?>
    <div>
        <input type="text" name="competence[libelle][]" placeholder="Compétence (ex : PHP)" value="<?= e($x['libelle'] ?? '') ?>" required>
        <select name="competence[niveau][]">
            <?php optionsNiveau(NIVEAUX_COMPETENCE, $x['niveau'] ?? '', '-- niveau (facultatif) --'); ?>
        </select>
        <?php boutonAnnuler(); ?>
    </div>
<?php }

function ligneLangue($x = []) { ?>
    <div>
        <input type="text" name="langue[libelle][]" placeholder="Langue (ex : Anglais)" value="<?= e($x['libelle'] ?? '') ?>" required>
        <select name="langue[niveau][]" required>
            <?php optionsNiveau(NIVEAUX_LANGUE, $x['niveau'] ?? '', '-- niveau --'); ?>
        </select>
        <?php boutonAnnuler(); ?>
    </div>
<?php }

function ligneInteret($libelle = '') { ?>
    <div>
        <input type="text" name="interet[]" placeholder="Centre d'intérêt (ex : Football)" value="<?= e($libelle) ?>" required>
        <?php boutonAnnuler(); ?>
    </div>
<?php }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $emailOriginal ? 'Modifier mon CV' : 'Créer mon CV' ?></title>
</head>
<body>

    <h2><?= $emailOriginal ? 'Modifier mon CV' : 'Créer mon CV' ?></h2>
    <p><a href="index.php">Retour à l'accueil</a></p>

    <?php if (!empty($erreurs)): ?>
        <div style="color:red;">
            <?php foreach ($erreurs as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="enregistrer_cv.php" method="POST" enctype="multipart/form-data">

        <!-- email du CV modifié : permet de savoir s'il s'agit d'une création ou d'une modification -->
        <input type="hidden" name="email_original" value="<?= e($emailOriginal) ?>">

        <h3>Nom et coordonnées</h3>
        <input type="text" name="nom" placeholder="Nom" value="<?= e($f['nom'] ?? '') ?>" required>
        <input type="text" name="prenom" placeholder="Prénom" value="<?= e($f['prenom'] ?? '') ?>" required><br><br>
        <input type="email" name="email" placeholder="Email (votre identifiant)" size="30" value="<?= e($f['email'] ?? '') ?>" required>
        <input type="tel" name="telephone" placeholder="Téléphone (06XXXXXXXX)" value="<?= e($f['telephone'] ?? '') ?>" required><br><br>
        <input type="text" name="adresse" placeholder="Adresse" size="60" value="<?= e($f['adresse'] ?? '') ?>" required>

        <h3>Photo</h3>
        <?php if ($photoActuelle): ?>
            <img src="<?= e($photoActuelle) ?>" alt="Photo actuelle" height="100"><br>
            Choisir une autre photo (facultatif) :
            <input type="file" name="photo" accept="image/jpeg,image/png">
        <?php else: ?>
            <input type="file" name="photo" accept="image/jpeg,image/png" required> (JPG ou PNG, 2 Mo max)
        <?php endif; ?>

        <h3>Formations</h3>
        <div id="liste-formations">
            <?php foreach ($f['formations'] ?? [] as $x) ligneFormation($x); ?>
        </div>
        <button type="button" onclick="ajouter('liste-formations', 'modele-formation')">+ Ajouter une formation</button>

        <h3>Stages</h3>
        <div id="liste-stages">
            <?php foreach ($f['stages'] ?? [] as $x) ligneStage($x); ?>
        </div>
        <button type="button" onclick="ajouter('liste-stages', 'modele-stage')">+ Ajouter un stage</button>

        <h3>Compétences</h3>
        <div id="liste-competences">
            <?php foreach ($f['competences'] ?? [] as $x) ligneCompetence($x); ?>
        </div>
        <button type="button" onclick="ajouter('liste-competences', 'modele-competence')">+ Ajouter une compétence</button>

        <h3>Langues</h3>
        <div id="liste-langues">
            <?php foreach ($f['langues'] ?? [] as $x) ligneLangue($x); ?>
        </div>
        <button type="button" onclick="ajouter('liste-langues', 'modele-langue')">+ Ajouter une langue</button>

        <h3>Centres d'intérêt</h3>
        <div id="liste-interets">
            <?php foreach ($f['interets'] ?? [] as $x) ligneInteret($x); ?>
        </div>
        <button type="button" onclick="ajouter('liste-interets', 'modele-interet')">+ Ajouter un centre d'intérêt</button>

        <br><br>
        <button type="submit">Enregistrer mon CV</button>
    </form>

    <!-- Modèles de lignes vides (invisibles), copiés par le bouton "+ Ajouter" -->
    <template id="modele-formation"><?php ligneFormation(); ?></template>
    <template id="modele-stage"><?php ligneStage(); ?></template>
    <template id="modele-competence"><?php ligneCompetence(); ?></template>
    <template id="modele-langue"><?php ligneLangue(); ?></template>
    <template id="modele-interet"><?php ligneInteret(); ?></template>

    <script>
        // Copie le modèle et l'ajoute à la fin de la liste
        function ajouter(idListe, idModele) {
            const modele = document.getElementById(idModele);
            document.getElementById(idListe).append(modele.content.cloneNode(true));
        }
    </script>
</body>
</html>
