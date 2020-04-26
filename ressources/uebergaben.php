<?php

function uebergabe_stornieren($ID, $Begruendung){

    $link = connect_db();
    $Benutzerrollen = lade_user_meta(lade_user_id());
    zeitformat();
    $Antwort = array();

    $Uebergabe = lade_uebergabe($ID);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $UserReservierung = lade_user_meta($Reservierung['user']);

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    //Keine Übergabe angegeben
    if (($ID == "") OR ($ID == 0)){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine zu stornierende &Uuml;bergabe angegeben!<br>";
    }

    //Übergabe bereits storniert
    if ($Uebergabe['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde bereits storniert!<br>";
    }

    if ($DAUcounter == 0){

        $Begruendung = htmlentities($Begruendung);

        $BausteineWartmails = array();
        $BausteineUsermails = array();
        $BausteineUsermails['vorname_user'] = $UserReservierung['vorname'];
        $BausteineUsermails['datum_resevierung'] = strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']));
        $BausteineUsermails['begruendung_wart'] = $Begruendung;

        if ($Benutzerrollen['wart'] == true){

            //Wartmode - nur User und Übernahmen werden informiert - plus anderer Wart wird informiert
            $Anfrage = "UPDATE uebergaben SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."', storno_kommentar = '$Begruendung' WHERE id = '$ID'";
            if (mysqli_query($link, $Anfrage)){

                //User informieren
                mail_senden('uebergabe-storniert-user', $UserReservierung['mail'], $UserReservierung['id'], $BausteineUsermails, '');

                //Übernahmen informieren
                $AnfrageLadeUebernahmen = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Uebergabe['res']."' AND storno_user = '0'";
                $AbfrageLadeUebernahmen = mysqli_query($link, $AnfrageLadeUebernahmen);
                $AnzahlLadeUebernahmen = mysqli_num_rows($AbfrageLadeUebernahmen);

                for ($a = 1; $a <= $AnzahlLadeUebernahmen; $a++){

                    $Uebernahme = mysqli_fetch_assoc($AbfrageLadeUebernahmen);

                    $Begruendung = "Stornierung der Schl&uuml;ssel&uuml;bergabe der vor dir fahrenden Gruppe durch einen Stocherkahnwart: - ".$Begruendung."";
                    uebernahme_stornieren($Uebernahme['id'], $Begruendung);

                }

                //Wenn nicht eigene Übergabe - anderen Wart informieren
                if ($Uebergabe['wart'] != lade_user_id()){

                    $WartUebergabe = lade_user_meta($Uebergabe['wart']);
                    $WartStornierung = lade_user_meta(lade_user_id());
                    $BausteineWartmails['vorname_wart'] = $WartUebergabe['vorname'];
                    $BausteineWartmails['zeitangabe_uebergabe'] = strftime("%A, den %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));
                    $BausteineWartmails['loeschender_wart'] = "".$WartStornierung['vorname']." ".$WartStornierung['nachname']."";
                    $BausteineWartmails['kommentar_loeschender_wart'] = $Begruendung;
                    $BausteineWartmails['zeitpunkt_uebergabe'] = strftime("%A, %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));

                    mail_senden('uebergabe-storniert-anderer-wart', $WartUebergabe['mail'], $WartUebergabe['id'], $BausteineWartmails, '');
                    sms_senden('uebergabe-storniert', $BausteineWartmails, $Uebergabe['wart'], NULL);

                }

                $Antwort['success'] = true;

            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler";
            }

        } else {

            //Usermode - auch der Wart wird informiert
            $Anfrage = "UPDATE uebergaben SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."', storno_kommentar = '$Begruendung' WHERE id = '$ID'";

            if (mysqli_query($link, $Anfrage)){

                //Übernahmen informieren
                $AnfrageLadeUebernahmen = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$Uebergabe['res']."' AND storno_user = '0'";
                $AbfrageLadeUebernahmen = mysqli_query($link, $AnfrageLadeUebernahmen);
                $AnzahlLadeUebernahmen = mysqli_num_rows($AbfrageLadeUebernahmen);

                for ($a = 1; $a <= $AnzahlLadeUebernahmen; $a++){

                    $Uebernahme = mysqli_fetch_assoc($AbfrageLadeUebernahmen);

                    $Begruendung = "Stornierung der Schl&uuml;ssel&uuml;bergabe der vor dir fahrenden Gruppe. Somit kann dir nicht garantiert werden, dass du von dieser Gruppe den Schl&uuml;ssel &uuml;bernehmen kannst!";
                    uebernahme_stornieren($Uebernahme['id'], $Begruendung);

                }

                //Wart informieren
                $WartUebergabe = lade_user_meta($Uebergabe['wart']);
                $BausteineWartmails['vorname_wart'] = $WartUebergabe['vorname'];
                $BausteineWartmails['zeitangabe_uebergabe'] = strftime("%A, den %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));
                $BausteineWartmails['kommentar_user'] = $Begruendung;
                $BausteineWartmails['zeitpunkt_uebergabe'] = strftime("%A, %d. %B %G - Beginn: %H:%M Uhr", strtotime($Uebergabe['beginn']));

                //Nach eigenen Einstellungen
                $Usersettings = lade_user_settings($Uebergabe['wart']);

                //Email
                if ($Usersettings['mail_uebergabe_storno'] == "1"){
                    mail_senden('uebergabe-storniert-wart', $WartUebergabe['mail'], $WartUebergabe['id'], $BausteineWartmails, '');
                }

                //SMS
                if ($Usersettings['sms_uebergabe_storno'] == "1"){
                    if (lade_einstellung('sms-active') == "TRUE"){
                        sms_senden('uebergabe-storniert', $BausteineWartmails, $Uebergabe['wart'], NULL);
                    }
                }

                $Antwort['success'] = true;

            } else {
                $Antwort['success'] = false;
                $Antwort['meldung'] = "Datenbankfehler";
            }

        }

    } else if ($DAUcounter > 0){

        $Antwort['success'] = false;
        $Antwort['meldung'] = $DAUerror;

    }

    return $Antwort;
}

function uebergabe_hinzufuegen($Res, $Wart, $Termin, $Beginn, $Kommentar, $Creator){

    $link = connect_db();
    $Timestamp = timestamp();
    $Antwort = array();
    zeitformat();

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    if ($Res == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss eine Reservierungsnummer angegeben sein!<br>";
    }

    //Hat die res schon ne gültige Übergabe?
    $AnfrageResSchonVersorgt = "SELECT id FROM uebergaben WHERE res = '$Res' AND storno_user = '0'";
    $AbfrageResSchonVersorgt = mysqli_query($link, $AnfrageResSchonVersorgt);
    $AnzahlResSchonVersorgt = mysqli_num_rows($AbfrageResSchonVersorgt);

    if ($AnzahlResSchonVersorgt > 0){
        $DAUcounter++;
        $DAUerror .= "F&uuml;r diese Resevierung gibt es schon eine Schl&uuml;ssel&uuml;bergabe! Bitte tauschen oder stornieren!<br>";
    }

    if ($Beginn == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst einen &Uuml;bergabezeitpunkt aussuchen!<br>";
    }

    if ($Wart == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Wart angegeben sein!<br>";
    }

    if ($Termin == ""){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Terminangebot angegeben sein!<br>";
    }

    //Ist das angebot inzwischen storniert?
    $Terminangebot = lade_terminangebot($Termin);
    if ($Terminangebot['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Das Terminangebot ist inzwischen abgelaufen!<br>";
    }

    //Limitierung schon abgelaufen?
    if($Terminangebot['terminierung'] != "0000-00-00 00:00:00"){
        if (time() > strtotime($Terminangebot['terminierung'])){
            $DAUcounter++;
            $DAUerror .= "Das Terminangebot ist inzwischen abgelaufen!<br>";
        }
    }

    //Hat der User überhaupt noch schlüssel?
    if(wart_verfuegbare_schluessel($Terminangebot['wart']) == 0){
        $DAUcounter++;
        $DAUerror .= "Leider sind bei diesem Wart inzwischen alle verf&uuml;gbaren Schl&uuml;ssel vergeben!<br>";
    }

    //Ist die Reservierung inzwischen storniert?
    $Reservierung = lade_reservierung($Res);
    if ($Reservierung['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Deine Reservierung wurde inzwischen storniert!<br>";
    }

    //Ist der User selber gesperrt?
    $UserMeta = lade_user_meta($Reservierung['user']);
    if ($UserMeta['ist_gesperrt'] == 'true'){
        $DAUcounter++;
        $DAUerror .= "Dein Account wurde leider f&uuml;r Buchungen gesperrt. Bitte setze dich mit uns in Verbindung!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;

    } else if ($DAUcounter == 0){

        $AnfrageUebergabeEintragen = "INSERT INTO uebergaben (res, wart, terminangebot, beginn, durchfuehrung, schluessel, angelegt_am, kommentar, storno_time, storno_user, storno_kommentar) VALUES ('$Res', '$Wart', '$Termin', '$Beginn', '0000-00-00 00:00:00', '0', '$Timestamp', '$Kommentar', '0000-00-00 00:00:00', '0', '')";
        if (mysqli_query($link, $AnfrageUebergabeEintragen)){

            //Daten für Mails und SMS laden
            $Termindaten = lade_terminangebot($Termin);
            $WartMeta = lade_user_meta($Wart);

            $BausteineUser = array();
            $BausteineUser['vorname_user'] = $UserMeta['vorname'];
            $BausteineUser['datum_uebergabe'] = strftime("%A, den %d. %B %G", strtotime($Beginn));
            $BausteineUser['uhrzeit_beginn'] = strftime("%R Uhr", strtotime($Beginn));
            $BausteineUser['dauer_uebergabe'] = lade_xml_einstellung('dauer-uebergabe-minuten');
            $BausteineUser['ort_uebergabe'] = $Termindaten['ort'];
            $BausteineUser['reservierungsnummer'] = $Res;
            $BausteineUser['kosten_reservierung'] = kosten_reservierung($Res);
            $BausteineUser['kontakt_wart'] = generiere_kontaktinformation_fuer_usermail_wart($Wart);

            $BausteineWart = array();
            $BausteineWart['vorname_wart'] = $WartMeta['vorname'];
            $BausteineWart['datum_uebergabe'] = strftime("%A, den %d. %B %G", strtotime($Beginn));
            $BausteineWart['uhrzeit_beginn'] = strftime("%R Uhr", strtotime($Beginn));
            $BausteineWart['ort_uebergabe'] = $Termindaten['ort'];
            $BausteineWart['reservierungsnummer'] = $Res;
            $BausteineWart['kosten_reservierung'] = kosten_reservierung($Res);
            $BausteineWart['kontakt_user'] = "".$UserMeta['vorname']." ".$UserMeta['nachname']."";

            if ($Kommentar != ""){
                $BausteineWart['kommentar_user'] = "Kommentar: ".$Kommentar."";
            } else {
                $BausteineWart['kommentar_user'] = "User hat keinen Kommentar hinterlassen.";
            }

            $BausteineSMSWart = array();
            $BausteineSMSWart['zeitpunkt_uebergabe'] = strftime("%a, %d. %b - %H:%M Uhr", strtotime($Beginn));
            $BausteineSMSWart['ort_uebergabe'] = $Termindaten['ort'];
            $BausteineSMSWart['name_user'] = "".$UserMeta['vorname']." ".$UserMeta['nachname']."";
            $BausteineSMSWart['tel_user'] = $UserMeta['telefon'];

            if ($Kommentar != ""){
                $BausteineSMSWart['kommentar_user'] = "Kommentar: ".$Kommentar."";
            } else {
                $BausteineSMSWart['kommentar_user'] = "";
            }

            if ($Reservierung['user'] == $Creator){

                //Bestätigungsmail an User
                if (mail_senden('uebergabe-angelegt-selbst', $UserMeta['mail'], $Reservierung['user'], $BausteineUser, '')){
                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = "Die &Uuml;bergabe wurde erfolgreich eingetragen!<br>Du erh&auml;ltst in K&uuml;rze eine Best&auml;tigungsmail:)";
                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Die &Uuml;bergabe wurde erfolgreich eingetragen!<br>Beim Senden der Best&auml;tigungsmail trat jedoch ein Fehler auf - bitte &uuml;berpr&uuml;fe deine Mailadresse in deinen Kontoeinstellungen!";
                }

                //Benachrichtigung des Wartes

                //Nach eigenen Einstellungen
                $Usersettings = lade_user_settings($Wart);

                //Email
                if ($Usersettings['mail-wart-neue-uebergabe'] == "1"){
                    mail_senden('uebergabe-bekommen-wart', $WartMeta['mail'], $Wart, $BausteineWart, '');
                }

                //SMS
                if ($Usersettings['sms-wart-neue-uebergabe'] == "1"){
                    if (lade_einstellung('sms-active') == "TRUE"){
                        sms_senden('neue-uebergabe-wart', $BausteineSMSWart, $Wart, NULL);
                    }
                }

            } else {

                //Von Wart erzeugt - andere Mail
                if (mail_senden('uebergabe-angelegt-wart', $UserMeta['mail'], $Reservierung['user'], $BausteineUser, '')){
                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = "Die &Uuml;bergabe wurde erfolgreich eingetragen!<br>Der User erh&auml;lt in K&uuml;rze eine Best&auml;tigungsmail:)";
                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Die &Uuml;bergabe wurde erfolgreich eingetragen!<br>Beim Senden der Best&auml;tigungsmail trat jedoch ein Fehler auf - bitte &uuml;berpr&uuml;fe die Mailadresse des Users!";
                }

                //Wart wird nicht benachrichtigt

            }

        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler";
        }
    }

    return $Antwort;
}

function uebergabe_durchfuehren($IDuebergabe, $Schluessel, $GezahlterBetrag, $AndererPreis, $Gratisfahrt, $Vertrag){

    $link = connect_db();
    $Antwort = array();
    $Uebergabe = lade_uebergabe($IDuebergabe);
    $Reservierung = lade_reservierung($Uebergabe['res']);

    //DAU

    $DAUcounter = 0;
    $DAUerror = "";

    //Kein Schlüssel
    if (($Schluessel == "") OR ($Schluessel == 0)){
        $DAUcounter++;
        $DAUerror .= "Du hast keinen Schl&uuml;ssel angegeben!<br>";
    }

    //Schlüssel steht nicht mehr zur Verfügung
    $AnfrageLadeSchluesselVerfuegbar = "SELECT akt_user FROM schluessel WHERE id = '$Schluessel'";
    $AbfrageLadeSchluesselVerfuegbar = mysqli_query($link, $AnfrageLadeSchluesselVerfuegbar);
    $ErgebnisLadeSchluesselVerfuegbar = mysqli_fetch_assoc($AbfrageLadeSchluesselVerfuegbar);

    if ($ErgebnisLadeSchluesselVerfuegbar['akt_user'] != $Uebergabe['wart']){
        $DAUcounter++;
        $DAUerror .= "Der ausgew&auml;hlte Schl&uuml;ssel steht dir nicht mehr zur Ausgabe zur Verf&uuml;gung!<br>";
    }

    //Keine Übergabe angegeben
    if (($IDuebergabe == "") OR ($IDuebergabe == 0)){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine &Uuml;bergabeID &uuml;bergeben!<br>";
    }

    //Übergabe storniert
    if ($Uebergabe['storno_user'] != 0){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde inzwischen storniert!<br>";
    }

    //Übergabe inzwischen durchgeführt
    if ($Uebergabe['durchfuehrung'] != "0000-00-00 00:00:00"){
        $DAUcounter++;
        $DAUerror .= "Die &Uuml;bergabe wurde inzwischen durchgef&uuml;hrt!<br>";
    }

    //Gratisfahrt gewählt aber Betrag gezahlt
    if (($Gratisfahrt == TRUE) AND ($GezahlterBetrag > 0)){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt angeben und trotzdem Geld einnehmen!<br>";
    }

    //Gratisfahrt und anderer Preis angegeben
    if (($Gratisfahrt == TRUE) AND ($AndererPreis > 0)){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und gleichzeitig einen anderen Tarif angeben!<br>";
    }

    //Zu viel gezahlt -> Betrag unrealistisch
    $Grenze = intval(lade_einstellung('stunde-18-nicht-student')) + 25;
    if (($GezahlterBetrag > $Grenze) OR ($AndererPreis > $Grenze)){
        $DAUcounter++;
        $DAUerror .= "Der von dir eingenommene Betrag &uuml;bersteigt die H&ouml;chsteinnahmegrenze von ".$Grenze."&euro;!<br>";
    }

    //User inzwischen gesperrt - keine Übergabe durchführen!
    if (user_gesperrt($Reservierung['user']) == TRUE){
        $DAUcounter++;
        $DAUerror .= "Der User ist inzwischen gesperrt! Bitte &uuml;berpr&uuml;fen!<br>";
    }

    //Alternativer Preis negativ
    if ($AndererPreis < 0){
        $DAUcounter++;
        $DAUerror .= "Alternativer Preis darf nicht negativ sein!<br>";
    }

    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['error'] = $DAUerror;

    } else {

        if ($Gratisfahrt == TRUE){
            reservierung_auf_gratis_setzen($Uebergabe['res']);
        }

        if ($AndererPreis > 0){
            reservierung_preis_aendern($Uebergabe['res'], $AndererPreis);
        }

        uebergabe_durchfuehrung_festhalten($IDuebergabe, $Schluessel);
        schluessel_an_user_ausgeben($IDuebergabe, $Schluessel, lade_user_id());
        einnahme_uebergabe_festhalten($IDuebergabe, $GezahlterBetrag, lade_user_id());

        //Vertragswesen
        if ($Vertrag == TRUE){
            vertragsunterzeichnung_festhalten($Reservierung['user'], lade_user_id());
        }

        $Antwort['success'] = TRUE;
        $Antwort['error'] = $DAUerror;

    }

    return $Antwort;
}

function uebergabe_durchfuehrung_festhalten($IDuebergabe, $Schluessel){

    $link = connect_db();
    $Timestamp = timestamp();

    $Anfrage = "UPDATE uebergaben SET durchfuehrung = '$Timestamp', schluessel = '$Schluessel' WHERE id = '$IDuebergabe'";
    if (mysqli_query($link, $Anfrage)){
        return true;
    } else {
        return false;
    }
}

function spontanuebergabe_durchfuehren($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag, $Vertrag){

    $link =connect_db();

    $DAUcounter = 0;
    $DAUerror = "";
    $Antwort = array();

    //Reservierung inzwischen schon versorgt
    $AnfrageLadeUebergabeResevierung = "SELECT id FROM uebergaben WHERE res = '$IDres' AND storno_user = '0' AND durchfuehrung > '0000-00-00 00:00:00'";
    $AbfrageLadeUebergabeResevierung = mysqli_query($link, $AnfrageLadeUebergabeResevierung);
    $AnzahlLadeUebergabeResevierung = mysqli_num_rows($AbfrageLadeUebergabeResevierung);

    if($AnzahlLadeUebergabeResevierung > 0){
        $DAUcounter++;
        $DAUerror .= "Die Reservierung wurde inzwischen schon versorgt!<br>";
    }

    //Reservierung inzwischen storniert
    $Reservierung = lade_reservierung($IDres);
    if($Reservierung['storno_user'] != "0"){
        $DAUcounter++;
        $DAUerror .= "Die Reservierung wurde inzwischen schon storniert!<br>";
    }

    //Anderer Preis ist keine Zahl
    if((!is_numeric($AndererPreis)) AND ($AndererPreis != "")){
        $DAUcounter++;
        $DAUerror .= "Alternativer Preis: Bitte gib nur ganze Zahlen an!<br>";
    }

    //Eingegebener Betrag ist keine Zahl
    if((!is_numeric($GezahlterBetrag)) AND ($GezahlterBetrag != "")){
        $DAUcounter++;
        $DAUerror .= "Zahlung: Bitte gib nur ganze Zahlen an!<br>";
    }

    //Schlüssel inzwischen schon vergeben
    $Schluessel = lade_schluesseldaten($IDschluessel);
    if($Schluessel['akt_user'] != lade_user_id()){
        $DAUcounter++;
        $DAUerror .= "Der von dir eingegebene Schl&uuml;ssel sollte nicht mehr zur Verf&uuml;gung stehen!<br>";
    }

    //Keine Reservierung gewählt
    if($IDres == ""){
        $DAUcounter++;
        $DAUerror .= "Du hast keine Reservierung ausgesucht!<br>";
    }

    //Kein Schlüssel gewählt
    if($IDschluessel == ""){
        $DAUcounter++;
        $DAUerror .= "Du hast keinen Schl&uuml;ssel ausgesucht!<br>";
    }

    //Gratisfahrt angekreuzt und Zahlung
    if(($Gratisfahrt == TRUE) AND ($AndererPreis != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und einen verg&uuml;nstigten Tarif angeben!<br>";
    }

    //Gratisfahrt angekreuzt und vergünstigter Tarif
    if(($Gratisfahrt == TRUE) AND ($GezahlterBetrag != "")){
        $DAUcounter++;
        $DAUerror .= "Du kannst keine Gratisfahrt und gleichzeitig eine Zahlung angeben!<br>";
    }

    if($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        //Andere Übergabe stornieren, falls vorhanden
        $AnfrageLadeAndereUebergabe = "SELECT id FROM uebergaben WHERE res = '$IDres' AND storno_user = '0'";
        $AbfrageLadeAndereUebergabe = mysqli_query($link, $AnfrageLadeAndereUebergabe);
        $AnzahlLadeAndereUebergabe = mysqli_num_rows($AbfrageLadeAndereUebergabe);

        if ($AnzahlLadeAndereUebergabe > 0){
            $Uebergabe = mysqli_fetch_assoc($AbfrageLadeAndereUebergabe);
            uebergabe_stornieren($Uebergabe['id'], 'Es wurde an anderer Stelle eine Spontan&uuml;bergabe durchgef&uuml;hrt');
        }

        //Andere Übernahme stornieren, falls vorhanden
        $AnfrageLadeAndereUebernahme = "SELECT id FROM uebernahmen WHERE reservierung = '$IDres' AND storno_user = '0'";
        $AbfrageLadeAndereUebernahme = mysqli_query($link, $AnfrageLadeAndereUebernahme);
        $AnzahlLadeAndereUebernahme = mysqli_num_rows($AbfrageLadeAndereUebernahme);

        if ($AnzahlLadeAndereUebernahme > 0){
            $Uebernahme = mysqli_fetch_assoc($AbfrageLadeAndereUebernahme);
            uebernahme_stornieren($Uebernahme['id'], '');
        }

        //Spontanübergabe eintragen
        $Ergebnis = spontanuebergabe_eintragen($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag, lade_user_id());

        //Vertragsunterzeichnung festhalten
        if ($Vertrag == TRUE){
            vertragsunterzeichnung_festhalten($Reservierung['user'], lade_user_id());
        }

        if ($Ergebnis['success'] == TRUE){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Spontan&uuml;bergabe erfolgreich eingetragen!";
        } else if ($Ergebnis['success'] == FALSE){
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $Ergebnis['meldung'];
        }
    }

    return $Antwort;
}

function spontanuebergabe_eintragen($IDres, $IDschluessel, $Gratisfahrt, $AndererPreis, $GezahlterBetrag, $Wart){

    $link = connect_db();
    $Errorcounter = 0;
    $Error = "";
    $Antwort = array();
    $Timestamp = timestamp();

    //Reservierung updaten bei Gratisfahrt
    if($Gratisfahrt == TRUE){
        if (!reservierung_auf_gratis_setzen($IDres)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Gratisfahrt!<br>";
        }
    }

    //Reservierung updaten bei Anderer Preis
    if ($AndererPreis != ""){
        reservierung_preis_aendern($IDres, $AndererPreis);
    }

    //Übergabeobjekt anlegen
    $AnfrageSpontanuebergabeEintragen = "INSERT INTO uebergaben (res, wart, terminangebot, beginn, durchfuehrung, schluessel, angelegt_am, kommentar, storno_time, storno_user, storno_kommentar) VALUES ('$IDres', '$Wart', '0', '".$Timestamp."', '".$Timestamp."', '$IDschluessel', '".$Timestamp."', 'Spontan&uuml;bergabe', '0000-00-00 00:00:00', '0', '')";
    if (!mysqli_query($link, $AnfrageSpontanuebergabeEintragen)){
        $Errorcounter++;
        $Error .= "Fehler beim Eintragen der Spontan&uuml;bergabe!<br>";
    } else {
        $AnfrageLadeIDuebergabe = "SELECT id FROM uebergaben WHERE angelegt_am = '".$Timestamp."' AND wart = '$Wart' AND storno_user = '0'";
        $AbfrageLadeIDuebergabe = mysqli_query($link, $AnfrageLadeIDuebergabe);
        $Spontanuebergabe = mysqli_fetch_assoc($AbfrageLadeIDuebergabe);

        //Schlüsselausgabe eintragen
        schluessel_an_user_ausgeben($Spontanuebergabe['id'], $IDschluessel, $Wart);
    }

    //Einnahme festhalten
    if ($GezahlterBetrag != ""){
        $Forderung = lade_forderung_res($IDres);
        $Konto = lade_konto_user($Wart);
        if(!einnahme_festhalten($Forderung['id'], $Konto['id'], $GezahlterBetrag, 19)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Einnahme!<br>";
        }
    }

    //Meldungen zurückgeben
    if ($Errorcounter == 0){
        $Antwort['success'] = true;
    } else if ($Errorcounter > 0){
        $Antwort['success'] = false;
        $Antwort['meldung'] = $Error;
    }

    return $Antwort;
}

function lade_terminangebot($IDtermin){

    $link = connect_db();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Terminangebot = mysqli_fetch_assoc($Abfrage);

    return $Terminangebot;
}

function lade_uebergabe($IDuebergabe){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebergaben WHERE id = '$IDuebergabe'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Uebergabe = mysqli_fetch_assoc($Abfrage);

    return $Uebergabe;

}

//LISTENELEMENTE
function terminangebot_listenelement_generieren($IDangebot){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDangebot'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Angebot = mysqli_fetch_assoc($Abfrage);

    $Wart = lade_user_meta($Angebot['wart']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M", strtotime($Angebot['von']))."</b> bis <b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b - %H:%M", strtotime($Angebot['von']))."</b> bis <b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";

    if($Angebot['terminierung'] == "0000-00-00 00:00:00"){
        $Terminierung = "keine Terminierung";
    } else {
        $Terminierung = "Terminierung: ".strftime("%a, %d. %b - %H:%M Uhr", strtotime($Angebot['terminierung']))."";
    }

    if($Angebot['kommentar'] == ""){
        $Kommentar = "kein Kommentar";
    } else {
        $Kommentar = $Angebot['kommentar'];
    }

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons'>label_outline</i>Terminangebot: ".$Zeitraum."</div>";
    echo "<div class='collapsible-body'>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'><i class='tiny material-icons'>alarm_on</i> ".$Terminierung."";
    echo "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Angebot['ort']."";
    echo "<li class='collection-item'><i class='tiny material-icons'>settings_ethernet</i> ".lade_entstandene_uebergaben($IDangebot)."";
    echo "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Kommentar."";
    echo "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Wart['vorname']." ".$Wart['nachname']."";
    echo "<li class='collection-item'> <a href='angebot_bearbeiten.php?id=".$IDangebot."'><i class='tiny material-icons'>mode_edit</i> bearbeiten</a> <a href='angebot_loeschen.php?id=".$IDangebot."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";
    echo "<li>";
    echo "<div class='collapsible-header hide-on-large-only'><i class='large material-icons'>label_outline</i>".$ZeitraumMobil."</div>";
    echo "<div class='collapsible-body'>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'><i class='tiny material-icons'>alarm_on</i> ".$Terminierung."";
    echo "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Angebot['ort']."";
    echo "<li class='collection-item'><i class='tiny material-icons'>settings_ethernet</i> ".lade_entstandene_uebergaben($IDangebot)."";
    echo "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Kommentar."";
    echo "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Wart['vorname']." ".$Wart['nachname']."";
    echo "<li class='collection-item'> <a href='angebot_bearbeiten.php?id=".$IDangebot."'><i class='tiny material-icons'>mode_edit</i> bearbeiten</a> <a href='angebot_loeschen.php?id=".$IDangebot."'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";
}

function uebergabe_listenelement_generieren($IDuebergabe, $Action){

    zeitformat();
    $Uebergabe = lade_uebergabe($IDuebergabe);
    $Terminangebot = lade_terminangebot($Uebergabe['terminangebot']);
    $Reservierung = lade_reservierung($Uebergabe['res']);
    $UserRes = lade_user_meta($Reservierung['user']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M Uhr", strtotime($Uebergabe['beginn']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b %H:%M Uhr", strtotime($Uebergabe['beginn']))."</b>";

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons'>today</i>Schl&uuml;ssel&uuml;bergabe: ".$Zeitraum."</div>";
    echo "<div class='collapsible-body'>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Terminangebot['ort']."";
    echo "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> <a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$UserRes['vorname']." ".$UserRes['nachname']."</a>";
    if($Uebergabe['kommentar'] != ""){
        echo "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Uebergabe['kommentar']."";
    }
    if($Action == TRUE){
        echo "<li class='collection-item'> <a href='uebergabe_durchfuehren.php?id=".$IDuebergabe."' class='btn waves-effect waves-light'><i class='tiny material-icons'>play_circle_filled</i> durchf&uuml;hren</a> <a href='uebergabe_loeschen_wart.php?id=".$IDuebergabe."' class='btn waves-effect waves-light'><i class='tiny material-icons'>delete</i> absagen</a>";
    }
    echo "</ul>";
    echo "</div>";
    echo "</li>";
    echo "<li>";
    echo "<div class='collapsible-header hide-on-large-only'><i class='large material-icons'>today</i>&Uuml;bergabe: ".$ZeitraumMobil."</div>";
    echo "<div class='collapsible-body'>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'><i class='tiny material-icons'>room</i> ".$Terminangebot['ort']."";
    echo "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> <a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$UserRes['vorname']." ".$UserRes['nachname']."</a>";
    if($Uebergabe['kommentar'] != ""){
        echo "<li class='collection-item'><i class='tiny material-icons'>comment</i> ".$Uebergabe['kommentar']."";
    }
    if($Action == TRUE){
        echo "<li class='collection-item'> <a href='uebergabe_durchfuehren.php?id=".$IDuebergabe."' class='btn waves-effect waves-light'><i class='tiny material-icons'>play_circle_filled</i> durchf&uuml;hren</a>";
        echo "<li class='collection-item'><a href='uebergabe_loeschen_wart.php?id=".$IDuebergabe."' class='btn waves-effect waves-light'><i class='tiny material-icons'>delete</i> absagen</a>";
    }

    echo "</ul>";
    echo "</div>";
    echo "</li>";
}

function termin_listenelement_generieren($IDtermin){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM termine WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Termin = mysqli_fetch_assoc($Abfrage);

    $Wart = lade_user_meta($Termin['wart']);

    //Textinhalte generieren
    $Zeitraum = "<b>".strftime("%A, %d. %b %G %H:%M Uhr", strtotime($Termin['zeitpunkt']))."</b>";

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>label_outline</i>Termin: </div>";
    echo "<div class='collapsible-body'>";
    echo "<ul class='collection'>";
    echo "<li class='collection-item'><i class='tiny material-icons'>class</i>";
    echo "<li class='collection-item'><i class='tiny material-icons'>schedule</i> ".$Zeitraum."";
    echo "<li class='collection-item'><i class='tiny material-icons'>info_outline</i> ";
    echo "<li class='collection-item'><i class='tiny material-icons'>perm_identity</i> Erstellt von ".$Wart['vorname']." ".$Wart['nachname']."";
    echo "<li class='collection-item'> <a href='termin_bearbeiten.php'><i class='tiny material-icons'>mode_edit</i> bearbeiten</a> <a href='termin_loeschen.php'><i class='tiny material-icons'>delete</i> l&ouml;schen</a>";
    echo "</ul>";
    echo "</div>";
    echo "</li>";
}

function spontanuebergabe_listenelement_generieren(){

    spontanuebergabe_listenelement_parser();

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>star</i>Spontan&uuml;bergabe</div>";
    echo "<div class='collapsible-body'>";
    echo "<div class='container'>";
    echo "<form method='post'>";

    echo "<div class='section'>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>today</i>";
    echo dropdown_aktive_res_spontanuebergabe('reservierung');
    echo "</div>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>vpn_key</i>";
    echo dropdown_verfuegbare_schluessel_wart('schluessel', lade_user_id());
    echo "</div>";

    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>grade</i>";
    echo "<input type='checkbox' name='gratis_fahrt' id='gratis_fahrt'>";
    echo "<label for='gratis_fahrt'>Als Gratisfahrt eintragen.</label>";
    echo "</div>";

    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>thumb_up</i>";
    echo "<input type='text' name='verguenstigung' id='verguenstigung' data-size='3'>";
    echo "<label for='verguenstigung'>Verg&uuml;nstigter Tarif</label>";
    echo "</div>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>toll</i>";
    echo "<input type='text' name='einnahme' id='einnahme' data-size='3'>";
    echo "<label for='einnahme'>Einnahmen</label>";
    echo "</div>";

    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>description</i>";
    echo "<input type='checkbox' name='vertrag' id='vertrag'>";
    echo "<label for='vertrag'>User hat Vertrag unterzeichnet.</label>";
    echo "</div>";

    echo "</div><div class='section'>";

    echo "<div class='input-field'>";
    echo "<button type='submit' name='action_spontanuebergabe_durchfuehren' class='btn waves-effect waves-light'>Durchf&uuml;hren</button>";
    echo "</div>";
    echo "</div>";

    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</li>";
}

function terminangebot_hinzufuegen_listenelement_generieren(){


    //Checkbox Schalter

    if(isset($_POST['terminierung_terminangebot_anlegen'])){
        $CheckboxTermin = "checked";
    } else {
        $CheckboxTermin = "unchecked";
    }

    terminangebot_hinzufuegen_listenelement_parser();

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>note_add</i>Terminangebot hinzuf&uuml;gen</div>";
    echo "<div class='collapsible-body'>";

    //echo "<div class='container'>";

    //Großer Screen
    echo "<div class='section hide-on-med-and-down'>";
    echo "<form method='POST'>";
    echo "<div class='container'>";

    echo "<div class='row'>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">today</i>";
    echo dropdown_datum('datum_terminangebot_anlegen', $_POST['datum_terminangebot_anlegen'], 30, TRUE);
    echo "<label for=\"datum_terminangebot_anlegen\">Datum</label>";
    echo "</div>";
    echo "<div class=\"input-field col s2\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_stunden('stunde_beginn_terminangebot_anlegen', $_POST['stunde_beginn_terminangebot_anlegen']);
    echo "<label for=\"stunde_beginn_terminangebot_anlegen\">Uhrzeit Beginn</label>";
    echo "</div>";
    echo "<div class=\"input-field col s1\">";
    echo dropdown_zentel_minuten('minute_beginn_terminangebot_anlegen', $_POST['minute_beginn_terminangebot_anlegen'], 00);
    echo "<label for=\"minute_beginn_terminangebot_anlegen\">Minuten</label>";
    echo "</div>";
    echo "<div class=\"input-field col s2\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_stunden('stunde_ende_terminangebot_anlegen', $_POST['stunde_ende_terminangebot_anlegen']);
    echo "<label for=\"stunde_ende_terminangebot_anlegen\">Uhrzeit Ende</label>";
    echo "</div>";
    echo "<div class=\"input-field col s1\">";
    echo dropdown_zentel_minuten('minute_ende_terminangebot_anlegen', $_POST['minute_ende_terminangebot_anlegen'], 00);
    echo "<label for=\"minute_ende_terminangebot_anlegen\">Minuten</label>";
    echo "</div>";
    echo "</div>";
    echo "<div class='row'>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">alarm_on</i>";
    echo "<input type='checkbox' name='terminierung_terminangebot_anlegen' id='terminierung_terminangebot_anlegen' " .$CheckboxTermin. ">";
    echo "<label for=\"terminierung_terminangebot_anlegen\">Terminierung aktivieren</label>";
    echo "</div>";
    echo "<div class=\"input-field col s6\">";
    echo dropdown_stunden('stunden_terminierung_terminangebot_anlegen', $_POST['stunden_terminierung_terminangebot_anlegen']);
    echo "<label for='stunden_terminierung_terminangebot_anlegen'>Terminierung Stunden</label>";
    echo "</div>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class='row'>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo dropdown_vorlagen_ortsangaben('ortsangabe_terminangebot_anlegen', lade_user_id(), $_POST['ortsangabe_terminangebot_anlegen']);
    echo "<label for=\"ortsangabe_terminangebot_anlegen\">Vorlage w&auml;hlen</label>";
    echo "</div>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo "<input type='text' id='ortsangabe_schriftlich_terminangebot_anlegen' name='ortsangabe_schriftlich_terminangebot_anlegen' value='".$_POST['ortsangabe_schriftlich_terminangebot_anlegen']."'>";
    echo "<label for=\"ortsangabe_schriftlich_terminangebot_anlegen\">Ortsangabe manuell eingeben</label>";
    echo "</div>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class='row'>";
    echo "<div class=\"input-field col s12\">";
    echo "<i class=\"material-icons prefix\">comment</i>";
    echo "<textarea name='kommentar_terminangebot_anlegen' id='kommentar_terminangebot_anlegen' class='materialize-textarea'>".$_POST['kommentar_terminangebot_anlegen']."</textarea>";
    echo "<label for=\"kommentar_terminangebot_anlegen\">Optional: weiterer Kommentar</label>";
    echo "</div>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class='row'>";
    echo "<div class=\"input-field\">";
    echo "<button class='btn waves-effect waves-light' type='submit' name='action_terminangebot_anlegen' value=''><i class=\"material-icons left\">send</i>Eintragen</button>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<button class='btn waves-effect waves-light' type='reset' name='reset_terminangebot_anlegen' value=''><i class=\"material-icons left\">clear_all</i>Reset</button>";
    echo "</div>";
    echo "</div>";

    echo "</div>";
    echo "</form>";
    echo "</div>";

    //kleiner Screen
    echo "<div class='section hide-on-large-only'>";
    echo "<form method='POST'>";
    echo "<div class='container'>";

    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">today</i>";
    echo dropdown_datum('datum_terminangebot_anlegen_mobil', $_POST['datum_terminangebot_anlegen_mobil'], 30, TRUE);
    echo "<label for=\"datum_terminangebot_anlegen_mobil\">Datum</label>";
    echo "</div>";
    echo "<div class='row'>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_stunden('stunde_beginn_terminangebot_anlegen_mobil', $_POST['stunde_beginn_terminangebot_anlegen_mobil']);
    echo "<label for=\"stunde_beginn_terminangebot_anlegen_mobil\">Uhrzeit Beginn</label>";
    echo "</div>";
    echo "<div class=\"input-field col s6\">";
    echo dropdown_zentel_minuten('minute_beginn_terminangebot_anlegen_mobil', $_POST['minute_beginn_terminangebot_anlegen_mobil'], 00);
    echo "<label for=\"minute_beginn_terminangebot_anlegen_mobil\">Minuten</label>";
    echo "</div>";
    echo "</div>";
    echo "<div class='row'>";
    echo "<div class=\"input-field col s6\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_stunden('stunde_ende_terminangebot_anlegen_mobil', $_POST['stunde_ende_terminangebot_anlegen_mobil']);
    echo "<label for=\"stunde_ende_terminangebot_anlegen_mobil\">Uhrzeit Ende</label>";
    echo "</div>";
    echo "<div class=\"input-field col s6\">";
    echo dropdown_zentel_minuten('minute_ende_terminangebot_anlegen_mobil', $_POST['minute_ende_terminangebot_anlegen_mobil'], 00);
    echo "<label for=\"minute_ende_terminangebot_anlegen_mobil\">Minuten</label>";
    echo "</div>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class='row'>";
    echo "<div class=\"input-field col s7\">";
    echo "<i class=\"material-icons prefix\">alarm_on</i>";
    echo "<input type='checkbox' name='terminierung_terminangebot_anlegen_mobil' id='terminierung_terminangebot_anlegen_mobil' " .$CheckboxTermin. ">";
    echo "<label for=\"terminierung_terminangebot_anlegen_mobil\">Terminierung aktivieren</label>";
    echo "</div>";
    echo "<div class=\"input-field col s4\">";
    //echo "<i class=\"material-icons prefix\">alarm_on</i>";
    echo dropdown_nummern('stunden_terminierung_terminangebot_anlegen_mobil', 1, 48, $_POST['stunden_terminierung_terminangebot_anlegen_mobil'], 'Zeitfenster');
    //echo "<label for='stunden_terminierung_terminangebot_anlegen'>Terminierung Stunden</label>";
    echo "</div>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo dropdown_vorlagen_ortsangaben('ortsangabe_terminangebot_anlegen_mobil', lade_user_id(), $_POST['ortsangabe_terminangebot_anlegen_mobil']);
    echo "<label for=\"ortsangabe_terminangebot_anlegen_mobil\">Vorlage w&auml;hlen</label>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo "<input type='text' id='ortsangabe_schriftlich_terminangebot_anlegen_mobil' name='ortsangabe_schriftlich_terminangebot_anlegen_mobil' value='".$_POST['ortsangabe_schriftlich_terminangebot_anlegen_mobil']."'>";
    echo "<label for=\"ortsangabe_schriftlich_terminangebot_anlegen_mobil\">Ortsangabe manuell eingeben</label>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">comment</i>";
    echo "<textarea name='kommentar_terminangebot_anlegen_mobil' id='kommentar_terminangebot_anlegen_mobil' class='materialize-textarea'>".$_POST['kommentar_terminangebot_anlegen_mobil']."</textarea>";
    echo "<label for=\"kommentar_terminangebot_anlegen_mobil\">Optional: weiterer Kommentar</label>";
    echo "</div>";

    echo "<div class='divider'></div>";

    echo "<div class=\"input-field\">";
    echo "<button class='btn waves-effect waves-light' type='submit' name='action_terminangebot_anlegen_mobil' value=''><i class=\"material-icons left\">send</i>Eintragen</button>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<button class='btn waves-effect waves-light' type='reset' name='reset_terminangebot_anlegen' value=''><i class=\"material-icons left\">clear_all</i>Formular leeren</button>";
    echo "</div>";

    echo "</div>";
    echo "</form>";
    echo "</div>";

    //echo "</div>";

    echo "</div>";
    echo "</li>";

}

function terminangebot_hinzufuegen_listenelement_parser(){

    if(isset($_POST['action_terminangebot_anlegen'])) {

        //DAU
        $DAUcounter = 0;
        $DAUerror = "";

        if(($_POST['datum_terminangebot_anlegen']) == ""){
            $DAUcounter++;
            $DAUerror .= "Du musst ein Datum f&uuml;r das Terminangebot angeben!<br>";
        }

        if(!isset($_POST['stunde_beginn_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Anfangsstunde w&auml;hlen!<br>";
        }

        if(!isset($_POST['minute_beginn_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Anfangsminute w&auml;hlen!<br>";
        }

        if(!isset($_POST['stunde_ende_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine End-stunde w&auml;hlen!<br>";
        }

        if(!isset($_POST['minute_ende_terminangebot_anlegen'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine End-minute w&auml;hlen!<br>";
        }

        if(isset($_POST['terminierung_terminangebot_anlegen'])){
            if(!isset($_POST['stunden_terminierung_terminangebot_anlegen'])){
                $DAUcounter++;
                $DAUerror .= "Wenn du eine Terminierung w&uuml;nschst, musst du angeben wie viele Stunden vorher das Angebot nicht mehr angezeigt werden soll!<br>";
            }
        }

        if(($_POST['ortsangabe_terminangebot_anlegen'] == "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen'] == "")){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Angabe zum Treffpunkt geben!<br>";
        }

        if(($_POST['ortsangabe_terminangebot_anlegen'] != "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen'] != "")){
            $DAUcounter++;
            $DAUerror .= "Du kannst nicht eine Ortsvorlage und eine manuelle Eingabe gleichzeitig machen!<br>";
        }

        $DatumBeginn = "".$_POST['datum_terminangebot_anlegen']." ".$_POST['stunde_beginn_terminangebot_anlegen'].":".$_POST['minute_beginn_terminangebot_anlegen'].":00";
        $DatumEnde = "".$_POST['datum_terminangebot_anlegen']." ".$_POST['stunde_ende_terminangebot_anlegen'].":".$_POST['minute_ende_terminangebot_anlegen'].":00";

        if (strtotime($DatumEnde) < strtotime($DatumBeginn)){
            $DAUcounter++;
            $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
        }

        if (strtotime($DatumBeginn) === strtotime($DatumEnde)){
            $DAUcounter++;
            $DAUerror .= "Die Zeitpunkte d&uuml;rfen nicht identisch sein!<br>";
        }

        //DAU auswerten
        if ($DAUcounter > 0){
            toast_ausgeben($DAUerror);
        } else {

            if (isset($_POST['terminierung_terminangebot_anlegen'])){
                $TerminierungBefehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen']." hours";
                $TerminierungTimestamp = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginn)));
            } else {
                $TerminierungTimestamp = "0000-00-00 00:00:00";
            }

            if ($_POST['ortsangabe_terminangebot_anlegen'] != ""){
                $Ortsangabe = $_POST['ortsangabe_terminangebot_anlegen'];
            } else {
                $Ortsangabe = $_POST['ortsangabe_schriftlich_terminangebot_anlegen'];
            }

            $Antwort = terminangebot_hinzufuegen(lade_user_id(), $DatumBeginn, $DatumEnde, $Ortsangabe, $_POST['kommentar_terminangebot_anlegen'], $TerminierungTimestamp);
            toast_ausgeben($Antwort['meldung']);
        }
    }

    if(isset($_POST['action_terminangebot_anlegen_mobil'])) {

        //DAU
        $DAUcounter = 0;
        $DAUerror = "";

        if(($_POST['datum_terminangebot_anlegen_mobil']) == ""){
            $DAUcounter++;
            $DAUerror .= "Du musst ein Datum f&uuml;r das Terminangebot angeben!<br>";
        }

        if(!isset($_POST['stunde_beginn_terminangebot_anlegen_mobil'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Anfangsstunde w&auml;hlen!<br>";
        }

        if(!isset($_POST['minute_beginn_terminangebot_anlegen_mobil'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Anfangsminute w&auml;hlen!<br>";
        }

        if(!isset($_POST['stunde_ende_terminangebot_anlegen_mobil'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine End-stunde w&auml;hlen!<br>";
        }

        if(!isset($_POST['minute_ende_terminangebot_anlegen_mobil'])){
            $DAUcounter++;
            $DAUerror .= "Du musst eine End-minute w&auml;hlen!<br>";
        }

        if(isset($_POST['terminierung_terminangebot_anlegen_mobil'])){
            if(!isset($_POST['stunden_terminierung_terminangebot_anlegen_mobil'])){
                $DAUcounter++;
                $DAUerror .= "Wenn du eine Terminierung w&uuml;nschst, musst du angeben wie viele Stunden vorher das Angebot nicht mehr angezeigt werden soll!<br>";
            }
        }

        if(($_POST['ortsangabe_terminangebot_anlegen_mobil'] == "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen_mobil'] == "")){
            $DAUcounter++;
            $DAUerror .= "Du musst eine Angabe zum Treffpunkt geben!<br>";
        }

        if(($_POST['ortsangabe_terminangebot_anlegen_mobil'] != "") AND ($_POST['ortsangabe_schriftlich_terminangebot_anlegen_mobil'] != "")){
            $DAUcounter++;
            $DAUerror .= "Du kannst nicht eine Ortsvorlage und eine manuelle Eingabe gleichzeitig machen!<br>";
        }

        $DatumBeginn = "".$_POST['datum_terminangebot_anlegen_mobil']." ".$_POST['stunde_beginn_terminangebot_anlegen_mobil'].":".$_POST['minute_beginn_terminangebot_anlegen_mobil'].":00";
        $DatumEnde = "".$_POST['datum_terminangebot_anlegen_mobil']." ".$_POST['stunde_ende_terminangebot_anlegen_mobil'].":".$_POST['minute_ende_terminangebot_anlegen_mobil'].":00";

        if (strtotime($DatumEnde) < strtotime($DatumBeginn)){
            $DAUcounter++;
            $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
        }

        if (strtotime($DatumBeginn) === strtotime($DatumEnde)){
            $DAUcounter++;
            $DAUerror .= "Die Zeitpunkte d&uuml;rfen nicht identisch sein!<br>";
        }

        //DAU auswerten
        if ($DAUcounter > 0){
            toast_ausgeben($DAUerror);
        } else {

            if (isset($_POST['terminierung_terminangebot_anlegen_mobil'])){
                $TerminierungBefehl = "- ".$_POST['stunden_terminierung_terminangebot_anlegen_mobil']." hours";
                $TerminierungTimestamp = date("Y-m-d G:i:s", strtotime($TerminierungBefehl, strtotime($DatumBeginn)));
            } else {
                $TerminierungTimestamp = "0000-00-00 00:00:00";
            }

            if ($_POST['ortsangabe_terminangebot_anlegen_mobil'] != ""){
                $Ortsangabe = $_POST['ortsangabe_terminangebot_anlegen_mobil'];
            } else {
                $Ortsangabe = $_POST['ortsangabe_schriftlich_terminangebot_anlegen_mobil'];
            }

            $Antwort = terminangebot_hinzufuegen(lade_user_id(), $DatumBeginn, $DatumEnde, $Ortsangabe, $_POST['kommentar_terminangebot_anlegen_mobil'], $TerminierungTimestamp);
            toast_ausgeben($Antwort['meldung']);
        }
    }
}

function spontanuebergabe_listenelement_parser(){

    if (isset($_POST['action_spontanuebergabe_durchfuehren'])){

        if(isset($_POST['vertrag'])){
            $Vertrag = TRUE;
        } else {
            $Vertrag = FALSE;
        }

        $Ergebnis = spontanuebergabe_durchfuehren($_POST['reservierung'], $_POST['schluessel'], $_POST['gratis_fahrt'], $_POST['verguenstigung'], $_POST['einnahme'], $Vertrag);
        toast_ausgeben($Ergebnis['meldung']);
    }

}

function terminangebot_hinzufuegen($IDwart, $Beginn, $Ende, $Ort, $Kommentar, $Terminierung){

    //Eintragen
    $link = connect_db();
    $Timestamp = timestamp();

    //DAU
    $DAUcounter = 0;
    $DAUerror = "";

    if($Ort == ""){
        $DAUcounter++;
        $DAUerror .= "Du musst eine Angabe zum Treffpunkt geben!<br>";
    }

    if(!isset($IDwart)){
        $DAUcounter++;
        $DAUerror .= "Es muss ein Wart angegeben sein!<br>";
    }

    if (strtotime($Ende) < strtotime($Beginn)){
        $DAUcounter++;
        $DAUerror .= "Der Anfang darf nicht nach dem Ende liegen!<br>";
    }

    if (strtotime($Beginn) === strtotime($Ende)){
        $DAUcounter++;
        $DAUerror .= "Die Zeitpunkte d&uuml;rfen nicht identisch sein! Second check!<br>";
    }

    if (time() > strtotime($Ende)){
        $DAUcounter++;
        $DAUerror .= "Du kannst kein Angebot f&uuml;r die Vergangenheit eingeben!<br>";
    }

    //Überprüfe clash mit vorhandenem Angebot
    $AnfrageClash = "SELECT id FROM terminangebote WHERE wart = '$IDwart' AND (((von <= '$Beginn') AND (bis >= '$Ende')) OR (('$Beginn' < von) AND ('$Ende' > von)) OR (('$Beginn' < bis) AND ('$Ende' > bis))) AND storno_user = '0'";
    $AbfrageClash = mysqli_query($link, $AnfrageClash);
    $AnzahlClash = mysqli_num_rows($AbfrageClash);

    if ($AnzahlClash > 0){
        $DAUcounter++;
        $DAUerror .= "Zu dem angegebenen Zeitpunkt hast du bereits ein Angebot im System!<br>";
    }

    //DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else {

        $Anfrage = "INSERT INTO terminangebote (wart, von, bis, terminierung, ort, kommentar, create_time, create_user, storno_time, storno_user) VALUES ('$IDwart', '$Beginn','$Ende','$Terminierung','$Ort','$Kommentar','$Timestamp','".lade_user_id()."','0000-00-00 00:00:00','0')";
        if (mysqli_query($link, $Anfrage)){
            $Antwort['success'] = TRUE;
            $Antwort['meldung'] = "Terminangebot erfolgreich eingetragen!";
        } else {
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Datenbankfehler";
        }
    }

    return $Antwort;
}

function lade_entstandene_uebergaben($IDangebot){

    $link = connect_db();
    $Antwort = "";

    $Anfrage = "SELECT id FROM uebergaben WHERE terminangebot = '$IDangebot' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){
        $Antwort .= "Bislang keine resultierenden &Uuml;bergaben.";
    } else if ($Anzahl > 1){
        $Antwort .= "Bislang ".$Anzahl." resultierende &Uuml;bergaben.";
    } else if ($Anzahl == 1){
        $Antwort .= "Bislang eine resultierende &Uuml;bergabe.";
    }

    return $Antwort;
}

function uebergabe_planen_listenelement_generieren(){

    uebergabe_planen_listenelement_parser();

    //Ausgabe
    echo "<li>";
    echo "<div class='collapsible-header'><i class='large material-icons'>open_in_browser</i>&Uuml;bergabe vorplanen</div>";
    echo "<div class='collapsible-body'>";
    echo "<div class='container'>";
    echo "<form method='post'>";

    //Reservierung und deren modifizierung
    echo "<h5>Reservierung w&auml;hlen</h5>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>today</i>";
    echo dropdown_aktive_res_spontanuebergabe('reservierung_uebergabe_vorplanen');
    echo "</div>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>grade</i>";
    echo "<input type='checkbox' name='gratis_fahrt_uebergabe_vorplanen' id='gratis_fahrt_uebergabe_vorplanen'>";
    echo "<label for='gratis_fahrt_uebergabe_vorplanen'>Als Gratisfahrt eintragen.</label>";
    echo "</div>";
    echo "<div class='input-field'>";
    echo "<i class='material-icons prefix'>thumb_up</i>";
    echo "<input type='text' name='verguenstigung_uebergabe_vorplanen' id='verguenstigung_uebergabe_vorplanen' data-size='3'>";
    echo "<label for='verguenstigung_uebergabe_vorplanen'>Verg&uuml;nstigter Tarif</label>";
    echo "</div>";

    //Übergabeort
    echo "<h5>&Uuml;bergabeort w&auml;hlen</h5>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo dropdown_vorlagen_ortsangaben('ortsangabe_uebergabe_vorplanen', lade_user_id(), $_POST['ortsangabe_uebergabe_vorplanen']);
    echo "<label for=\"ortsangabe_uebergabe_vorplanen\">Vorlage w&auml;hlen</label>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">room</i>";
    echo "<input type='text' id='ortsangabe_schriftlich_uebergabe_vorplanen' name='ortsangabe_schriftlich_uebergabe_vorplanen' value='".$_POST['ortsangabe_schriftlich_uebergabe_vorplanen']."'>";
    echo "<label for=\"ortsangabe_schriftlich_uebergabe_vorplanen\">Ortsangabe manuell eingeben</label>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">comment</i>";
    echo "<textarea name='kommentar_uebergabe_vorplanen' id='kommentar_uebergabe_vorplanen' class='materialize-textarea'>".$_POST['kommentar_uebergabe_vorplanen']."</textarea>";
    echo "<label for=\"kommentar_uebergabe_vorplanen\">Optional: weiterer Kommentar</label>";
    echo "</div>";


    //Übergabezeitpunkt
    echo "<h5>&Uuml;bergabezeit w&auml;hlen</h5>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">today</i>";
    echo dropdown_datum('datum_terminangebot_anlegen', $_POST['datum_uebergabe_vorplanen'], 30, TRUE);
    echo "<label for=\"datum_terminangebot_anlegen\">Datum</label>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_stunden('stunde_beginn_terminangebot_anlegen', $_POST['stunde_beginn_uebergabe_vorplanen']);
    echo "<label for=\"stunde_beginn_terminangebot_anlegen\">Uhrzeit Beginn</label>";
    echo "</div>";
    echo "<div class=\"input-field\">";
    echo "<i class=\"material-icons prefix\">schedule</i>";
    echo dropdown_zentel_minuten('minute_beginn_terminangebot_anlegen', $_POST['minute_beginn_uebergabe_vorplanen'], 00);
    echo "<label for=\"minute_beginn_terminangebot_anlegen\">Minuten</label>";
    echo "</div>";

    echo "<div class='input-field'>";
    echo "<button type='submit' name='action_uebergabe_vorplanen_durchfuehren' class='btn waves-effect waves-light'>Anlegen</button>";
    echo "</div>";
    echo "</form>";
    echo "</div>";
    echo "</div>";
    echo "</li>";

}

function uebergabe_planen_listenelement_parser(){

    if (isset($_POST['action_uebergabe_vorplanen_durchfuehren'])){

        //Reservierung
        $ResID = $_POST['reservierung_uebergabe_vorplanen'];

        //GRatisfahrt?
        if ($_POST['gratis_fahrt_uebergabe_vorplanen']){
            $Gratis = TRUE;
        } else {
            $Gratis = FALSE;
        }

        //Vergünstigung?
        $Verguenstigung = $_POST['verguenstigung_uebergabe_vorplanen'];

        //Übergabeort?
        $Dropdown = $_POST['ortsangabe_uebergabe_vorplanen'];
        $Manuell = $_POST['ortsangabe_schriftlich_uebergabe_vorplanen'];
        if (($Dropdown != "") AND ($Manuell != "")){
            toast_ausgeben('Du kannst nicht gleichzeitig eine Vorlage nutzen und einen &Uuml;bergabeort schriftlich eingeben!');
            return NULL;
        } else {
            $Uebergabeort = "".$Dropdown."".$Manuell."";
        }

        //Kommentar
        $Kommentar = $_POST['kommentar_uebergabe_vorplanen'];

        //Übergabezeitpunkt
        $Zeitpunkt = "".$_POST['datum_terminangebot_anlegen']." ".$_POST['stunde_beginn_terminangebot_anlegen'].":".$_POST['minute_beginn_terminangebot_anlegen'].":00";

        $Parser = geplante_uebergabe_eintragen($ResID, $Gratis, $Verguenstigung, $Uebergabeort, $Kommentar, $Zeitpunkt);
        if ($Parser['meldung'] != ""){
            toast_ausgeben($Parser['meldung']);
        }

    } else {
        return NULL;
    }
}

function geplante_uebergabe_eintragen($ResID, $Gratis, $Verguenstigung, $Uebergabeort, $Kommentar, $Zeitpunkt){

    $link = connect_db();
    $Antwort = array();
    $DAUcounter = 0;
    $DAUerror = "";

    //MEEEGGAAA DAU-Checks

    //Eingegebener Zeitpunkt liegt in Vergangenheit
    if(strtotime($Zeitpunkt) < time()){
        $DAUcounter++;
        $DAUerror .= "- Der eingegebene Zeitpunkt liegt in der Vergangenheit!<br>";
    }

    //Kein Übergabeort gewählt
    if($Uebergabeort == ""){
        $DAUcounter++;
        $DAUerror .= "- Du musst eine Angabe zum &Uuml;bergabeort eingeben!<br>";
    }

    //Keine Reservierung gewählt
    if(($ResID == 0) OR ($ResID == "")){
        $DAUcounter++;
        $DAUerror .= "- Du musst eine Reservierung anw&auml;hlen!<br>";
    } else {

        //Reservierung bereits storniert
        $Reservierung = lade_reservierung($ResID);
        if($Reservierung['storno_user'] != "0"){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung wurde inzwischen storniert!<br>";
        }

        //Reservierung hat schon eine Übergabe geklickt
        $Uebergaben = lade_uebergabe_res($ResID);
        if($Uebergaben != NULL){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung hat inzwischen schon eine &Uuml;bergabe ausgemacht!<br>";
        }

        //Reservierung ist schon abgelaufen
        if(strtotime($Reservierung['ende'] < time())){
            $DAUcounter++;
            $DAUerror .= "- Die Reservierung ist inzwischen abgelaufen!<br>";
        }
    }

    //Gratis und Vergünstigung
    if(($Gratis === TRUE) AND (intval($Verguenstigung) != 0)){
        $DAUcounter++;
        $DAUerror .= "- Du kannst nicht eine Verg&uuml;nstigung und eine Gratisfahrt gleichzeitig eingeben!<br>";
    }

    //Vergünstigung negativ
    if(intval($Verguenstigung) < 0){
        $DAUcounter++;
        $DAUerror .= "- Du kannst keine Reservierungen f&uuml;r negative Preise einstellen!<br>";
    }

    //DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = "<p><b>Fehler beim Eintragen der &Uuml;bergabe:</b></p><p>".$DAUerror."</p>";
    } else {

        //Terminangebot erstellen

        $Antwort = geplante_uebergabe_hinzufuegen($ResID, lade_user_id(), $Gratis, $Verguenstigung, $Uebergabeort, $Zeitpunkt, $Kommentar);

    }

    return $Antwort;

}

function geplante_uebergabe_hinzufuegen($ResID, $Wart, $Gratis, $Verguenstigung, $Uebergabeort, $Zeitpunkt, $Kommentar){

    $link = connect_db();
    $Errorcounter = 0;
    $Error = "";
    $Antwort = array();
    $Timestamp = timestamp();

    //Reservierung updaten bei Gratisfahrt
    if($Gratis == TRUE){
        if (!reservierung_auf_gratis_setzen($ResID)){
            $Errorcounter++;
            $Error .= "Fehler beim Eintragen der Gratisfahrt!<br>";
        }
    }

    //Reservierung updaten bei Anderer Preis
    if ($Verguenstigung != ""){
        reservierung_preis_aendern($ResID, $Verguenstigung);
    }

    //Terminobjekt anlegen
    $ZeitpunktZwei = date("Y-m-d G:i:s", strtotime("+ 10 Minutes", strtotime($Zeitpunkt)));
    $Hinzufuegen = terminangebot_hinzufuegen($Wart, $Zeitpunkt, $ZeitpunktZwei, $Uebergabeort, $Kommentar, '0000-00-00 00:00:00');

    if ($Hinzufuegen['success'] == FALSE){

        //Überprüfe clash mit vorhandenem Angebot
        $AnfrageClash = "SELECT id, ort FROM terminangebote WHERE wart = '$Wart' AND (((von <= '$Zeitpunkt') AND (bis >= '$ZeitpunktZwei')) OR (('$Zeitpunkt' < von) AND ('$ZeitpunktZwei' > von)) OR (('$Zeitpunkt' < bis) AND ('$ZeitpunktZwei' > bis))) AND storno_user = '0'";
        $AbfrageClash = mysqli_query($link, $AnfrageClash);
        $AnzahlClash = mysqli_num_rows($AbfrageClash);

        if ($AnzahlClash > 0){

            //Wenn Ortsangabe mit Clash überein stimmt, weitermachen mit clashangebot, ansonsten, Error!
            $ClashAngebot = mysqli_fetch_assoc($AbfrageClash);

            if($Uebergabeort != $ClashAngebot['ort']){
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Fehler: zu dem eingegebenen Zeitpunkt hast du bereits ein anderes &Uuml;bergabeangebot!<br>Wenn du dieses verwenden m&ouml;chtest, verwende im Formular die identische Ortsangabe: ".$ClashAngebot['ort']."";
            } else if ($Uebergabeort == $ClashAngebot['ort']){

                //Weitermachen!!
                $Antwort = uebergabe_hinzufuegen($ResID, $Wart, $ClashAngebot['id'], $Zeitpunkt, $Kommentar, lade_user_id());
            }

        } else {
            $Antwort = $Hinzufuegen;
        }

    } else {
        //ID Terminangebot laden
        $AnfrageLadeAngebotID = "SELECT id FROM terminangebote WHERE wart = '$Wart' AND von = '$Zeitpunkt' AND bis = '$ZeitpunktZwei' AND storno_user = '0' AND ort = '$Uebergabeort'";
        $AbfrageLadeAngebotID = mysqli_query($link, $AnfrageLadeAngebotID);
        $AnzahlLadeAngebotID = mysqli_num_rows($AbfrageLadeAngebotID);

        if ($AnzahlLadeAngebotID == 0){
            //Fehler
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Anlegen des &Uuml;bergabeangebotes! Kein Objekt angelegt!";
        } else if ($AnzahlLadeAngebotID > 1){
            //Fehler
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = "Fehler beim Anlegen des &Uuml;bergabeangebotes! Es existieren zu viele Objekte!";
        } else if ($AnzahlLadeAngebotID == 1){

            //Weiter
            $Angebot = mysqli_fetch_assoc($AbfrageLadeAngebotID);
            $Antwort = uebergabe_hinzufuegen($ResID, $Wart, $Angebot['id'], $Zeitpunkt, $Kommentar, lade_user_id());
        }
    }

    return $Antwort;

}

function schluesseluebergabe_ausmachen_moeglichkeiten_anzeigen($IDres){

    $link = connect_db();
    $Reservierung = lade_reservierung($IDres);
    $HTML = "";

    if(res_hat_uebergabe($IDres)){

        $HTML .= "<div class='card-panel materialize-" .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
        $HTML .= "<h5 class='center-align'>Fehler!</h5>";
        $HTML .= "<div class='section center-align'>";
        $HTML .= "<p>Du hast f&uuml;r diese Reservierung bereits eine Schl&uuml;ssel&uuml;bergabe ausgemacht!</p>";
        $HTML .= button_link_creator('Zurück', './my_reservations.php', 'arrow_back', '');
        $HTML .= "</div>";
        $HTML .= "</div>";

    } else {

        $BefehlGrenz = "- ".lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
        $BefehlGrenzZwei = "- ".lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
        $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
        $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

        //Passen zeitlich?
        $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."' AND storno_user = '0' ORDER BY von ASC";
        $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
        $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

        $HTMLcollapsible = "";
        if ($AnzahlSucheTerminangebote == 0){

            //Für seine reservierung gibts nichts passendes
            $HTMLcollapsible .= collapsible_item_builder('Kein passender Termin verf&uuml;gbar!', 'Derzeit gibt es keinen passenden Termin f&uuml;r deine Reservierung. Bitte schau daher einfach in K&uuml;rze wieder vorbei:)', 'error');

        } else if ($AnzahlSucheTerminangebote > 0){

            $Counter = 0;

            for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

                $Terminangebot = mysqli_fetch_assoc($AbfrageSucheTerminangebote);
                //Hat der Wart noch Schlüssel?
                if(wart_verfuegbare_schluessel($Terminangebot['wart']) > 0){
                    $Counter++;
                    $HTMLcollapsible .= terminangebot_listenelement_buchbar_generieren($Terminangebot['id']);
                }
            }

            if ($Counter == 0){
                //Hier gibts nichts, aber Zeit wäre dazu - liegt an shchlüsseln
                $HTMLcollapsible .= collapsible_item_builder('Keine Schl&uuml;ssel verf&uuml;gbar!', 'Derzeit sind alle Schl&uuml;ssel im Umlauf. Daher k&ouml;nnen wir dir aktuell keinen Termin anbieten. Wir arbeiten daran immer schnell wieder an welche zu kommen. Bitte schau daher einfach in K&uuml;rze wieder vorbei:)', 'error');
            }
        }

        $HTML .= section_builder(collapsible_builder($HTMLcollapsible));
        $HTML .= section_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''));
    }

    return $HTML;
}

function parser_uebergabe_hinzufuegen_ueser($ReservierungID){

    $link = connect_db();
    $Reservierung = lade_reservierung($ReservierungID);

    $BefehlGrenz = "- ".lade_xml_einstellung('max-tage-vor-abfahrt-uebergabe')." days";
    $BefehlGrenzZwei = "- ".lade_xml_einstellung('max-minuten-vor-abfahrt-uebergabe')." minutes";
    $GrenzstampNachEinstellung = date("Y-m-d G:i:s", strtotime($BefehlGrenz, strtotime($Reservierung['beginn'])));
    $GrenzstampNachEinstellungZwei = date("Y-m-d G:i:s", strtotime($BefehlGrenzZwei, strtotime($Reservierung['beginn'])));

    //Passen zeitlich?
    $AnfrageSucheTerminangebote = "SELECT * FROM terminangebote WHERE von > '$GrenzstampNachEinstellung' AND von < '$GrenzstampNachEinstellungZwei' AND bis > '".timestamp()."'  AND storno_user = '0'";
    $AbfrageSucheTerminangebote = mysqli_query($link, $AnfrageSucheTerminangebote);
    $AnzahlSucheTerminangebote = mysqli_num_rows($AbfrageSucheTerminangebote);

    $Antwort = NULL;

    if ($AnzahlSucheTerminangebote == 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = ("Leider stehen derzeit keine Terminangebote zur Verf&uuml;gung!");
    } else if ($AnzahlSucheTerminangebote > 0){

        for ($a = 1; $a <= $AnzahlSucheTerminangebote; $a++){

            $Termin = mysqli_fetch_assoc($AbfrageSucheTerminangebote);

            $Suchbefehl = "action_termin_".$Termin['id']."";
            $Terminfeld = "zeitfenster_gewaehlt_terminangebot_".$Termin['id']."";
            $Kommentarfeld = "kommentar_uebergabe_".$Termin['id']."";

            if (isset($_POST[$Suchbefehl])){
                $hinzufueger = uebergabe_hinzufuegen($ReservierungID, $Termin['wart'], $Termin['id'], $_POST[$Terminfeld], $_POST[$Kommentarfeld], lade_user_id());
                $Antwort = $hinzufueger;
            }
        }
    }

    return $Antwort;
}

function uebergabe_erfolgreich_eingetragen_user(){

    $Antwort = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $Antwort .= "<h5 class='center-align'>Gl&uuml;ckwunsch!</h5>";
    $Antwort .= "<div class='section center-align'>";
    $Antwort .= "<p>Nun hast du erfolgreich eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Jetzt muss nur noch das Treffen klappen und es steht deinem Stocherabenteuer nichts mehr im Wege!</p>";
    $Antwort .= "<p><a href='my_reservations.php' class='btn waves-effect waves-light'>Zur&uuml;ck</a></p>";
    $Antwort .= "</div>";
    $Antwort .= "</div>";

    return $Antwort;

}

function terminangebot_listenelement_buchbar_generieren($IDangebot){

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDangebot'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Angebot = mysqli_fetch_assoc($Abfrage);

    $Wart = lade_user_meta($Angebot['wart']);

    //Textinhalte generieren
    #$Zeitraum = "<b>".strftime("%A, %d. %B %G %H:%M", strtotime($Angebot['von']))."</b> bis <b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";
    $ZeitraumMobil = "<b>".strftime("%a, %d. %b - %H:%M", strtotime($Angebot['von']))."</b> bis <b>".strftime("%H:%M Uhr", strtotime($Angebot['bis']))."</b>";

    if($Angebot['kommentar'] == ""){
        $Kommentar = "";
    } else {
        $Kommentar = collection_item_builder($Angebot['kommentar']);
    }

    $Dropdownname = "zeitfenster_gewaehlt_terminangebot_".$IDangebot."";
    $ZeileMitBuchung = table_form_dropdown_terminzeitfenster_generieren('Zeitfenster wählen', $Dropdownname, $IDangebot, '');
    $ZeileMitBuchung .= table_form_string_item('Kommentar', 'kommentar_uebergabe_'.$IDangebot.'', $_POST['kommentar_uebergabe_'.$IDangebot.''], false);
    $ZeileMitBuchung .= table_row_builder(table_header_builder('').table_data_builder(form_button_builder('action_termin_'.$IDangebot.'', '&Uuml;bergabe ausmachen', 'action', 'send')));
    $TabelleMitBuchung = table_builder($ZeileMitBuchung);
    $Formular = form_builder($TabelleMitBuchung, './uebergabe_ausmachen.php', 'post', '', '');
    $FormularCollection = collection_item_builder($Formular);

    //Ausgabe
    $Collection = collection_item_builder("<i class='tiny material-icons'>room</i> ".$Angebot['ort']."");
    $Collection .= $Kommentar;
    $Collection .= collection_item_builder("<i class='tiny material-icons'>perm_identity</i> Schl&uuml;sselwart: ".$Wart['vorname']." ".$Wart['nachname']."");
    $Collection .= $FormularCollection;
    $HTML = collapsible_item_builder("Terminangebot: ".$ZeitraumMobil."", $Collection, 'today');

    return $HTML;
}

?>