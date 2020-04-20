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
        $Antwort['meldung'] = "Dein Useraccount wurde erfolgreich angelegt! Du erh&auml;ltst noch eine eMail, die den Vorgang best&auml;tigt!<br>Bitte best&auml;tige die Anmeldung indem du auf den Link in der Mail klickst!:)";
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

function check_password($PSWD) {

    // Define URL for haveibeenpwned.com API as constant
    define('PWNED_URL', 'https://api.pwnedpasswords.com/range/');

    // Check if password is at least 10 chars long
    if (strlen($PSWD) < 10)
        return 'Das Passwort ist zu kurz. Es muss mindestens 10 Zeichen lang sein';

    // Check if password contains numbers and letters
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).*$/', $PSWD))
        return 'Das Passwort muss Zahlen und Buchstaben enthalten';

    // Check if password contains at least 3 numbers
    if (preg_match_all('/[0-9]/', $PSWD) < 3)
        return 'Das Passwort muss mindestens drei Zahlen enthalten';

    // Check if password contains the words password or passwort (case insensitive)
    if (preg_match('/pa[s\$]{0,2}w[o0]{0,1}rd|pa[s\$]{0,2}w[o0]{0,1}rt/i', $PSWD))
        return 'Das Passwort darf die W&ouml;rter Passwort, Password oder Variationen davon nicht enthalten';

    // Check if password contains too many repeating chars
    if (preg_match('/(.)\1{3,}/i', $PSWD))
        return 'Das Passwort darf nicht mehr als 3 gleiche Zeichen (egal ob gro&szlig;/klein) hintereinander enthalten';

    if (preg_match('/(.{3,})\1{2,}/i', $PSWD))
        return 'Das Passwort darf keine sich wiederholenden Zeichenketten enthalten (egal ob gro&szlig;/klein, z.B. abcABCabc)';

    // Check if password contains continuous alphabetical rows
    if (preg_match('/abcde|bcdef|cdefg|defgh|efghi|fghij|ghijk|hijkl|ijklm|jklmn|klmno|lmnop|mnopq|nopqr|opqrs|pqrst|qrstu|rstuv|stuvw|tuvwx|uvwxy|vwxyz/i', $PSWD))
        return 'Das Passwort darf keine alphabetischen Zeichenketten enthalten (z.B. abcde)';

    // Check if password contains continuous ascending or descending numbers
    if (preg_match('/.*1\D{0,1}2\D{0,1}3.*|.*2\D{0,1}3\D{0,1}4.*|.*3\D{0,1}4\D{0,1}5.*|.*4\D{0,1}5\D{0,1}6.*|.*5\D{0,1}6\D{0,1}7.*|.*6\D{0,1}7\D{0,1}8.*|.*7\D{0,1}8\D{0,1}9.*|.*8\D{0,1}9\D{0,1}0.*|.*9\D{0,1}8\D{0,1}7.*|.*8\D{0,1}7\D{0,1}6.*|.*7\D{0,1}6\D{0,1}5.*|.*6\D{0,1}5\D{0,1}4.*|.*5\D{0,1}4\D{0,1}3.*|.*4\D{0,1}3\D{0,1}2.*|.*3\D{0,1}2\D{0,1}1.*/', $PSWD))
        return 'Das Passwort darf keine fortlaufenden Zahlenreihen enthalten (z.B. 1234, 9o8i7u6z)';

    // Check if password contains continuous chars from keyboard rows
    if (preg_match('/qwert|asdfg|yxcvb|zxcvb|<yxcv|<zxcv|poiuz|poiuy|üpoiu|\+üpoi|lkjhg|äölkj|mnbvc|-.,mn/i', $PSWD))
        return 'Das Passwort darf keine fortlaufenden Buchstabenreihen der Tastatur enthalten (z.B. qwert, asdfg)';

    // Check if password begins or ends with 1 or 2 numbers and does not contain any other numbers
    if (preg_match('/^[0-9]{1,2}|[0-9]{1,2}$/', $PSWD) && !preg_match('/[0-9]/', substr($PSWD, 2, -2)))
        return 'Das Passwort darf Zahlen nicht nur als Pr&auml;fix oder Suffix enthalten (z.B. passwort99)';

    // Compute SHA1 hash + convert to uppercase
    $hash_to_check = strtoupper(sha1($PSWD));

    // Split hash in two parts
    $hash_to_check_prefix = substr($hash_to_check, 0, 5);
    $hash_to_check_suffix = substr($hash_to_check, 5);

    // query haveibeenpwned.com, submit first 5 chars of the hash
    $pwned_response = file_get_contents(PWNED_URL . $hash_to_check_prefix);

    // Check HTTP connection
    if (!strpos($http_response_header[0], '200 OK')){
        $Message = "HTTP connection error to ".PWNED_URL.": ".$http_response_header[0]."";
        add_protocol_entry(0, 'check_password', $Message);
    }

    // Check, if second part of the hash is in the received list
    if (strpos($pwned_response, $hash_to_check_suffix))
        return 'Dieses Passwort wurde in geleakten Daten gefunden, bitte ein anderes verwenden.';
    else
        return 'OK';
}

?>