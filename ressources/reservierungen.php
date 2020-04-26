<?php
function reservierung_hinzufuegen($Von, $Bis, $UserRes, $GratisFahrt, $ErmaessigterTarif){

    //Houseeeping
    $link = connect_db();
    zeitformat();
    $Antwort = NULL;

    //DAU-Block

    $DAUcounter = 0;
    $DAUerror = "";

    //Stunden beide null - wrsl versehentlich drauf getippt

    if ((date("G", strtotime($Von)) == "0") OR (date("G", strtotime($Bis)) == "0")){
        $DAUcounter++;
        $DAUerror .= "Bitte gib ein valides Zeitfenster ein!<br>";
    } else {

        //Reservierung in der Vergangenheit??

        if (time() > strtotime($Von)){
            $DAUcounter++;
            $DAUerror .= "Deine Angaben liegen in der Vergangenheit! Reservierungen k&ouml;nnen nur in die Zukunft gebucht werden.<br>";
        }

        //Zeiten verdreht?

        if (strtotime($Von) > strtotime($Bis)){
            $DAUcounter++;
            $DAUerror .= "Das Ende der Reservierung darf nicht vor dem Anfang liegen!<br>";
        }

        //Zeiten identisch?

        if (strtotime($Von) == strtotime($Bis)){
            $DAUcounter++;
            $DAUerror .= "Die eingegebenen Zeiten sind identisch!<br>";
        }

        //Zeiten außerhalb der genehmigten Wochentagszeiten
        $Wochentag = strftime("%u", strtotime($Von));

        //Einstellungen laden
        if ($Wochentag == 1){
            $FruehesterBeginn = lade_xml_einstellung('von-montag');
            $SpaetestesEnde = lade_xml_einstellung('bis-montag');
        } else if ($Wochentag == 2){
            $FruehesterBeginn = lade_xml_einstellung('von-dienstag');
            $SpaetestesEnde = lade_xml_einstellung('bis-dienstag');
        } else if ($Wochentag == 3){
            $FruehesterBeginn = lade_xml_einstellung('von-mittwoch');
            $SpaetestesEnde = lade_xml_einstellung('bis-mittwoch');
        } else if ($Wochentag == 4){
            $FruehesterBeginn = lade_xml_einstellung('von-donnerstag');
            $SpaetestesEnde = lade_xml_einstellung('bis-donnerstag');
        } else if ($Wochentag == 5){
            $FruehesterBeginn = lade_xml_einstellung('von-freitag');
            $SpaetestesEnde = lade_xml_einstellung('bis-freitag');
        } else if ($Wochentag == 6){
            $FruehesterBeginn = lade_xml_einstellung('von-samstag');
            $SpaetestesEnde = lade_xml_einstellung('bis-samstag');
        } else if ($Wochentag == 7){
            $FruehesterBeginn = lade_xml_einstellung('von-sonntag');
            $SpaetestesEnde = lade_xml_einstellung('bis-sonntag');
        }

        if (intval(date("G", strtotime($Von))) < intval($FruehesterBeginn)){
            $DAUcounter++;
            $DAUerror .= "Der eingegebene Anfang deiner Reservierung ist zu fr&uuml;h!<br>";
        }

        if (intval(date("G", strtotime($Bis))) > intval($SpaetestesEnde)){
            $DAUcounter++;
            $DAUerror .= "Das eingegebene Ende deiner Reservierung ist zu sp&auml;t!<br>";
        }

        if (intval(date("G", strtotime($Bis))) < intval($FruehesterBeginn)){
            $DAUcounter++;
            echo $Bis;
            $DAUerror .= "Das eingegebene Ende deiner Reservierung ist zu fr&uuml;h!<br>";
        }

        if (intval(date("G", strtotime($Von))) > intval($SpaetestesEnde)){
            $DAUcounter++;
            $DAUerror .= "Der eingegebene Anfang deiner Reservierung ist zu sp&auml;t!<br>";
        }

        //Maximale Stundenzahl einer Reservierung überschritten

        $MaxHoursRes = lade_xml_einstellung('max-dauer-einer-reservierung');
        $date1 = new DateTime($Von);
        $date2 = new DateTime($Bis);
        $diff = $date2->diff($date1);
        $hours = $diff->h;
        $hours = $hours + ($diff->days*24);

        if ($hours > $MaxHoursRes){
            $ZuViel = $hours - $MaxHoursRes;
            $DAUcounter++;
            $DAUerror .= "Deine Reservierung ist ".$ZuViel." Stunden zu lang.<br>";
        }

        //Kurzfristigkeitscheck
        $Benutzerrolle = lade_user_meta($UserRes);
        $eintraegerrolle = lade_user_meta(lade_user_id());

        //User hat keine eigenen Schlüssel zur Verfügung
        if (($Benutzerrolle['hat_eig_schluessel'] != 'true')){
            if (($Benutzerrolle['wg_hat_schluessel'] != 'true')){

                //Wenn ein Wart es einträgt, egal - ansonsten gemäß Einstellungen Error
                if ($eintraegerrolle['ist_wart'] != 'true'){

                    $MaxStundenVorAbfahrtBuchen = lade_xml_einstellung('max-stunden-vor-abfahrt-buchbar');
                    $command = "- ".$MaxStundenVorAbfahrtBuchen." hours";

                    if (time() > strtotime($command, strtotime($Von))){
                        $DAUcounter++;
                        $DAUerror .= "Du kannst Reservierungen nur bis ".$MaxStundenVorAbfahrtBuchen." Stunden vor Abfahrt neu anlegen.<br>";
                    }
                }
            }
        }

        //Reservierung nur für dieses Jahr möglich

        $DiesesJahr = date("Y");
        $EingegebenesJahr = date("Y", strtotime($Von));

        if ($DiesesJahr != $EingegebenesJahr){
            $DAUcounter++;
            $DAUerror .= "Du kannst Reservierungen nur f&uuml;r das aktuelle Jahr buchen.<br>";
        }

        //Konflikt mit anderen Reservierungen / Sperren / Pausen?
        if (!($stmt = $link->prepare("SELECT id FROM reservierungen WHERE (((? <= beginn) AND (? > beginn)) OR ((beginn <= ?) AND (? <= ende)) OR ((? < ende) AND (? >= ende))) AND storno_user = 0"))) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("ssssss", $Von, $Bis, $Von, $Bis, $Von, $Bis)) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            if (mysqli_num_rows($res) > 0){
                $DAUcounter++;
                $DAUerror .= "In dem eingegebenen Zeitfenster liegt bereits eine andere Reservierung vor!<br>";
            }
        }

        if (!($stmt = $link->prepare("SELECT id FROM sperrungen WHERE (((beginn <= ?) AND (ende >= ?)) OR ((? < beginn) AND (? > beginn)) OR ((? < ende) AND (? > ende))) AND storno_user = 0"))) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("ssssss", $Von, $Bis, $Von, $Bis, $Von, $Bis)) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            if (mysqli_num_rows($res) > 0){
                $DAUcounter++;
                $DAUerror .= "In dem eingegebenen Zeitfenster liegt eine Sperrung des Kahns vor!<br>";
            }
        }

        if (!($stmt = $link->prepare("SELECT id FROM pausen WHERE (((beginn <= ?) AND (ende >= ?)) OR ((? < beginn) AND (? > beginn)) OR ((? < ende) AND (? > ende))) AND storno_user = 0"))) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("ssssss", $Von, $Bis, $Von, $Bis, $Von, $Bis)) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort['erfolg'] = false;
            echo  __LINE__;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            if (mysqli_num_rows($res) > 0){
                $DAUcounter++;
                $DAUerror .= "In dem eingegebenen Zeitfenster liegt eine Betriebspause des Kahns vor!<br>";
            }
        }

        //User gesperrt?

        if ($Benutzerrolle['ist_gesperrt'] == 'true'){
            $DAUcounter++;
            if ($eintraegerrolle['ist_wart'] == 'true'){
                $DAUerror .= "Das Benutzerkonto des Users ist derzeit gesperrt. Du kannst Sperrungen im Bereich 'Wartfunktionen -> User verwalten.<br>";
            } else {
                $DAUerror .= "Dein Benutzerkonto ist derzeit f&uuml;r Reservierungen gesperrt.<br>";
            }
        }
    }

    //DAU auswerten
    if ($DAUcounter > 0){
        $Antwort['success'] = FALSE;
        $Antwort['meldung'] = $DAUerror;
    } else if ($DAUcounter == 0){

        //Reservierung eintragen

        if ($GratisFahrt == TRUE){
            $GratisFahrt = 1;
        } else {
            $GratisFahrt = 0;
        }

        if ($ErmaessigterTarif < 0){
            $ErmaessigterTarif = 0;
        } else if ($ErmaessigterTarif == NULL){
            $ErmaessigterTarif = 0;
        }

        if (!($stmt = $link->prepare("INSERT INTO reservierungen (user, beginn, ende, storno_user, storno_zeit, gratis_fahrt, preis_geaendert, verlaengert, angelegt_von, angelegt_am) VALUES (?, ?, ?, 0, '0000-00-00 00:00:00', ?, ?, 0, ?, ?)"))) {
            $Antwort['success'] = FALSE;
            echo  __LINE__;
            $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
        }
        if (!$stmt->bind_param("issiiis", $UserRes,$Von, $Bis, $GratisFahrt, $ErmaessigterTarif, lade_user_id(), timestamp())) {
            $Antwort['success'] = FALSE;
            echo  __LINE__;
            $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
        }
        if (!$stmt->execute()) {
            $Antwort['success'] = FALSE;
            echo  __LINE__;
            $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
        } else {

            if (!($stmt = $link->prepare("SELECT id FROM reservierungen WHERE user = ? AND beginn = ? AND ende = ? AND storno_user = 0"))) {
                $Antwort['success'] = FALSE;
                echo  __LINE__;
                $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
            }
            if (!$stmt->bind_param("iss", $UserRes,$Von, $Bis)) {
                $Antwort['success'] = FALSE;
                echo  __LINE__;
                $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
            }
            if (!$stmt->execute()) {
                $Antwort['success'] = FALSE;
                echo  __LINE__;
                $Antwort['meldung'] = "Fehler beim Datenbankzugriff. Bitte Admin kontaktieren!";
            } else {
                $res = $stmt->get_result();
                $Reservierung = mysqli_fetch_assoc($res);

                //Forderung generieren
                $Kosten = kosten_reservierung($Reservierung['id']);
                if($Kosten != 0){
                    forderung_generieren($Kosten, 19, $UserRes, '', intval($Reservierung['id']), '', zahlungsgrenze_forderung_laden($Bis), $UserRes);
                }

                //Mail an User senden - Wärte erhalten keine:

                if ((sizeof($Benutzerrolle) == 0) OR ((sizeof($Benutzerrolle) > 0) AND ($Benutzerrolle['student'] == TRUE))){

                    $UserMeta = lade_user_meta($UserRes);

                    $Bausteine = array();
                    $Bausteine['vorname_user'] = $UserMeta['vorname'];
                    $Bausteine['datum_reservierung'] = strftime("%A, den %d. %B %G", strtotime($Von));
                    $Bausteine['uhrzeit_beginn'] = strftime("%R Uhr", strtotime($Von));
                    $Bausteine['uhrzeit_ende'] = strftime("%R Uhr", strtotime($Bis));
                    $Bausteine['reservierungsnummer'] = $Reservierung['id'];
                    $Bausteine['kosten_reservierung'] = $Kosten;

                    if (mail_senden('reservierung-angelegt', $UserMeta['mail'], $Bausteine)){
                        $Antwort['success'] = TRUE;
                        $Antwort['meldung'] = "Deine Reservierung wurde erfolgreich eingetragen und tr&auml;gt die #".$Reservierung['id']."<br>Du erh&auml;ltst in K&uuml;rze eine Best&auml;tigungsmail:)";
                    } else {
                        $Antwort['success'] = FALSE;
                        $Antwort['meldung'] = "Deine Reservierung wurde erfolgreich eingetragen und tr&auml;gt die #".$Reservierung['id']."<br>Beim Senden der Best&auml;tigungsmail trat jedoch ein Fehler auf - bitte &uuml;berpr&uuml;fe deine Mailadresse in deinen Kontoeinstellungen!";
                    }

                } else {
                    $Antwort['success'] = TRUE;
                    $Antwort['meldung'] = "Deine Reservierung wurde erfolgreich eingetragen und tr&auml;gt die #".$Reservierung['id']."<br>";
                }
            }
        }
    }

    return $Antwort;
}

function res_hat_uebergabe($IDres){

    $link = connect_db();

    $Anfrage = "SELECT id FROM uebergaben WHERE res = '$IDres' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl > 0){
        return TRUE;
    } else if ($Anzahl == 0){
        return FALSE;
    }
}

function res_hat_uebernahme($IDres){

    $link = connect_db();

    $Anfrage = "SELECT id FROM uebernahmen WHERE reservierung = '$IDres' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl > 0){
        return TRUE;
    } else if ($Anzahl == 0){
        return FALSE;
    }
}

function lade_reservierung($ResID){

    $link = connect_db();

    $Anfrage = "SELECT * FROM reservierungen WHERE id = '$ResID'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Reservierung = mysqli_fetch_assoc($Abfrage);

    return $Reservierung;

}

function rueckgabe_notwendig_res($IDres){

    $link = connect_db();

    $Reservierung = lade_reservierung($IDres);

    //Ist ein Schlüssel ausgegeben worden?
    $Anfrage = "SELECT * FROM schluesselausgabe WHERE reservierung = '$IDres'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){
        //Garkeine Ausgabe erfolgt
        return false;
    } else if($Anzahl > 0){
        //Ausgabe angelegt weitere Checks:
        $Ausgabe = mysqli_fetch_assoc($Abfrage);

        if ($Ausgabe['ausgabe'] != "0000-00-00 00:00:00"){

            //Ausgabe ist  erfolgt - storno?
            if ($Ausgabe['storno_time'] == "0000-00-00 00:00:00"){

                //Nicht storniert - darf er dan Schlüssel weiter behalten?
                $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, reservierung FROM schluesselausgabe WHERE user = '".$Reservierung['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe = '0000-00-00 00:00:00' AND id <> '".$Ausgabe['id']."'";
                $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
                $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

                if ($AnzahlWeitereReservierungenMitDiesemSchluessel > 0){
                    //Er darf den schlüssel noch weiter behalten
                    return false;

                } else {

                    //Er soll den schlüssel zurück geben
                    if ($Ausgabe['rueckgabe'] == "0000-00-00 00:00:00"){
                        return true;
                    } else {
                        return false;
                    }
                }

            } else {
                return false;
            }

        } else {
            return false;
        }
    }
}

function zahlungswesen($ID){

    $OffeneForderung = lade_offene_forderung_res($ID);
    $OffeneAusgleiche = lade_offene_ausgleiche_res($ID);

    if (sizeof($OffeneForderung) == 0){

        //Keine offene Forderung
        //Musste überhaupt gezahlt werden?

        //Bekommt er gar etwas zurück?
        if (sizeof($OffeneAusgleiche) > 0){

            $Antwort = "<b>Du bekommst f&uuml;r die Fahrt noch Geld zur&uuml;ck!</b><br>Mache hier einen <a href='treffen_ausmachen.php?reason=rueckgabe'>Termin f&uuml;r eine Geldr&uuml;ckgabe</a> aus. Ansonsten kannst du das auch mit deiner n&auml;chsten Fahrt verrechnen.";

        } else {
            $Antwort = "Du musst nichts mehr bezahlen!";
        }

    } else if (sizeof($OffeneForderung) > 0) {

        //Es muss noch gezahlt werden
        $Antwort = "<b>Du musst deine Reservierung noch bezahlen</b><br>Dazu gibt es bei uns folgende M&ouml;glichkeiten:";

        $CollectionItems = collection_item_builder("<i class='tiny material-icons'>label</i> Du zahlst wenn du dein Schl&uuml;ssel bei einem der Stocherkahnw&auml;rte abholst.");
        $CollectionItems .= collection_item_builder("<i class='tiny material-icons'>label</i> Du wirfst das Geld nach der Fahrt zusammen mit dem Schl&uuml;ssel in den Briefkasten des Stocherkahnwartes welcher dir den Schl&uuml;ssel gegeben hat.");

        //PayPal?
        if(lade_xml_einstellung('paypal-aktiv') == "true"){
            $CollectionItems .= collection_item_builder( "<i class='tiny material-icons'>label</i> Du kannst jetzt direkt <a href='paypal.php?res='".$ID."''>mit PayPal bezahlen.</a>");
        }

        $Antwort .= collection_builder($CollectionItems);
    }

    return $Antwort;
}

function uebergabewesen($ID){

    $link = connect_db();
    $Antwort = "";

    //Lade die Reservierung
    $AnfrageResLaden = "SELECT * FROM reservierungen WHERE id = '$ID'";
    $AbfrageResLaden = mysqli_query($link, $AnfrageResLaden);
    $Reservierung = mysqli_fetch_assoc($AbfrageResLaden);

    $Schluesselrollen = lade_user_meta($Reservierung['user']);

    if (($Schluesselrollen['hat_eig_schluessel'] == 1) OR ($Schluesselrollen['wg_hat_schluessel'] == 1)){

        $Antwort = "Du hast einen eigenen Schl&uuml;ssel und brauchst daher keine &Uuml;bergabe. Wir w&uuml;nschen eine gute Fahrt!:)";

    } else {

        //Lade Übergaben und übergaben
        $AnfrageUebergabe = "SELECT * FROM uebergaben WHERE res = '$ID' AND storno_user = '0'";
        $AbfrageUebergabe = mysqli_query($link, $AnfrageUebergabe);
        $AnzahlUebergabe = mysqli_num_rows($AbfrageUebergabe);

        if ($AnzahlUebergabe > 0){

            //Übergabe gebucht
            $Uebergabe = mysqli_fetch_assoc($AbfrageUebergabe);

            if ($Uebergabe['durchfuehrung'] === "0000-00-00 00:00:00"){

                //Ist das Zeitfenster abgelaufen?
                $BefehlDauer = "+ ".lade_xml_einstellung('dauer-uebergabe-minuten')." minutes";
                $Grenzzeit = strtotime($BefehlDauer, strtotime($Uebergabe['beginn']));
                if (time() > $Grenzzeit){

                    //Übergabe ist abgelaufen
                    $Antwort = "<b>Du hast eine Schl&uuml;ssel&uumlbergabe ausgemacht, welche jedoch abgelaufen ist.</b><br><a href='uebergabe_infos_user.php?id=".$Uebergabe['id']."'><i class='material-icons tiny'>info</i> Infos zur &Uuml;bergabe</a><br><a href='neue_uebergabe_ausmachen.php?res=".$ID."'><i class='material-icons tiny'>loop</i> Neue &Uuml;bergabe ausmachen</a>";

                } else if (time() < $Grenzzeit){
                    //Übergabe steht noch aus
                    $Antwort = "<b>Du hast eine Schl&uuml;ssel&uumlbergabe ausgemacht.</b><br><a href='uebergabe_infos_user.php?id=".$Uebergabe['id']."'><i class='material-icons tiny'>info</i> Infos zur &Uuml;bergabe</a>";
                }

            } else {
                //Übergabe durchgeführt
                $Antwort = "Die Schl&uuml;ssel&uuml;bergabe wurde erfolgreich durchgef&uuml;hrt!<br>";
            }

        } else {

            //Keine Übergabe gebucht

            //Übernahme?
            $AnfrageUebernahme = "SELECT * FROM uebernahmen WHERE reservierung = '$ID' AND storno_user = '0'";
            $AbfrageUebernahme = mysqli_query($link, $AnfrageUebernahme);
            $AnzahlUebernahme = mysqli_num_rows($AbfrageUebernahme);

            if ($AnzahlUebernahme > 0){

                $Uebernahme = mysqli_fetch_assoc($AbfrageUebernahme);
                //Übernahme gebucht
                $Antwort = "<b>Du hast eine Schl&uuml;ssel&uumlbernahme von der Gruppe vor dir ausgemacht.</b><br>Bitte sei p&uuml;nktlich um <b>".date("G", strtotime($Reservierung['beginn']))." Uhr</b> an der Stocherkahnanlegestelle um den Schl&uuml;ssel entgegenzunehmen!<br><a href='uebernahme_absagen.php?uebernahme=".$Uebernahme['id']."'>&Uuml;bernahme absagen</a>";

            } else {

                //Nix gebucht
                //Ist eine Übernahme möglich?
                $Uebernahmemoeglich = uebernahme_moeglich($ID);
                if ($Uebernahmemoeglich == TRUE){
                    //Keine Übergabe, Übernahme möglich
                    $Antwort = "<b>Du musst dich noch darum k&uuml;mmern wie du an den Kahnschl&uuml;ssel kommst.</b><br><a href='uebergabe_ausmachen.php?res=".$ID."'>Schl&uuml;ssel&uuml;bergabe ausmachen</a><br>";

                    if (lade_xml_einstellung('uebernahmefunktion-globel-aktiv') === "true"){
                        $Antwort .= "<a href='uebernahme_ausmachen.php?res=".$ID."'>Du kannst auch einfach den Schl&uuml;ssel von der Gruppe vor dir &uuml;bernehmen.</a>";
                    }

                } else if ($Uebernahmemoeglich == FALSE){

                    //Keine Übergabe, keine Übernahme möglich
                    $Antwort = "<b>Du musst dich noch darum k&uuml;mmern wie du an den Kahnschl&uuml;ssel kommst.</b><br><a href='uebergabe_ausmachen.php?res=".$ID."'>Schl&uuml;ssel&uuml;bergabe ausmachen</a>";

                }
            }
        }
    }
    return $Antwort;
}

function schluesselwesen($ID){

    $link = connect_db();
    $Timestamp = timestamp();
    $Antwort = "";

    //Lade Res
    $Reservierung = lade_reservierung($ID);

    //Ist ein schlüssel ausgeteilt?
    $Anfrage = "SELECT * FROM schluesselausgabe WHERE reservierung = '$ID' AND storno_user = '0' AND ausgabe > '0000-00-00 00:00:00'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl > 0){

        $Ausgabe = mysqli_fetch_assoc($Abfrage);

        //Rückgabe notwendig? Res vorbei?
        if (strtotime($Reservierung['ende']) > strtotime($Timestamp)){

            $Antwort = "Dir ist Schl&uuml;ssel #".$Ausgabe['schluessel']." zugeteilt.";

        } else {

            //Res vorbei - darf er den Schlüssel weiter behalten?
            $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, reservierung FROM schluesselausgabe WHERE user = '".$Reservierung['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe = '0000-00-00 00:00:00' AND id <> '".$Ausgabe['id']."'";
            $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
            $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

            if ($AnzahlWeitereReservierungenMitDiesemSchluessel > 0){

                //Er darf den schlüssel noch weiter behalten
                $Antwort = "Du darfst den Schl&uuml;ssel noch f&uuml;r weitere Reservierungen verwenden.";

            } else {

                if ($Ausgabe['rueckgabe'] === "0000-00-00 00:00:00"){
                    //Er soll den schlüssel zurück geben
                    $Antwort = "Bitte bring deinen Schl&uuml;ssel zeitnah zur&uuml;ck. Du kannst ihn ganz einfach in unseren <a href='schluesselrueckgabe_howto.php'>R&uuml;ckgabebriefkasten</a> werfen:)";
                } else {
                    //Er soll den schlüssel zurück geben
                    $Antwort = "Deine Schl&uuml;sselr&uumlckgabe wurde festgehalten! Vielen Dank:)";
                }
            }
        }

    } else if ($Anzahl == 0){

        //Hat er einen eigenen Schlüssel?
        $Schluesselrollen = lade_user_meta($Reservierung['user']);

        if (($Schluesselrollen['hat_eig_schluessel'] == "1")){
            $Antwort = "Du hast einen eigenen Schl&uuml;ssel.";
        } else {

            //Hat er eine Schlüsselübernahme gebucht?
            if (res_hat_uebernahme($ID)){

                $UebernahmeReservierung = lade_uebernahme_res($ID);
                $VorfahrendeReservierungID = $UebernahmeReservierung['reservierung_davor'];
                $UebergabeVorfahrendeReservierung = lade_uebergabe_res($VorfahrendeReservierungID);

                if ($UebergabeVorfahrendeReservierung['durchfuehrung'] == "0000-00-00 00:00:00"){
                    $Antwort = "Die Gruppe vor dir hat noch keinen Schl&uuml;ssel ausgeteilt bekommen.";
                } else if (strtotime("0000-00-00 00:00:00") < strtotime($UebergabeVorfahrendeReservierung['durchfuehrung'])){
                    $Antwort = "Du &uuml;bernimmst Schl&uuml;ssel #".$UebergabeVorfahrendeReservierung['schluessel']." von der Gruppe vor dir.";
                }

            } else {
                //Er hat keinen shclüssle
                if($Reservierung['storno_user'] == "0"){
                    $Antwort = "Dir ist bislang noch kein Schl&uuml;ssel zugeteilt worden.";
                } else if($Reservierung['storno_user'] != "0"){
                    $Antwort = "Dir war kein Schl&uuml;ssel zugeteilt.";
                }
            }
        }
    }

    return $Antwort;
}

function lade_uebernahme_res($IDres){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebernahmen WHERE reservierung = '$IDres' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    return $Ergebnis;
}

function lade_uebergabe_res($IDres){

    $link = connect_db();

    $Anfrage = "SELECT * FROM uebergaben WHERE res = '$IDres' AND storno_user = '0'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl > 0){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
    } else {
        $Ergebnis = NULL;
    }

    return $Ergebnis;
}

function anschlussfahrt($ID){

    $link = connect_db();

    //Lade Res##
    $Anfrage = "SELECT ende FROM reservierungen WHERE id = '$ID'";
    $Abfrage = mysqli_query($link, $Anfrage);

    $Res = mysqli_fetch_assoc($Abfrage);

    //Suche anshclussfahrt
    $AnfrageAnschluss = "SELECT id FROM reservierungen WHERE beginn = '".$Res['ende']."' AND storno_user = '0'";
    $AbfrageAnschluss = mysqli_query($link, $AnfrageAnschluss);
    $AnzahlAnschluss = mysqli_num_rows($AbfrageAnschluss);

    if ($AnzahlAnschluss > 0){

        //Übernahme?
        $AnfrageUebernahme = "SELECT id FROM uebernahmen WHERE reservierung_davor = '$ID' AND storno_user = '0'";
        $AbfrageUebernahme = mysqli_query($link, $AnfrageUebernahme);
        $AnzahlUebernahme = mysqli_num_rows($AbfrageUebernahme);

        if ($AnzahlUebernahme > 0){
            $Uebernahme = mysqli_fetch_assoc($AbfrageUebernahme);
            return "<p>Nach dir ist eine andere Gruppe dran. Bitte sei p&uuml;nktlich wieder zur&uuml;ck:)</p><p><b>ACHTUNG: Die Gruppe wird den Kahnschl&uuml;ssel von dir &uuml;bernehmen!</b></br>Wenn du nicht sicher bist, ob du bis ganz ans Ende deiner Reservierung fahren wirst, oder andere Gr&uuml;nde haben solltest, die Schl&uuml;ssel&uuml;bernahme nicht machen zu wollen/k&ouml;nnen, kannst du diese <a href='uebernahme_absagen.php?uebernahme=".$Uebernahme['id']."'>HIER</a> absagen.</p>";
        } else {
            return "Nach dir ist eine andere Gruppe dran. Bitte sei p&uuml;nktlich wieder zur&uuml;ck:)";
        }
    } else {
        return "Derzeit befindet sich nach deiner Reservierung keine weitere im Programm. <br> Bitte versuch trotzdem den Kahn p&uuml;nktlich zur&uuml;ckzugeben;)";
    }
}

function lade_offene_forderung_res($ResID){

    $link = connect_db();

    $Array = array();

    $AnfrageLadeForderungen = "SELECT * FROM finanz_forderungen WHERE referenz_res = '$ResID' AND storno_user = '0'";
    $AbfrageLadeForderungen = mysqli_query($link, $AnfrageLadeForderungen);
    $AnzahlLadeForderungen = mysqli_num_rows($AbfrageLadeForderungen);

    for ($a = 1; $a <= $AnzahlLadeForderungen; $a++){

        $Forderung = mysqli_fetch_assoc($AbfrageLadeForderungen);

        $AnfrageSucheNachZahlungen = "SELECT * FROM finanz_einnahmen WHERE forderung_id = '".$Forderung['id']."' AND storno_user = '0'";
        $AbfrageSucheNachZahlungen = mysqli_query($link, $AnfrageSucheNachZahlungen);
        $AnzahlSucheNachZahlungen = mysqli_num_rows($AbfrageSucheNachZahlungen);

        $Einnahmenzaehler = 0;
        for ($b = 1; $b <= $AnzahlSucheNachZahlungen; $b++){
            $Zahlung = mysqli_fetch_assoc($AbfrageSucheNachZahlungen);
            $Einnahmenzaehler = $Einnahmenzaehler + $Zahlung['betrag'];
        }

        if ($Forderung['betrag'] > $Einnahmenzaehler){
            //Forderung nicht vollständig getilgt!
            $Array = $Forderung;
        }
    }

    return $Array;
}

function lade_offene_ausgleiche_res($ResID){

    $link = connect_db();

    $Array = array();

    $AnfrageLadeForderungen = "SELECT * FROM finanz_ausgleiche WHERE referenz_res = '$ResID' AND storno_user = '0'";
    $AbfrageLadeForderungen = mysqli_query($link, $AnfrageLadeForderungen);
    $AnzahlLadeForderungen = mysqli_num_rows($AbfrageLadeForderungen);

    for ($a = 1; $a <= $AnzahlLadeForderungen; $a++){

        $Ausgleich = mysqli_fetch_assoc($AbfrageLadeForderungen);

        $AnfrageSucheNachZahlungen = "SELECT * FROM finanz_ausgaben WHERE ausgleich_id = '".$Ausgleich['id']."' AND storno_user = '0'";
        $AbfrageSucheNachZahlungen = mysqli_query($link, $AnfrageSucheNachZahlungen);
        $AnzahlSucheNachZahlungen = mysqli_num_rows($AbfrageSucheNachZahlungen);

        $Einnahmenzaehler = 0;
        for ($b = 1; $b <= $AnzahlSucheNachZahlungen; $b++){
            $Zahlung = mysqli_fetch_assoc($AbfrageSucheNachZahlungen);
            $Einnahmenzaehler = $Einnahmenzaehler + $Zahlung['betrag'];
        }

        if ($Ausgleich['betrag'] > $Einnahmenzaehler){
            //Forderung nicht vollständig getilgt!
            array_push($Array, $Ausgleich);
        }
    }

    return $Array;
}

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

function kosten_reservierung($ReservierungID){

    $Reservierung = lade_reservierung($ReservierungID);
    $UserRes = lade_user_meta($Reservierung['user']);

    //Kostenberechnung für normalos
    $date1 = new DateTime($Reservierung['beginn']);
    $date2 = new DateTime($Reservierung['ende']);
    $diff = $date2->diff($date1);
    $hours = $diff->h;
    $hours = $hours + ($diff->days*24);

    //LOGIK
    if($Reservierung['gratis_fahrt']>0){
        $Kosten = 0;
    } else {
        if($Reservierung['preis_geaendert']>0){
            $Kosten = $Reservierung['preis_geaendert'];
        } else {
            $Nutzergruppen = lade_alle_nutzgruppen();
            $GratisCounter = 0;
            $FreifahrtenCounter = 0;
            foreach ($Nutzergruppen as $Nutzergruppe){
                if($UserRes[$Nutzergruppe['name']] == "true"){
                    if($Nutzergruppe['alle_res_gratis'] == "true"){
                        $GratisCounter++;
                    } elseif ($Nutzergruppe['hat_freifahrten_pro_jahr'] > 0){
                        $FreifahrtenCounter+=$Nutzergruppe['hat_freifahrten_pro_jahr'];
                    } else {
                        $IDrueckfallnutzgergruppe = $Nutzergruppe['id'];
                    }
                }
            }

            if ($GratisCounter>0){
                $Kosten = 0;
            } else {
                if ($FreifahrtenCounter>0){
                    $AnzahlResDiesesJahr = sizeof(lade_weitere_aktive_reservierungen_user($Reservierung['user']));
                    if($AnzahlResDiesesJahr<$FreifahrtenCounter){
                        $Kosten = 0;
                    } else {
                        $Operator = 'kosten_'.$hours.'_h';
                        $Kosten = intval(lade_nutzergruppe_meta($IDrueckfallnutzgergruppe, $Operator));
                    }
                } else {
                    $Operator = 'kosten_'.$hours.'_h';
                    $Kosten = intval(lade_nutzergruppe_meta($IDrueckfallnutzgergruppe, $Operator));
                }
            }
        }
    }

    return $Kosten;
}

function lade_weitere_aktive_reservierungen_user($IDres){

    $link = connect_db();
    $Timestamp = timestamp();
    $Resursprung = lade_reservierung($IDres);
    $Ergebnis = array();

    $Anfrage = "SELECT * FROM reservierungen WHERE user = '".$Resursprung['user']."' AND storno_user = '0' AND id <> '$IDres' AND beginn > '$Timestamp' ORDER BY beginn ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for ($a = 1; $a <= $Anzahl; $a++){
        $Res = mysqli_fetch_assoc($Abfrage);
        array_push($Ergebnis, $Res);
    }

    return $Ergebnis;
}

function card_resinfos_generieren($IDres){
    $Reservierung = lade_reservierung($IDres);
    zeitformat();

    $Antwort = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $Antwort .= "<h5>Infos zu deiner Reservierung</h5>";
    $Antwort .= "<div class='section'>";

    $Antwort .= "<ul>";
    $Antwort .= "<li>Reservierungsnummer: ".$Reservierung['id']."</li>";
    $Antwort .= "<li>Datum: ".strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']))."</li>";
    $Antwort .= "<li>Abfahrt: ".strftime("%H:00 Uhr", strtotime($Reservierung['beginn']))."</li>";
    $Antwort .= "<li>R&uuml;ckgabe: ".strftime("%H:00 Uhr", strtotime($Reservierung['ende']))."</li>";
    $Antwort .= "</ul>";

    $Antwort .= "</div>";
    $Antwort .= "</div>";

    return $Antwort;
}