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

    //Einnahmenkonten

    //Ausgabenkonten

    //Neutralkonten

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
            $AktionLinks = form_button_builder('highlight_user_actions_'.$User['id'].'', 'hervorheben', 'action', 'highlight');
            $WartkontoItems .= table_row_builder(table_data_builder('<p '.$Highlight.'>'.$User['vorname'].'&nbsp;'.$User['nachname'].'</p>').table_data_builder($Einnahmen.'&euro;').table_data_builder($Ausgaben.'&euro;').table_data_builder('<p '.$StyleGUV.'>'.$Differenz.'&euro;</p>').table_data_builder($AktionLinks));
            $WartkontoCounter++;
        }
    }
    if ($WartkontoCounter > 0){
        $BigItems .= collapsible_item_builder('Wartkonten', table_builder($WartkontoItems), 'android');
    } else{
        $BigItems .= collapsible_item_builder('Wartkonten', 'Bislang keine Wartkonten angelegt!', 'android');
    }

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