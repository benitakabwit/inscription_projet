<?php 
// api/models/Resultat.php

class Resultat {
    public $id;
    public $etudiant_id;
    public $note;
    public $mention;

    public function __construct($etudiant_id, $note, $mention, $id = null) {
        $this->id = $id;
        $this->etudiant_id = $etudiant_id;
        $this->note = $note;
        $this->mention = $mention;
    }
}