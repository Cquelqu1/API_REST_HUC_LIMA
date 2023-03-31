<?php
    
    include('jwt_utils.php');
    include('config.php');
    
     header("Content-Type:application/json");
    
    $jwt = get_bearer_token() ;
    
    $jwtSpec = explode('.', $jwt);
    $payload = base64_decode($jwtSpec[1]);
    
    $role = json_decode($payload)->role;
    $user = json_decode($payload)->username;
    
     /// Identification du type de méthode HTTP envoyée par le client
     $http_method = $_SERVER['REQUEST_METHOD'];
     switch ($http_method){
        /// GET
        case "GET" :
            switch($role) {
                case "1" : 
                        $articles = Get();
                        $articles2 = GetLike($articles);
                    if ($articles2 == null) {
                        deliver_response(404, "Aucun article trouvé", null);
                    } else {
                        /// Envoie de la réponse au Client
                        deliver_response(200, "Requete GET réussie", $articles2);
                    }
                    break;
    
                case "0" : 
                    $articles = Get();
                    $articles2 = GetLike($articles);
                    
                    if ($articles2 == null) {
                        deliver_response(404, "Aucun article trouvé", null);
                    }else {
                        deliver_response(200, "Requete GET réussie", $articles2);
                    }
                    break;
    
                default :
                    $articles = Get();
                    if ($articles == null) {
                        deliver_response(404, "Aucun article trouvé", null);
                    }else {
                        deliver_response(200, "Requete GET réussie", $articles);
                    }
            }
            break;

        /// POST
        case "POST" :
        /// Récupération des données du Client
            $data = file_get_contents('php://input');
            $data2 = json_decode($data, true);
            if ($role == '0') {
                if (!empty($data2['id_Article'])){
                    PostLike($data2['Liker'],$user,$data2['id_Article']);
                    deliver_response(201, "Requete POSTLike réussie", $data2['Liker']);
                }else if (!empty($data2['Contenu'])){
                Post($data2['Contenu'], $user);
                /// Envoie de la réponse au Client
                deliver_response(201, "Requete POST réussie", $data2['Contenu']);
                }else {
                    deliver_response(400, "Erreur requete POST", null);
                }
            }else {
                deliver_response(401, "Vous ne possédé pas les acréditations pour poster un article", null );
            }
            break;
            
        /// PUT
        case "PUT" :
        /// Récupération des données du Client
            $data = file_get_contents('php://input');
            $data2 = json_decode($data, true);
        if ($role == '0') {
            Put($data2['id_articles'], $data2['Contenu']);
        
        /// Envoie de la réponse au Client
            deliver_response(200, "Requete PUT réussie", NULL);
        }
        break;

        /// DELETE
        case "DELETE" :
            switch($role) {
                case "1" : 
                    /// Récupération des données du Client
                    $data = file_get_contents('php://input');
                    $data2 = json_decode($data, true);
                    if (!empty($data2['id_Article'])){
                        $del = Delete($data2['id_Article']);
                    } else {
                        $del = null;
                        deliver_response(404, "Erreur vous n'avez pas fournis d'id", $del);
                    }
                    if ($del == false){
                        deliver_response(404, "Aucun article ne correspond à l'id que vous avez fournis", $del);
                    }else {
                        deliver_response(200, "Requete DELETE réussie", $del);
                    }
                break ;
    
                case "0" :
                    /// Récupération des données du Client
                    $data = file_get_contents('php://input');
                    $data2 = json_decode($data, true);
                    if (!empty($data2['id_Article'])){
                        if (Auteur($data2['id_Article'], $user) == true) {
                            $del = Delete($data2['id_Article']);
                        } else {
                            $del = false ;
                        }
                    } else {
                        $del = null;
                    }
    
                    if ($del === null) {
                        deliver_response(400, "Erreur vous n'avez pas fournis d'id", $del);
                    }else if ($del === false) {
                        deliver_response(403, "Aucun article ne correspond à l'id que vous avez fournis", $del);
                    }else {
                        deliver_response(200, "Requete DELETE réussie", $del);
                    }
                break ;

                default :
                        deliver_response(400, "Vous ne possédé pas les acréditations pour supprimer un article", NULL);
                break ;
            }
            break ;
        default :
            deliver_response(400, "Erreur Requete non reconnue", NULL);
        break ;
        }
    
    ///Retourne l'auteur, le contenu et la dat de publication
    function Get() {
        include('config.php');
        $req = $linkpdo->query('SELECT id_Article, Nom, Contenu, DatePubli FROM article INNER JOIN utilisateur on article.Nom = utilisateur.Nom ORDER BY DatePubli');
        if ($req == false) {
            die('Erreur linkpdo Get');
        }
        while($data = $req->fetch(PDO::FETCH_ASSOC)) {
            $articles[] = $data;
        }
        return $articles ;
    }

    ///Retourne le nombre de like par article
    function GetLike($articles){
        include('config.php');
        foreach ($articles as $a){
            $req = $linkpdo->query('SELECT id_Article, COUNT(Liker) from aimer where id_Article='. $a['id_Article'].';');
            if ($req == false) {
                die('Erreur linkpdo GetLike');
            }
            while($data = $query->fetch(PDO::FETCH_ASSOC)) {
                $likes[] = $data;
            }
        }
        for ($i = 0 ,$maxArticles = count($articles); $i < $maxArticles ; $i++) {
            for ($j = 0 ,$maxLike = count($likes); $j < $maxLike ; $j++) {
                if($articles[$i]['id_Article']==$likes[$j]['id_Article'])
                    array_push($articles[$i],$likes[$j]);
           }
        }
        return $articles ;
    }

    ///Permet de publier un article
    function Post($contenu, $user) {
        include('config.php');
        $req = $linkpdo->prepare('INSERT INTO article(DatePubli, Contenu, Nom) VALUES (:DatePubli, :Contenu, :Nom)');
        if ($req == false) {
            die('Erreur linkpdo Post');
        }
        $req->bindValue(':DatePubli', date("Y-m-d H:i:s"));
        $req->bindValue(':Contenu', $contenu );
        $req->bindValue(':Nom', $user);
        $req->execute();
        if ($req == false) {
            die('Erreur execute Post');
        }
    }

    function PostLike($liker, $user,$id_Article) {
        include('config.php');
        $req = $linkpdo->prepare('INSERT INTO aimer (id_Article, Nom, Liker) VALUES (:id_Article, :Nom, :Liker)');
        if ($req == false) {
            die('Erreur linkpdo PostLike');
        }
        $req->bindValue(':id_Article', $id_Article );
        $req->bindValue(':Nom', $user);
        $req->bindValue(':Liker', $liker);
        $req->execute();
        if ($req == false) {
            die('Erreur execute PostLike');
        }
    }

    function Put($id_Article, $contenu,) {
        include('config.php');
        $req = $linkpdo->prepare('UPDATE article SET Contenu = :Contenu WHERE id_Article = :id_Article');
        if ($req == false) {
            die('Erreur linkpdo Put');
        }
        $req->bindValue(':Contenu', $contenu);
        $req->bindValue(':id_Article', $id_Article);
        $req->execute();
        if ($req == false) {
            die('Erreur execute Put');
        }
    }
    
    function Delete($id_Article) {
        include('config.php');
        $req = $linkpdo->query('DELETE FROM aimer WHERE id_Article ='. $id_Article);
        if ($req == false) {
            die('Erreur execute Delete');
        }
        $req2 = $linkpdo->query('DELETE FROM article WHERE id_Article ='. $id_Article);
        if ($req2 == false) {
            die('Erreur execute Delete');
        }
        return True ;
    }

    function Auteur($id_Article, $user) {
        include('config.php');
        $req = $linkpdo->prepare('SELECT a.Nom FROM utilisateur u JOIN article a on u.Nom = a.Nom WHERE a.id_Article = ?');
        if ($req == false) {
            die('Erreur linkpdo Auteur');
        }
        $req->execute([$id_Article]);
        if ($req == false) {
            die('Erreur execute Auteur');
        }
        $auteur = $req->fetch();
        return ($auteur[0] == $user) ;
    }

    function ValidUser($nom, $userMdp) {
        include 'config.php';
        $req = $linkpdo->prepare('SELECT motDePasse FROM utilisateur WHERE nom = ?');
        if ($req == false) {
            die('Erreur linkpdo');
        }
        $req->execute([$nom]);
        if ($req == false) {
            die('Erreur linkpdo');
        }
        $data = $req->fetch(); 
            return (password_verify($userMdp, $data[0]));
    }
    
    /// Envoi de la réponse au Client
    function deliver_response($status, $status_message, $data){
        /// Paramétrage de l'entête HTTP, suite
        header("HTTP/1.1 $status $status_message");
        /// Paramétrage de la réponse retournée
        $response['status'] = $status;
        $response['status_message'] = $status_message;
        $response['data'] = $data;
        /// Mapping de la réponse au format JSON
        $json_response = json_encode($response);
        echo $json_response;
    }
?>