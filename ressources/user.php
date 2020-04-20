<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 16:18
 */

function lade_user_id(){
    //Session initiieren
    session_start();
    $UserSessionID = intval($_SESSION['user_id']);
    return $UserSessionID;
}

function lade_user_meta($UserID){

    $link = connect_db();

    if (!($stmt = $link->prepare("SELECT * FROM user_meta WHERE user = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("s",$UserID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Hits = mysqli_num_rows($res);
    $Result = array();
    for($a=1;$a<=$Hits;$a++){
        $Row = mysqli_fetch_assoc($res);
        $Result[$Row['schluessel']] = $Row['wert'];
    }

    return $Result;
}

function add_new_user($Vorname, $Nachname, $Strasse, $Hausnummer, $PLZ, $Stadt, $Mail, $PSWD, $Rollen){

    $link = connect_db();

    $PSWD_hashed = password_hash($PSWD, PASSWORD_DEFAULT);
    if($PSWD_hashed == false){
        echo "Error with hashing";
    }

    echo "adding user account";
    if (!($stmt = $link->prepare("INSERT INTO users (mail,secret,register) VALUES (?,?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("sss", $Mail, $PSWD_hashed, timestamp())) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;

    } else {
        echo "selecting user id";
        if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
            $Antwort['erfolg'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("s", $Mail)) {
            $Antwort['erfolg'] = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        }

        $res = $stmt->get_result();
        $Ergebnis = mysqli_fetch_assoc($res);

        #Weitere Userinfos hinzufügen
        echo "adding user meta";
        add_user_meta($Ergebnis['id'], 'vorname', $Vorname);
        add_user_meta($Ergebnis['id'], 'nachname', $Nachname);
        add_user_meta($Ergebnis['id'], 'strasse', $Strasse);
        add_user_meta($Ergebnis['id'], 'hausnummer', $Hausnummer);
        add_user_meta($Ergebnis['id'], 'plz', $PLZ);
        add_user_meta($Ergebnis['id'], 'stadt', $Stadt);

        #Rollen eingeben
        foreach($Rollen as $Rolle => $Wert){
            add_user_meta($Ergebnis['id'], $Rolle, $Wert);
        }

        $Antwort['erfolg'] = True;
        $Antwort['meldung'] = "Dein Useraccount wurde erfolgreich angelegt! Du erh&auml;ltst noch eine eMail, die den Vorgang best&auml;tigt!<br>Du kannst dich jetzt <a href='./login.php'>hier einloggen</a>!:)";
    }


    return $Antwort;
}

function add_user_meta($UserID, $Key, $Value){

    $link = connect_db();

    if (!($stmt = $link->prepare("INSERT INTO user_meta (user,schluessel,wert,timestamp) VALUES (?,?,?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("isss", $UserID, $Key, $Value, timestamp())) {
        $Antwort['erfolg'] = false;
        echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
       return true;
    }

}

function update_user_meta($UserID, $Key, $Value){

    $link = connect_db();

    if ($Value == ''){
        return false;
    } else {

        if (!($stmt = $link->prepare("INSERT INTO user_meta (user,schluessel,wert,timestamp) VALUES (?,?,?,?)"))) {
            $Antwort['erfolg'] = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("isss", $UserID, $Key, $Value, timestamp())) {
            $Antwort['erfolg'] = false;
            echo  "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            return true;
        }
    }
}

?>