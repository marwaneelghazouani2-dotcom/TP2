<?php
// Connexion à la base de données tp_cv (XAMPP : utilisateur root, sans mot de passe)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=tp_cv;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base tp_cv : " . $e->getMessage()
        . "<br>Vérifiez que MySQL est démarré et que tp_cv.sql a été importé.");
}
