<?php
    ///Connexion au serveur MySQL
    $server = 'localhost';
    $db = 'projet_r401';
    $login = 'root';
    try {
        $linkpdo = new PDO("mysql:host=$server;dbname=$db", $login);
    }
    catch (Exception $e) {
        die('Erreur : ' . $e -> getMessage());
        }
?>                