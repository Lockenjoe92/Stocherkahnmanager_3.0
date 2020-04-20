<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 22.11.18
 * Time: 13:00
 */

function ds_anlegen_formular($Parser){

    $HTML = "<h2>Neue Datenschutzerkl&auml;rung anlegen</h2>";
    $HTML .= $Parser['meldung'];
    $HTML .= "<form action='datenschutzerklaerungen.php' method='post'>";
    $HTML .= "Version: <input type='text' name='version_large' id='version_large' placeholder='".$_POST['version_large']."'>";
    $HTML .= "Erkl&auml;rung: <input type='text' name='erklaerung_large' id='erklaerung_large' placeholder='".$_POST['erklaerung_large']."'>";
    $HTML .= "Inhalt: <textarea type='text' name='inhalt_large' id='inhalt_large'>".$_POST['inhalt_large']."</textarea>";
    $HTML .= "<input type='submit' name='action_large'>";
    $HTML .= "</form>";

    return $HTML;

}

function ds_anlegen_parser(){

    $link = connect_db();
    $CurrentUser = lade_user_id();
    $UserMeta = lade_user_meta($CurrentUser);

    if(isset($_POST['action_large'])){

        ## DAU CHECKS BEFORE LOGIN ATTEMPT ##
        $DAUcounter = 0;
        $DAUerror = "";
        $arg = "large";

        if($UserMeta['ist_admin'] != 'true'){
            $DAUcounter ++;
            $DAUerror .= "Du hast keine Berechtigung f&uuml;r diesen Vorgang!<br>";
        }

        if(empty($_POST['inhalt_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte den Inhalt der Datenschutzerkl&auml;rung an!<br>";
        }

        if(empty($_POST['erklaerung_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte eine Erkl&auml;rung f&uuml;r die User an!<br>";
        }

        if(empty($_POST['version_'.$arg.''])){
            $DAUcounter ++;
            $DAUerror .= "Gib bitte eine Versionsangabe an!<br>";
        } else {

            if (!($stmt = $link->prepare("SELECT id FROM datenschutzerklaerungen WHERE version = ?"))) {
                echo "Prepare failed: (" . $link->errno . ") " . $link->error;
            }

            if (!$stmt->bind_param("s",$_POST['version_'.$arg.''])) {
                echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
            }

            if (!$stmt->execute()) {
                echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            }

            $res = $stmt->get_result();
            $num_results = mysqli_num_rows($res);

            if($num_results > 0){
                $DAUcounter ++;
                $DAUerror .= "Die von dir eingegebene Version ist bereits vergeben!<br>";
            }

        }

        ## DAU auswerten
        if ($DAUcounter > 0){
            $Antwort['meldung'] = $DAUerror;
            return $Antwort;

        } else {
            $Antwort = ds_anlegen($_POST['erklaerung_'.$arg.''], $_POST['version_'.$arg.''], $_POST['inhalt_'.$arg.''], $CurrentUser);
            return $Antwort;
        }

    } else{return null;}

}

function ds_anlegen($Erklaerung, $Version, $Inhalt, $User){

    $link = connect_db();
    $timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO datenschutzerklaerungen (version, erklaerung, inhalt, ersteller, create_time) VALUES (?,?,?,?,?)"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->bind_param("sssis",$Version, $Erklaerung, $Inhalt, $User, $timestamp)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return $Antwort['erfolg'] = false;
    } else {
        $Antwort['meldung'] = 'Anlegen erfolgreich!';
        $Antwort['erfolg'] = true;
        return $Antwort;
    }

}

function aktuelle_ds_id_laden(){

    $link = connect_db();

    $Anfrage = "SELECT id FROM datenschutzerklaerungen WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis['id'];
}

function ds_unterschreiben($User, $DSid){

    $link = connect_db();
    $Timestamp = timestamp();

    if (!($stmt = $link->prepare("INSERT INTO ds_unterzeichnungen (ds_id, user_id, timestamp) VALUES (?,?,?)"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("iis",$DSid, $User, $Timestamp)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        return false;
    } else {
        return true;
    }

}

function ds_unterschreiben_formular_parts(){

    $link = connect_db();
    if(isset($_POST['ds'])){$Checked='checked';}else{$Checked='unchecked';}

    $Anfrage = "SELECT erklaerung, inhalt FROM datenschutzerklaerungen WHERE archivar = '0' ORDER BY create_time DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    $HTML = "<h3>Datenschutzerkl&auml;rung</h3>";
    $HTML .= "<p>Zur Info:<br>".$Ergebnis['erklaerung']."</p>";
    $HTML .= "<p>".$Ergebnis['inhalt']."</p>";
    $HTML .= " <p><label><input type='checkbox' name='ds' id='ds' checked='".$Checked."'><span>Ich stimme den Nutzungsbedingungen, sowie der Speicherung und Verarbeitung gem&auml;&szlig; der Datenschutzerkl&auml;rung zu.</span></label></p>";

    return $HTML;

}

?>