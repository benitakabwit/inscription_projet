<?php
// api/models/Etudiant.php

class Etudiant {
    public $id;
    public $nom;
    public $prenom;
    public $contact;
    public $email;

    public function __construct($nom, $prenom, $contact, $email, $id = null) {
        $this->id = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->contact = $contact;
        $this->email = $email;
    }
}

// Créez des classes similaires pour Dossier, Resultat, Paiement