<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
needs_dse_mv_update();
$Header = "Wartansicht - " . lade_db_einstellung('site_name');

$HTML = section_builder("<h1>Wartansicht</h1>");

#ParserStuff
$Parser = wartwesen_parser();
if(isset($Parser['meldung'])){
    $HTML .= "<h5>".$Parser['meldung']."</h5>";
}


$HTML .= section_termine_uebergaben();
$HTML .= section_status();
$HTML .= section_wart_schluessel();
$HTML .= spalte_moegliche_rueckzahlungen();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);




function section_wart_schluessel(){
$HTML = spalte_anstehende_rueckgaben();
$HTML .= section_verfuegbare_schluessel();
return $HTML;
}
function section_verfuegbare_schluessel(){

    $link = connect_db();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    if ($AnzahlLadeVerfuegbareSchluessel > 0){

        $HTML = "<div class='section'>";
        $HTML .= "<h5 class='header hide-on-med-and-down'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
        $HTML .= "<h5 class='header hide-on-large-only center-align'>Verf&uuml;gbare Schl&uuml;ssel</h5>";

        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
            $TitleString = "Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."";
            $Content = form_builder(table_builder(table_header_builder(form_button_builder('action_schluessel_'.$Schluessel['id'].'_herausnehmen', 'Herausnehmen', 'action', 'send'))), '#', 'post', '','');
            $HTML .= collapsible_item_builder($TitleString, $Content, 'vpn_key', $Schluessel['farbe_materialize']);

        }

        $HTML .= "</ul>";

        $HTML .= "</div>";
    }

    return $HTML;
}
function spalte_verfuegbare_schluessel_parser(){

    $link = connect_db();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_herausnehmen";

        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'], lade_user_id(), '', lade_user_id());
            $Event = "Schl&uuml;ssel ".$Schluessel['id']." von ".lade_user_id()." aus R&uuml;ckgabekasten genommen";
            add_protocol_entry(lade_user_id(), $Event, 'schluessel');
        }
    }

    return $Antwort;
}
function spalte_moegliche_rueckzahlungen(){

    spalte_moegliche_rueckzahlungen_parser();

    zeitformat();
    $link = connect_db();
    $Counter = 0;
    $HTML = "";

    //Offene Nachzahlungen laden (Alle abgelaufenen Reservierungen die noch nicht vollst. bezahlt sind)
    $AnfrageLadeReservierungen = "SELECT * FROM reservierungen WHERE storno_user = '0' AND gratis_fahrt = '0' AND ende < '".timestamp()."' ORDER BY beginn ASC";
    $AbfrageLadeReservierungen = mysqli_query($link, $AnfrageLadeReservierungen);
    $AnzahlLadeReservierungen = mysqli_num_rows($AbfrageLadeReservierungen);

    for ($a = 1; $a <= $AnzahlLadeReservierungen; $a++){
        $Reservierung = mysqli_fetch_assoc($AbfrageLadeReservierungen);

        //Überprüfe: hat User überhaupt eine Übergabe bekommen?
        $AnfrageLadeUebergaben = "SELECT * FROM schluesselausgabe WHERE reservierung = '".$Reservierung['id']."' AND ausgabe <> '0000-00-00 00:00:00' AND storno_user = '0'";
        $AbfrageLadeUebergaben = mysqli_query($link, $AnfrageLadeUebergaben);
        $AnzahlLadeUebergaben = mysqli_num_rows($AbfrageLadeUebergaben);

        if (($AnzahlLadeUebergaben > 0)){

            $Forderung = lade_forderung_res($Reservierung['id']);
            $BisherigeZahlungen = lade_gezahlte_summe_forderung($Forderung['id']);

            if($BisherigeZahlungen < intval($Forderung['betrag'])){
                $Counter++;
                $Restbetrag = intval($Forderung['betrag']) - $BisherigeZahlungen;
                $UserMeta = lade_user_meta($Reservierung['user']);
                $UserNachzahlung = "".$UserMeta['vorname']." ".$UserMeta['nachname']."";
                $Typ = "mail_erinnerung_nachzahlung_intervall-".$Reservierung['id']."";
                $DifferenzTage = tage_differenz_berechnen(timestamp(), $Reservierung['ende']);

                $LetzteErinnerungTimestamp = timestamp_letzte_mail_gesendet($Reservierung['user'], $Typ);
                if ($LetzteErinnerungTimestamp == FALSE){
                    $LetzeErinnerung = "Keine Erinnerung gesendet!";
                } else {
                    $LetzeErinnerung = strftime("%A, %d. %B", strtotime($LetzteErinnerungTimestamp));
                }

                //Spans

                if ($DifferenzTage == 0){
                    $Span = "<span class=\"new badge hide-on-med-and-down\" data-badge-caption=\"Fahrt seit heute vorbei\"></span>";
                    $SpanMobile = "<span class=\"new badge hide-on-large-only\" data-badge-caption=\"Fahrt heute vorbei\"></span>";
                } else if (1 == $DifferenzTage){
                    $Span = "<span class=\"new badge yellow darken-2 hide-on-med-and-down\" data-badge-caption='Ein Tag seit Fahrtende'></span>";
                    $SpanMobile = "<span class=\"new badge yellow darken-2 hide-on-large-only\" data-badge-caption='Seit einem Tag'></span>";
                } else if ((1 < $DifferenzTage) AND ($DifferenzTage < 7)){
                    $Span = "<span class=\"new badge yellow darken-2 hide-on-med-and-down\" data-badge-caption='".$DifferenzTage." Tage seit Fahrtende'></span>";
                    $SpanMobile = "<span class=\"new badge yellow darken-2 hide-on-large-only\" data-badge-caption='Seit ".$DifferenzTage." Tagen'></span>";
                } else if ($DifferenzTage >= 7){
                    $Span = "<span class=\"new badge red hide-on-med-and-down\" data-badge-caption='".$DifferenzTage." Tage seit Fahrtende'></span>";
                    $SpanMobile = "<span class=\"new badge red hide-on-large-only\" data-badge-caption='Seit ".$DifferenzTage." Tagen'></span>";
                }


                $HTML .= "<li>";
                $HTML .= "<div class='collapsible-header'>".$Span."".$SpanMobile."<i class='large material-icons'>toll</i>Nachzahlung Res. ".$Reservierung['id']." - ".$UserNachzahlung."</div>";
                $HTML .= "<div class='collapsible-body'>";
                $HTML .= "<div class='container'>";
                $HTML .= "<form method='post'>";
                $HTML .= "<ul class='collection'>";
                $HTML .= "<li class='collection-item'>User: ".$UserNachzahlung."</li>";
                $HTML .= "<li class='collection-item'>Forderung: ".$Restbetrag."&euro;</li>";
                $HTML .= "<li class='collection-item'>Letzte Erinnerung: ".$LetzeErinnerung."</li>";
                $HTML .= "<li class='collection-item'>
                                        <div class='input-field'>
                                        <input type='text' name='betrag' id='betrag' value='".$_POST['betrag']."'><label for='betrag'>Gezahlter Betrag</label>
                                        </div>
                                        <div class='input-field'>
                                        <button class='btn waves-effect waves-light' type='submit' name='action_nachzahlung_".$Reservierung['id']."_festhalten'>Nachzahlung festhalten</button>
                                        </div>
                                        <div class='input-field'>
                                        <button class='btn waves-effect waves-light' type='submit' name='action_nachzahlung_".$Reservierung['id']."_erinnerung_senden'>Erinnerung senden</button>
                                        </div>
                                        </li>";
                $HTML .= "</ul>";
                $HTML .= "</form>";
                $HTML .= "</div>";
                $HTML .= "</div>";
                $HTML .= "</li>";

            }

        }

    }

    if ($Counter > 0){
        $HTMLexport = "<div class='section'>";
        $HTMLexport .= "<h5 class='header hide-on-med-and-down'>Offene R&uuml;ck-/Nachzahlungen</h5>";
        $HTMLexport .= "<h5 class='header hide-on-large-only center-align'>R&uuml;ck-/Nachzahlungen</h5>";
        $HTMLexport .= "<ul class='collapsible popout' data-collapsible='accordion'>";
        $HTMLexport .= $HTML;
        $HTMLexport .= "</ul>";
        $HTMLexport .= "</div>";
    }

    return $HTMLexport;
}
function spalte_moegliche_rueckzahlungen_parser(){

    $link = connect_db();

    $AnfrageLadeReservierungen = "SELECT * FROM reservierungen WHERE storno_user = '0' AND gratis_fahrt = '0' AND ende < '".timestamp()."'";
    $AbfrageLadeReservierungen = mysqli_query($link, $AnfrageLadeReservierungen);
    $AnzahlLadeReservierungen = mysqli_num_rows($AbfrageLadeReservierungen);

    for ($a = 1; $a <= $AnzahlLadeReservierungen; $a++){
        $Reservierung = mysqli_fetch_assoc($AbfrageLadeReservierungen);
        $HTMLeinztragen = "action_nachzahlung_".$Reservierung['id']."_festhalten";
        $HTMLerinnern = "action_nachzahlung_".$Reservierung['id']."_erinnerung_senden";

        if (isset($_POST[$HTMLeinztragen])){
            $Ergebnis = nachzahlung_reservierung_festhalten($Reservierung['id'], $_POST['betrag'], lade_user_id());
            toast_ausgeben($Ergebnis['meldung']);
        }

        if (isset($_POST[$HTMLerinnern])){
            toast_ausgeben('Funktion muss noch implementiert werden!');
        }
    }
}
function section_termine_uebergaben(){
    $HTML = section_uebergaben();
    $HTML .= section_termine();
    return $HTML;
}
function section_uebergaben(){
    //Grundsätzliches
    $link = connect_db();

    //Lade aktive Übergaben
    $AnfrageLadeAktiveUebergaben = "SELECT id FROM uebergaben WHERE durchfuehrung = '0000-00-00 00:00:00' AND wart = '".lade_user_id()."' AND storno_user = '0' ORDER BY beginn ASC";
    $AbfrageLadeAktiveUebergaben = mysqli_query($link, $AnfrageLadeAktiveUebergaben);
    $AnzahlLadeAktiveUebergaben = mysqli_num_rows($AbfrageLadeAktiveUebergaben);

    $HTML =  "<div class='section'>";
    $HTML .= "<h5 class='header hide-on-med-and-down'>Deine Schl&uuml;ssel&uuml;bergaben</h5>";
    $HTML .= "<h5 class='header hide-on-large-only center-align'>Deine Schl&uuml;ssel&uuml;bergaben</h5>";

    if ($AnzahlLadeAktiveUebergaben == 0){
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>error</i> Derzeit keine Schl&uuml;ssel&uuml;bergaben!</div>";
        $HTML .= "</li>";
        $HTML .= spontanuebergabe_listenelement_generieren();
        $HTML .= uebergabe_planen_listenelement_generieren();
        $HTML .= uebernahme_planen_listenelement_generieren();
        $HTML .= dokumente_listenelement_generieren();
        $HTML .= faq_user_hauptansicht_generieren();
        $HTML .= "</ul>";

    } else if ($AnzahlLadeAktiveUebergaben > 0){
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeAktiveUebergaben; $a ++){
            $Uebergabe = mysqli_fetch_assoc($AbfrageLadeAktiveUebergaben);
            $HTML .= uebergabe_listenelement_generieren($Uebergabe['id'], TRUE);
        }

        $HTML .= spontanuebergabe_listenelement_generieren();
        $HTML .= uebergabe_planen_listenelement_generieren();
        $HTML .= uebernahme_planen_listenelement_generieren();
        $HTML .= dokumente_listenelement_generieren();
        $HTML .= faq_user_hauptansicht_generieren();

        $HTML .= "</ul>";
    }

    $HTML .= "</div>";
    return $HTML;
}
function section_termine(){
    //Grundsätzliches
    $link = connect_db();

    //Lade aktive Übergaben
    $AnfrageLadeAktiveTermine = "SELECT id FROM termine WHERE durchfuehrung = '0000-00-00 00:00:00' AND wart = '".lade_user_id()."' AND storno_user = '0' ORDER BY zeitpunkt ASC";
    $AbfrageLadeAktiveTermine = mysqli_query($link, $AnfrageLadeAktiveTermine);
    $AnzahlLadeAktiveTermine = mysqli_num_rows($AbfrageLadeAktiveTermine);

    $HTML = "";

    if ($AnzahlLadeAktiveTermine > 0){

        $HTML = "<div class='section'>";
        $HTML .= "<h5 class='header hide-on-med-and-down'>Weitere Termine</h5>";
        $HTML .= "<h5 class='header hide-on-large-only center-align'>Weitere Termine</h5>";
        $HTML .= "<div class='section'>";
        $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

        for ($a = 1; $a <= $AnzahlLadeAktiveTermine; $a ++){
            $Termin = mysqli_fetch_assoc($AbfrageLadeAktiveTermine);
            $HTML .= termin_listenelement_generieren($Termin['id']);
        }

        $HTML .= "</ul>";
        $HTML .= "</div>";
        $HTML .= "</div>";
    }

    return $HTML;
}
function section_status(){

    //Tage Schalter auswerten
    $AnzahlTage = tage_schalter_parser();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header hide-on-med-and-down'>Kahn&uuml;bersicht</h5>";
    $HTML .= "<h5 class='header hide-on-large-only center-align'>Kahn&uuml;bersicht</h5>";
    $HTML .= "<div class='section'>";
    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    for ($a = 0; $a <= $AnzahlTage; $a++){
        $HTML .= listenelement_tagesgeschehen_generieren($a);
    }

    $HTML .= listenelement_tage_schalter_generieren($AnzahlTage);

    $HTML .= "</ul>";
    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function listenelement_tagesgeschehen_generieren($TageVerschiebung){

    zeitformat();
    $link = connect_db();
    $UebernahmenTag = array();
    $Befehl = "+ ".$TageVerschiebung." days";
    $PausenInhalt = "";
    $SperrungenInhalt = "";
    $ReservierungenInhalt = "";
    $TerminangeboteInhalt = "";
    $UebergabenInhalt = "";
    $UebernahmenInhalt = "";
    $PausenInhaltMobile = "";
    $SperrungenInhaltMobile = "";
    $ReservierungenInhaltMobile = "";
    $TerminangeboteInhaltMobile = "";
    $UebergabenInhaltMobile = "";
    $UebernahmenInhaltMobile = "";


    if ($TageVerschiebung == 0){
        $DatumAngabe = "Heute - ".htmlentities(strftime("%A, %d. %B %G"))."";
        $DatumAngabeMobile = "Heute";
    } else if ($TageVerschiebung == 1){
        $DatumAngabe = "Morgen - ".htmlentities(strftime("%A, %d. %B %G", strtotime($Befehl)))."";
        $DatumAngabeMobile = "Morgen";
    } else if ($TageVerschiebung == 2){
        $DatumAngabe = "&Uuml;bermorgen - ".htmlentities(strftime("%A, %d. %B %G", strtotime($Befehl)))."";
        $DatumAngabeMobile = htmlentities(strftime("%a, %d. %b", strtotime($Befehl)));
    } else if ($TageVerschiebung > 2){
        $DatumAngabe = htmlentities(strftime("%A, %d. %B %G", strtotime($Befehl)));
        $DatumAngabeMobile = htmlentities(strftime("%a, %d. %b", strtotime($Befehl)));
    }

    if ($TageVerschiebung == 0){
        $DatumSuchtagBeginn = "".date("Y-m-d")." 00:00:01";
        $DatumSuchtagEnde = "".date("Y-m-d")." 23:59:59";
    } else if ($TageVerschiebung > 0){
        $DatumSuchtagBeginn = "".date("Y-m-d", strtotime($Befehl))." 00:00:01";
        $DatumSuchtagEnde = "".date("Y-m-d", strtotime($Befehl))." 23:59:59";
    }

    //Reservierungen
    $AnfrageLadeResTag = "SELECT * FROM reservierungen WHERE storno_user = '0' AND beginn > '".$DatumSuchtagBeginn."' AND beginn < '".$DatumSuchtagEnde."' ORDER BY beginn ASC";
    $AbfrageLadeResTag = mysqli_query($link, $AnfrageLadeResTag);
    $AnzahlLadeResTag = mysqli_num_rows($AbfrageLadeResTag);

    if($AnzahlLadeResTag == 1){
        $Reservierungen = "<span class=\"new badge blue\" data-badge-caption=\"Reservierung\">1</span>";
        $ReservierungenMobile = "<span class=\"new badge blue\" data-badge-caption=\"Res\">1</span>";
    } else if ($AnzahlLadeResTag > 1){
        $Reservierungen = "<span class=\"new badge blue\" data-badge-caption=\"Reservierungen\">".$AnzahlLadeResTag."</span>";
        $ReservierungenMobile = "<span class=\"new badge blue\" data-badge-caption=\"Res\">".$AnzahlLadeResTag."</span>";
    }

    if ($AnzahlLadeResTag > 0){
        $ReservierungenInhalt .= "<h5>Reservierungen</h5><div class='collection'>";
        $ReservierungenInhaltMobile .= "<h5 class='center-align'>Reservierungen</h5><div class='collection'>";

        for($d = 1; $d <= $AnzahlLadeResTag; $d++){
            $ResAktuell = mysqli_fetch_assoc($AbfrageLadeResTag);
            $UserMeta = lade_user_meta($ResAktuell['user']);

            $User = "<a href='./benutzermanagement_wart.php?user=".$ResAktuell['user']."'>".$UserMeta['vorname']." ".$UserMeta['nachname']."</a>";
            $UserMobile = "<a href='./benutzermanagement_wart.php?user=".$ResAktuell['user']."'>".$UserMeta['vorname']." ".$UserMeta['nachname']."</a>";

            if (($UserMeta['hat_eig_schluessel'] == "1") OR ($UserMeta['wg_hat_schluessel'] == "1")){

                $SpanResUebergabestatus = "<span class=\"new badge\" data-badge-caption=\"User hat eigenen Schl&uuml;ssel\"></span>";
                $SpanResUebergabestatusMobile = "<span class=\"new badge\" data-badge-caption=\"Hat eig. Schl&uuml;ssel\"></span>";

            } else {

                $AnfrageLadeUebergabeRes = "SELECT * FROM uebergaben WHERE res = '".$ResAktuell['id']."' AND storno_user = '0'";
                $AbfrageLadeUebergabeRes = mysqli_query($link, $AnfrageLadeUebergabeRes);
                $AnzahlLadeUebergabeRes = mysqli_num_rows($AbfrageLadeUebergabeRes);

                if ($AnzahlLadeUebergabeRes == 0){

                    //Evtl ne Übergabe ausgemacht??
                    $AnfrageLadeUebernahmeRes = "SELECT * FROM uebernahmen WHERE reservierung = '".$ResAktuell['id']."' AND storno_user = '0'";
                    $AbfrageLadeUebernahmeRes = mysqli_query($link, $AnfrageLadeUebernahmeRes);
                    $AnzahlLadeUebernahmeRes = mysqli_num_rows($AbfrageLadeUebernahmeRes);

                    if ($AnzahlLadeUebernahmeRes == 0){
                        $SpanResUebergabestatus = "<span class=\"new badge red\" data-badge-caption=\"keine &Uuml;bergabe ausgemacht\"></span>";
                        $SpanResUebergabestatusMobile = "<span class=\"new badge red\" data-badge-caption=\"keine &Uuml;bergabe\"></span>";
                    } else if ($AnzahlLadeUebernahmeRes > 0){

                        array_push($UebernahmenTag, mysqli_fetch_assoc($AbfrageLadeUebernahmeRes));
                        $SpanResUebergabestatus = "<span class=\"new badge orange darken-2\" data-badge-caption=\"&Uuml;bernahme ausgemacht\"></span>";
                        $SpanResUebergabestatusMobile = "<span class=\"new badge orange darken-2\" data-badge-caption=\"&Uuml;bernahme\"></span>";

                    }

                } else if($AnzahlLadeUebergabeRes == 1){

                    $UebergabeRes = mysqli_fetch_assoc($AbfrageLadeUebergabeRes);

                    if ($UebergabeRes['durchfuehrung'] == "0000-00-00 00:00:00"){
                        //Ausgemacht - nicht durchgeführt
                        if (time() < strtotime($UebergabeRes['beginn'])){
                            //Steht noch an - ok
                            $SpanResUebergabestatus = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"&Uuml;bergabe ausgemacht\"></span>";
                            $SpanResUebergabestatusMobile = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"&Uuml;b. ausgemacht\"></span>";

                        } else if (time() > strtotime($UebergabeRes['beginn'])){
                            //Abgelaufen
                            $SpanResUebergabestatus = "<span class=\"new badge red\" data-badge-caption=\"&Uuml;bergabe abgelaufen\"></span>";
                            $SpanResUebergabestatusMobile = "<span class=\"new badge red\" data-badge-caption=\"&Uuml;b. abgelaufen\"></span>";
                        }

                    } else if ($UebergabeRes['durchfuehrung'] != "0000-00-00 00:00:00"){
                        //Ausgemacht - durchgeführt
                        $SpanResUebergabestatus = "<span class=\"new badge\" data-badge-caption=\"&Uuml;bergabe durchgef&uuml;hrt\"></span>";
                        $SpanResUebergabestatusMobile = "<span class=\"new badge\" data-badge-caption=\"&Uuml;b. erfolgt\"></span>";
                    }
                }
            }

            $ReservierungenInhalt .= "<p class='collection-item'><i class='tiny material-icons'>today</i> ".date("G", strtotime($ResAktuell['beginn']))." bis ".date("G", strtotime($ResAktuell['ende']))." Uhr - ".$User."".$SpanResUebergabestatus."</p>";
            $ReservierungenInhaltMobile .= "<p class='collection-item'><i class='tiny material-icons'>today</i> ".date("G", strtotime($ResAktuell['beginn']))." bis ".date("G", strtotime($ResAktuell['ende']))." Uhr - ".$UserMobile."".$SpanResUebergabestatusMobile."</p>";
        }
        $ReservierungenInhalt .= "</div>";
        $ReservierungenInhaltMobile .= "</div>";
    }

    //Terminangebote
    $AnfrageLadeTerminangebote = "SELECT * FROM terminangebote WHERE storno_user = '0' AND von > '".$DatumSuchtagBeginn."' AND von < '".$DatumSuchtagEnde."' ORDER BY von ASC";
    $AbfrageLadeTerminangebote = mysqli_query($link, $AnfrageLadeTerminangebote);
    $AnzahlLadeTerminangebote = mysqli_num_rows($AbfrageLadeTerminangebote);

    if($AnzahlLadeTerminangebote == 1){
        $Angebote = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"Terminangebot\">1</span>";
        $AngeboteMobile = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"Angeb\">1</span>";
    } else if ($AnzahlLadeTerminangebote > 1){
        $Angebote = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"Terminangebote\">".$AnzahlLadeTerminangebote."</span>";
        $AngeboteMobile = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"Angeb\">".$AnzahlLadeTerminangebote."</span>";
    }

    if ($AnzahlLadeTerminangebote > 0){

        $TerminangeboteInhalt .= "<h5>Terminangebote</h5><div class='collection'>";
        $TerminangeboteInhaltMobile .= "<h5 class='center-align'>Terminangebote</h5><div class='collection'>";

        for ($s = 1; $s <= $AnzahlLadeTerminangebote; $s++){

            $TerminangebotAktuell = mysqli_fetch_assoc($AbfrageLadeTerminangebote);
            $WartAngebotMeta = lade_user_meta($TerminangebotAktuell['wart']);

            $AnfrageLadeEntstandeneUebergaben = "SELECT id FROM uebergaben WHERE terminangebot = '".$TerminangebotAktuell['id']."' AND storno_user = '0'";
            $AbfrageLadeEntstandeneUebergaben = mysqli_query($link, $AnfrageLadeEntstandeneUebergaben);
            $AnzahlLadeEntstandeneUebergaben = mysqli_num_rows($AbfrageLadeEntstandeneUebergaben);

            if ($AnzahlLadeEntstandeneUebergaben == 0){
                $SpanEntstandeneUebergaben = "<span class=\"new badge red\" data-badge-caption=\"keine entstandenen &Uuml;bergaben\"></span>";
                $SpanEntstandeneUebergabenMobile = "<span class=\"new badge red\" data-badge-caption=\"keine &Uuml;b.\"></span>";
            } else if ($AnzahlLadeEntstandeneUebergaben > 1){
                $SpanEntstandeneUebergaben = "<span class=\"new badge\" data-badge-caption=\"entstandene &Uuml;bergaben\">".$AnzahlLadeEntstandeneUebergaben."</span>";
                $SpanEntstandeneUebergabenMobile = "<span class=\"new badge\" data-badge-caption=\"ent. &Uuml;b.\">".$AnzahlLadeEntstandeneUebergaben."</span>";
            } else if ($AnzahlLadeEntstandeneUebergaben == 1){
                $SpanEntstandeneUebergaben = "<span class=\"new badge\" data-badge-caption=\"eine entstandene &Uuml;bergabe\"></span>";
                $SpanEntstandeneUebergabenMobile = "<span class=\"new badge\" data-badge-caption=\"eine &Uuml;b.\"></span>";
            }

            $TerminangeboteInhalt .= "<p class='collection-item hide-on-med-and-down'><i class='tiny material-icons'>today</i> ".date("G:i", strtotime($TerminangebotAktuell['von']))." bis ".date("G:i", strtotime($TerminangebotAktuell['bis']))." Uhr - ".$WartAngebotMeta['vorname']."".$SpanEntstandeneUebergaben."</p>";
            $TerminangeboteInhaltMobile .= "<p class='collection-item hide-on-large-only'><i class='tiny material-icons'>today</i> ".date("G:i", strtotime($TerminangebotAktuell['von']))." bis ".date("G:i", strtotime($TerminangebotAktuell['bis']))." Uhr - ".$WartAngebotMeta['vorname']."".$SpanEntstandeneUebergabenMobile."</p>";

        }

        $TerminangeboteInhalt .= "</div>";
        $TerminangeboteInhaltMobile .= "</div>";
    }

    //Übergaben
    $AnfrageLadeUebergabenTag = "SELECT * FROM uebergaben WHERE storno_user = '0' AND beginn > '".$DatumSuchtagBeginn."' AND beginn < '".$DatumSuchtagEnde."' ORDER BY beginn ASC";
    $AbfrageLadeUebergabenTag = mysqli_query($link, $AnfrageLadeUebergabenTag);
    $AnzahlLadeUebergabenTag = mysqli_num_rows($AbfrageLadeUebergabenTag);

    if($AnzahlLadeUebergabenTag == 1){
        $Uebergaben = "<span class=\"new badge\" data-badge-caption=\"&Uuml;bergabe\">1</span>";
        $UebergabenMobile = "<span class=\"new badge\" data-badge-caption=\"&Uuml;b\">1</span>";
    } else if ($AnzahlLadeUebergabenTag > 1){
        $Uebergaben = "<span class=\"new badge\" data-badge-caption=\"&Uuml;bergaben\">".$AnzahlLadeUebergabenTag."</span>";
        $UebergabenMobile = "<span class=\"new badge\" data-badge-caption=\"&Uuml;b\">".$AnzahlLadeUebergabenTag."</span>";
    }

    if ($AnzahlLadeUebergabenTag > 0){

        $ReservierungenInhaltInhalt = "";
        $ReservierungenInhaltMobileInhalt = "";

        for ($e = 1; $e <= $AnzahlLadeUebergabenTag; $e++){
            $UebergabeAktuell = mysqli_fetch_assoc($AbfrageLadeUebergabenTag);
            $ResUebergabe = lade_reservierung($UebergabeAktuell['res']);
            $WartUebergabeMeta = lade_user_meta($UebergabeAktuell['wart']);
            $UserUebergabeMeta = lade_user_meta($ResUebergabe['user']);
            $WartUebergabe = "".$WartUebergabeMeta['vorname']."";
            $UserUebergabe = "".$UserUebergabeMeta['vorname']." ".$UserUebergabeMeta['nachname']."";

            if ($UebergabeAktuell['durchfuehrung'] == "0000-00-00 00:00:00"){

                if (time() > strtotime($UebergabeAktuell['beginn'])){
                    $SpanUebergabestatus = "<span class=\"new badge red\" data-badge-caption=\"abgelaufen\"></span>";
                } else if (time() < strtotime($UebergabeAktuell['beginn'])){
                    $SpanUebergabestatus = "<span class=\"new badge yellow darken-2\" data-badge-caption=\"steht an\"></span>";
                }

            } else if ($UebergabeAktuell['durchfuehrung'] != "0000-00-00 00:00:00"){
                $SpanUebergabestatus = "<span class=\"new badge\" data-badge-caption=\"durchgef&uuml;hrt\"></span>";
            }

            $ReservierungenInhaltInhalt .= "<p class='collection-item'><i class='tiny material-icons'>today</i> ".date("G:i", strtotime($UebergabeAktuell['beginn']))." Uhr - ".$WartUebergabe." an ".$UserUebergabe."".$SpanUebergabestatus."</p>";
            $ReservierungenInhaltMobileInhalt .= "<p class='collection-item'><i class='tiny material-icons'>today</i> ".date("G:i", strtotime($UebergabeAktuell['beginn']))." Uhr - ".$WartUebergabe." an ".$UserUebergabe."".$SpanUebergabestatus."</p>";
        }

        $UebergabenInhalt .= "<h5>&Uuml;bergaben</h5><div class='collection'>";
        $UebergabenInhaltMobile .= "<h5 class='center-align'>&Uuml;bergaben</h5><div class='collection'>";
        $UebergabenInhalt .= $ReservierungenInhaltInhalt;
        $UebergabenInhaltMobile .= $ReservierungenInhaltMobileInhalt;
        $UebergabenInhalt .= "</div>";
        $UebergabenInhaltMobile .= "</div>";
    }

    //Sperren
    $AnfrageLadeSperrungTag = "SELECT * FROM sperrungen WHERE storno_user = '0'";
    $AbfrageLadeSperrungTag = mysqli_query($link, $AnfrageLadeSperrungTag);
    $AnzahlLadeSperrungTag = mysqli_num_rows($AbfrageLadeSperrungTag);

    for ($b = 1; $b <= $AnzahlLadeSperrungTag; $b++){
        $SperrungTag = mysqli_fetch_assoc($AbfrageLadeSperrungTag);

        if ((date("Y-m-d", strtotime($Befehl)) >= date("Y-m-d", strtotime($SperrungTag['beginn']))) AND (date("Y-m-d", strtotime($Befehl)) <= date("Y-m-d", strtotime($SperrungTag['ende'])))){
            $Sperrung = "<span class=\"new badge red\" data-badge-caption=\"".$SperrungTag['typ']." ".date("G", strtotime($SperrungTag['beginn']))."-".date("G", strtotime($SperrungTag['ende']))." Uhr\"></span>";
        }
    }

    //Pausen
    $AnfrageLadePauseTag = "SELECT * FROM pausen WHERE storno_user = '0'";
    $AbfrageLadePauseTag = mysqli_query($link, $AnfrageLadePauseTag);
    $AnzahlLadePauseTag = mysqli_num_rows($AbfrageLadePauseTag);

    for ($c = 1; $c <= $AnzahlLadePauseTag; $c++){
        $PauseTag = mysqli_fetch_assoc($AbfrageLadePauseTag);

        if ((date("Y-m-d", strtotime($Befehl)) >= date("Y-m-d", strtotime($PauseTag['beginn']))) AND (date("Y-m-d", strtotime($Befehl)) <= date("Y-m-d", strtotime($PauseTag['ende'])))){
            $Pause = "<span class=\"new badge red\" data-badge-caption=\"".$PauseTag['typ']."\"></span>";
        }
    }

    //Übernahmen
    if (sizeof($UebernahmenTag) > 0){

        if (sizeof($UebernahmenTag) == 1){
            $TextUebernahmenBigScreen = "eine &Uuml;bernahme";
        } else if (sizeof($UebernahmenTag) > 1){
            $TextUebernahmenBigScreen = "".sizeof($UebernahmenTag)." %Uuml;bernahmen";
        }

        //Inhalt der Übernahmen generieren
        $UebernahmenText = "";
        foreach ($UebernahmenTag as $Uebernahme) {

            $Reservierung = lade_reservierung($Uebernahme['reservierung']);
            $ReservierungDavor = lade_reservierung($Uebernahme['reservierung_davor']);
            $ResUser = lade_user_meta($Reservierung['user']);
            $ResUserDavor = lade_user_meta($ReservierungDavor['user']);

            $UserDanach = "<a href='benutzermanagement_wart.php?user=".$Reservierung['user']."'>".$ResUser['vorname']." ".$ResUser['nachname']."</a>";
            $UserDavor = "<a href='benutzermanagement_wart.php?user=".$ReservierungDavor['user']."'>".$ResUserDavor['vorname']." ".$ResUserDavor['nachname']."</a>";

            $UebernahmenText .= "<p class='collection-item'><i class='tiny material-icons'>swap_calls</i> ".date("G:i", strtotime($Reservierung['beginn']))." Uhr - ".$UserDavor." an ".$UserDanach." - <a href='uebernahme_absagen.php?uebernahme=".$Uebernahme['id']."'><i class='tiny material-icons'>delete</i> Stornieren</a></p>";
        }

        $Uebernahmen = "<span class=\"new badge orange darken-2\" data-badge-caption=\"".$TextUebernahmenBigScreen."\"></span>";
        $UebernahmenMobile = "<span class=\"new badge orange darken-2\" data-badge-caption=\"".sizeof($UebernahmenTag)." &Uuml;bern.\"></span>";

        $UebernahmenInhalt .= "<h5>&Uuml;bernahmen</h5><div class='collection'>";
        $UebernahmenInhaltMobile .= "<h5 class='center-align'>&Uuml;bernahmen</h5><div class='collection'>";
        $UebernahmenInhalt .= $UebernahmenText;
        $UebernahmenInhaltMobile .= $UebernahmenText;
        $UebernahmenInhalt .= "</div>";
        $UebernahmenInhaltMobile .= "</div>";
    }


    //Großer Screen
    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons'>today</i>".$DatumAngabe."".$Pause."".$Sperrung."".$Angebote."".$Reservierungen."".$Uebergaben."".$Uebernahmen."</div>";
    $HTML .= "<div class='collapsible-body'>";

    $HTML .= $PausenInhalt;
    $HTML .= $SperrungenInhalt;
    $HTML .= $ReservierungenInhalt;
    $HTML .= $TerminangeboteInhalt;
    $HTML .= $UebergabenInhalt;
    $HTML .= $UebernahmenInhalt;

    $HTML .= "</div>";
    $HTML .= "</li>";

    //Kleiner Screen
    $HTML .= "<li>";
    $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons'>today</i>".$DatumAngabeMobile."".$Pause."".$Sperrung."".$AngeboteMobile."".$ReservierungenMobile."".$UebergabenMobile."".$UebernahmenMobile."</div>";
    $HTML .= "<div class='collapsible-body'>";

    $HTML .= $PausenInhaltMobile;
    $HTML .= $SperrungenInhaltMobile;
    $HTML .= $ReservierungenInhaltMobile;
    $HTML .= $TerminangeboteInhaltMobile;
    $HTML .= $UebergabenInhaltMobile;
    $HTML .= $UebernahmenInhaltMobile;

    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}
function listenelement_tage_schalter_generieren($AnzahlTage){

    if ($AnzahlTage == 7){
        $HTML = "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>search</i> Ansicht ver&auml;ndern</div>";
        $HTML .= "<div class='collapsible-body'>";
        $HTML .= "<form method='post'>";
        $HTML .= "<table><tr>";
        $HTML .= "<th><input type='hidden' name='tage' value='".$AnzahlTage."'><button class='btn waves-effect waves-light' type='submit' name='reset' disabled>Zur&uuml;cksetzen</button></th><th><button class='btn waves-effect waves-light' type='submit' name='plus_one_week'>+1 Woche</button></th><th><button class='btn waves-effect waves-light' type='submit' name='plus_one_month'>+1 Monat</button></th>";
        $HTML .= "</tr></table>";
        $HTML .= "</form>";
        $HTML .= "</div>";
        $HTML .= "</li>";
    } else if ($AnzahlTage > 7){
        $HTML = "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>search</i> Ansicht ver&auml;ndern</div>";
        $HTML .= "<div class='collapsible-body'>";
        $HTML .= "<form method='post'>";
        $HTML .= "<table><tr>";
        $HTML .= "<th><input type='hidden' name='tage' value='".$AnzahlTage."'><button class='btn waves-effect waves-light' type='submit' name='reset'>Zur&uuml;cksetzen</button></th><th><button class='btn waves-effect waves-light' type='submit' name='plus_one_week'>+1 Woche</button></th><th><button class='btn waves-effect waves-light' type='submit' name='plus_one_month'>+1 Monat</button></th>";
        $HTML .= "</tr></table>";
        $HTML .= "</form>";
        $HTML .= "</div>";
        $HTML .= "</li>";
    }

    return  $HTML;
}
function tage_schalter_parser(){

    if (isset($_POST['tage'])){
        if ($_POST['tage'] < 7){
            $AnzahlTagePost = 7;
        } else {
            $AnzahlTagePost = $_POST['tage'];
        }

        if (isset($_POST['reset'])){
            $AnzahlTage = 7;
        }

        if (isset($_POST['plus_one_week'])){
            $AnzahlTage = $AnzahlTagePost + 7;
        }

        if (isset($_POST['plus_one_month'])){
            $AnzahlTage = $AnzahlTagePost + 31;
        }

    } else {
        $AnzahlTage = 7;
    }

    return $AnzahlTage;
}
function wartwesen_parser(){

    $Parser = spalte_verfuegbare_schluessel_parser();
    if(!isset($Parser['meldung'])){
        $Parser = spalte_anstehende_rueckgaben_parser();
    }

    return $Parser;
}
