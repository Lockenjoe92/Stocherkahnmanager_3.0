<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_kasse');
$Header = "Vereinskasse - " . lade_db_einstellung('site_name');
$HTML = section_builder("<h1 class='center-align'>Vereinskasse</h1>");

if($_POST['year_global']!=''){
   $YearGlobal = $_POST['year_global'];
} else {
    $YearGlobal = date('Y');
}

#ParserStuff
$Parser = vereinskasse_parser($YearGlobal);
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

$HTML .= uebersicht_section_vereinskasse($YearGlobal);
$HTML .= kontos_section_vereinskasse($YearGlobal, $Parser);
$HTML .= forderungen_section_vereinskasse();
$HTML .= ausgaben_section_vereinskasse();
$HTML .= history_transactions_section_vereinskasse();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function vereinskasse_parser($YearGlobal){

    $Antwort = array();

    for($a=1;$a<=100000;$a++){
        if(isset($_POST['highlight_user_actions_'.$a.''])){
            $Antwort['highlight_user']=$a;
        }
    }

    if(isset($_POST['action_add_konto'])){
        $Antwort = konto_anlegen($_POST['new_konto_name'], $_POST['new_konto_typ'], $_POST['new_konto_initial']);
    }

    return $Antwort;
}
function uebersicht_section_vereinskasse($YearGlobal){

    $Gesamteinnahmen = gesamteinnahmen_jahr($YearGlobal);
    $Gesamtausgaben = gesamtausgaben_jahr($YearGlobal);
    $Differenz = $Gesamteinnahmen - $Gesamtausgaben;
    if (floatval($Differenz) >= 0){
        $StyleGUV = "class=\"green lighten-2\"";
    } else {
        $StyleGUV = "class=\"red lighten-1\"";
    }

    $HTML = "<h3 class='center-align'>Jahresstatistik ".$YearGlobal."</h3>";
    $Table = table_row_builder(table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder(form_select_item('year_global', 2017, date('Y'), $_POST['year_global'], '', 'Betrachtungsjahr', '')));
    $Table .= table_row_builder(table_data_builder($Gesamteinnahmen."&euro;").table_data_builder($Gesamtausgaben."&euro;").table_data_builder("<p ".$StyleGUV.">".$Differenz."&euro;</p>").table_data_builder(form_button_builder('change_betrachtungsjahr', 'wechseln', 'action', 'send')));
    $HTML .= form_builder(table_builder($Table), '#', 'post', 'jahresstats');

    return section_builder($HTML);
}
function kontos_section_vereinskasse($YearGlobal, $Parser){

    $BigItems = '';
    $link = connect_db();

    //Einnahmenkonten
    $Anfrage5 = "SELECT * FROM finanz_konten WHERE typ = 'einnahmenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage5 = mysqli_query($link, $Anfrage5);
    $Anzahl5 = mysqli_num_rows($Abfrage5);
    $EinnahmenkontoCounter = 0;
    $EinnahmenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Forderungen').table_header_builder('Einnahmen').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($e = 1; $e <= $Anzahl5;$e++) {
        $Ergebnis5 = mysqli_fetch_assoc($Abfrage5);
        $Forderungen = forderungen_konto($Ergebnis5['id'], $YearGlobal);
        $ForderungenSumme = 0.0;
        $EinnahmenSumme = 0.0;
        foreach ($Forderungen as $Forderung){
            $ForderungenSumme = $ForderungenSumme + $Forderung['betrag'];
            $Einnahme = lade_einnahmen_forderung($Forderung['id']);
            $EinnahmenSumme = $EinnahmenSumme + $Einnahme;
        }
        $Differenz = $EinnahmenSumme - $ForderungenSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('show_details_konto_'.$Ergebnis5['id'].'', 'Details', 'action', 'search');
        $EinnahmenkontoItems .= table_row_builder(table_data_builder($Ergebnis5['name']).table_data_builder($ForderungenSumme.'&euro;').table_data_builder($EinnahmenSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $EinnahmenkontoCounter++;
    }
    if ($EinnahmenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Einnahmenkonten', table_builder($EinnahmenkontoItems), 'attach_money');
    } else{
        $BigItems .= collapsible_item_builder('Einnahmenkonten', 'Bislang keine Einnahmenkonten angelegt!', 'attach_money');
    }

    //Ausgabenkonten
    $Anfrage6 = "SELECT * FROM finanz_konten WHERE typ = 'ausgabenkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage6 = mysqli_query($link, $Anfrage6);
    $Anzahl6 = mysqli_num_rows($Abfrage6);
    $AusgabenkontoCounter = 0;
    $AusgabenkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Geplant').table_header_builder('Ausgegeben').table_header_builder('Differenz').table_header_builder('Aktionen'));
    for ($f = 1; $f <= $Anzahl6;$f++) {
        $Ergebnis6 = mysqli_fetch_assoc($Abfrage6);
        $Ausgleiche = ausgleiche_konto($Ergebnis6['id'], $YearGlobal);
        $AUSgleichSumme = 0.0;
        $AusgabeSumme = 0.0;
        foreach ($Ausgleiche as $Ausgleich){
            $AUSgleichSumme = $AUSgleichSumme + $Ausgleich['betrag'];
            $Ausgabe = lade_ausgaben_ausgleich($Ausgleich['id']);
            $AusgabeSumme = $AusgabeSumme + $Ausgabe;
        }
        $Differenz = $AUSgleichSumme - $AusgabeSumme;
        if (floatval($Differenz) >= 0){
            $StyleGUV = "class=\"green lighten-2\"";
        } else {
            $StyleGUV = "class=\"red lighten-1\"";
        }
        $Buttons = form_button_builder('show_details_konto_'.$Ergebnis6['id'].'', 'Details', 'action', 'search');
        $AusgabenkontoItems .= table_row_builder(table_data_builder($Ergebnis6['name']).table_data_builder($AUSgleichSumme.'&euro;').table_data_builder($AusgabeSumme.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($Buttons));
        $AusgabenkontoCounter++;
    }
    if ($AusgabenkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Ausgabenkonten', table_builder($AusgabenkontoItems), 'money_off');
    } else{
        $BigItems .= collapsible_item_builder('Ausgabenkonten', 'Bislang keine Ausgabenkonten angelegt!', 'money_off');
    }

    //Neutralkonten
    $Anfrage7 = "SELECT * FROM finanz_konten WHERE typ = 'neutralkonto' AND verstecker = '0' ORDER BY typ, name ASC";
    $Abfrage7 = mysqli_query($link, $Anfrage7);
    $Anzahl7 = mysqli_num_rows($Abfrage7);
    $NeutralkontoCounter = 0;
    $NeutralkontoItems = table_row_builder(table_header_builder('Konto').table_header_builder('Aktueller Kontostand').table_header_builder('Aktionen'));
    for ($g = 1; $g <= $Anzahl7;$g++) {
        $Ergebnis7 = mysqli_fetch_assoc($Abfrage7);
        $Buttons = form_button_builder('show_details_konto_'.$Ergebnis7['id'].'', 'Details', 'action', 'search');
        $NeutralkontoItems .= table_row_builder(table_data_builder($Ergebnis7['name']).table_data_builder($Ergebnis7['wert_akt'].'&euro;').table_data_builder($Buttons));
        $NeutralkontoCounter++;
    }
    if ($NeutralkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Neutralkonten', table_builder($NeutralkontoItems), 'iso');
    } else{
        $BigItems .= collapsible_item_builder('Neutralkonten', 'Bislang keine Neutralkonten angelegt!', 'iso');
    }

    //Wartkonten
    $Users = get_sorted_user_array_with_user_meta_fields('nachname');
    $WartkontoCounter = 0;
    $WartkontoItems = table_row_builder(table_header_builder('Wart!n').table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss').table_header_builder('Aktionen'));
    foreach ($Users as $User){
        if ($User['ist_wart'] == 'true') {
            $Konto = lade_konto_user($User['id']);
            $Einnahmen = gesamteinnahmen_jahr_konto($YearGlobal,$Konto['id']);
            $Ausgaben = gesamtausgaben_jahr_konto($YearGlobal,$Konto['id']);
            $Differenz = $Einnahmen-$Ausgaben;
            if (floatval($Differenz) >= 0){
                $StyleGUV = "class=\"green lighten-2\"";
            } else {
                $StyleGUV = "class=\"red lighten-1\"";
            }
            if($Parser['highlight_user']==$User['id']){
                $Highlight = 'class="blue lighten-2"';
            } else {
                $Highlight = '';
            }
            $Buttons = form_button_builder('show_details_konto_'.$Konto['id'].'', 'Details', 'action', 'search');
            $AktionLinks = form_button_builder('highlight_user_actions_'.$User['id'].'', 'hervorheben', 'action', 'highlight');
            $WartkontoItems .= table_row_builder(table_data_builder('<p '.$Highlight.'>'.$User['vorname'].'&nbsp;'.$User['nachname'].'</p>').table_data_builder($Einnahmen.'&euro;').table_data_builder($Ausgaben.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AktionLinks.'&nbsp;'.$Buttons));
            $WartkontoCounter++;
        }
    }
    if ($WartkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Wartkonten', table_builder($WartkontoItems), 'android');
    } else{
        $BigItems .= collapsible_item_builder('Wartkonten', 'Bislang keine Wartkonten angelegt!', 'android');
    }

    $BigItems .= konto_anlegen_formular();

    $HTML = '<h3 class="center-align">Konten</h3>';
    $HTML .= form_builder(collapsible_builder($BigItems), '#', 'post');

    return section_builder($HTML);
}
function forderungen_section_vereinskasse(){
    return null;
}
function ausgaben_section_vereinskasse(){
    return null;
}
function history_transactions_section_vereinskasse(){
    return null;
}
function konto_anlegen_formular(){

    $Table = table_form_string_item('Kontoname', 'new_konto_name', $_POST['new_konto_name']);
    $Table .= table_row_builder(table_header_builder('Kontotyp').table_data_builder(dropdown_kontotyp_waehlen('new_konto_typ', $_POST['new_konto_typ'])));
    $Table .= table_form_string_item('Anfangswert', 'new_konto_initial', $_POST['new_konto_initial']);
    $Table .= table_row_builder(table_header_builder(form_button_builder('action_add_konto', 'Anlegen', 'action', 'send')).table_data_builder(''));
    $Table = table_builder($Table);
    return collapsible_item_builder('Konto anlegen', $Table, 'add_new');
}