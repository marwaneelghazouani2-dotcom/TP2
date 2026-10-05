<?php
// ================== index.php (accueil de la partie 2) ==================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Générateur de CV</title>
</head>
<body>

    <h1>Générateur de CV</h1>
    <p>Remplissez vos informations et téléchargez votre CV au format PDF.</p>

    <h3>Nouveau CV</h3>
    <p><a href="formulaire_cv.php?nouveau=1">Créer mon CV</a></p>

    <h3>Retrouver mon CV</h3>
    <?php if (isset($_GET['introuvable'])): ?>
        <p style="color:red;">Aucun CV trouvé pour cet email.</p>
    <?php endif; ?>
    <form action="apercu_cv.php" method="GET">
        <input type="email" name="email" placeholder="Votre email" size="30" required>
        <button type="submit">Retrouver</button>
    </form>

</body>
</html>
