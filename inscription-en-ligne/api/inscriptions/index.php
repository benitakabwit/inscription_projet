<?php
// api/inscriptions/index.php

require_once '../config/database.php';
require_once '../models/Etudiant.php';
require_once '../models/Dossier.php';
require_once '../functions.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

var_dump($_POST);
var_dump($_FILES);
exit();

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        $nom = $_POST['nom'] ?? null;
        $prenom = $_POST['prenom'] ?? null;
        $contact = $_POST['contact'] ?? null;
        $email = $_POST['email'] ?? null;
        $photo = $_FILES['photo'] ?? null;
        $releves = $_FILES['releves'] ?? null;

        // Valider les données de l'étudiant (ajoutez des validations plus strictes)
        if (!$nom || !$prenom || !$email) {
            handle_error('Informations étudiant incomplètes.', 400);
        }

        try {
            $etudiant = new Etudiant($nom, $prenom, $contact, $email);
            $stmt = $pdo->prepare("INSERT INTO etudiants (nom, prenom, contact, email) VALUES (?, ?, ?, ?)");
            $stmt->execute([$etudiant->nom, $etudiant->prenom, $etudiant->contact, $etudiant->email]);
            $etudiant_id = $pdo->lastInsertId();

            $photo_nom = null;
            $releves_nom = null;

            // Gestion de l'upload de la photo
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                $photo_tmp_name = $photo['tmp_name'];
                $photo_nom = 'photo_' . $etudiant_id . '_' . uniqid() . '.' . pathinfo($photo['name'], PATHINFO_EXTENSION);
                $photo_destination = '../../data/dossiers/' . $photo_nom; // Assurez-vous que ce dossier existe et est accessible en écriture
                if (!move_uploaded_file($photo_tmp_name, $photo_destination)) {
                    handle_error('Erreur lors du téléchargement de la photo.', 500);
                }
            } elseif ($photo && $photo['error'] !== UPLOAD_ERR_NO_FILE) {
                handle_error('Erreur lors du téléchargement de la photo.', 400);
            }

            // Gestion de l'upload des relevés
            if ($releves && $releves['error'] === UPLOAD_ERR_OK) {
                $releves_tmp_name = $releves['tmp_name'];
                $releves_nom = 'releves_' . $etudiant_id . '_' . uniqid() . '.' . pathinfo($releves['name'], PATHINFO_EXTENSION);
                $releves_destination = '../../data/dossiers/' . $releves_nom; // Assurez-vous que ce dossier existe et est accessible en écriture
                if (!move_uploaded_file($releves_tmp_name, $releves_destination)) {
                    handle_error('Erreur lors du téléchargement des relevés.', 500);
                }
            } elseif ($releves && $releves['error'] !== UPLOAD_ERR_NO_FILE) {
                handle_error('Erreur lors du téléchargement des relevés.', 400);
            }

            // Similaire pour le dossier (gestion des fichiers)
            $stmt_dossier = $pdo->prepare("INSERT INTO dossiers (etudiant_id, photo, releves) VALUES (?, ?, ?)");
            $stmt_dossier->execute([$etudiant_id, $photo_nom, $releves_nom]);
            $dossier_id = $pdo->lastInsertId();

            $identifiant_unique = uniqid();
            $stmt_inscription = $pdo->prepare("INSERT INTO inscriptions (etudiant_id, dossier_id, identifiant_unique) VALUES (?, ?, ?)");
            $stmt_inscription->execute([$etudiant_id, $dossier_id, $identifiant_unique]);

            send_json_response(['identifiant' => $identifiant_unique], 201);

        } catch (PDOException $e) {
            handle_error("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
        break;

    case 'GET':
        $id_etudiant = $_GET['id'] ?? null;
        if ($id_etudiant) {
            try {
                $stmt = $pdo->prepare("SELECT statut FROM inscriptions WHERE etudiant_id = ?");
                $stmt->execute([$id_etudiant]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($result) {
                    send_json_response(['statut' => $result['statut']]);
                } else {
                    handle_error("Identifiant étudiant non trouvé.", 404);
                }
            } catch (PDOException $e) {
                handle_error("Erreur lors de la requête : " . $e->getMessage());
            }
        } else {
            handle_error("Identifiant étudiant manquant.", 400);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['erreur' => 'Méthode non autorisée.']);
        break;
}