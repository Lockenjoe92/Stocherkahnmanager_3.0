<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_kasse');
$Header = "Vereinskasse - " . lade_db_einstellung('site_name');
$HTML = section_builder("<h1 class='center-align'>Vereinskasse</h1>");

#ParserStuff
$Parser = vereinskasse_parser();
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

$HTML .= uebersicht_section_vereinskasse();
$HTML .= kontos_section_vereinskasse();
$HTML .= forderungen_section_vereinskasse();
$HTML .= ausgaben_section_vereinskasse();
$HTML .= history_transactions_section_vereinskasse();

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function vereinskasse_parser(){
    return null;
}

function uebersicht_section_vereinskasse(){

    $Gesamteinnahmen = gesamteinnahmen_jahr(date("Y"));
    $Gesamtausgaben = gesamtausgaben_jahr(date("y"));
    $Differenz = $Gesamteinnahmen - $Gesamtausgaben;

    $HTML = "<h3 class='center-align'>Jahresstatistik</h3>";
    $Table = table_row_builder(table_header_builder('Einnahmen').table_header_builder('Ausgaben').table_header_builder('Überschuss'));
    $Table .= table_row_builder(table_data_builder($Gesamteinnahmen."&euro;").table_data_builder($Gesamtausgaben."&euro;").table_data_builder($Differenz."&euro;"));
    $HTML .= table_builder($Table);

    return section_builder($HTML);
}
function kontos_section_vereinskasse(){
    return null;
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