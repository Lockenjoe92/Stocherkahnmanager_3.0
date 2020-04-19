<?php

function login_formular($Parser, $SessionMessage){

    $HTMLform = row_builder('<h1>Login zum Koordinationstool</h1>', '', 'hide-on-small-and-down');
    $HTMLform .= row_builder('<h3>Login zum Koordinationstool</h3>', '', 'hide-on-med-and-up');


    $HTMLform .= "<div class='row'>";
    $HTMLform .= "<div class='input-field'>";
    $HTMLform .= "<input id='login_mail' type='email' name='mail' value='".$Parser['mail']."'>";
    $HTMLform .= "<label for='login_mail'>E-Mail</label>";
    $HTMLform .= "</div>";
    $HTMLform .= "</div>";

    $HTMLform .= "<div class='row'>";
    $HTMLform .= "<div class='input-field'>";
    $HTMLform .= "<input id='login_pswd' type='password' name='pass'>";
    $HTMLform .= "<label for='login_pswd'>Passwort</label>";
    $HTMLform .= "</div>";
    $HTMLform .= "</div>";

    $RegisterActive = lade_xml_einstellung('register_active');

    $HTMLBigscreenButtons = form_button_builder('submit', 'Einloggen', 'submit', 'send', 'col s3');
    if ($RegisterActive == "true") {
        $HTMLBigscreenButtons .= button_link_creator('Registrieren', './register.php', 'person_add', 'col s3 offset-s1');
    }
    $HTMLBigscreenButtons .= button_link_creator('Passwort vergessen', './forgot_password.php', 'loop', 'col s3 offset-s1');
    $HTMLBigscreenButtons = row_builder($HTMLBigscreenButtons);

    $HTMLMobileButtons = row_builder(form_button_builder('submit', 'Einloggen', 'submit', 'send'));
    if ($RegisterActive == "true"){
        $HTMLMobileButtons .= row_builder(button_link_creator('Registrieren', './register.php', '', 'person_add'));
    }
    $HTMLMobileButtons .= row_builder(button_link_creator('Passwort vergessen', './forgot_password.php', '', 'loop'));

    $FormSections = section_builder($HTMLform);
    $FormSections .= section_builder($HTMLBigscreenButtons, '', 'hide-on-small-and-down');
    $FormSections .= section_builder($HTMLMobileButtons, '', 'hide-on-med-and-up');

    $HTML = form_builder($FormSections,'#', 'post');

    #if(isset($SessionMessage)){
     #   $HTML .= $SessionMessage;
    #}

    if(!empty($Parser['meldung'])){
        $HTML .= error_button_creator($Parser['meldung'],  '', '');
        #$HTML .= toast($Parser['meldung']);
    }

    $Container = container_builder($HTML);

    return $Container;
}

function login_parser(){

    if(isset($_POST['submit'])){

        ## DAU CHECKS BEFORE LOGIN ATTEMPT ##
        $DAUcounter = 0;
        $DAUerror = "";

        if(empty($_POST['mail'])){
            $DAUcounter ++;
            $DAUerror .= "Du musst eine E-Mail-Adresse eingeben! ";
        } else {

             if (!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                 $DAUcounter ++;
                 $DAUerror .= "Du musst eine g&uuml;ltige E-Mail-Adresse eingeben! ";
             }
        }

        if(empty($_POST['pass'])){
            $DAUcounter ++;
            $DAUerror .= "Du musst dein Passwort eingeben! ";
        }

        if ($DAUcounter > 0){
            $Antwort['meldung'] = $DAUerror;
            $Antwort['mail'] = $_POST['mail'];
            return $Antwort;

        } else {

            protect_brute_force();
            $link = connect_db();
            $stmt = $link->prepare("SELECT id, pswd_hash, id_hash FROM users WHERE mail = ?");

            $stmt->bind_param("s",$_POST['mail']);

            $stmt->execute();

            $res = $stmt->get_result();
            $num_user = mysqli_num_rows($res);

            if ($num_user != 1){
                $Antwort['meldung'] = "Passwort oder E-Mail ung&uuml;ltig!";
            } else {

                $Vals = $res->fetch_assoc();
                $StoredSecret = $Vals['pswd_hash'];

                if (password_verify($_POST['pass'], $StoredSecret)){

                    $Antwort['meldung'] = "Einloggen erfolgreich!!";

                    //Session initiieren
                    session_start();
                    $_SESSION['user_id'] = $Vals['id'];
                    $_SESSION['shared_secret'] = $Vals['id_hash'];
                    $_SESSION['timestamp'] = timestamp();

                    //Redirect
                    $UserMeta = lade_user_meta($Vals['id']);

                    if ($UserMeta['ist_taskforce'] == 'true'){
                        header("Location: ./taskforce_main_view.php");
                    } else {
                        header("Location: ./helper_view.php");
                    }
                    die();

                } else {
                    $Antwort['meldung'] = "Passwort oder E-Mail ung&uuml;ltig!";
                }

            }


            return $Antwort;
        }

    } else {
        return null;
    }
}

function session_manager($Necessary_User_Role = NULL){

    /**
     * Stellt fest, ob eine Session noch gültig ist
     * Lädt hierzu die entsprechende Einstellung aus der settings-Datei
     *
     * return-values: true & false
     */

    session_start();
    $User_login = $_SESSION['user_id'];
    $SharedSecret = $_SESSION['shared_secret'];
    $Timestamp = $_SESSION['timestamp'];
    $Ergebnis = null;

    if (!empty($User_login)){

        //Überprüfe vorhandensein von User-Login
        $link = connect_db();

        if (!($stmt = $link->prepare("SELECT * FROM users WHERE id = ? AND id_hash = ?"))) {
            $Ergebnis = false;
        }
        if (!$stmt->bind_param("is", intval($User_login), $SharedSecret)) {
            $Ergebnis = false;
        }
        if (!$stmt->execute()) {
            $Ergebnis = false;
        } else {

            $Result = $stmt->get_result();
            $AnzahlLoginUeberpruefen = mysqli_num_rows($Result);

            if($AnzahlLoginUeberpruefen == 0) {
                #Userkonto existiert nicht
                $Ergebnis = false;
            } else {

                if ($Necessary_User_Role != NULL){

                    $UserMeta = lade_user_meta($User_login);
                    if ($UserMeta[$Necessary_User_Role] != 'true'){
                        #"User does not have neccessary rights.";
                        $Ergebnis = false;
                    }

                }

            }

            //Importiere Einstellung
            $MaxMinutes = lade_xml_einstellung('max-session-length');
            $MinimumTimestamp = strtotime("- " .$MaxMinutes. " minutes", $Timestamp);
            $OldTimestamp = strtotime($Timestamp);

            // Testen ob letzter Login zu lange her
            if ($MinimumTimestamp > $OldTimestamp){
                $Ergebnis = false;
            }

        }

    } else {
        #Session enthält keine User-ID
        $Ergebnis = false;
    }

    //Weiterleiten an die Login-Seite bei Fehler
    if ($Ergebnis === false){
        session_start();
        session_destroy();

        //Redirect
        header("Location: ./login.php");
        die();

    //Erneuern des Timestamps bei erfolg
    } else {
        $_SESSION['timestamp'] = timestamp();
        return true;
    }
}

function load_session_message(){
    session_start();

    if($_SESSION['session_overtime'] == true){
        session_destroy();
        return "Deine Sitzung ist abgelaufen! Bitte melde dich erneut an!";
    } elseif(isset($_SESSION['session_overtime'])){
        session_destroy();
        return "Fehler in deiner Sitzung! Melde dich bitte erneut an!";
    } else {
        return null;
    }
}

function register_formular($Parser){

    $HTML = $Parser['meldung'];
    $HTML .= "<form action='register.php' method='post'>";
    $HTML .= "Vorname: <input type='text' name='vorname_large' id='vorname_large' placeholder='".$_POST['vorname_large']."'>";
    $HTML .= "Nachname: <input type='text' name='nachname_large' id='nachname_large' placeholder='".$_POST['nachname_large']."'>";
    $HTML .= "E-Mail: <input type='email' name='mail_large' id='mail_large' placeholder='".$_POST['mail_large']."'>";
    $HTML .= "Passwort: <input type='password' name='password_large' id='password_large'>";
    $HTML .= "Passwort wiederholen: <input type='password' name='password_verify_large' id='password_verify_large'>";
    #$HTML .= ds_unterschreiben_formular_parts();
    $HTML .= "<input type='submit' name='action_large'>";
    $HTML .= "</form>";

    return $HTML;

}

function register_parser(){

    if(lade_xml_einstellung('register_active') != 'true'){
        //Redirect
        header("Location: ./login.php");
        die();
    }

    if(isset($_POST['action_large'])){

        $link = connect_db();

        ## DAU CHECKS BEFORE LOGIN ATTEMPT ##
        $DAUcounter = 0;
        $DAUerror = "";
        $arg = 'large';

        if(empty($_POST['vorname_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deinen Vornamen an!<br>";
        }

        if(empty($_POST['nachname_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deinen Nachnamen an!<br>";
        }

        #if(!isset($_POST['ds'])){
            #$DAUcounter ++;
            #$DAUerror .= "Bitte die Datenschutzerkl&auml;rung abhaken!<br>";
        #}

        if(empty($_POST['mail_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Du musst eine E-Mail-Adresse eingeben!<br>";
        } else {

            protect_brute_force();
            if (!filter_var($_POST['mail_'.$arg.''], FILTER_VALIDATE_EMAIL)) {
                $DAUcounter ++;
                $DAUerror .= "Du musst eine g&uuml;ltige E-Mail-Adresse eingeben!<br>";
            } else {

                $stmt = $link->prepare("SELECT id FROM users WHERE mail = ?");

                $stmt->bind_param("s",$_POST['mail_'.$arg.'']);

                $stmt->execute();

                $res = $stmt->get_result();
                $num_results = mysqli_num_rows($res);

                if($num_results > 0){
                    $DAUcounter ++;
                    $DAUerror .= "Die von dir eingegebene E-Mail-Adresse ist bereits mit einem anderen Account verkn&uuml;pft! Versuche es mit einer anderen E-Mail oder verwende die <a href='./reset_password.php'>Passwort zur&uuml;cksetzen Funktion</a>.<br>";
                }

            }
        }

        if(empty($_POST['password_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte ein Passwort an!<br>";
        } else {

            if($_POST['password_'.$arg.''] != $_POST['password_verify_'.$arg.'']){
                $DAUcounter ++;
                $DAUerror .= "Die eingegebenen Passw&ouml;rter sind nicht identisch!<br>";
            }

        }

        ## DAU auswerten
        if ($DAUcounter > 0){
            $Antwort['meldung'] = $DAUerror;
            return $Antwort;

        } else {
            $Rollen = generate_initial_user_meta();
            $Antwort = add_new_user($_POST['vorname_'.$arg.''], $_POST['nachname_'.$arg.''], $_POST['mail_'.$arg.''], $_POST['password_'.$arg.''], $Rollen);
            if ($Antwort['erfolg'] === false) {
                return $Antwort; // oder nur false. um keine Fehlerdaten weiterzugeben?
            }

            #Lade User ID
            if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
                $Antwort['erfolg'] = false;
                return $Antwort;
            }

            if (!$stmt->bind_param("s",$_POST['mail_'.$arg.''])) {
                $Antwort['erfolg'] = false;
                return $Antwort;
            }

            if (!$stmt->execute()) {
                $Antwort['erfolg'] = false;
                return $Antwort;
            } else {

                $res = $stmt->get_result();
                $Results = mysqli_fetch_assoc($res);
                $UserID = $Results['id'];
            }

            #Datenschutzunterzeichnung festhalten
            #if(isset($_POST['ds_checked'])){
                #ds_unterschreiben($UserID,aktuelle_ds_id_laden());
            #}

            return $Antwort;
        }

    } else{return null;}
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
  * In dieser Funktion sollen Sicherungsmechanismen gegen brute forcing eingebaut werden.
  * Vorläufig nur eine primitive 1 Sekunden Verzögerung bei jeder Passworteingabe
  */
function protect_brute_force() {
    sleep(1);
}
