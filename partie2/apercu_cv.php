<?php
// ================== apercu_cv.php ==================
// Affiche le CV enregistré, avec les boutons PDF / Modifier.
require 'fonctions.php';

$email = strtolower(trim($_GET['email'] ?? ''));
$cv = chargerCV($pdo, $email);

if (!$cv) {
    header('Location: index.php?introuvable=1');
    exit;
}

$et = $cv['etudiant'];
$lienEmail = urlencode($et['email']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CV de <?= e($et['prenom'] . ' ' . $et['nom']) ?></title>
</head>
<body>

    <?php if (isset($_GET['ok'])): ?>
        <p style="color:green;">Votre CV a bien été enregistré.</p>
    <?php endif; ?>

    <p>
        <a href="cv_pdf.php?email=<?= $lienEmail ?>" target="_blank">Voir le PDF</a> |
        <a href="cv_pdf.php?email=<?= $lienEmail ?>&telecharger=1">Télécharger le PDF</a> |
        <a href="formulaire_cv.php?email=<?= $lienEmail ?>">Modifier</a> |
        <a href="index.php">Accueil</a>
    </p>
    <hr>

    <?php if ($et['photo']): ?>
        <img src="<?= e($et['photo']) ?>" alt="Photo" height="140" style="float:right;">
    <?php endif; ?>

    <h1><?= e($et['prenom'] . ' ' . mb_strtoupper($et['nom'])) ?></h1>
    <p>
        Email : <?= e($et['email']) ?><br>
        Téléphone : <?= e($et['telephone']) ?><br>
        Adresse : <?= e($et['adresse']) ?>
    </p>

    <h2>Formations</h2>
    <ul>
        <?php foreach ($cv['formations'] as $x): ?>
            <li>
                <?= e(periode($x['date_debut'], $x['date_fin'])) ?> :
                <strong><?= e($x['diplome']) ?></strong>, <?= e($x['etablissement']) ?>
                <?php if ($x['description']): ?><br><?= e($x['description']) ?><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($cv['stages']): ?>
        <h2>Stages</h2>
        <ul>
            <?php foreach ($cv['stages'] as $x): ?>
                <li>
                    <?= e(periode($x['date_debut'], $x['date_fin'])) ?> :
                    <strong><?= e($x['intitule']) ?></strong>, <?= e($x['entreprise']) ?>
                    <?php if ($x['lieu']): ?>(<?= e($x['lieu']) ?>)<?php endif; ?>
                    <?php if ($x['description']): ?><br><?= e($x['description']) ?><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2>Compétences</h2>
    <ul>
        <?php foreach ($cv['competences'] as $x): ?>
            <li><?= e($x['libelle']) ?><?= $x['niveau'] ? ' : ' . e($x['niveau']) : '' ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Langues</h2>
    <ul>
        <?php foreach ($cv['langues'] as $x): ?>
            <li><?= e($x['libelle']) ?> : <?= e($x['niveau']) ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Centres d'intérêt</h2>
    <p><?= e(implode(', ', array_column($cv['interets'], 'libelle'))) ?></p>

</body>
</html>
