<?php 
// api/models/Dossier.php

class Dossier {
    public $id;
    public $numero;
    public $date_creation;
    public $etat;

    public function __construct($numero, $date_creation, $etat, $id = null) {
        $this->id = $id;
        $this->numero = $numero;
        $this->date_creation = $date_creation;
        $this->etat = $etat;
    }
}



