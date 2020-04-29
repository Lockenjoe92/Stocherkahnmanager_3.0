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
    if ((!isset($VonUser)) AND (!isset($VonKonto))){
        $DAUcounter++;
        $DAUerror .= "Es muss angegeben sein an wen die Forderung gerichtet ist!<br>";
    }

    //Referenz
    if ((!isset($Referenz)) AND (!isset($ReferenzReservierung))){
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

    return intval($Ergebnis['wert_aktuell']);
}

function lade_forderung_res($ResID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_forderungen WHERE referenz_res = '$ResID' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Forderung = mysqli_fetch_assoc($Abfrage);

    return $Forderung;
}

function lade_konto_user($User){

    $link = connect_db();

    $Anfrage = "SELECT * FROM finanz_konten WHERE name = '$User' AND verstecker = '0'";
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
        $Zaehler = $Zaehler + intval($Einnahme['betrag']);
    }

    return $Zaehler;
}