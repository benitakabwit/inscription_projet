<?php
// api/paiements/index.php

require_once '../config/database.php';
require_once '../functions.php';
require_once '../models/Paiement.php'; // Assurez-vous d'avoir le modèle Paiement

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = get_json_data();
    $etudiant_id = $data['etudiant_id'] ?? null;
    $montant = $data['montant'] ?? null;
    $date_paiement = date('Y-m-d H:i:s'); // Date actuelle
    $methode = $data['methode'] ?? null;

    if (!$etudiant_id || !$montant || !$methode) {
        handle_error("Informations de paiement manquantes.", 400);
    }

    try {
        // Création d'une instance de Paiement
        $paiement = new Paiement($etudiant_id, $montant, $date_paiement, $methode);

        // Insérer le paiement dans la base de données
        $stmt = $pdo->prepare("INSERT INTO paiements (etudiant_id, montant, date_paiement, methode) VALUES (?, ?, ?, ?)");
        $stmt->execute([$paiement->etudiant_id, $paiement->montant, $paiement->date_paiement, $paiement->methode]);

        // Réponse JSON avec les détails du paiement
        $recu = [
            'etudiant_id' => $paiement->etudiant_id,
            'montant' => $paiement->montant,
            'methode' => $paiement->methode,
            'date_paiement' => $paiement->date_paiement,
            'message' => 'Paiement enregistré avec succès.'
        ];

        send_json_response(['recu' => $recu], 201);

    } catch (PDOException $e) {
        handle_error("Erreur lors de l'enregistrement du paiement : " . $e->getMessage());
    }

} else {
    http_response_code(405);
    echo json_encode(['erreur' => 'Méthode non autorisée.']);
}