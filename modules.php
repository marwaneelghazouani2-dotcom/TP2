<?php
// ================== modules.php ==================
// Liste des modules de chaque filière.
// Elle est utilisée par formulaire.php (affichage) et recap.php (vérification).
// Pour changer un module, il suffit de modifier ce tableau.

return [
    '2AP'  => ['Analyse', 'Algèbre', 'Mécanique', 'Électricité', 'Chimie', 'Informatique'],
    'GSTR' => ['Réseaux Av', 'Télécom numériques', 'Traitement du signal', 'Systèmes embarqués', 'Sécurité des réseaux'],
    'GI'   => ['Pro Av', 'Compilation', 'Reseaux Av', 'Web Avance', 'POO', 'BD'],
    'SCM'  => ['Logistique', 'Gestion des stocks', 'Recherche opérationnelle', 'Gestion de production', 'ERP'],
    'GC'   => ['RDM', 'Béton armé', 'Géotechnique', 'Topographie', 'Hydraulique'],
    'MS'   => ['Automatique', 'Électronique de puissance', 'CAO', 'Robotique', 'Mécanique des solides'],
];
