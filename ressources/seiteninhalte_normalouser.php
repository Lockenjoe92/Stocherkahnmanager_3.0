<?php

//STARTSEITE NORMALOUSER
function seiteninhalt_normalouser_generieren(){
    $HTML = eigene_reservierungen_user();
    #$HTML .= faellige_schluesselrueckgaben_user();
    #$HTML .= faellige_zahlungen_user();
    #$HTML .= moegliche_rueckzahlungen_user();
    return $HTML;
}
function eigene_reservierungen_user(){

    $link = connect_db();
    $Timestamp = timestamp();
    $UserID = lade_user_id();
    zeitformat();

    //Alle res laden
    $AnfangDesJahres = "".date("Y")."-01-01 00:00:01";
    $EndeDesJahres = "".date("Y")."-12-31 23:59:59";

    //Lade ID
    if (!($stmt = $link->prepare("SELECT * FROM reservierungen WHERE user = ? AND beginn >= ? AND ende <= ? AND ende > ? ORDER BY beginn ASC"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("isss", $UserID, $AnfangDesJahres, $EndeDesJahres, $Timestamp)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        $SectionHTML = "<h3>Deine Reservierungen</h3>";

        $res = $stmt->get_result();
        $AnzahlLadeAlleReservierungenDiesesJahres = mysqli_num_rows($res);

        if ($AnzahlLadeAlleReservierungenDiesesJahres == 0) {

            $SectionHTML .= "<p class='caption'>Derzeit hast du keine aktiven Reservierungen.</p><br>";
            $SectionHTML .= button_link_creator('neue Reservierung', 'reservierung_hinzufuegen.php', 'note_add', '');

        } elseif ($AnzahlLadeAlleReservierungenDiesesJahres > 0) {

            $CollapsibleItems = "";
            for ($a = 1; $a <= $AnzahlLadeAlleReservierungenDiesesJahres; $a++) {

                $Reservierung = mysqli_fetch_assoc($res);

                $DatumHeader = strftime("%A, %d. %B %G", strtotime($Reservierung['beginn']));
                $UhrzeitBeginn = strftime("%H:00", strtotime($Reservierung['beginn']));
                $UhrzeitEnde = strftime("%H:00", strtotime($Reservierung['ende']));

                if ($Reservierung['storno_user'] == 0) {

                    //Reservierung ist in zukunft und nicht storniert
                    $SpanUebergabeNotwendig = "";
                    if ((res_hat_uebergabe($Reservierung['id']) == FALSE) AND (res_hat_uebernahme($Reservierung['id']) == FALSE)) {
                        $SpanUebergabeNotwendig = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"Du musst noch eine Schl&uuml;bergabe ausmachen!\"></span>";
                    }

                    $CollpsibleHeader = "Reservierung #" . $Reservierung['id'] . " - " . $DatumHeader . "" . $SpanUebergabeNotwendig . "";

                    $TableRows = table_row_builder(table_header_builder('Fahrzeiten') . table_data_builder("Abfahrt: " . $UhrzeitBeginn . " Uhr<br>R&uuml;ckgabe: " . $UhrzeitEnde . " Uhr"));
                    $TableRows .= table_row_builder(table_header_builder('Bezahlung') . table_data_builder(zahlungswesen($Reservierung['id'])));
                    $TableRows .= table_row_builder(table_header_builder('Schlüsselübergabe') . table_data_builder(uebergabewesen($Reservierung['id'])));
                    $TableRows .= table_row_builder(table_header_builder('Schlüssel') . table_data_builder(schluesselwesen($Reservierung['id'])));
                    $TableRows .= table_row_builder(table_header_builder('Anschlussfahrt') . table_data_builder(anschlussfahrt($Reservierung['id'])));
                    $TableRows .= table_row_builder(table_header_builder(button_link_creator('Bearbeiten', 'reservierung_bearbeiten.php?id=' . $Reservierung['id'] . '', 'edit', 'materialize-' . lade_xml_einstellung('site_buttons_color') . '')) . table_data_builder(button_link_creator('Löschen', 'reservierung_loeschen.php?id=' . $Reservierung['id'] . '', 'delete', 'materialize-' . lade_xml_einstellung('site_error_buttons_color') . '')));

                    $CollapsibleContent = table_builder($TableRows);
                    $CollapsibleItems .= collapsible_item_builder($CollpsibleHeader, $CollapsibleContent, 'label_outline');

                } else {

                    //Reservierung ist in Zukunft und storniert
                    $CounterMussNochWasAngezeigtWerden = 0;
                    ##########$OffeneAusgleiche = lade_offene_ausgleiche_res($Reservierung['id']);
                    $OffeneAusgleiche = array();

                    if (sizeof($OffeneAusgleiche) > 0) {
                        $CounterMussNochWasAngezeigtWerden++;
                    }

                    if (rueckgabe_notwendig_res($Reservierung['id'])) {
                        $CounterMussNochWasAngezeigtWerden++;
                    }

                    if ($CounterMussNochWasAngezeigtWerden > 0) {

                        $CollpsibleHeader = "Resvierung #" . $Reservierung['id'] . " - " . $DatumHeader . " +++ STORNIERT +++";
                        $TableRows = table_row_builder(table_header_builder('payment') . table_data_builder(zahlungswesen($Reservierung['id'])));
                        $TableRows .= table_row_builder(table_header_builder('vpn_key') . table_data_builder(schluesselwesen($Reservierung['id'])));
                        $CollapsibleContent = table_builder($TableRows);
                        $CollapsibleItems .= collapsible_item_builder($CollpsibleHeader, $CollapsibleContent, 'label_outline');

                    }

                }

            }

            $CollapsibleItems .= collapsible_item_builder("<a href='reservierung_hinzufuegen.php?typ=pause'>Hinzuf&uuml;gen</a>", '', 'note_add');
            $SectionHTML .= collapsible_builder($CollapsibleItems);
        }
    }

    $HTML = section_builder($SectionHTML);
    $HTML .= divider_builder();

    $HelpfulLinksHTML = dokumente_listenelement_generieren();
    $HelpfulLinksHTML .= faq_user_hauptansicht_generieren();
    #$HelpfulLinksHTML .= grillinfo_hauptansicht_generieren($UserID);
    $HTML .= section_builder(collapsible_builder($HelpfulLinksHTML));

    return $HTML;
}
function faellige_schluesselrueckgaben_user(){

    $link = connect_db();

    //Lade ID
    if (!($stmt = $link->prepare("SELECT * FROM schluesselausgabe WHERE user = ? AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' AND storno_user = 0"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("s", lade_user_id())) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        $res = $stmt->get_result();
        $Anzahl = mysqli_num_rows($res);

        if ($Anzahl > 0) {

            $Counter = 0;
            $ErforderlicheRueckgabenInhalt = "";

            for ($a = 1; $a <= $Anzahl; $a++) {
                $Ergebnis = mysqli_fetch_assoc($res);
                $Schluessel = lade_schluesseldaten($Ergebnis['schluessel']);
                $KorrespondierendeRes = lade_reservierung($Ergebnis['reservierung']);
                $SpanRueckgabeErforderlich = "";

                if ($KorrespondierendeRes['storno_zeit'] == "0000-00-00 00:00:00") {

                    if (time() > strtotime($KorrespondierendeRes['ende'])) {

                        $Jetzt = new DateTime();
                        $EndeRes = new DateTime($KorrespondierendeRes['ende']);
                        $interval = $Jetzt->diff($EndeRes);
                        $Days = $interval->format('%a');

                        //Weniger als ein Tag
                        if (intval($Days) === 0) {
                            $SpanRueckgabeErforderlich = "<span class=\"new badge\" data-badge-caption=\"R&uuml;ckgabe seit heute erforderlich\"></span>";
                        }

                        //Weniger als 3 Tage
                        if ((0 < intval($Days)) AND (intval($Days) < 3)) {
                            $SpanRueckgabeErforderlich = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"R&uuml;ckgabe seit " . $Days . " Tagen erforderlich\"></span>";
                        }

                        //Seit über eine Woche
                        if (intval($Days) >= 7) {
                            $SpanRueckgabeErforderlich = "<span class=\"new badge red\" data-badge-caption=\"R&uuml;ckgabe seit " . $Days . " Tagen erforderlich\"></span>";
                        }
                    }

                } else if ($KorrespondierendeRes['storno_zeit'] != "0000-00-00 00:00:00") {

                    //Rückgabe erforderlich
                    $Counter++;

                    $Jetzt = new DateTime();
                    $StornoRes = new DateTime($KorrespondierendeRes['storno_time']);
                    $interval = $Jetzt->diff($StornoRes);
                    $Days = $interval->format('%a');

                    //Weniger als ein Tag
                    if (intval($Days) === 0) {
                        $SpanRueckgabeErforderlich = "<span class=\"new badge\" data-badge-caption=\"R&uuml;ckgabe seit heute erforderlich\"></span>";
                    }

                    //Weniger als 3 Tage
                    if ((0 < intval($Days)) AND (intval($Days) < 3)) {
                        $SpanRueckgabeErforderlich = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"R&uuml;ckgabe seit " . $Days . " Tagen erforderlich\"></span>";
                    }

                    //Seit über eine Woche
                    if (intval($Days) >= 7) {
                        $SpanRueckgabeErforderlich = "<span class=\"new badge red\" data-badge-caption=\"R&uuml;ckgabe seit " . $Days . " Tagen erforderlich\"></span>";
                    }

                }

                $ErforderlicheRueckgabenInhalt .= collection_item_builder("". $SpanRueckgabeErforderlich . "<i class='tiny material-icons " . $Schluessel['farbe_materialize'] . "'>vpn_key</i> Schl&uuml;ssel #" . $Schluessel['id'] . "");
            }

            if ($a > 0) {
                $HTML = collection_builder($ErforderlicheRueckgabenInhalt);
            }
        }

    }

    return $HTML;
}
function faellige_zahlungen_user(){
    return null;
}
function moegliche_rueckzahlungen_user(){
    return null;
}
function dokumente_listenelement_generieren(){

    return collapsible_item_builder("Dokumente und N&uuml;tzliches", lade_xml_einstellung('inhalt-dokumente-und-nuetzliches'), 'library_books');

}
function faq_user_hauptansicht_generieren(){

    return collapsible_item_builder("FAQ f&uuml;r User", lade_xml_einstellung('html-faq-user-hauptansicht'), 'live_help');

}

//RESERVIERUNG ANLEGEN
function seiteninhalt_reservierung_hinzufuegen(){

    //Titelinfo
    $HTML = lade_xml_einstellung('titelinfo-reservierung-hinzufuegen');

    //Benutzerrollen interpretieren
    $Benutzerrollen = lade_user_meta(lade_user_id());
    if ($Benutzerrollen['ist_wart'] == 'true') {
        $Kalenderrolle = "wart";
    } else {
        $Kalenderrolle = "user";
    }

    //Parser
    $Parser = reservierung_hinzufuegen_parser();
    if($Parser === TRUE){
        $HTML .= section_builder(error_button_creator('Fehler beim Anlegen', '', ''));
    } elseif ($Parser === FALSE){
        $HTML .= section_builder(error_button_creator('Anlegen erfolgreich', '', 'materialize-green darken-2'));
    }

    //Kalender
    $HTML .= section_builder(kalender_gross($Kalenderrolle), '', 'hide-on-small-and-down');
    $HTML .= section_builder(kalender_mobil($Kalenderrolle), '', 'hide-on-med-and-up');


    //Buchungsfenster
    $HTML .= section_builder(buchungsfenster($Kalenderrolle, $Parser), '', 'hide-on-small-and-down');
    $HTML .= section_builder(buchungsfenster_mobil($Kalenderrolle, $Parser), '', 'hide-on-med-and-up');

    return $HTML;
}
function buchungsfenster_mobil($Kalenderrolle, $Buttonmode)
{

    if (($Buttonmode === NULL) OR ($Buttonmode === FALSE)) {

        $Antwort = "<h5 class='center-align'>Daten der Reservierung eingeben</h5>";

        $TableHTML = table_form_datepicker_reservation_item('Datum', 'datum_buchung', $_POST['datum_buchung'], false, true);
        $TableHTML .= table_form_select_item('Abfahrt', 'beginn_reservierung', lade_xml_einstellung('earliest_begin'), lade_xml_einstellung('latest_begin'), $_POST['beginn_reservierung'], 'Uhr', '', '');
        $TableHTML .= table_form_select_item('Ende', 'ende_reservierung', lade_xml_einstellung('earliest_begin'), lade_xml_einstellung('latest_begin'), $_POST['beginn_reservierung'], 'Uhr', '', '');
        $Antwort .= table_builder($TableHTML);

        if ($Kalenderrolle === "wart") {

            //Checkbox parser
            if (isset($_POST['gratis_fahrt'])) {
                $Checkbox = "checked";
            } else {
                $Checkbox = "";
            }

            $Antwort .= "<div class='divider'></div>";

            $Antwort .= "<h5 class='center-align'>Anderen User eintragen</h5>";

            $TableWartHTML = table_form_dropdown_menu_user('User', 'user_reservierung', $_POST['user_reservierung']);
            $TableWartHTML .= table_form_swich_item('Fahrt gratis', 'gratis_fahrt', 'Nein', 'Ja', $Checkbox, false);
            $TableWartHTML .= table_form_select_item('Verg&uuml;nstigter Tarif', 'verguenstigung', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $_POST['user_reservierung'], '&euro;', 'Vergünstigung', '');
            $Antwort .= table_builder($TableWartHTML);
        }

        $Antwort .= "<div class='divider'></div>";

        if ($Kalenderrolle === "wart") {
            $Antwort .= table_builder(table_row_builder(table_data_builder(form_button_builder('input_action', 'Eintragen', 'action', 'send', '')) . table_data_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''))));
        } else {
            $Antwort .= table_builder(table_row_builder(table_data_builder(form_button_builder('input_action', 'Eintragen', 'action', 'send', ''))));
        }

        $Antwort = form_builder($Antwort, './reservierung_hinzufuegen.php', 'post', '', '');

    } else if ($Buttonmode === TRUE) {

        $Antwort = section_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''));

    }

    return $Antwort;
}
function buchungsfenster($Kalenderrolle, $Buttonmode)
{

    if (($Buttonmode === NULL) OR ($Buttonmode === FALSE)) {

        $Antwort = "<h3 class='center-align'>Daten der Reservierung eingeben</h3>";

        $TableHTML = table_form_datepicker_reservation_item('Datum', 'datum_buchung', $_POST['datum_buchung'], false, true);
        $TableHTML .= table_form_select_item('Abfahrt', 'beginn_reservierung', lade_xml_einstellung('earliest_begin'), lade_xml_einstellung('latest_begin'), $_POST['beginn_reservierung'], 'Uhr', '', '');
        $TableHTML .= table_form_select_item('Ende', 'ende_reservierung', lade_xml_einstellung('earliest_begin'), lade_xml_einstellung('latest_begin'), $_POST['beginn_reservierung'], 'Uhr', '', '');
        $Antwort .= table_builder($TableHTML);

        if ($Kalenderrolle === "wart") {

            //Checkbox parser
            if (isset($_POST['gratis_fahrt'])) {
                $Checkbox = "checked";
            } else {
                $Checkbox = "";
            }

            $Antwort .= "<div class='divider'></div>";

            $Antwort .= "<h5 class='center-align'>Anderen User eintragen</h5>";

            $TableWartHTML = table_form_dropdown_menu_user('User', 'user_reservierung', $_POST['user_reservierung']);
            $TableWartHTML .= table_form_swich_item('Fahrt gratis', 'gratis_fahrt', 'Nein', 'Ja', $Checkbox, false);
            $TableWartHTML .= table_form_select_item('Verg&uuml;nstigter Tarif', 'verguenstigung', 0, lade_xml_einstellung('max-kosten-einer-reservierung'), $_POST['user_reservierung'], '&euro;', 'Vergünstigung', '');
            $Antwort .= table_builder($TableWartHTML);
        }

        $Antwort .= "<div class='divider'></div>";

        if ($Kalenderrolle === "wart") {
            $Antwort .= table_builder(table_row_builder(table_data_builder(form_button_builder('input_action', 'Eintragen', 'action', 'send', '')) . table_data_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''))));
        } else {
            $Antwort .= table_builder(table_row_builder(table_data_builder(form_button_builder('input_action', 'Eintragen', 'action', 'send', '')). table_data_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''))));
        }

        $Antwort = form_builder($Antwort, './reservierung_hinzufuegen.php', 'post', '', '');

    } else if ($Buttonmode === TRUE) {

        $Antwort = section_builder(button_link_creator('Zurück', './my_reservations.php', 'arrow_back', ''));

    }

    return $Antwort;

}
function reservierung_hinzufuegen_parser(){

    $Antwort = NULL;

    if (isset($_POST['input_action'])) {

        $Anfang = "" . $_POST['datum_buchung'] . " " . $_POST['beginn_reservierung'] . ":00:00";
        $Ende = "" . $_POST['datum_buchung'] . " " . $_POST['ende_reservierung'] . ":00:00";
        var_dump($_POST);
        $AktuelleUserID = lade_user_id();
        $Benutzerrollen = lade_user_meta(lade_user_id());

        if ($Benutzerrollen['ist_wart'] == TRUE) {

            if (isset($_POST['user_reservierung'])) {
                $UserRes = $_POST['user_reservierung'];
            } else {
                $UserRes = $AktuelleUserID;
            }

            if (isset($_POST['gratis_fahrt'])) {
                $GratisFahrt = TRUE;
            } else {
                $GratisFahrt = FALSE;
            }

            if ($_POST['verguenstigung'] > 0) {
                $Ermaessigung = $_POST['verguenstigung'];
            } else {
                $Ermaessigung = 0;
            }

            $Ergebnis = reservierung_hinzufuegen($Anfang, $Ende, $UserRes, $GratisFahrt, $Ermaessigung);

        } else {
            $UserRes = $AktuelleUserID;
            $Ergebnis = reservierung_hinzufuegen($Anfang, $Ende, $UserRes, NULL, NULL);
        }

        //Eintrag auswerten
        if ($Ergebnis['success'] == TRUE) {
            $Antwort = TRUE;
        } else if ($Ergebnis['success'] == FALSE) {
            $Antwort = FALSE;
        }
    }

    if (isset($_POST['input_action_mobil'])) {

        $Anfang = "" . $_POST['datum_buchung_mobil'] . " " . $_POST['beginn_reservierung_mobil'] . ":00:00";
        $Ende = "" . $_POST['datum_buchung_mobil'] . " " . $_POST['ende_reservierung_mobil'] . ":00:00";
        $AktuelleUserID = lade_user_id();
        $Benutzerrollen = lade_user_meta(lade_user_id());

        if ($Benutzerrollen['wart'] == TRUE) {

            if (isset($_POST['user_reservierung_mobil'])) {
                $UserRes = $_POST['user_reservierung_mobil'];
            } else {
                $UserRes = $AktuelleUserID;
            }

            if (isset($_POST['gratis_fahrt_mobil'])) {
                $GratisFahrt = TRUE;
            } else {
                $GratisFahrt = FALSE;
            }

            if ($_POST['verguenstigung_mobil'] > 0) {
                $Ermaessigung = $_POST['verguenstigung_mobil'];
            } else {
                $Ermaessigung = "";
            }

            $Ergebnis = reservierung_hinzufuegen($Anfang, $Ende, $UserRes, $GratisFahrt, $Ermaessigung);

        } else {
            $UserRes = $AktuelleUserID;
            $Ergebnis = reservierung_hinzufuegen($Anfang, $Ende, $UserRes, NULL, NULL);
        }

        //Eintrag auswerten
        if ($Ergebnis['success'] == TRUE) {
            $Antwort = TRUE;
        } else if ($Ergebnis['success'] == FALSE) {
            $Antwort = FALSE;
        }
    }

    return $Antwort;
}