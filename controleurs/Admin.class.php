<?php

/**
 * Classe Contrôleur des requêtes de l'interface admin
 * 
 */

class Admin extends Routeur {

  private $entite;
  private $action;
  private $categorie;
  private $film_id;
  private $oUtilisateur;
  private $utilisateur_id;

  private $methodes = [
    'film' => [
      'l' => 'listerFilms',
      'a' => 'ajouterFilm',
      'm' => 'modifierFilm',
      's' => 'supprimerFilm'
    ],
    'utilisateur' => [
      'l' => 'listerUtilisateurs',
      'a' => 'ajouterUtilisateur',
      'm' => 'modifierUtilisateur',
      's' => 'supprimerUtilisateur',
      'g' => 'genererMdp',
      'c' => 'connecter',
      'd' => 'deconnecter'
    ]
  ];

  private $classRetour = "fait";
  private $messageRetourAction = "";
  private $messageErreurConnexion = "";


  /**
   * Constructeur qui initialise des propriétés à partir du query string
   * et la propriété oRequetesSQL déclarée dans la classe Routeur
   *
   */
  public function __construct() {
    $this->entite  = $_GET['entite']  ?? 'film';
    $this->action  = $_GET['action']  ?? 'l';
    $this->film_id = $_GET['film_id'] ?? null;
    $this->utilisateur_id = $_GET['utilisateur_id'] ?? null;
    $this->oRequetesSQL = new RequetesSQL;
  }

  /**
   * Gérer l'interface d'administration
   * 
   */  
  public function authentifier() {
    if(isset($_SESSION['oUtilisateur'])){
      $this->oUtilisateur = $_SESSION['oUtilisateur'];
      $this->gererAdmin();
    } else {
      $this->connecter();
    }
  }

  /**
   * 
   * Connecter l'utilisateur
   * 
   */
  public function connecter(){
    if(count($_POST) !== 0){
       $oUtilisateur = $this->oRequetesSQL->connecter($_POST);
       if($oUtilisateur !== false){
        $_SESSION['oUtilisateur'] = $oUtilisateur;
        $this->oUtilisateur = $_SESSION['oUtilisateur'];
         //Si il s'agit d'un Utilisateur on refuse l'acces
         if($this->oUtilisateur->utilisateur_profil == Utilisateur::PROFIL_USER){
          throw new Exception(self::ERROR_NO_ACCESS);
        }
        $this->listerFilms();
        exit;
       } else {
         $this->classRetour = 'erreur';
         $this->messageErreurConnexion = "Courriel ou mot de passe incorrect.";
       }
    } 
    
    new Vue('vAdminUtilisateurConnecter',
            array(
              'titre'               => 'Gestion des utilisateurs',
              'oUtilisateur'       =>  $_POST,
              'classRetour'         => $this->classRetour,  
              'messageErreurConnexion' => $this->messageErreurConnexion
            ),
            'gabarit-admin-min');
  }


  public function gererAdmin(){
    if (isset($this->methodes[$this->entite])) {
      // l'entité existe dans le tableau $this->methodes
      if (isset($this->methodes[$this->entite][$this->action])) {
        // l'action existe dans le tableau $this->methodes pour cette entité

        // la méthode associée à l'action de cette entité peut donc être exécutée
        $methode = $this->methodes[$this->entite][$this->action];
         //Si il s'agit d'un Utilisateur on refuse l'acces
         if($this->oUtilisateur->utilisateur_profil == Utilisateur::PROFIL_USER){
          throw new Exception(self::ERROR_NO_ACCESS);
        }
        $this->$methode();

      } else {
        throw new Exception("L'action $this->action de l'entité $this->entite n'existe pas.");
      }

    } else {
      throw new Exception("L'entité $this->entite n'existe pas.");
    }
  }

  public function deconnecter(){
    unset($_SESSION['oUtilisateur']);
    $this->connecter();
  }
  /**
   * Lister les films
   * 
   */
  public function listerFilms() {
    $oFilms = $this->oRequetesSQL->getFilms('admin');

    new Vue('vAdminFilms',
            array(
              'titre'               => 'Gestion des films',
              'oUtilisateur'        => $this->oUtilisateur,
              'oFilms'              => $oFilms,
              'classRetour'         => $this->classRetour,  
              'messageRetourAction' => $this->messageRetourAction
            ),
            'gabarit-admin');
  }

  /**
   * Ajouter un film
   * 
   */
  public function ajouterFilm() {
    
    if (count($_POST) !== 0) {
      // retour de la saisie du formulaire
      $oFilm = new Film($_POST);
      // création d'un objet Film pour alimenter et contrôler tous les champs saisis dans les propriétés correspondantes  
      $erreurs = $oFilm->erreurs;  // récupération de la propriété "tableau des erreurs issues des contrôles des setters"
      if (count($erreurs) === 0) {
        $film_id = $this->oRequetesSQL->ajouterFilm([
          'film_titre'        => $oFilm->film_titre,
          'film_duree'        => $oFilm->film_duree,
          'film_annee_sortie' => $oFilm->film_annee_sortie,
          'film_resume'       => $oFilm->film_resume,
          'film_statut'       => $oFilm->film_statut,
          'film_genre_id'     => $oFilm->film_genre_id
        ]);
        if ( $film_id > 0) {
          $this->messageRetourAction = "Ajout du film numéro $film_id effectué";
        } else {
          $this->classRetour = 'erreur';
          $this->messageRetourAction = 'Ajout du film  effectué';
        }
        // retour sur la page de liste avec ou sans erreur
        $this->listerFilms();
        exit;
      }
    } else {
      // initialisations pour le premier chargement du formulaire
      $erreurs = [];
      $oFilm = new Film;
    }

    // récupération de tous les genres pour alimenter la select dans le formulaire
    $oGenres = $this->oRequetesSQL->getGenres();

    new Vue('vAdminFilmAjouter',
            array(
              'titre'         => 'Ajouter un film',
              'oUtilisateur'  => $this->oUtilisateur,
              'oFilm'         => $oFilm,
              'oGenres'       => $oGenres
            ),
            'gabarit-admin');
  }


  /**
   * Modifier un film
   * 
   */
  public function modifierFilm() {

    // echo $this->film_id.'ligne 133';

    if (!preg_match('/^\d+$/', $this->film_id))
      throw new Exception("Numéro du film non renseigné pour une modification");

    if (count($_POST) !== 0) {
      $_POST = $_POST + ['film_affiche' => "a-$this->film_id.jpg"];
      
      // retour de la saisie du formulaire  
      $oFilm = new Film($_POST);   // création d'un objet Film pour alimenter et contrôler tous les champs saisis dans les propriétés correspondantes  
      $erreurs = $oFilm->erreurs;  // récupération de la propriété "tableau des erreurs issues des contrôles des setters"
      if (count($erreurs) === 0) {
        if($this->oRequetesSQL->modifierFilm([
          'film_id'           => $oFilm->film_id, 
          'film_titre'        => $oFilm->film_titre,
          'film_duree'        => $oFilm->film_duree,
          'film_annee_sortie' => $oFilm->film_annee_sortie,
          'film_resume'       => $oFilm->film_resume,
          'film_statut'       => $oFilm->film_statut,
          'film_affiche'      => $oFilm->film_affiche,
          'film_genre_id'     => $oFilm->film_genre_id
        ])) {
          $this->messageRetourAction = "Modification du film numéro $this->film_id effectuée.";
        } else {
          $this->classRetour = "erreur";
          $this->messageRetourAction = "modification du film numéro $this->film_id non effectuée.";
        }
        // retour sur la page de liste avec ou sans erreur
        $this->listerFilms();
        exit;
      }
    } else {
      // initialisations pour le premier chargement du formulaire avec les données du film à modifier
      $oFilm = $this->oRequetesSQL->getFilm($this->film_id);
    }

    // récupération de tous les genres pour alimenter la select dans le formulaire
    $oGenres = $this->oRequetesSQL->getGenres();
    
    new Vue('vAdminFilmModifier',
            array(
              'titre'   => "Modifier le film numéro $this->film_id",
              'oUtilisateur'  => $this->oUtilisateur,
              'oFilm'   => $oFilm,
              'oGenres' => $oGenres
            ),
            'gabarit-admin');
  }


    /**
   * Supprimer un film
   * 
   */
  public function supprimerFilm() {

    if (!preg_match('/^\d+$/', $this->film_id))
      throw new Exception("Numéro de film incorrect pour une suppression.");

    if ($this->oRequetesSQL->supprimerFilm($this->film_id)) {
      $this->messageRetourAction = "Suppression du film numéro $this->film_id effectuée.";
    } else {
      $this->classRetour = "erreur";
      $this->messageRetourAction = "Suppression du film numéro $this->film_id non effectuée.";
    }
    // retour sur la page de liste avec ou sans erreur
    $this->listerFilms();
  }

  
    /**
   * Lister les utilisateurs
   * 
   */
  public function listerUtilisateurs() {
    echo "ici ligne 269 Admin";
    $oUtilisateurs = $this->oRequetesSQL->getUtilisateurs();
    echo "ici ligne 271 Admin";
    new Vue('vAdminUsers',
            array(
              'titre'               => 'Gestion des utilisateurs',
              'oUtilisateur'        => $this->oUtilisateur,
              'oUtilisateurs'       => $oUtilisateurs,
              'classRetour'         => $this->classRetour,  
              'messageRetourAction' => $this->messageRetourAction
            ),
            'gabarit-admin');
  }


  /**
   * Ajouter un utilisateur
   * 
   */
  public function ajouterUtilisateur() {
    
    if (count($_POST) !== 0) {
      $_POST = $_POST + ['utilisateur_mdp' => (new RequetesSQL)->genererMdp()];
    if(!(new RequetesSQL)->courrielDejaExistant($_POST)){
      // retour de la saisie du formulaire
      $oUser = new Utilisateur($_POST);
      // création d'un objet Film pour alimenter et contrôler tous les champs saisis dans les propriétés correspondantes  
      $erreurs = $oUser->erreurs;  // récupération de la propriété "tableau des erreurs issues des contrôles des setters"
      if (count($erreurs) === 0) {
        $user_id = $this->oRequetesSQL->ajouterUtilisateur([
          'utilisateur_nom'         => $oUser->utilisateur_nom,
          'utilisateur_prenom'      => $oUser->utilisateur_prenom,
          'utilisateur_courriel'    => $oUser->utilisateur_courriel,
          'utilisateur_profil'      => $oUser->utilisateur_profil,
          'utilisateur_mdp'         => $oUser->utilisateur_mdp
        ]);
        if ( $user_id > 0) {
          $this->messageRetourAction = "Ajout de l'utilisateur numéro $user_id effectué";
        } else {
          $this->classRetour = 'erreur';
          $this->messageRetourAction = 'Ajout de l\'utilisateur non effectué';
        }

      }
    } else {
      $this->classRetour = 'erreur';
      $this->messageRetourAction = 'Mot de passe existant retenter l\'ajout.';
    }
    // retour sur la page de liste avec ou sans erreur
    $this->listerUtilisateurs();
    exit;
    } else {
      // initialisations pour le premier chargement du formulaire
      $erreurs = [];
      $oUser = new Utilisateur;
    }

    new Vue('vAdminUserAjouter',
            array(
              'titre'   => 'Ajouter un utilisateur',
              'oUtilisateur'  => $this->oUtilisateur
            ),
            'gabarit-admin');
  }
  
/**
   * Modifier un utilisateur
   * 
   */
  public function modifierUtilisateur() {

    if (!preg_match('/^\d+$/', $this->utilisateur_id))
      throw new Exception("Numéro de l'utilisateur non renseigné pour une modification");

    if (count($_POST) !== 0) {
      // retour de la saisie du formulaire  
      $oUser = new Utilisateur($_POST);   // création d'un objet Film pour alimenter et contrôler tous les champs saisis dans les propriétés correspondantes  
      $erreurs = $oUser->erreurs;  // récupération de la propriété "tableau des erreurs issues des contrôles des setters"
      if (count($erreurs) === 0) {
        if($this->oRequetesSQL->modifierUtilisateur([
          'utilisateur_id'           => $oUser->utilisateur_id, 
          'utilisateur_nom'          => $oUser->utilisateur_nom,
          'utilisateur_prenom'       => $oUser->utilisateur_prenom,
          'utilisateur_courriel'     => $oUser->utilisateur_courriel
        ])) {
          $this->messageRetourAction = "Modification de l'utilisateur numéro $this->utilisateur_id effectuée.";
        } else {
          $this->classRetour = "erreur";
          $this->messageRetourAction = "modification du l'utilisateur numéro $this->utilisateur_id non effectuée.";
        }
        // retour sur la page de liste avec ou sans erreur
        $this->listerUtilisateurs();
        exit;
      }
    } else {
      // initialisations pour le premier chargement du formulaire avec les données du film à modifier
      $oUser = $this->oRequetesSQL->getUtilisateur($this->utilisateur_id);
    }

    new Vue('vAdminUserModif',
            array(
              'titre'   => "Modifier l'utilisateur numéro $this->utilisateur_id",
              'oUtilisateur'  => $this->oUtilisateur
            ),
            'gabarit-admin');
  }



  /**
   * Supprimer un film
   * 
   */
  public function supprimerUtilisateur() {

    if (!preg_match('/^\d+$/', $this->utilisateur_id))
      throw new Exception("Numéro d'utilisateur incorrect pour une suppression.");

    if ($this->oRequetesSQL->supprimerUtilisateur($this->utilisateur_id)) {
      $this->messageRetourAction = "Suppression d'utilisateur numéro $this->utilisateur_id effectuée.";
    } else {
      $this->classRetour = "erreur";
      $this->messageRetourAction = "Suppression d'utilisateur numéro $this->utilisateur_id non effectuée.";
    }
    // retour sur la page de liste avec ou sans erreur
    $this->listerUtilisateurs();
  }

/**
   * Modifier un utilisateur
   * 
   */
  public function genererMdp() {

    if (!preg_match('/^\d+$/', $this->utilisateur_id))
      throw new Exception("Numéro de l'utilisateur non renseigné pour une modification");

    if (count($_POST) !== 0) {
      // retour de la saisie du formulaire  
      $oUser = new Utilisateur(['utilisateur_mdp' => (new RequetesSQL)->genererMdp()]);   // création d'un objet utilisateur pour conserver son mot de passe 
      $erreurs = $oUser->erreurs;  // récupération de la propriété "tableau des erreurs issues des contrôles des setters"
      if (count($erreurs) === 0) {
        if($this->oRequetesSQL->modifierUtilisateur([
          'utilisateur_mdp'           => $oUser->utilisateur_mdp, 
        ])) {
          $this->messageRetourAction = "Modification du mot de passe de l'utilisateur numéro $this->utilisateur_id effectuée.";
        } else {
          $this->classRetour = "erreur";
          $this->messageRetourAction = "Modification du mot de passe de l'utilisateur numéro $this->utilisateur_id non effectuée.";
        }
        // retour sur la page de liste avec ou sans erreur
        $this->listerUtilisateurs();
        exit;
      }
    }

    new Vue('vAdminUser',
            array(
              'titre'   => "Modifier l'utilisateur numéro $this->utilisateur_id",
              'oUtilisateur'   => $this->oUtilisateur
            ),
            'gabarit-admin');
  }





}