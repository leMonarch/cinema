<?php

/**
 * 
 * Classe de l'entité utilisateur
 *
 */

class Utilisateur
{
  private $utilisateur_id;
  private $utilisateur_nom;
  private $utilisateur_prenom;
  private $utilisateur_courriel;
  private $utilisateur_profil;
  private $utilisateur_mdp;


  private $erreurs = array();

  const PROFIL_ADMIN = "administrateur";
  const PROFIL_EDIT  = "editeur";
  const PROFIL_USER  = "utilisateur";

  /**
   * Constructeur de la classe
   * @param array $proprietes, tableau associatif des propriétés 
   *
   */ 
  public function __construct($proprietes = []) {
    $t = array_keys($proprietes);
    foreach ($t as $nom_propriete) {
      $this->__set($nom_propriete, $proprietes[$nom_propriete]);
    } 
  }

  /**
   * Hydratation des propriétés de la classe sans passer par les setters ()
   * quand les données sont sûres car elles proviennent de la base de données 
   * @param array $proprietes, tableau associatif des propriétés 
   * 
   */ 
  public function hydrater($proprietes = []) {
    foreach ($proprietes as $nom_propriete => $val_propriete) {
      $this->$nom_propriete = $val_propriete;
    } 
    return $this;
  }
  
  /**
   * Accesseur magique d'une propriété de l'objet
   * @param string $prop, nom de la propriété
   * @return property value
   * 
   */     
  public function __get($prop) {
    return $this->$prop;
  }

  // Getters explicites nécessaires au moteur de templates TWIG pour accéder aux propriétés private
  public function getUtilisateur_id()     { return $this->utilisateur_id; }
  public function getUtilisateur_nom()    { return $this->utilisateur_nom; }
  public function getUtilisateur_prenom() { return $this->utilisateur_prenom; }
  public function getUtilisateur_courriel() { return $this->utilisateur_courriel; }
  public function getUtilisateur_profil() { return $this->utilisateur_profil; }
  public function getUtilisateur_mdp() { return $this->utilisateur_mdp; }
  public function getErreurs()       { return $this->erreurs; }
  
  /**
   * Mutateur magique qui exécute le mutateur de la propriété en paramètre 
   * @param string $prop, nom de la propriété
   * @param $val, contenu de la propriété à mettre à jour
   * 
   */   
  public function __set($prop, $val) {
    $setProperty = 'set'.ucfirst($prop);
    $this->$setProperty($val);
  }

  /**
   * Mutateur de la propriété utilisateur_id 
   * @param int $utilisateur_id
   * @return $this
   * 
   */    
  public function setUtilisateur_id($utilisateur_id) {
    unset($this->erreurs['utilisateur_id']);
    $regExp = '/^\d+$/';
    if (!preg_match($regExp, $utilisateur_id)) {
      $this->erreurs['utilisateur_id'] = 'Numéro incorrect.';
    }
    $this->utilisateur_id = $utilisateur_id;
    return $this;
  }    

  /**
   * Mutateur de la propriété utilisateur_nom 
   * @param string $utilisateur_nom
   * @return $this
   * 
   */    
  public function setUtilisateur_nom($utilisateur_nom) {
    unset($this->erreurs['utilisateur_nom']);
    $utilisateur_nom = trim($utilisateur_nom);
    $regExp = '/^[a-zÀ-ÖØ-öø-ÿ]{2,}( [a-zÀ-ÖØ-öø-ÿ]{2,})*$/i';
    if (!preg_match($regExp, $utilisateur_nom)) {
      $this->erreurs['utilisateur_nom'] = 'Au moins 2 caractères alphabétiques pour chaque mot.';
    }
    $this->utilisateur_nom = $utilisateur_nom;
    return $this;
  }

  /**
   * Mutateur de la propriété utilisateur_prenom 
   * @param string $utilisateur_prenom
   * @return $this
   * 
   */    
  public function setUtilisateur_prenom($utilisateur_prenom) {
    unset($this->erreurs['utilisateur_prenom']);
    $utilisateur_prenom = trim($utilisateur_prenom);
    $regExp = '/^[a-zÀ-ÖØ-öø-ÿ]{2,}( [a-zÀ-ÖØ-öø-ÿ]{2,})*$/i';
    if (!preg_match($regExp, $utilisateur_prenom)) {
      $this->erreurs['utilisateur_prenom'] = 'Au moins 2 caractères alphabétiques pour chaque mot.';
    }
    $this->utilisateur_prenom = $utilisateur_prenom;
    return $this;
  }

    /**
   * Mutateur de la propriété utilisateur_courriel
   * @param string $utilisateur_courriel
   * @return $this
   * 
   */    
  public function setUtilisateur_courriel($utilisateur_courriel) {
    unset($this->erreurs['utilisateur_courriel']);
    $utilisateur_courriel = trim($utilisateur_courriel);
    if (!filter_var($utilisateur_courriel, FILTER_VALIDATE_EMAIL)) {
      $this->erreurs['utilisateur_courriel'] = 'Au moins 2 caractères alphabétiques pour chaque mot.';/*********************************************************** */
    }
    $this->utilisateur_courriel = $utilisateur_courriel;
    return $this;
  }

    /**
   * Mutateur de la propriété utilisateur_profil
   * @param string $utilisateur_profil
   * @return $this
   * 
   */    
  public function setUtilisateur_profil($utilisateur_profil) {
    unset($this->erreurs['utilisateur_profil']);
    if ($utilisateur_profil != Utilisateur::PROFIL_ADMIN &&
        $utilisateur_profil != Utilisateur::PROFIL_EDIT  && 
        $utilisateur_profil != Utilisateur::PROFIL_USER) {
      $this->erreurs['utilisateur_profil'] = 'Profil incorrect.';
    }
    $this->utilisateur_profil = $utilisateur_profil;
    return $this;
  }

      /**
   * Mutateur de la propriété utilisateur_profil
   * @param string $utilisateur_profil
   * @return $this
   * 
   */    
  public function setUtilisateur_mdp($utilisateur_mdp) {
    unset($this->erreurs['utilisateur_profil']);
    $regExp = '/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[^\w\s]).{8,14}$/i';
    if (!preg_match($regExp, $utilisateur_mdp)) {
      $this->erreurs['utilisateur_mdp'] = 'Entre 8 et 14 caracteres contenant au moins 1 minuscule, 1 majuscule, 1 chiffre et 1 caractere special.';
    }
    $this->utilisateur_mdp = $utilisateur_mdp;
    return $this;
  }


}