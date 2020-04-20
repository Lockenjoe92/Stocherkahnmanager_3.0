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

function lade_user_meta($UserID, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    $stmt = $dbcon->prepare("SELECT * FROM user_meta WHERE nutzer = ?");

    $stmt->bind_param("s",$UserID);

    $stmt->execute();

    $res = $stmt->get_result();
    $Hits = mysqli_num_rows($res);
    $Result = array();
    for($a=1;$a<=$Hits;$a++){
        $Row = mysqli_fetch_assoc($res);
        $Result[$Row['schluessel']] = $Row['wert'];
    }

    $Result['mail'] = lade_user_mail($UserID);

    return $Result;
}

function lade_user_mail($UserID, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    $stmt = $dbcon->prepare("SELECT mail FROM users WHERE id = ?");

    $stmt->bind_param("s",$UserID);

    $stmt->execute();

    $res = $stmt->get_result();

    $Result = mysqli_fetch_assoc($res);
    $Result = $Result['mail'];

    return $Result;
}

function lade_user_name($UserID) {

    $dbcon = connect_db();

    # get vorname
    $stmt = $dbcon->prepare("SELECT wert FROM user_meta WHERE schluessel='vorname' and nutzer=?");
    $stmt->bind_param("i",$UserID);
    $stmt->execute();
    $res = $stmt->get_result();

    $temp = mysqli_fetch_assoc($res);
    $vorname = $temp["wert"];

    # get nachname
    $stmt = $dbcon->prepare("SELECT wert FROM user_meta WHERE schluessel='nachname' and nutzer=?");
    $stmt->bind_param("i",$UserID);
    $stmt->execute();
    $res = $stmt->get_result();

    $temp = mysqli_fetch_assoc($res);
    $nachname = $temp["wert"];

    $name = $vorname . " " . $nachname;
    return $name;
}

function add_new_user($Vorname, $Nachname, $Mail, $PSWD, $Rollen, $Mode='csv_auto'){

    $pswdchk_result = check_password($PSWD);
    if ($pswdchk_result !== 'OK'){
        $Antwort['erfolg'] = false;
        $Antwort['meldung'] = $pswdchk_result;
        return $Antwort;
    }

    $link = connect_db();

    $PSWD_hashed = password_hash($PSWD, PASSWORD_DEFAULT);
    $ID_hash = generateRandomString(32);

    if($PSWD_hashed == false){
        $Antwort['erfolg'] = false;
        $Antwort['meldung'] = "Fehler beim Verschl&uuml;sseln des Passwortes";
        return $Antwort;
    }

    if(($Mode=='csv_auto') || ($Mode=='taskforce_create_user')){
        $isStartPSWD = 1;
    } elseif ($Mode=='normal_register_form'){
        $isStartPSWD = 0;
    } else {
        $isStartPSWD = 1;
    }

    if (!($stmt = $link->prepare("INSERT INTO users (mail,pswd_hash,id_hash, is_start_pswd) VALUES (?,?,?,?)"))) {
        $Antwort['erfolg'] = false;
    }
    if (!$stmt->bind_param("sssi", $Mail, $PSWD_hashed, $ID_hash, $isStartPSWD)) {
        $Antwort['erfolg'] = false;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;

    } else {
    
        if ($Mode=='normal_register_form') {
            add_protocol_entry('register', ''.$Vorname.' '.$Nachname.' hat sich erfolgreich registriert.');
        } else {
            add_protocol_entry('register', 'Benutzer '.$Vorname.' '.$Nachname.' wurde erfolgreich durch User '.lade_user_id().' angelegt.');
        }

        if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ?"))) {
            $Antwort['erfolg'] = false;
        }
        if (!$stmt->bind_param("s", $Mail)) {
            $Antwort['erfolg'] = false;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {

            $res = $stmt->get_result();
            $Ergebnis = mysqli_fetch_assoc($res);

            #Weitere Userinfos hinzufügen
            add_user_meta($Ergebnis['id'], 'vorname', $Vorname);
            add_user_meta($Ergebnis['id'], 'nachname', $Nachname);

            #Rollen eingeben
            if(!empty($Rollen)) {
                foreach ($Rollen as $Rolle => $Wert) {
                    add_user_meta($Ergebnis['id'], $Rolle, $Wert);
                }
            }

            $Antwort['erfolg'] = True;
            $Antwort['meldung'] = "Der Useraccount wurde erfolgreich angelegt! Sie erhalten noch eine E-Mail, die den Vorgang best&auml;tigt!<br>Sie k&ouml;nnen sich jetzt <a href='./login.php'>hier einloggen</a>!";

            if(($Mode=='csv_auto') || ($Mode=='taskforce_create_user')){
                mail_senden('registrierung-erfolgreich-auto-helfer', $Mail, array('[vorname]' => $Vorname, '[username]' => $Mail, '[password]' => $PSWD));
            } elseif ($Mode=='normal_register_form'){
                mail_senden('registrierung-erfolgreich-register-formular', $Mail, array('[vorname]' => $Vorname, '[username]' => $Mail));
            }

        }
    }
    return $Antwort;
}

function add_user_meta($UserID, $Key, $Value, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }
    $UserID = intval($UserID);

    if (!($stmt = $dbcon->prepare("INSERT INTO user_meta (nutzer,schluessel,wert) VALUES (?,?,?)"))) {
        return false;
    }
    if (!$stmt->bind_param("iss", $UserID, $Key, $Value)) {
        return false;
    }
    if (!$stmt->execute()) {
        return false;
    } else {
        add_protocol_entry('user_meta', ''.$Key.' bei User '.$UserID.' durch User '.lade_user_id().' eingetragen.');
        return true;
    }

}

###

function user_needs_to_change_first_pswd($UserID){

    $link = connect_db();

    $stmt = $link->prepare("SELECT is_start_pswd FROM users WHERE id = ?");

    $stmt->bind_param("s",$UserID);

    $stmt->execute();

    $res = $stmt->get_result();

    $Result = mysqli_fetch_assoc($res);

    if($Result['is_start_pswd'] == 1){
        return true;
    } else {
        return false;
    }

}

function update_user_meta($UserID, $Key, $Value, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    if ($Value == ''){
        return false;
    } else {
        if(user_meta_exists($UserID, $Key)) {
            if (!($stmt = $dbcon->prepare("UPDATE user_meta SET wert = ? WHERE nutzer = ? AND schluessel = ?"))) {
                return false;
            }
            if (!$stmt->bind_param("sis", $Value, $UserID, $Key)) {
                return false;
            }
            if (!$stmt->execute()) {
                return false;
            } else {
                add_protocol_entry('user_meta', '' . $Key . ' bei User ' . $UserID . ' geändert durch User ' . lade_user_id() . '.');
                return true;
            }
        } else {
            return add_user_meta($UserID, $Key, $Value, $dbcon);
        }
    }
}

function update_user_mail($User, $Value, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    if (!($stmt = $dbcon->prepare("UPDATE users SET mail = ? WHERE id = ?"))) {
        return false;
    }
    if (!$stmt->bind_param("si", $Value, intval($User))) {
        return false;
    }
    if (!$stmt->execute()) {
        return false;
    } else {
        add_protocol_entry('user_email', 'E-Mail von User ' . $User . ' wurde ge&auml;ndert durch User '.lade_user_id().'.');
        return true;
    }
}

function update_user_password($User, $Password, $Forgot=false)
{

    $link = connect_db();
    $PSWD_hashed = password_hash($Password, PASSWORD_DEFAULT);

    if ($PSWD_hashed == false) {
        return false;
    } else {

        if($Forgot == false){
            $isStartPSWD = 0;
        } elseif ($Forgot == true){
            $isStartPSWD = 1;
        }

        if (!($stmt = $link->prepare("UPDATE users SET pswd_hash = ?, is_start_pswd = ? WHERE id = ?"))) {
            return false;
        }
        if (!$stmt->bind_param("sii", $PSWD_hashed, $isStartPSWD, intval($User))) {
            return false;
        }
        if (!$stmt->execute()) {
            return false;
        } else {
            add_protocol_entry('user_passwort', 'Passwort von User ' . $User . ' wurde ge&auml;ndert.');
            return true;
        }
    }
}

function user_meta_exists($UserID, $Key, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    if (!($stmt = $dbcon->prepare("SELECT id FROM user_meta WHERE nutzer = ? AND schluessel = ?"))) {
        $Antwort['erfolg'] = false;
    }
    if (!$stmt->bind_param("is", intval($UserID), $Key)) {
        $Antwort['erfolg'] = false;
    }
if (!$stmt->execute()) {
    return null;
} else {
    $res = $stmt->get_result();
    $nums = mysqli_num_rows($res);

    if($nums == 1){
        return true;
    } else {
        return false;
    }
}

}

function user_id_exists($UserID, $dbcon=null){

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }

    if (!($stmt = $dbcon->prepare("SELECT id FROM users WHERE id = ?"))) {
        return null;
    }
    if (!$stmt->bind_param("i", intval($UserID))) {
        return null;
    }
    if (!$stmt->execute()) {
        return null;
    } else {
        $res = $stmt->get_result();
        $nums = mysqli_num_rows($res);

        if($nums == 1){
            return true;
        } else {
            return false;
        }
    }
}

function user_id_from_email($user_email, $dbcon=null) {

    if (empty($dbcon)) {
        $dbcon = connect_db();
    }
    if (!($stmt = $dbcon->prepare("SELECT id FROM users WHERE mail = ?"))) {
        return false;
    }
    if (!$stmt->bind_param("s", $user_email)) {
        return false;
    }
    if (!$stmt->execute()) {
        return false;
    } else {
        $res = $stmt->get_result();
        $Results = mysqli_fetch_assoc($res);
        $UserID = $Results['id'];
        return $UserID;
    }
}

function generate_initial_user_meta() {
    return array('refNummer'=>'', 'telefon'=>'', 'erfahrungDiagnose'=>'', 'einsatzkategorien'=>'[2]', 'has_qualifications'=>'[]', 'studiengang'=>'', 'fachsemester'=>'0', 'verfuegbarkeit'=>'0', 'vertrag'=>'nein', 'vertragsnummer'=>'', 'bemerkungDekanat'=>'', 'zusatzQuali1'=>'', 'zusatzQuali2'=>'', 'zusatzQuali3'=>'', 'fortbildungen'=>'', 'aufenthaltsort'=>'[]');
}

function generate_random_password() {
    for ($i=0; $i<10000; $i++)
    {
        $RandomPassword = bin2hex(random_bytes(8));
        $pswdchk_result = check_password($RandomPassword);
        if ($pswdchk_result === 'OK') {
            return $RandomPassword;
        }
    }
    add_protocol_entry('error', 'Error: Random password generation failed.');
    return false;
}

function check_password($PSWD) {

    // Define URL for haveibeenpwned.com API as constant
    define('PWNED_URL', 'https://api.pwnedpasswords.com/range/');

    // Check if password is at least 10 chars long
    if (strlen($PSWD) < 10)
      return error_button_creator('Das Passwort ist zu kurz. Es muss mindestens 10 Zeichen lang sein');

    // Check if password contains numbers and letters
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).*$/', $PSWD))
      return error_button_creator('Das Passwort muss Zahlen und Buchstaben enthalten');

    // Check if password contains at least 3 numbers
    if (preg_match_all('/[0-9]/', $PSWD) < 3)
      return error_button_creator('Das Passwort muss mindestens drei Zahlen enthalten');

    // Check if password contains the words password or passwort (case insensitive)
    if (preg_match('/pa[s\$]{0,2}w[o0]{0,1}rd|pa[s\$]{0,2}w[o0]{0,1}rt/i', $PSWD))
      return error_button_creator('Das Passwort darf die W&ouml;rter Passwort, Password oder Variationen davon nicht enthalten');

    // Check if password contains too many repeating chars
    if (preg_match('/(.)\1{3,}/i', $PSWD))
      return error_button_creator('Das Passwort darf nicht mehr als 3 gleiche Zeichen (egal ob gro&szlig;/klein) hintereinander enthalten');

    if (preg_match('/(.{3,})\1{2,}/i', $PSWD))
      return error_button_creator('Das Passwort darf keine sich wiederholenden Zeichenketten enthalten (egal ob gro&szlig;/klein, z.B. abcABCabc)');

    // Check if password contains continuous alphabetical rows
    if (preg_match('/abcde|bcdef|cdefg|defgh|efghi|fghij|ghijk|hijkl|ijklm|jklmn|klmno|lmnop|mnopq|nopqr|opqrs|pqrst|qrstu|rstuv|stuvw|tuvwx|uvwxy|vwxyz/i', $PSWD))
      return error_button_creator('Das Passwort darf keine alphabetischen Zeichenketten enthalten (z.B. abcde)');

    // Check if password contains continuous ascending or descending numbers
    if (preg_match('/.*1\D{0,1}2\D{0,1}3.*|.*2\D{0,1}3\D{0,1}4.*|.*3\D{0,1}4\D{0,1}5.*|.*4\D{0,1}5\D{0,1}6.*|.*5\D{0,1}6\D{0,1}7.*|.*6\D{0,1}7\D{0,1}8.*|.*7\D{0,1}8\D{0,1}9.*|.*8\D{0,1}9\D{0,1}0.*|.*9\D{0,1}8\D{0,1}7.*|.*8\D{0,1}7\D{0,1}6.*|.*7\D{0,1}6\D{0,1}5.*|.*6\D{0,1}5\D{0,1}4.*|.*5\D{0,1}4\D{0,1}3.*|.*4\D{0,1}3\D{0,1}2.*|.*3\D{0,1}2\D{0,1}1.*/', $PSWD))
      return error_button_creator('Das Passwort darf keine fortlaufenden Zahlenreihen enthalten (z.B. 1234, 9o8i7u6z)');

    // Check if password contains continuous chars from keyboard rows
    if (preg_match('/qwert|asdfg|yxcvb|zxcvb|<yxcv|<zxcv|poiuz|poiuy|üpoiu|\+üpoi|lkjhg|äölkj|mnbvc|-.,mn/i', $PSWD))
      return error_button_creator('Das Passwort darf keine fortlaufenden Buchstabenreihen der Tastatur enthalten (z.B. qwert, asdfg)');

    // Check if password begins or ends with 1 or 2 numbers and does not contain any other numbers
    if (preg_match('/^[0-9]{1,2}|[0-9]{1,2}$/', $PSWD) && !preg_match('/[0-9]/', substr($PSWD, 2, -2)))
      return error_button_creator('Das Passwort darf Zahlen nicht nur als Pr&auml;fix oder Suffix enthalten (z.B. passwort99)');

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
        add_protocol_entry('check_password', $Message);
    }

    // Check, if second part of the hash is in the received list
    if (strpos($pwned_response, $hash_to_check_suffix))
      return error_button_creator('Dieses Passwort wurde in geleakten Daten gefunden, bitte ein anderes verwenden.','','');
    else
      return 'OK';
}
