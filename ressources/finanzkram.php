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