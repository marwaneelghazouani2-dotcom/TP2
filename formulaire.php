<?php
session_start();
$d = $_SESSION['data'] ?? [];
$erreurs = $_SESSION['erreurs'] ?? [];
unset($_SESSION['erreurs']);

// AJOUT : la liste des modules de chaque filière (fichier commun avec recap.php)
$modulesParFiliere = require 'modules.php';
$filiereChoisie = $d['filiere'] ?? '';

// AJOUT : reconstruire la liste des projets/stages déjà saisis (retour par MODIFIER)
// $d['type'], $d['date_debut']... sont des tableaux : la case n°0 = 1er projet, etc.
$projets = [];
foreach ($d['type'] ?? [] as $i => $type) {
    $projets[] = [
        'type'        => $type,
        'date_debut'  => $d['date_debut'][$i] ?? '',
        'date_fin'    => $d['date_fin'][$i] ?? '',
        'lieu'        => $d['lieu'][$i] ?? '',
        'description' => $d['description'][$i] ?? '',
    ];
}

// AJOUT : affiche UNE ligne "projet ou stage" (vide si $p est vide)
// Elle sert deux fois : pour les lignes déjà saisies, et comme modèle pour le bouton "Ajouter".
function afficherLigneProjet($p = []) {
    $type = $p['type'] ?? 'Projet';
    ?>
    <div class="ligne-projet">
        <select name="type[]">
            <option value="Projet" <?= $type === 'Projet' ? 'selected' : '' ?>>Projet</option>
            <option value="Stage"  <?= $type === 'Stage'  ? 'selected' : '' ?>>Stage</option>
        </select>
        Début : <input type="date" name="date_debut[]" value="<?= htmlspecialchars($p['date_debut'] ?? '') ?>">
        Fin : <input type="date" name="date_fin[]" value="<?= htmlspecialchars($p['date_fin'] ?? '') ?>">
        <input type="text" name="lieu[]" placeholder="Lieu" value="<?= htmlspecialchars($p['lieu'] ?? '') ?>">
        <input type="text" name="description[]" placeholder="Description" size="35" value="<?= htmlspecialchars($p['description'] ?? '') ?>">
        <button type="button" onclick="this.parentElement.remove()">Annuler</button>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Formulaire</title>
</head>
<body>

    <?php if (!empty($erreurs)): ?>
        <div style="color:red;">
            <?php foreach ($erreurs as $e): ?>
                <p><?= htmlspecialchars($e) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="recap.php" method="POST" enctype="multipart/form-data">

        <h3>Renseignements Personnels</h3>

        <input type="text" name="nom" placeholder="Nom" value="<?= htmlspecialchars($d['nom'] ?? '') ?>" required>
        <input type="text" name="prenom" placeholder="Prénom" value="<?= htmlspecialchars($d['prenom'] ?? '') ?>" required>
        <input type="number" name="age" min="0" placeholder="Âge" value="<?= htmlspecialchars($d['age'] ?? '') ?>">
        <input type="tel" name="tel" placeholder="Téléphone" value="<?= htmlspecialchars($d['tel'] ?? '') ?>">
        <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($d['email'] ?? '') ?>" required>
        <input type="date" name="date_naissance" value="<?= htmlspecialchars($d['date_naissance'] ?? '') ?>">

        <h3>Renseignements Académiques</h3>

        <!-- MODIFIÉ : les boutons radio sont générés par une boucle,
             et chaque clic appelle afficherModules() pour changer la liste des modules -->
        <p>Filière :</p>
        <?php foreach (array_keys($modulesParFiliere) as $f): ?>
            <input type="radio" name="filiere" value="<?= $f ?>"
                   <?= $filiereChoisie === $f ? 'checked' : '' ?>
                   onchange="afficherModules(this.value)"> <?= $f ?>
        <?php endforeach; ?>

        <p>Année :</p>
        <select name="annee">
            <option value="1" <?= ($d['annee'] ?? '') === '1' ? 'selected' : '' ?>>1ère année</option>
            <option value="2" <?= ($d['annee'] ?? '') === '2' ? 'selected' : '' ?>>2ème année</option>
            <option value="3" <?= ($d['annee'] ?? '') === '3' ? 'selected' : '' ?>>3ème année</option>
        </select>

        <!-- MODIFIÉ : les modules dépendent de la filière choisie -->
        <p>Modules suivis :</p>
        <div id="modules">
            <?php if (isset($modulesParFiliere[$filiereChoisie])): ?>
                <?php foreach ($modulesParFiliere[$filiereChoisie] as $m): ?>
                    <label>
                        <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($m) ?>"
                               <?= in_array($m, $d['modules'] ?? []) ? 'checked' : '' ?>> <?= htmlspecialchars($m) ?>
                    </label>
                <?php endforeach; ?>
            <?php else: ?>
                <em>Choisissez d'abord une filière.</em>
            <?php endif; ?>
        </div>

        <p>Nombre de projets réalisés cette année :</p>
        <input type="number" name="nb_projets" min="0" value="<?= htmlspecialchars($d['nb_projets'] ?? '') ?>">

        <!-- MODIFIÉ : projets et stages, avec un bouton pour ajouter une ligne
             et un bouton "Annuler" sur chaque ligne pour la retirer -->
        <h3>Projets et stages réalisés</h3>
        <div id="liste-projets">
            <?php foreach ($projets as $p) afficherLigneProjet($p); ?>
        </div>
        <button type="button" onclick="ajouterProjet()">+ Ajouter un projet ou un stage</button>

        <!-- Modèle d'une ligne vide : invisible, il est copié à chaque clic sur "Ajouter" -->
        <template id="modele-projet">
            <?php afficherLigneProjet(); ?>
        </template>

        <h3>Centres d'intérêt</h3>
        <textarea name="interets"><?= htmlspecialchars($d['interets'] ?? '') ?></textarea>

        <h3>Compétences</h3>
        <textarea name="competences"><?= htmlspecialchars($d['competences'] ?? '') ?></textarea>

        <h3>Langues</h3>
        <textarea name="langues"><?= htmlspecialchars($d['langues'] ?? '') ?></textarea>

        <h3>Remarques</h3>
        <textarea name="remarques"><?= htmlspecialchars($d['remarques'] ?? '') ?></textarea>

        <h3>Fichier</h3>
        <input type="file" name="fichier">
        <?php if (!empty($d['fichier_nom'])): ?>
            (fichier actuel : <?= htmlspecialchars($d['fichier_nom']) ?>)
        <?php endif; ?>

        <br><br>
        <button type="submit">Envoyer</button>
        <button type="reset">Effacer</button>

    </form>

    <script>
        // AJOUT : liste des modules de chaque filière, envoyée par PHP au JavaScript
        const MODULES = <?= json_encode($modulesParFiliere, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

        // Remplace les cases à cocher par les modules de la filière choisie
        function afficherModules(filiere) {
            const zone = document.getElementById('modules');
            zone.innerHTML = '';                       // on vide l'ancienne liste

            MODULES[filiere].forEach(function (module) {
                const label = document.createElement('label');
                const caseACocher = document.createElement('input');
                caseACocher.type = 'checkbox';
                caseACocher.name = 'modules[]';
                caseACocher.value = module;
                label.append(caseACocher, ' ' + module + ' ');
                zone.append(label);
            });
        }

        // Ajoute une nouvelle ligne "projet ou stage" (copie du modèle)
        function ajouterProjet() {
            const modele = document.getElementById('modele-projet');
            const copie = modele.content.cloneNode(true);
            document.getElementById('liste-projets').append(copie);
        }
    </script>
</body>
</html>
