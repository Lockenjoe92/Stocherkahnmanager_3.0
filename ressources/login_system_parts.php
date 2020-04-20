<?php

function login_formular($Parser, $SessionMessage){

    $HTMLform = row_builder('<h1>Login zum Buchungstool</h1>', '', 'hide-on-small-and-down');
    $HTMLform .= row_builder('<h3>Login zum Buchungstool</h3>', '', 'hide-on-med-and-up');


    $HTMLform .= "<div class='row'>";
    $HTMLform .= "<div class='input-field'>";
    $HTMLform .= "<input id='login_mail' type='email' name='mail' value='".$Parser['mail']."'>";
    $HTMLform .= "<label for='login_mail'>Mail</label>";
    $HTMLform .= "</div>";
    $HTMLform .= "</div>";

    $HTMLform .= "<div class='row'>";
    $HTMLform .= "<div class='input-field'>";
    $HTMLform .= "<input id='login_pswd' type='password' name='pass'>";
    $HTMLform .= "<label for='login_pswd'>Passwort</label>";
    $HTMLform .= "</div>";
    $HTMLform .= "</div>";

    $HTMLBigscreenButtons = form_button_builder('submit', 'Einloggen', 'submit', 'send', 'col s3');
    $HTMLBigscreenButtons .= button_link_creator('Registrieren', './register.php', 'person_add', 'col s3 offset-s1');
    $HTMLBigscreenButtons .= button_link_creator('Passwort vergessen', './iforgot.php', 'loop', 'col s3 offset-s1');
    $HTMLBigscreenButtons = row_builder($HTMLBigscreenButtons);

    $HTMLMobileButtons = row_builder(form_button_builder('submit', 'Einloggen', 'submit', 'send'));
    $HTMLMobileButtons .= row_builder(button_link_creator('Registrieren', './register.php', '', 'person_add'));
    $HTMLMobileButtons .= row_builder(button_link_creator('Passwort vergessen', './iforgot.php', '', 'loop'));

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
            $DAUerror .= "Du musst eine eMail-Adresse eingeben! ";
        } else {

             if (!filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                 $DAUcounter ++;
                 $DAUerror .= "Du musst eine echte eMail-Adresse eingeben! ";
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

            $link = connect_db();
            if (!($stmt = $link->prepare("SELECT id, secret FROM users WHERE mail = ?"))) {
                echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            }

            if (!$stmt->bind_param("s",$_POST['mail'])) {
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }

            if (!$stmt->execute()) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }

            $res = $stmt->get_result();
            $num_user = mysqli_num_rows($res);

            if ($num_user != 1){
                $Antwort['meldung'] = "Userkonto existiert nicht!";
            } else {

                $Vals = $res->fetch_assoc();
                $StoredSecret = $Vals['secret'];

                if (password_verify($_POST['pass'], $StoredSecret)){

                    $Antwort['meldung'] = "Einloggen erfolgreich!!";

                    //Session initiieren
                    session_start();
                    $_SESSION['user_id'] = $Vals['id'];
                    $_SESSION['timestamp'] = timestamp();

                    //Redirect
                    $UserMeta = lade_user_meta($Vals['id']);
                    
                    if ($UserMeta['ist_wart'] == 'true'){
                        header("Location: ./wartwesen.php");
                    } else {
                        header("Location: ./my_reservations.php");
                    }
                    die();

                } else {
                    $Antwort['meldung'] = "Passwort ung&uuml;ltig!";
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
     * Lödt hierzu die entsprechende Einstellung aus der settings-Datei
     *
     * return-values: true & false
     */

    session_start();
    $Timestamp = timestamp();

    $User_login = $_SESSION['user_id'];
    $LetzterSeitenaufruf = $_SESSION['timestamp'];
    $Ergebnis = true;
    $SessionOvertime = false;

    if (!empty($User_login)){

        //Überprüfe vorhandensein von User-Login
        $link = connect_db();
        $AnfrageLoginUeberpruefen = "SELECT * FROM users WHERE id = '$User_login'";
        $AbfrageLoginUeberpruefen = mysqli_query($link, $AnfrageLoginUeberpruefen);
        $AnzahlLoginUeberpruefen = mysqli_num_rows($AbfrageLoginUeberpruefen);

        if($AnzahlLoginUeberpruefen == 0) {
            #Userkonto existiert nicht
            echo "No user account found!";
            $Ergebnis = false;
        } else {

            if ($Necessary_User_Role != NULL){

                $UserMeta = lade_user_meta($User_login);
                if ($UserMeta[$Necessary_User_Role] != 'true'){
                    echo "User does not have neccessary rights.";
                    $Ergebnis = false;
                }

            }

        }

        //Importiere Einstellung
        $MaxMinutes = 1;
        $MinimumTimestamp = strtotime("- " .$MaxMinutes. " minutes", $Timestamp);
        $OldTimestamp = strtotime($Timestamp);

        if ($MinimumTimestamp > $OldTimestamp){
            $SessionOvertime = true;
            $Ergebnis = false;
        }

    } else {
        #Session enthält keine User-ID
        echo "No user ID in Session.";
        $Ergebnis = false;
    }

    //Weiterleiten an die Login-Seite bei Fehler
    if ($Ergebnis === false){

        //Session initiieren
        session_start();
        session_destroy();
        session_start();

        $_SESSION['session_overtime'] = $SessionOvertime;

        //Redirect
        header("Location: ./login.php");
        die();

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
    $HTML .= "Stra&szlig;e: <input type='text' name='strasse_large' id='strasse_large' placeholder='".$_POST['strasse_large']."'> Hausnummer: <input type='text' name='hausnummer_large' id='hausnummer_large' placeholder='".$_POST['hausnummer_large']."'>";
    $HTML .= "Postleitzahl: <input type='text' name='plz_large' id='plz_large' placeholder='".$_POST['plz_large']."'> Stadt: <input type='text' name='stadt_large' id='stadt_large' placeholder='".$_POST['stadt_large']."'>";
    $HTML .= "eMail: <input type='email' name='mail_large' id='mail_large' placeholder='".$_POST['mail_large']."'>";
    $HTML .= "Passwort: <input type='password' name='password_large' id='password_large'>";
    $HTML .= "Passwort wiederholen: <input type='password' name='password_verify_large' id='password_verify_large'>";
    $HTML .= ds_unterschreiben_formular_parts();
    $HTML .= "<input type='submit' name='action_large'>";
    $HTML .= "</form>";

    return $HTML;

}

function register_parser(){

    $link = connect_db();

    if(isset($_POST['action_large'])){

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

        if(empty($_POST['strasse_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deine Anschrift an!<br>";
        }

        if(empty($_POST['hausnummer_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deine Hausnummer an!<br>";
        }

        if(empty($_POST['plz_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deine Postleitzahl an!<br>";
        }

        if(empty($_POST['stadt_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte deinen Wohnort an!<br>";
        }

        if(!isset($_POST['ds'])){
            $DAUcounter ++;
            $DAUerror .= "Bitte die Datenschutzerkl&auml;rung abhaken!<br>";
        }

        if(empty($_POST['mail_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Du musst eine eMail-Adresse eingeben!<br>";
        } else {

            if (!filter_var($_POST['mail_'.$arg.''], FILTER_VALIDATE_EMAIL)) {
                $DAUcounter ++;
                $DAUerror .= "Du musst eine echte eMail-Adresse eingeben!<br>";
            } else {

                if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
                    echo "Prepare failed: (" . $link->errno . ") " . $link->error;
                }

                if (!$stmt->bind_param("s",$_POST['mail_'.$arg.''])) {
                    echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
                }

                if (!$stmt->execute()) {
                    echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                }

                $res = $stmt->get_result();
                $num_results = mysqli_num_rows($res);

                if($num_results > 0){
                    $DAUcounter ++;
                    $DAUerror .= "Die von dir eingegebene eMail-Adresse ist bereits mit einem anderen Account verkn&uuml;pft! Versuche es mit einer anderen eMail oder verwende die <a href='./reset_password.php'>Passwort zur&uuml;cksetzen Funktion</a>.<br>";
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

            $Antwort = add_new_user($_POST['vorname_'.$arg.''], $_POST['nachname_'.$arg.''],
                $_POST['strasse_'.$arg.''], $_POST['hausnummer_'.$arg.''],
                $_POST['plz_'.$arg.''], $_POST['stadt_'.$arg.''],
                $_POST['mail_'.$arg.''], $_POST['password_'.$arg.''], null);

            #Lade User ID
            if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = '?'"))) {
                echo "Prepare failed: (" . $link->errno . ") " . $link->error;
                return $Antwort['erfolg'] = false;
            }

            if (!$stmt->bind_param("s",$_POST['mail_'.$arg.''])) {
                echo "Binding parameters Load User ID failed: (" . $stmt->errno . ") " . $stmt->error;
                return $Antwort['erfolg'] = false;
            }

            if (!$stmt->execute()) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
                return $Antwort['erfolg'] = false;
            } else {

                $res = $stmt->get_result();
                $Results = mysqli_fetch_assoc($res);
                $UserID = $Results['id'];
            }

            #Datenschutzunterzeichnung festhalten
            if(isset($_POST['ds_checked'])){
                ds_unterschreiben($UserID,aktuelle_ds_id_laden());
            }

            return $Antwort;
        }

    } else{return null;}
}

?>