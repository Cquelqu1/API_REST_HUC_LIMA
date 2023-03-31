<?php

    include('jwt_utils.php');
    include('ServeurREST.php');
    include('config.php');
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data !== null) {
        if (ValidUser($data['nom'], $data['Mdp'])){
            $nom = $data['nom'];
            $requete = $linkpdo->prepare('SELECT Moderator FROM utilisateur WHERE nom = ?');
            $requete->execute([$nom]);
            $role = $requete->fetch(); 
        
            $headers = array('alg' => 'HS256', 'typ' => 'JWT');
            $payload = array('username'=>$nom,'role'=>$role,'exp'=>(time()+60));
            //Création du jeton
            $jwt = generate_jwt($headers, $payload);

            if ($jwt == FALSE) {
                deliver_response(401, "Authentification echouee, votre login ou mot de passe est incorrect", null);
            } else if (is_jwt_valid($jwt) == TRUE){
                deliver_response(201, "[LOCAL SERVEUR REST] POST REQUEST : Token generate ok", $jwt);

            } else {
                deliver_response(401, "Authentification echouee, erreur dans le token", null);
            }
        }
    }else {
        echo "Connexion non authentifiée acceptée";
    }
?>