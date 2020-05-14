<?php
function forderung_generieren($Betrag, $Steuersatz, $VonUser, $VonKonto, $ReferenzReservierung, $Referenz, $ZahlbarBis, $Buchender){

    $DAUcounter = 0;
    $DAUerror = "";
    $Timestamp = timestamp();
    $link = connect_db();

    //DAU Block

    //Betrag
    if(!isset($Betrag)){
        $DAUcounter++;
        $DAUerror .= "Es wurde kein zu buchender Betrag eingegeben!<br>";
    }

    if ($Betrag < 0){
        $DAUcounter++;
        $DAUerror .= "Der Forderungsbetrag darf nicht negativ sein!<br>";
    }

    //Steuersatz
    if (!isset($Steuersatz)){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Angabe zum Steuersatz gemacht werden!<br>";
    }

    if (($Steuersatz < 1) AND ($Steuersatz >= 1)){
        $DAUcounter++;
        $DAUerror .= "Der Steuersatz muss in ganzen Zahlen angegeben werden!<br>";
    }

    //Forderung von
    if (($VonUser=='') AND ($VonKonto=='')){
        $DAUcounter++;
        $DAUerror .= "Es muss angegeben sein an wen die Forderung gerichtet ist!<br>";
    }

    //Referenz
    if (($Referenz=='') AND ($ReferenzReservierung=='')){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Referenz angegeben sein!<br>";
    }

    //Zahlungsziel
    if(!isset($ZahlbarBis)){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Zahlungsziel angegeben sein!<br>";
    }

    //DAU auswertung

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        //Forderung eintragen
        $AnfrageForderungEintragen = "INSERT INTO finanz_forderungen (betrag, steuersatz, von_user, von_konto, referenz_res, referenz, zahlbar_bis, timestamp, bucher, update_time, update_user, storno_time, storno_user) VALUES ('$Betrag', '$Steuersatz', '$VonUser', '$VonKonto', '$ReferenzReservierung', '$Referenz', '$ZahlbarBis', '$Timestamp', '$Buchender', '0000-00-00 00:00:00', '0', '0000-00-00 00:00:00', '0')";
        $AbfrageForderungEintragen = mysqli_query($link, $AnfrageForderungEintragen);

        if ($AbfrageForderungEintragen){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Forderung erfolgreich eingetragen!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Zugriff auf die Datenbank!<br>";
        }
    }
    return $Antwort;
}
function zahlungsgrenze_forderung_laden($EndeReservierung){

    $GrenzeXML = lade_xml_einstellung('zeit-tage-nach-res-ende-zahlen');
    $Befehl = "+ ".$GrenzeXML." days";
    $Grenze = date("Y-m-d G:i:s", strtotime($Befehl, strtotime($EndeReservierung)));

    return $Grenze;
}
function forderung_stornieren($ForderungID){

    $link = connect_db();

    $AnfrageForederungStornieren = "UPDATE finanz_forderungen SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '".$ForderungID."'";
    if(mysqli_query($link, $AnfrageForederungStornieren)){
        return true;
    } else {
        return false;
    }
}

function forderung_bearbeiten($NeuerBetrag, $ForderungID){

    $link = connect_db();

    $Anfrage = "UPDATE finanz_forderungen SET betrag = '$NeuerBetrag' WHERE id = '$ForderungID'";
    $Abfrage = mysqli_query($link, $Anfrage);

}
function lade_kontostand($Empfangskonto){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE id = '$Empfangskonto'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return floatval($Ergebnis['wert_aktuell']);
}

function lade_forderung_res($ResID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_forderungen WHERE referenz_res = '$ResID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Forderung = mysqli_fetch_assoc($Abfrage);

    return $Forderung;
}

function lade_offene_forderungen_user($UserID){
    $link = connect_db();
    $ReturnArray = array();
    $Anfrage = "SELECT * FROM finanz_forderungen WHERE von_user = '$UserID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link,$Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    for($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = lade_einnahmen_forderung($Ergebnis['id']);
        if($Einnahmen<$Ergebnis['betrag']){
            array_push($ReturnArray, $Ergebnis);
        }
    }
    return $ReturnArray;
}

function lade_konto_user($User){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE name = '$User' AND verstecker = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_forderung($ID){
    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_forderungen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_konto_via_id($ID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Konto = mysqli_fetch_assoc($Abfrage);

    return $Konto;
}

function lade_einnahme($IDeinnahme){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_einnahmen WHERE id = '$IDeinnahme'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Einnahme = mysqli_fetch_assoc($Abfrage);

    return $Einnahme;
}

function lade_ausgabe($IDeinnahme){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_ausgaben WHERE id = '$IDeinnahme'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Einnahme = mysqli_fetch_assoc($Abfrage);

    return $Einnahme;
}

function einnahme_loeschen($ID){
    $link = connect_db();
    $Einnahme = lade_einnahme($ID);
    $Konto = lade_konto_via_id($Einnahme['konto_id']);
    $Anfrage = "UPDATE finanz_einnahmen SET storno = '".timestamp()."', storno_user = ".lade_user_id()." WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']-$Einnahme['betrag'];
        return update_kontostand($Einnahme['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function ausgabe_loeschen($ID){
    $link = connect_db();
    $Ausgabe = lade_ausgabe($ID);
    $Konto = lade_konto_via_id($Ausgabe['konto_id']);
    $Anfrage = "UPDATE finanz_ausgaben SET storno = '".timestamp()."', storno_user = ".lade_user_id()." WHERE id = '$ID'";
    if(mysqli_query($link, $Anfrage)){
        $NeuerKontostand = $Konto['wert_aktuell']+$Ausgabe['betrag'];
        return update_kontostand($Ausgabe['konto_id'], $NeuerKontostand);
    } else{
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler beim Löschen';
        return $Antwort;
    }
}

function gesamteinnahmen_jahr($Jahr){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Einnahmen = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Einnahme = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = $Einnahmen + $Einnahme['betrag'];
    }

    return $Einnahmen;
}

function gesamteinnahmen_jahr_konto($Jahr, $KontoID){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_einnahmen WHERE konto_id = ".$KontoID." AND timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Einnahmen = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Einnahme = mysqli_fetch_assoc($Abfrage);
        $Einnahmen = $Einnahmen + $Einnahme['betrag'];
    }

    return $Einnahmen;
}

function gesamtausgaben_jahr($Jahr){

    $link = connect_db();

    $AnfangJahr = "".$Jahr."-01-01 00:00:01";
    $EndeJahr = "".$Jahr."-12-31 23:59:59";

    $Anfrage = "SELECT id, betrag FROM finanz_ausgaben WHERE timestamp > '$AnfangJahr' AND timestamp < '$EndeJahr' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);
    $Ausgaben = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Ausgabe = mysqli_fetch_assoc($Abfrage);
        $Ausgaben = $Ausgaben + $Ausgabe['betrag'];
    }

    return $Ausgaben;
}

function lade_gezahlte_summe_forderung($ForderungID){

    $link = connect_db();

    $AnfrageLadeZahlungen = "SELECT id, betrag FROM finanz_einnahmen WHERE forderung_id = '$ForderungID' AND storno_user = '0'";
    $AbfrageLadeZahlungen = mysqli_query($link, $AnfrageLadeZahlungen);
    $AnzahlLadeZahlungen = mysqli_num_rows($AbfrageLadeZahlungen);

    $Zaehler = 0;

    for ($a = 1; $a <= $AnzahlLadeZahlungen; $a++){

        $Einnahme = mysqli_fetch_assoc($AbfrageLadeZahlungen);
        $Zaehler = $Zaehler + floatval($Einnahme['betrag']);
    }

    return $Zaehler;
}
function einnahme_festhalten($Forderung, $Empfangskonto, $Betrag, $Steuersatz){

    $Timestamp = timestamp();
    $link = connect_db();

    $Anfrage = "INSERT INTO finanz_einnahmen (betrag, steuersatz, forderung_id, konto_id, timestamp, bucher, storno, storno_user) VALUES ('$Betrag', '$Steuersatz', '$Forderung', '$Empfangskonto', '$Timestamp', '".lade_user_id()."', '0000-00-00 00:00:00', '0')";
    if (mysqli_query($link, $Anfrage)){

        //Konto aktualisieren
        $KontoAktuell = lade_kontostand($Empfangskonto);
        $KontoNeu = floatval($KontoAktuell) + floatval($Betrag);
        update_kontostand($Empfangskonto, $KontoNeu);

        return true;
    } else {
        return false;
    }

}
function update_kontostand($KontoID, $KontostandNeu){

    $link = connect_db();

    $Anfrage = "UPDATE finanz_konten SET wert_aktuell = '$KontostandNeu' WHERE id = '$KontoID'";
    $Abfrage = mysqli_query($link, $Anfrage);

    return $Abfrage;
}

function einnahme_uebergabe_festhalten($UebergabeID, $GezahlterBetrag, $Empfaenger){

    $Uebergabe = lade_uebergabe($UebergabeID);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $Forderung = lade_forderung_res($Reservierung['id']);
    $Konto = lade_konto_user($Empfaenger);

    if (einnahme_festhalten($Forderung['id'], $Konto['id'], $GezahlterBetrag, 19)){
        return true;
    } else {
        return false;
    }
}
function wartkonto_anlegen($User){

    $link = connect_db();

    $Anfrage = "INSERT INTO finanz_konten (name, wert_start, wert_aktuell, typ, ersteller, erstellt, verstecker, versteckt) VALUES ('$User', 0, 0, 'wartkonto', '".lade_user_id()."', '".timestamp()."', 0, '0000-00-00 00:00:00')";
    $Abfrage = mysqli_query($link, $Anfrage);

    return $Abfrage;
}

function lade_gezahlte_betraege_ausgleich($AusgleichID){

    $link = connect_db();

    $Anfrage = "SELECT betrag FROM finanz_ausgaben WHERE ausgleich_id = '$AusgleichID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    $Counter = 0;

    for ($a = 1; $a <= $Anzahl; $a++){
        $Ausgabe = mysqli_fetch_assoc($Abfrage);
        $Counter = $Counter + $Ausgabe['betrag'];
    }

    return $Counter;
}

function nachzahlung_reservierung_festhalten($IDres, $Betrag, $Wart){

    $Antwort = array();

    $DAUcounter = 0;
    $DAUerror = "";

    if ($IDres == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Reservierung ausw&auml;hlen!<br>";
    }

    if ($Betrag == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen Betrag angeben!<br>";
    }

    //Forderung schon beglichen
    $Forderung = lade_forderung_res($IDres);
    $BisherigeZahlungen = lade_gezahlte_summe_forderung($Forderung['id']);
    if ($BisherigeZahlungen > floatval($Forderung['betrag'])){
        $DAUcounter++;
        $DAUerror .= "Forderung wurde inzwischen vollst&auml;ndig beglichen!<br>";
    }

    //Zuviel geld
    $Differenz = floatval($Forderung['betrag']) - $BisherigeZahlungen;
    $DifferenzBetrag = $Betrag - $Differenz;
    if ($DifferenzBetrag > 20){
        $DAUcounter++;
        $DAUerror .= "Der eingegebene Betrag &uuml;bersteigt die zul&auml;ssige Trinkgeldgrenze!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        $ForderungRes = lade_forderung_res($IDres);
        $KontoWart = lade_konto_user($Wart);

        if (einnahme_festhalten($ForderungRes['id'], $KontoWart['id'], $Betrag, 19)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Einnahme erfolgreich eingetragen!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Eintragen der Einnahme!";
        }
    }

    return $Antwort;
}

function lade_ausgleich($IDausgleich){
    $link = connect_db();
    $Anfrage = "SELECT * FROM finanz_ausgleiche WHERE id = '$IDausgleich'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);
    return $Termin;
}

function rueckzahlung_ausgleich_durchfuehren($TerminID, $Summe){

    $Termin = lade_termin($TerminID);
    if($Termin['grund']!='ausgleich'){
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    } else {
        $Ausgleich = lade_ausgleich($Termin['id_grund']);
        $Konto = lade_konto_user(lade_user_id());
        $Ausgabe = ausgabe_hinzufuegen($Summe, 19, $Ausgleich['id'], $Konto['id']);
        if($Ausgabe['success']){
            $Antwort = termin_durchfuehren($TerminID);
        } else {
            $Antwort = $Ausgabe;
        }
    }

    return $Antwort;
}

function ausgabe_hinzufuegen($Betrag, $Steuersatz, $Ausgleich, $Konto){

    $link = connect_db();
    if (!($stmt = $link->prepare("INSERT INTO finanz_ausgaben (betrag, steuersatz, ausgleich_id, konto_id, timestamp, bucher) VALUES (?,?,?,?,?,?)"))) {
        #echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    }

    if (!$stmt->bind_param("siiisi",$Betrag, $Steuersatz, $Ausgleich, $Konto, timestamp(), lade_user_id())) {
        #echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    }

    if (!$stmt->execute()) {
        #echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        $Antwort['success']=false;
        $Antwort['meldung']='Datenbankfehler';
    } else {
        $Kontoinfos = lade_konto_via_id($Konto);
        $NeuerKontostand = $Kontoinfos['wert_aktuell']-$Betrag;
        update_kontostand($Konto, $NeuerKontostand);

        $Antwort['success']=true;
        $Antwort['meldung']='Ausgabe erfolgreich festgehalten!';
    }

    return $Antwort;
}