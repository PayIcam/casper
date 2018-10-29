<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Slim
require 'vendor/autoload.php';
use \Payutc\Casper\Config;
use \Payutc\Casper\JsonClientFactory;
use \Payutc\Casper\JsonClientMiddleware;

// Load configuration
require "config.inc.php";
Config::initFromArray($_CONFIG);

// Settings for cookies
$sessionPath = parse_url(Config::get("casper_url"), PHP_URL_PATH);
session_set_cookie_params(0, $sessionPath);
session_start();

// Slim initialization
$app = new \Slim\Slim(Config::get('slim_config'));

// This middleware loads all our json clients
$app->add(new JsonClientMiddleware);

// A few helpers to handle amounts in cents
function format_amount($val) {
	return number_format($val/100, 2, ',', ' ');
}

function parse_user_amount($val) {
    $amount = str_replace(',','.', $val);
    return $amount*100;
}

// --- Coeur de casper

// Page principale
$app->get('/', function() use($app) {
    $app->render('header.php', array(
        "title" => Config::get("title"),
        "loggedin" => true
    ));

    // The array that will be sent to the template
    $pageData = array();

    $pageData["canReload"] = true;
    $pageData["canReloadPapercut"] = true;
    try {
        $reloadInfo = JsonClientFactory::getInstance()->getClient("RELOAD")->info();
        $pageData["maxReload"] = $reloadInfo->max_reload;
        $pageData["minReload"] = $reloadInfo->min;
    }
    catch(\JsonClient\JsonException $e){
        $pageData["canReload"] = false;
        $pageData["cannotReloadMessage"] = $e->getMessage();
    }

    $pageData["isBlocked"] = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->isBlockedMe();

    $resultat = JsonClientFactory::getInstance()->getClient("RELOADPAPERCUT")->getSoldePaperCut();
    $pageData["reloadsPapercut"] = json_decode($resultat, 1);


    $account = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->historique();
    $pageData["historique"] = $account->historique;

    $env = $app->environment();

    $pageData["userDetails"] = array(
        "firstname" => $env["user_data"]->firstname,
        "lastname" => $env["user_data"]->lastname,
        "credit" => $account->credit,
        "credit_ecocup" => $account->credit_ecocup
    );

    $app->render('main.php', $pageData);

    $app->render('footer.php');
})->name('home');

// Blocage du compte
$app->get('/block', function() use ($app) {
    try {
        JsonClientFactory::getInstance()->getClient("MYACCOUNT")->setSelfBlock(array(
            "blocage" => true
        ));
    }
    catch(\JsonClient\JsonException $e){
        $app->flash('block_erreur', $e->getMessage());
        $app->getLog()->error("Block failed: ".$e->getMessage());
    }
    $app->response()->redirect($app->urlFor('home'));
});

// Déblocage du compte
$app->get('/unblock', function() use ($app) {
    try {
        JsonClientFactory::getInstance()->getClient("MYACCOUNT")->setSelfBlock(array(
            "blocage" => false
        ));
    }
    catch(\JsonClient\JsonException $e){
        $app->flash('block_erreur', $e->getMessage());
        $app->getLog()->error("Unblock failed: ".$e->getMessage());
    }
    $app->response()->redirect($app->urlFor('home'));
});

// Autocomplete du virement
$app->get('/ajax', function() use ($app) {
    if(!empty($_GET["q"])) {
        $search = JsonClientFactory::getInstance()->getClient("RELOAD")->userAutocomplete(array(
            "queryString" => $_GET["q"]
        ));

        echo json_encode($search);
    }
});

// Départ vers le rechargement
$app->post('/reload', function() use ($app) {
    if(empty($_POST["montant"])) {
        $app->flash('error_reload', "Saisissez un montant");
        $app->response()->redirect($app->urlFor('home'));
    }

    $amount = parse_user_amount($_POST['montant']);

    try {
        $reloadUrl = JsonClientFactory::getInstance()->getClient("RELOAD")->reload(array(
            "amount" => $amount,
            "callbackUrl" => Config::get("casper_url")
        ));
        $app->redirect($reloadUrl);
    }
    catch(\JsonClient\JsonException $e){
        $app->flash('reload_erreur', $e->getMessage());
        $app->flash('reload_value', $amount/100);
        $app->getLog()->error("Reload failed: ".$e->getMessage());
        $app->response()->redirect($app->urlFor('home'));
    }
});

$app->post('/reload_papercut', function() use ($app) {
    if(empty($_POST["montant"])) {
        $app->flash('error_reload', "Saisissez un montant");
        $app->response()->redirect($app->urlFor('home'));
    }

    $status = JsonClientFactory::getInstance()->getClient("RELOADPAPERCUT")->getStatus();
    $isCotisant = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->isCotisant();
    $account = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->historique();
    $account->credit;

    $amount = parse_user_amount($_POST['montant']);

    if(!$isCotisant) {
        $app->flash('reloadPaperCut_erreur', "Les non cotisants ne peuvent pas utiliser la fonction reloadPapercut.");
    } else if($amount < 0) {
        $app->flash('reloadPaperCut_erreur', "Tu ne peux pas faire un virement négatif (bien essayé)");
    } else if($amount == 0) {
        $app->flash('reloadPaperCut_erreur', "Pas de montant saisi");
    } else if($account->credit < $amount) {
        $app->flash('reloadPaperCut_erreur', "Tu n'as pas assez d'argent pour réaliser ce virement");
    } else {
        // Connect the application if required
        if(empty($status->application)){
            $app->getLog()->debug("No app logged in for WEBSALECONFIRM, calling loginApp");
            try {
                JsonClientFactory::getInstance()->getClient("RELOADPAPERCUT")->loginApp(array(
                    "key" => Config::get("application_key")
                ));
            } catch (\JsonClient\JsonException $e) {
                $app->getLog()->error("Application login error for RELOADPAPERCUT: ".$e->getMessage());
                throw $e;
            }
        }

        try {
            $resultat = JsonClientFactory::getInstance()->getClient("RELOADPAPERCUT")->reload_papercut(array(
                "amount" => $amount*1
            ));
            if ($resultat == $amount)
                $app->flash('reloadPaperCut_ok', 'Le virement de '.format_amount($resultat).' € a réussi.');
            else
                $app->flash('reloadPaperCut_erreur', "qqch de louche s'est passé: ".$resultat);
        } catch(\JsonClient\JsonException $e) {
            echo $e->getMessage();
            $app->flash('reloadPaperCut_erreur', $e->getMessage());
            $app->flash('reloadPaperCut_value', abs($amount/100));
        }
    }

    $app->response()->redirect($app->urlFor('home'));
});

// Virement à un ami
$app->post('/virement', function() use ($app) {
    // Récupèration du montant en cents
    $montant = parse_user_amount($_POST['montant']);

    try {
        $virement = JsonClientFactory::getInstance()->getClient("TRANSFER")->transfer(array(
            "amount" => $montant,
            "userID" => $_POST['userId'],
            "message" => $_POST['message']
        ));

        $sender = $app->environment()['user_data'];
        $receiver_email = JsonClientFactory::getInstance()->getClient("TRANSFER")->getUserEmail(array("usr_id" => $_POST['userId']));


        $mail = new PHPMailer;
        $mailer_config = Config::get('PHPMailer');

        $mail->isSMTP();                         // Set mailer to use SMTP
        $mail->Host = $mailer_config['Host'];             // Specify main and backup SMTP servers
        $mail->SMTPAuth = $mailer_config['SMTPAuth'];     // Enable SMTP authentication
        $mail->Username = $mailer_config['Username'];     // SMTP username
        $mail->Password = $mailer_config['Password'];     // SMTP password
        $mail->SMTPSecure = $mailer_config['SMTPSecure']; // Enable TLS encryption, `ssl` also accepted
        $mail->Port = $mailer_config['Port'];             // TCP port to connect to
        $mail->Encoding = 'base64';
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('contact.payicam@gmail.com', 'Contact PayIcam');

        $mail->addAddress($receiver_email);

        $mail->isHTML(true);

        $subject = 'Virement reçu de la part de '.$sender->firstname . ' ' . $sender->lastname;
        $message = empty($_POST['message']) ? '' : 'Son message : ' . $_POST['message'];
        $mail->Subject = $subject;
        $mail->Body    = '<html>
            <head>
                <meta charset="utf-8">
                <title>'.$subject.'</title>
            </head>
            <body>
                <h1>'.$subject.'</h1>
                <p>Tu as reçu ' . format_amount($montant) . '€ de la part de ' . $sender->firstname . ' ' . $sender->lastname . '</p>
                <p>' . $message . '</p>
                <p>A bientôt,</p>
                <p><em>PayIcam</em></p>
            </body>
        </html>
        ';

        $mail->AltBody = $subject."\n".
            'Tu as reçu ' . format_amount($montant) . '€ de la part de ' . $sender->firstname . ' ' . $sender->lastname ."\n".
            $message .
            'A bientôt,'."\n".
            "PayIcam";

        if(!$mail->send()) {
            $app->flash('virement_ok', 'Le virement de '.format_amount($montant).' € a réussi. Le mail automatique a toutefois échoué... Prévenez votre ami tout seul, et signalez nous le bug :)');
        }

        $app->flash('virement_ok', 'Le virement de '.format_amount($montant).' € a réussi.');
    }
    catch(\JsonClient\JsonException $e){
        $app->flash('virement_erreur', $e->getMessage());
    }

    // Retour vers la page d'accueil
    $app->response()->redirect($app->urlFor('home'));
});

// --- Enregistrement

// Affichage de la charte
$app->get('/register', function() use ($app) {
    $app->render('header.php', array(
        "title" => Config::get("title"),
        "loggedin" => true
    ));

    $app->render('register.php', array("form" => true));

    $app->render('footer.php');
})->name('register');

// Enregistrement après validation de la charte
$app->post('/register', function() use ($app) {
    try {
        // Appel serveur
        $result = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->register();

        // Si ok, go vers la page d'accueil de payicam
        $app->redirect("../");
        // $app->redirect($app->urlFor('home'));
    }
    catch(\JsonClient\JsonException $e){
        // Si on a une erreur on l'affiche
        $app->flash('register_erreur', $e->getMessage());

        // On n'a pas réussi à s'enregistrer, retour vers la charte
        $app->redirect($app->urlFor('register'));
    }
});

// --- Websale confirmation gateway

// Initial access
$app->get('/validation', function() use ($app) {
    // If no transaction data, go home
    if(empty($_GET['tra_id']) || empty($_GET['token'])){
        $app->getLog()->error("No transaction data recieved");
        $app->redirect($app->urlFor('home'));
    }

    // Get environment
    $env = $app->environment();

    // Get data the transaction data
    try {
        $transactionData = JsonClientFactory::getInstance()->getClient("WEBSALECONFIRM")->getTransactionInfo(array(
            'tra_id' => $_GET['tra_id'],
            'token' => $_GET['token']
        ));

        // If this transaction is not waiting
        if($transactionData->status != 'W'){
            throw new \Exception("Cette transaction n'est pas en attente.");
        }
    }
    catch(\Exception $e){
        $app->getLog()->error("Cannot get transaction ".$_GET['tra_id']." with token ".$_GET['token'].": ".$e->getMessage());

        $app->render('header.php', array("title" => Config::get("title", "payutc"), "loggedin" => false));
        $app->render('error.php', array('login_erreur' => "Impossible de récupérer la transaction"));
        $app->render('footer.php');
        $app->stop();
    }

    $app->render('header.php', array(
        "title" => Config::get("title"),
        "loggedin" => $env["loggedin"]
    ));

    $products = array();
    foreach($transactionData->products as $product) {
        $products[$product->id] = $product;
    }

    if($env["loggedin"]){
        $account = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->historique();

        $canReload = true;
        $maxReload = 10000-$account->credit;
        $minReload = 1000;
        $cannotReloadMessage = "";
        try {
            $reloadInfo = JsonClientFactory::getInstance()->getClient("RELOAD")->info();
            $maxReload = $reloadInfo->max_reload;
            $minReload = $reloadInfo->min;
        }
        catch(\JsonClient\JsonException $e){
            $canReload = false;
            $cannotReloadMessage = $e->getMessage();
        }

        $app->render('websale_payutc.php', array(
            "purchases" => $transactionData->purchases,
            "products" => $products,
            "total" => $transactionData->total,
            "solde" => $account->credit,
            "maxReload" => $maxReload,
            "minReload" => $minReload,
            "canReload" => $canReload,
            "cannotReloadMessage" => $cannotReloadMessage,
            "fundation" => $transactionData->fun_name,
            "firstname" => $env['user_data']->firstname,
            "logoutUrl" => $app->urlFor('logout')."?tra_id=".$_GET['tra_id']."&token=".$_GET['token']
        ));
    }
    else {
        $app->render('websale.php', array(
            "purchases" => $transactionData->purchases,
            "products" => $products,
            "total" => $transactionData->total,
            "fundation" => $transactionData->fun_name,
            "tra_id" => $_GET['tra_id'],
            "token" => $_GET['token']
        ));
    }

    $app->render('footer.php');
});

// Submit of payment form
$app->post('/validation', function() use ($app) {
    // If no transaction data, go home
    if(empty($_POST['tra_id']) || empty($_POST['token']) || empty($_POST['method'])){
        $app->getLog()->error("No transaction data recieved");
        $app->redirect($app->urlFor('home'));
    }

    // Get environment
    $env = $app->environment();

    // Get data the transaction data
    try {
        if($_POST['method'] == "direct"){
            if(empty($_POST['cgu'])){
                throw new \Exception("Vous devez accepter les CGU de PayIcam pour continuer");
            }

            $nextUrl = JsonClientFactory::getInstance()->getClient("WEBSALECONFIRM")->doTransaction(array(
                'tra_id' => $_POST['tra_id'],
                'token' => $_POST['token'],
                'montant_reload' => 0
            ));
        }
        else if($_POST['method'] == "payutc" && $env["loggedin"]){
            $montant = !empty($_POST['montant']) ? parse_user_amount($_POST['montant']) : 0;
            $nextUrl = JsonClientFactory::getInstance()->getClient("WEBSALECONFIRM")->doTransaction(array(
                'tra_id' => $_POST['tra_id'],
                'token' => $_POST['token'],
                'montant_reload' => $montant
            ));
        }
        else {
            throw new \Exception("Méthode de paiement non reconnue");
        }
    }
    catch(\Exception $e){
        $app->getLog()->error("Cannot do transaction ".$_POST['tra_id']." with token ".$_POST['token'].": ".$e->getType(). " -  ".$e->getMessage());

        $app->render('header.php', array("title" => Config::get("title", "payutc"), "loggedin" => false));
        $app->render('error.php', array('login_erreur' => "Impossible de valider la transaction"));
        $app->render('footer.php');
        $app->stop();
    }

    $app->redirect($nextUrl);
});

// Return from payline
$app->get('/validationReturn', function() use ($app) {
    // Get data the transaction data
    try {
        // If no token, fail
        if(empty($_GET['token'])){
            $app->getLog()->error("No token recieved");
            throw new \Exception("No token received");
        }

        $nextUrl = JsonClientFactory::getInstance()->getClient("WEBSALECONFIRM")->notificationPayline(array(
            'token_payline' => $_GET['token']
        ));
    }
    catch(\Exception $e){
        $app->getLog()->error("Cannot do notification with token ".$_GET['token'].": ".$e->getMessage());

        $app->render('header.php', array("title" => Config::get("title", "payutc"), "loggedin" => false));
        $app->render('error.php', array('login_erreur' => "Impossible de notifier la transaction"));
        $app->render('footer.php');
        $app->stop();
    }

    $app->redirect($nextUrl);
});

// --- CAS
$app->get('/login', function() use ($app) {
    // Si pas de ticket, c'est une invitation à se connecter
    if(empty($_GET["ticket"])) {
        $app->getLog()->debug("No CAS ticket, unsetting cookies and redirecting to CAS");
        // On jette les cookies actuels
        JsonClientFactory::getInstance()->destroyCookie();

        // If we have transaction parameters, save them
        if(!empty($_GET['tra_id']) && !empty($_GET['token'])){
            $app->getLog()->debug("Setting login redirect URL to validation");

            $_SESSION['login_redirect'] = "validation?tra_id=".$_GET['tra_id']."&token=".$_GET['token'];
        }

        // Redirection vers le CAS
        $app->redirect(JsonClientFactory::getInstance()->getClient("MYACCOUNT")->getCasUrl()."login?service=".Config::get("casper_url").'login');
    } else {
        // Connexion au serveur avec le ticket CAS
        try {
            $app->getLog()->debug("Trying loginCas");

            $result = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->loginCas(array(
                "ticket" => $_GET["ticket"],
                "service" => Config::get("casper_url").'login'
            ));
        } catch (\JsonClient\JsonException $e) {
            // Si l'utilisateur n'existe pas, go inscription
            if($e->getType() == "Payutc\Exception\UserNotFound"){
                // On doit garder le cookie car le serveur garde le login de son côté
                JsonClientFactory::getInstance()->setCookie(JsonClientFactory::getInstance()->getClient("MYACCOUNT")->cookie);

                // Redirection vers la charte
                $app->redirect($app->urlFor('register'));
            }

            $app->getLog()->warn("Error with CAS ticket ".$_GET["ticket"].": ".$e->getMessage());

            // Affichage d'une page avec juste l'erreur
            $app->render('header.php', array("title" => Config::get("title", "payutc"), "loggedin"=>false));
            $app->render('error.php', array('login_erreur' => 'Erreur de login CAS<br /><a href="'.$app->urlFor('login').'">Réessayer</a>'));
            $app->render('footer.php');
            $app->stop();
        }

        // On stocke le cookie
        JsonClientFactory::getInstance()->setCookie(JsonClientFactory::getInstance()->getClient("MYACCOUNT")->cookie);

        // Go vers la page d'accueil
        if(!empty($_SESSION['login_redirect'])){
            $url = $_SESSION['login_redirect'];
            unset($_SESSION['login_redirect']);

            $app->getLog()->debug("Redirect after login: $url");

            $app->redirect($url);
        }
        else {
            $app->redirect($app->urlFor('home'));
        }
    }
})->name('login');

$app->get('/logout', function() use ($app) {
    // On clot la session avec le serveur
    try {
        JsonClientFactory::getInstance()->getClient("MYACCOUNT")->logout();
    }
    catch (\JsonClient\JsonException $e){
        // No worries, we'll just continue
    }

    // Throw our cookies away
    JsonClientFactory::getInstance()->destroyCookie();

    $logoutUrl = JsonClientFactory::getInstance()->getClient("MYACCOUNT")->getCasUrl()."/logout";

    // If we have transaction parameters, save them
    if(!empty($_GET['tra_id']) && !empty($_GET['token'])){
        $app->getLog()->debug("Setting logout redirect URL to validation");

        $logoutUrl .= "?url=".urlencode(Config::get("casper_url")."validation?tra_id=".$_GET['tra_id']."&token=".$_GET['token']);
    }

    // Logout from CAS
    $app->redirect($logoutUrl);
})->name('logout');

// Affichage de la charte (Pour le lien CGU du paiement en ligne)
$app->get('/cgu', function() use ($app) {
    $app->render('header.php', array(
        "title" => Config::get("title"),
        "loggedin" => false
    ));

    $app->render('register.php', array("form" => false));

    $app->render('footer.php');
})->name('cgu');

$app->run();
