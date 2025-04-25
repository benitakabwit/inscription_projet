<?php
// api/inscriptions/attestations.php

require_once '../config/database.php';
require_once '../functions.php';
require_once '../../vendor/tecnickcom/tcpdf/tcpdf.php'; // Exemple avec TCPDF

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$id_etudiant = $_GET['id'] ?? null;

if (!$id_etudiant) {
    handle_error("Identifiant étudiant manquant.", 400);
}

try {
    $stmt = $pdo->prepare("SELECT e.nom, e.prenom FROM etudiants e JOIN inscriptions i ON e.id = i.etudiant_id WHERE i.etudiant_id = ?");
    $stmt->execute([$id_etudiant]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($etudiant) {
        // Créer le PDF de l'attestation
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Votre Institution');
        $pdf->SetTitle('Attestation d\'Inscription');
        $pdf->SetSubject('Attestation');
        $pdf->SetKeywords('attestation, inscription');
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->AddPage();
        $html = "<h1>Attestation d'Inscription</h1><p>Nom: " . $etudiant['nom'] . "</p><p>Prénom: " . $etudiant['prenom'] . "</p><p>Est inscrit(e) à [Nom de la formation] pour l'année académique [Année].</p>";
        $pdf->writeHTML($html, true, false, true, false, '');

        // Enregistrer l'attestation (temporairement dans le dossier data)
        $chemin_pdf = '../../data/attestations/attestation_' . $id_etudiant . '.pdf';
        $pdf->Output($chemin_pdf, 'F');

        send_json_response(['chemin_attestation' => '/data/attestations/attestation_' . $id_etudiant . '.pdf']); // Le frontend devra gérer le téléchargement
    } else {
        handle_error("Identifiant étudiant non trouvé.", 404);
    }

} catch (PDOException $e) {
    handle_error("Erreur lors de la génération de l'attestation : " . $e->getMessage());
}