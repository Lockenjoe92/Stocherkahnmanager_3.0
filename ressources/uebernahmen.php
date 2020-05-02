<?php

function uebernahme_moeglich($ReservierungID){

    $link = connect_db();

    $AnfrageLaden = "SELECT * FROM reservierungen WHERE id = '$ReservierungID'";
    $AbfrageLaden = mysqli_query($link, $AnfrageLaden);
    $Reservierung = mysqli_fetch_assoc($AbfrageLaden);

    $Benutzereinstellungen = lade_user_meta($Reservierung['user']);

    if($Benutzereinstellungen['darf_uebernahme'] == 'true'){

        $Anfrage = "SELECT id FROM reservierungen WHERE ende = '".$Reservierung['beginn']."' AND storno_user = '0'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl > 0){

            $Vorgehende = mysqli_fetch_assoc($Abfrage);

            //Überprüfen ob die vorhergehende res ne Übergabe hat
            $AnfrageDrei = "SELECT id FROM uebergaben WHERE res = '".$Vorgehende['id']."' AND storno_user = '0'";
            $AbfrageDrei = mysqli_query($link, $AnfrageDrei);
            $AnzahlDrei = mysqli_num_rows($AbfrageDrei);

            if ($AnzahlDrei > 0){
                //Übernahme möglich
                return true;
            } else {
                //Keine übernahme möglich
                return false;
            }
        }
    }
}

function uebernahme_stornieren($UebernahmeID, $Begruendung){

    $link = connect_db();
    zeitformat();

    $Uebernahme = lade_uebernahme($UebernahmeID);
    $ResUebernahme = lade_reservierung($Uebernahme['reservierung']);
    $UserResUebernahme = lade_user_meta($ResUebernahme['user']);
    $ResUebernahmeDavor = lade_reservierung($Uebernahme['reservierung_davor']);
    $UserResUebernahmeDavor = lade_user_meta($ResUebernahmeDavor['user']);

    $AnfrageStorno = "UPDATE uebernahmen SET storno_user = '".lade_user_id()."', storno_time = '".timestamp()."' WHERE id = '$UebernahmeID'";
    if (mysqli_query($link, $AnfrageStorno)){
        if ($Begruendung != ""){
            //Nur wenns was zu erzählen gibt
            $BausteineUebernahmeMails = array();
            $BausteineUebernahmeMails['vorname_user'] = $UserResUebernahme['vorname'];
            $BausteineUebernahmeMails['datum_resevierung'] = strftime("%A, %d. %B %G", strtotime($ResUebernahme['beginn']));
            $BausteineUebernahmeMails['begruendung'] = htmlentities($Begruendung);
            mail_senden('uebernahme-storniert-user', $UserResUebernahme['mail'], $BausteineUebernahmeMails);
            mail_senden('uebernahme-storniert-user-davor', $UserResUebernahmeDavor['mail'], $BausteineUebernahmeMails);
        }
        return true;
    } else {
        return false;
    }
}

function uebernahme_eintragen($ReservierungID, $Kommentar){

    $Antwort = array();
    $link = connect_db();
    $Reservierung = lade_reservierung($ReservierungID);
    zeitformat();

    //Instant DAU checks:

    $DAUcounter = 0;
    $DAUerror = "";
    $Wartmode = FALSE;

    //Keine Reservierung übermittelt
    if ($ReservierungID == ""){
        $DAUcounter++;
        $DAUerror .= "Es wurde keine Reservierung gew&auml;hlt!<br>";
    }

    //Reservierung gehört nicht dem User - ist es noch ein Wart?
    if (lade_user_id() != intval($Reservierung['user'])){

        $UserAktuell = lade_user_meta(lade_user_id());
        $Benutzerrollen = benutzerrollen_laden($UserAktuell['username']);

        if ($Benutzerrollen['wart'] != TRUE){
            $DAUcounter++;
            $DAUerror .= "Du hast nicht die n&ouml;tigen Rechte um diese Reservierung zu bearbeiten!<br>";
        } else if ($Benutzerrollen['wart'] == TRUE){
            $Wartmode = TRUE;
        }
    }

    if ($DAUcounter > 0){

        //Check High priority Fails
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;

    } else {

        //User darf noch keine Übernahme machen
        $Benutzereinstellungen = benutzereinstellung_laden(lade_user_id());

        if (intval($Benutzereinstellungen['uebernahme']) != 1){

            //Bei StoKaWart egal
            $UserAktuell = lade_user_meta(lade_user_id());
            $Benutzerrollen = benutzerrollen_laden($UserAktuell['username']);

            if ($Benutzerrollen['wart'] != TRUE){
                $DAUcounter++;
                $DAUerror .= "Du hast nicht die n&ouml;tigen Einweisungen um eine Schl&uuml;ssel&uuml;bernahme auszumachen!<br>";
            } else if ($Benutzerrollen['wart'] == TRUE){
                $Wartmode = TRUE;
            }
        }

        //Reservierung hat schon ne gültige Schlüsselübergabe
        $AnfrageUebergabestatusDieseRes = "SELECT id FROM uebergaben WHERE res = '$ReservierungID' AND storno_user = '0'";
        $AbfrageUebergabestatusDieseRes = mysqli_query($link, $AnfrageUebergabestatusDieseRes);
        $AnzahlUebergabestatusDieseRes = mysqli_num_rows($AbfrageUebergabestatusDieseRes);

        if ($AnzahlUebergabestatusDieseRes > 0){

            $Uebergabe = mysqli_fetch_assoc($AbfrageUebergabestatusDieseRes);

            $DAUcounter++;
            $DAUerror .= "Du hast f&uuml;r diese Reservierung bereits eine Schl&uuml;ssel&uuml;bergabe ausgemacht! Falls du lieber den Schl&uuml;ssel der Vorgruppe &uuml;bernehmen m&ouml;chtest, <a href='uebergabe_stornieren_user.php?id=".$Uebergabe['id']."'>storniere bitte zuerst die &Uuml;bergabe!</a><br>";
        }

        //Reservierung hat schon eine Übernahme ausgemacht
        $AnfrageUebernahmeResSchonVorhanden = "SELECT id FROM uebernahmen WHERE reservierung = '$ReservierungID' AND storno_user = '0'";
        $AbfrageUebernahmeResSchonVorhanden = mysqli_query($link, $AnfrageUebernahmeResSchonVorhanden);
        $AnzahlUebernahmeResSchonVorhanden = mysqli_num_rows($AbfrageUebernahmeResSchonVorhanden);

        if ($AnzahlUebernahmeResSchonVorhanden > 0){
            $DAUcounter++;
            $DAUerror .= "Du hast hast f&uuml;r diese Reservierung bereits eine &Uuml;bergabe ausgemacht!<br>";
        }

        //Es gibt keine Vorfahrende Reservierung mit ausgemachter Übergabe mehr!
        $AnfrageLadeResVorher = "SELECT * FROM reservierungen WHERE ende = '".$Reservierung['beginn']."' AND storno_user = '0'";
        $AbfrageLadeResVorher = mysqli_query($link, $AnfrageLadeResVorher);
        $AnfrageLadeResVorher = mysqli_num_rows($AbfrageLadeResVorher);

        if ($AnfrageLadeResVorher == 0){
            $DAUcounter++;
            $DAUerror .= "Es gibt leider keine Reservierung mehr vor dir! <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>Buche dir einfach eine Schl&uuml;ssel&uuml;bergabe</a> durch einen unserer Stocherkahnw&auml;rte:)<br>";
        } else if ($AnfrageLadeResVorher > 0){

            $ReservierungVorher = mysqli_fetch_assoc($AbfrageLadeResVorher);

            //Es gibt ne Res, aber hat sie auch eine ausgemachte/durchgeführte Schlüsselübergabe?
            $AnfrageUebergabestatus = "SELECT id FROM uebergaben WHERE res = '".$ReservierungVorher['id']."' AND storno_user = '0'";
            $AbfrageUebergabestatus = mysqli_query($link, $AnfrageUebergabestatus);
            $AnzahlUebergabestatus = mysqli_num_rows($AbfrageUebergabestatus);

            if ($AnzahlUebergabestatus == 0){

                //Hat die reservierung vielleicht eine Schlüsselübernahme gebucht? -> Wenn ja, einstellung Checken ob man Schlüssel über mehrere Reservierungen weitergeben darf:
                if (lade_einstellung('schluesseluebernahme-ueber-mehrere-res') == "TRUE"){

                    $AnfrageHatVorfahrendeReservierungUebernahme = "SELECT id FROM uebernahmen WHERE reservierung_davor = '".$ReservierungVorher['id']."' AND storno_user = '0'";
                    $AbfrageHatVorfahrendeReservierungUebernahme = mysqli_query($link, $AnfrageHatVorfahrendeReservierungUebernahme);
                    $AnzahlHatVorfahrendeReservierungUebernahme = mysqli_num_rows($AbfrageHatVorfahrendeReservierungUebernahme);

                    if ($AnzahlHatVorfahrendeReservierungUebernahme == 0){
                        $DAUcounter++;
                        $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                    }

                } else {
                    $DAUcounter++;
                    $DAUerror .= "Leider hat die Reservierung vor dir noch keinen zugeteilten Schl&uuml;ssel! Entweder du wartest noch ein wenig, oder <a href='./uebernahme_ausmachen.php?res=".$ReservierungID."'>du buchst dir einfach eine eigeneSchl&uuml;ssel&uuml;bergabe</a>!<br>";
                }
            }
        }

        if ($DAUcounter > 0){
            //Check low priority fails
            $Antwort['success'] = FALSE;
            $Antwort['meldung'] = $DAUerror;
        } else {

            //Inform Gruppe davor:
            $UserReservierungDavor = lade_user_meta($ReservierungVorher['user']);
            $UserReservierung = lade_user_meta($Reservierung['user']);
            $BausteineGruppeDavor = array();
            $BausteineGruppeDavor['vorname_user'] = $UserReservierungDavor['vorname'];
            $BausteineGruppeDavor['angaben_reservierung_datum'] = strftime("%A, den %d. %B %G", strtotime($ReservierungVorher['beginn']));
            $BausteineGruppeDavor['angaben_reservierung_beginn'] = strftime("%H", strtotime($ReservierungVorher['beginn']));
            $BausteineGruppeDavor['angaben_reservierung_ende'] = strftime("%H", strtotime($ReservierungVorher['ende']));
            $BausteineGruppeDavor['name_nachfolgender_user'] = "".$UserReservierung['vorname']." ".$UserReservierung['nachname']."";
            if ($Kommentar != ""){
                $BausteineGruppeDavor['kommentar'] = "<p>Kommentar des anlegenden Users: ".$Kommentar."</p>";
            }
            $TypMailAngabeDavor = "uebernahme-angelegt-vorgruppe-".$ReservierungVorher['id']."";

            if (mail_senden('uebernahme-angelegt-vorgruppe', $UserReservierungDavor['mail'], $ReservierungVorher['user'], $BausteineGruppeDavor, $TypMailAngabeDavor)){

                $BausteineGruppe = array();
                $BausteineGruppe['vorname_user'] = $UserReservierungDavor['vorname'];
                $BausteineGruppe['angaben_reservierung_datum'] = strftime("%A, den %d. %B %G", strtotime($Reservierung['beginn']));
                $BausteineGruppe['angaben_reservierung_beginn'] = strftime("%H", strtotime($Reservierung['beginn']));
                $BausteineGruppe['angaben_reservierung_ende'] = strftime("%H", strtotime($Reservierung['ende']));
                $BausteineGruppe['name_vorheriger_user'] = "".$UserReservierungDavor['vorname']." ".$UserReservierungDavor['nachname']."";
                if ($Kommentar != ""){
                    $BausteineGruppe['kommentar'] = "<p>Hier der Kommentar des anlegenden Users: ".$Kommentar."</p>";
                }
                $TypMailAngabe = "uebernahme-angelegt-nachgruppe-".$Reservierung['id']."";

                if (mail_senden('uebernahme-angelegt-nachgruppe', $UserReservierung['mail'], $Reservierung['user'], $BausteineGruppe, $TypMailAngabe)){

                    $AnfrageUebernahmeEintragen = "INSERT INTO uebernahmen (reservierung, reservierung_davor, create_time, create_user, storno_time, storno_user, kommentar) VALUES ('$ReservierungID', '".$ReservierungVorher['id']."', '".timestamp()."', '".lade_user_id()."', '0000-00-00 00:00:00', '0', '$Kommentar')";
                    if (mysqli_query($link, $AnfrageUebernahmeEintragen)){
                        $Antwort['success'] = TRUE;
                        $Antwort['meldung'] = "Schl&uuml;ssel&uuml;bernahme erfolgreich eingetragen!";
                    } else {
                        $Antwort['success'] = FALSE;
                        $Antwort['meldung'] = "Fehler beim Eintragen der Schl&uuml;ssel&uuml;bernahme!";
                    }

                } else {
                    $Antwort['success'] = FALSE;
                    $Antwort['meldung'] = "Fehler beim Informieren des Users.";
                }

            } else {
                $Antwort['success'] = FALSE;
                $Antwort['meldung'] = "Fehler beim Informieren der vorfahrenden Gruppe.";
            }
        }
    }

    return $Antwort;

}

function lade_uebernahme($UebernahmeID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebernahmen WHERE id = '$UebernahmeID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;
}

function uebernahme_planen_listenelement_generieren(){

    //Ausgabe
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'><i class='large material-icons'>sync</i>&Uuml;bernahme vorplanen</div>";
    $HTML .= "<div class='collapsible-body'>";
    $HTML .= "<div class='container'>";
    $HTML .= "<form method='post'>";

    //Reservierung und deren modifizierung
    $HTML .= "<h4>Reservierung w&auml;hlen</h4>";

    $Parser = uebernahme_planen_listenelement_parser();
    if(isset($Parser)){
        $HTML .= "<h5>".$Parser."</h5>";
    }

    $HTML .= "<div class='input-field'>";
    $HTML .= "<i class='material-icons prefix'>today</i>";
    $HTML .= dropdown_aktive_res_spontanuebergabe('reservierung_uebernahme_vorplanen');
    $HTML .= "</div>";

    $HTML .= "<div class='input-field'>";
    $HTML .= form_button_builder('action_uebernahme_vorplanen_durchfuehren', 'Vorplanen', 'submit', 'send');
    $HTML .= "</div>";
    $HTML .= "</form>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}

function uebernahme_planen_listenelement_parser(){

    if (isset($_POST['action_uebernahme_vorplanen_durchfuehren'])){

        if (intval($_POST['reservierung_uebernahme_vorplanen']) > 0){
            $header = "Location: ./uebernahme_vorplanen.php?res=".$_POST['reservierung_uebernahme_vorplanen']."";
            header($header);
            die();

        } else {
            $Antwort = 'Du hast keine Reservierung ausgew&auml;hlt!';
            return $Antwort;
        }
    }
}

?>