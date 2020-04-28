<?php

function lade_schluesseldaten($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM schluessel WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    return mysqli_fetch_assoc($Abfrage);

}
function lade_letze_erinnerung_schluesselrueckgabe($IDres){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);
    $Typ = "mail_erinnerung_schluesselrueckgabe_intervall-".$IDres."";
    $TypZwei = "mail_erinnerung_schluesselrueckgabe_direkt_nach_fahrt-".$IDres."";

    $Anfrage = "SELECT timestamp FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$Typ' ORDER BY timestamp DESC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if($Anzahl == 0){

        $AnfrageZwei = "SELECT timestamp FROM mail_protokoll WHERE empfaenger = '".$Reservierung['user']."' AND typ = '$TypZwei' ORDER BY timestamp DESC";
        $AbfrageZwei = mysqli_query($link, $AnfrageZwei);
        $AnzahlZwei = mysqli_num_rows($AbfrageZwei);

        if ($AnzahlZwei == 0){
            return null;
        } else if ($AnzahlZwei > 0){
            $ErgebnisZwei = mysqli_fetch_assoc($AbfrageZwei);
            return $ErgebnisZwei['timestamp'];
        }

    } else if ($Anzahl > 0){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        return $Ergebnis['timestamp'];
    }
}
function schluesselrueckgabe_festhalten($ID){

    $link = connect_db();

    $AnfrageLadeAlleOffenenAusgaben = "SELECT id FROM schluesselausgabe WHERE schluessel = '$ID' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' AND storno_user = '0'";
    $AbfrageLadeAlleOffenenAusgaben = mysqli_query($link, $AnfrageLadeAlleOffenenAusgaben);
    $AnzahlLadeAlleOffenenAusgaben = mysqli_num_rows($AbfrageLadeAlleOffenenAusgaben);

    $Error = 0;

    for ($a = 1; $a <= $AnzahlLadeAlleOffenenAusgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleOffenenAusgaben);#

        $AnfrageUpdate = "UPDATE schluessel SET akt_user = '0', akt_ort = 'rueckgabekasten' WHERE id = '$ID'";
        if (mysqli_query($link, $AnfrageUpdate)){

            $AnfrageRueckgabeFesthalten = "UPDATE schluesselausgabe SET rueckgabe = '".timestamp()."' WHERE id = '".$Ausgabe['id']."'";
            if (!mysqli_query($link, $AnfrageRueckgabeFesthalten)){
                $Error++;
            } else {

                add_protocol_entry(lade_user_id(), 'Schluesselr&uuml;ckgabe durch '.lade_user_id().' festgehalten.', 'mail');

            }

        } else {
            $Error++;
        }
    }

    if ($Error == 0){
        return true;
    } else {
        return false;
    }

}
function schluessel_hinzufuegen($ChosenID, $Farbe, $FarbeMat, $RFID){

    $link = connect_db();
    $Antwort = array();

    //DAU
    $DAUcounter = 0;
    $DAUerror = "";

    if ($ChosenID == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Schl&uuml;sselnummer angeben!<br>";
    }

    if ($Farbe == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Schl&uuml;sselfarbe angeben!<br>";
    }

    if ($FarbeMat == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Materialize-Schl&uuml;sselfarbe angeben! Welche Farben es gibt kannst du <a href='http://materializecss.com/color.html'>hier</a> sehen.<br>";
    }

    $AnfrageDAU = "SELECT * FROM schluessel WHERE id = '$ChosenID'";
    $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
    $AnzahlDAU = mysqli_num_rows($AbfrageDAU);

    if ($AnzahlDAU > 0){
        $DAUcounter++;
        $DAUerror .= "Du hast diesen Schl&uuml;ssel bereits angelegt! Lade ggf. die Seite neu.<br>";
    }

    //DAU auswerten

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        if(isset($_POST['is_wartschluessel'])){
            $AnAusWart = 'on';
        } else {
            $AnAusWart = 'off';
        }

        $Anfrage = "INSERT INTO schluessel (id, farbe, farbe_materialize, RFID, ist_wartschluessel, akt_ort, akt_user, create_time, delete_time, delete_user, loeschgrund) VALUES ('$ChosenID','$Farbe', '$FarbeMat', '$RFID', '$AnAusWart', 'rueckgabekasten', '', '".timestamp()."', '0000-00-00 00:00:00', '0', '')";
        if (mysqli_query($link, $Anfrage)){

            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Schlüssel erfolgreich angelegt!";

        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Datenbankzugriff!";
        }
    }

    return $Antwort;
}
function schluessel_umbuchen($Schluessel, $AktuellerOrtUser, $AnUser, $AnOrt, $Wart){

    $link = connect_db();
    $Antwort = array();

    //DAU block falls notwendig
    $DAUcounter = 0;
    $DAUerror = "";

    //Kein schlüssel
    if($Schluessel == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen zu bewegenden Schl&uuml;ssel angeben!<br>";
    }

    //kein aktueller ort oder user übergeben
    if($AktuellerOrtUser == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss ein absendender Ort oder User übergeben werden!<br>";
    }

    //kein ort oder user angegeben
    if(($AnUser == "") AND ($AnOrt == "")){
        $DAUcounter++;
        $DAUerror .= "Du musst ein Ziel ausw&auml;hlen!<br>";
    }

    //sowohl ort als auch user gegeben
    if(($AnUser != "") AND ($AnOrt != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst nicht zwei Ziele angeben!<br>";
    }

    //AnUser ist kein Wart -> dann muss eine Schlüsselübergabe gemacht werden!
    if($AnUser > 0){

        $Anfrage = "SELECT id FROM user_rollen WHERE user = '$AnUser' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl == 0){
            $DAUcounter++;
            $DAUerror .= "Der gew&auml;hlte User ist kein Verwaltungsmitglied, sondern ein normaler User.<br>Um einem User einen Schl&uuml;ssel zu geben, nutze bitte die &Uuml;bergabefunktionen! So kann das System sich um eine zeitige R&uuml;ckgabe k&uuml;mmern!<br>";
        }
    }

    if($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){
        $Anfrage = "UPDATE schluessel SET akt_ort = '$AnOrt', akt_user = '$AnUser' WHERE id = '$Schluessel'";
        if (mysqli_query($link, $Anfrage)){

            $Event = "Umbuchung von ".$AktuellerOrtUser." nach ".$AnOrt."".$AnUser." durch ".$Wart."";
            add_protocol_entry(lade_user_id(), $Event, 'schluessel');

            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Der Schl&uuml;ssel wurde erfolgreich umgebucht!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler!";
        }
    }

    return $Antwort;
}
function schluessel_bearbeiten($SchluesselID, $NewID, $Farbe, $FarbeMatCSS, $RFID){

    $link = connect_db();
    $Antwort = array();
    $DAUcounter = 0;
    $DAUerror = "";

    //Kein Schlüssel gewählt
    if($SchluesselID == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen Schl&uuml;ssel ausw&auml;hlen!<br>";
    } else {

        //Schlüssel schon storniert?
        $Schluessel = lade_schluesseldaten($SchluesselID);
        if(intval($Schluessel['delete_user']) != 0){
            $DAUcounter++;
            $DAUerror .= "Der ausgew&auml;hlte Schl&uuml;ssel ist inzwischen storniert!<br>";
        }

        if($NewID!=""){
            if($SchluesselID!=$NewID){

                $AnfrageDAU = "SELECT * FROM schluessel WHERE id = '$NewID' AND delete_user = 0";
                $AbfrageDAU = mysqli_query($link, $AnfrageDAU);
                $AnzahlDAU = mysqli_num_rows($AbfrageDAU);

                if ($AnzahlDAU > 0){
                    $DAUcounter++;
                    $DAUerror .= "Ein Schlüssel mit dieser Nummer existiert bereits!<br>";
                }

            }
        }

    }

    if(($Farbe == "") AND ($FarbeMatCSS == "") AND ($RFID == "") AND ($NewID == "")){
        $DAUcounter++;
        $DAUerror .= "Du hast keine &Auml;nderungen eingegeben!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else {

        //Befehl bauen
        $Aenderungsbefehl = "";
        if ($Farbe != ""){
            $Aenderungsbefehl .= "farbe = '".$Farbe."', ";
        }

        if ($FarbeMatCSS != ""){
            $Aenderungsbefehl .= "farbe_materialize = '".$FarbeMatCSS."', ";
        }

        if ($Farbe != ""){
            $Aenderungsbefehl .= "RFID = '".$RFID."'";
        }

        if($NewID!=""){
            $Aenderungsbefehl .= "id = '".$NewID."'";
        }

        $Anfrage = "UPDATE schluessel SET ".$Aenderungsbefehl." WHERE id = '$SchluesselID'";
        if (mysqli_query($link, $Anfrage)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "&Auml;nderungen am Schl&uuml;ssel ".$SchluesselID." erfolgreich eingetragen!";
            $EintragText = "Schl&uuml;ssel ".$SchluesselID." von Wart ".lade_user_id()." bearbeitet: ".$Aenderungsbefehl."";
            add_protocol_entry(lade_user_id(),$EintragText, 'schluessel');
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler!";
        }
    }

    return $Antwort;
}