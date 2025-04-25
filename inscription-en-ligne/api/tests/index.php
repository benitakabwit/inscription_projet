<?php
// api/tests/index.php

require_once '../config/database.php';
require_once '../functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $id_etudiant = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? null;

        if (!$id_etudiant) {
            handle_error("Identifiant étudiant manquant.", 400);
        }

        if ($action === 'questions') {
            // Récupérer les questions du test (exemple simple)
            try {
                $stmt = $pdo->prepare("SELECT q.id, q.question_text FROM questions q JOIN tests t ON q.test_id = t.id WHERE t.nom = 'Test d\'Admission'"); // Adaptez la requête
                $stmt->execute([]);
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                send_json_response($questions);
            } catch (PDOException $e) {
                handle_error("Erreur lors de la récupération des questions : " . $e->getMessage());
            }
        } elseif ($action === 'resultat') {
            try {
                $stmt = $pdo->prepare("SELECT score, decision FROM resultats_test WHERE etudiant_id = ?");
                $stmt->execute([$id_etudiant]);
                $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($resultat) {
                    send_json_response($resultat);
                } else {
                    send_json_response(['statut' => 'En attente de délibération']); // Ou un autre statut par défaut
                }
            } catch (PDOException $e) {
                handle_error("Erreur lors de la récupération du résultat : " . $e->getMessage());
            }
        } else {
            handle_error("Action non spécifiée.", 400);
        }
        break;

    case 'POST':
        $id_etudiant = $_GET['id'] ?? null;
        $action = $_GET['action'] ?? null;
        $data = get_json_data();
        $reponses = $data['reponses'] ?? null;

        if (!$id_etudiant || $action !== 'soumettre' || !$reponses) {
            handle_error("Données incorrectes pour la soumission des réponses.", 400);
        }

        try {
            // Traiter les réponses, calculer le score, enregistrer le résultat
            $score = 0;
            // Exemple très simple : suppose que la// Exemple très simple : suppose que la réponse à la question avec l'ID 1 est 'A'
            foreach ($reponses as $question_id => $reponse_etudiant) {
                // Dans une application réelle, vous auriez une table 'options' et 'reponses_correctes'
                if ($question_id == 1 && strtoupper($reponse_etudiant) == 'A') {
                    $score++;
                }
                // Enregistrer la réponse de l'étudiant
                $stmt_reponse = $pdo->prepare("INSERT INTO reponses_etudiant (etudiant_id, test_id, question_id, reponse) VALUES (?, (SELECT id FROM tests WHERE nom = 'Test d\'Admission'), ?, ?)");
                $stmt_reponse->execute([$id_etudiant, $question_id, $reponse_etudiant]);
            }

            $decision = $score >= 1 ? 'Admis' : 'Échoué'; // Exemple de logique de décision

            $stmt_resultat = $pdo->prepare("INSERT INTO resultats_test (etudiant_id, test_id, score, decision) VALUES (?, (SELECT id FROM tests WHERE nom = 'Test d\'Admission'), ?, ?)");
            $stmt_resultat->execute([$id_etudiant, $score, $decision]);

            send_json_response(['score' => $score, 'decision' => $decision]);

        } catch (PDOException $e) {
            handle_error("Erreur lors de la soumission des réponses : " . $e->getMessage());
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['erreur' => 'Méthode non autorisée.']);
        break;
}