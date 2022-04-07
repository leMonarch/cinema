<?php

/**
 * Classe Routeur
 * analyse l'uri et exécute la méthode associée  
 *
 */

class Routeur {

  private $routes = [
    // uri, classe, méthode
    // --------------------
    ["admin",         "Admin",    "authentifier"],
    ["",              "Frontend", "listerAlaffiche"],
    ["prochainement", "Frontend", "listerProchainement"],
    ["film",          "Frontend", "voirFilm"]
  ];

  protected $oRequetesSQL; // objet RequetesSQL utilisé par tous les contrôleurs

  const BASE_URI = "\/"; // chemin du dossier racine pour le serveur lancé par VS Code, "\/31B\/TP2\/" par exemple si le dossier racine de l'application est /31B/TP2/ (sous htdocs de XAMPP) 

  const ERROR_NOT_FOUND  = "HTTP 404";
  const ERROR_NO_ACCESS  = "HTTP 403";
  
  /**
   * Constructeur qui valide l'URI
   * et instancie la méthode du contrôleur correspondante
   *
   */
  public function __construct() {
    try {

      // extraction de la partie centrale de l'uri, sans le chemin du dossier racine au début et sans le query string à la fin 
      $uri = preg_replace("/^".self::BASE_URI."([^?]*)\??.*$/", "$1", $_SERVER["REQUEST_URI"]);

      // balayage du tableau des routes
      foreach ($this->routes as $route) {

        $routeUri     = $route[0];
        $routeClasse  = $route[1];
        $routeMethode = $route[2];
        
        if ($routeUri ===  $uri) {
          // on exécute la méthode associée à l'uri
          $oRouteClasse = new $routeClasse;
          $oRouteClasse->$routeMethode();  
          exit;
        }
      }
      // aucune route ne correspond à l'uri
      throw new \Exception(self::ERROR_NOT_FOUND);
    }
    catch (\Error | \Exception $e) {
      $this->erreur($e->getMessage());
    }
  }

  /**
   * Méthode qui envoie un compte-rendu d'erreur
   * @param string $erreur, message d'erreur ou code d'erreur HTTP 
   *
   */
  public static function erreur($erreur) {
    $message = '';
    if ($erreur == self::ERROR_NOT_FOUND) {
      header('HTTP/1.1 404 Not Found');
      new Vue('vErreur404', [], 'gabarit-erreur');
    } else if($erreur == self::ERROR_NO_ACCESS){
      header('HTTP/1.1 403 No Access');
      new Vue('vErreur403', ['message' => $message], 'gabarit-erreur');
    } else {
      header('HTTP/1.1 500 Internal Server Error');
      echo "Routeur ligne 75";
      $message = $erreur;
      new Vue("vErreur500", array('message' => $message), 'gabarit-erreur');
    }
    exit;
  }
}