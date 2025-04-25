<?php 
// api/models/Paiement.php

class Paiement {
    public $id;
    public $etudiant_id;
    public $montant;
    public $date_paiement;
    public $methode;

    public function __construct($etudiant_id, $montant, $date_paiement, $methode, $id = null) {
        $this->id = $id;
        $this->etudiant_id = $etudiant_id;
        $this->montant = $montant;
        $this->date_paiement = $date_paiement;
        $this->methode = $methode;
    }
}