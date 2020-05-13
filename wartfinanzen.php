<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Wartfinanzen - " . lade_db_einstellung('site_name');
$UserID = lade_user_id();
$HTML = section_builder("<h1 class='center-align'>Wartfinanzen</h1>");

#ParserStuff
$Parser = wartfinanzen_parser($UserID);
if(isset($Parser['meldung'])){
    $HTML .= "<h5>".$Parser['meldung']."</h5>";
}

$HTML .= section_wartkasse($UserID);
$HTML .= section_vergangene_transaktionen($UserID);
$HTML .= section_forderung_an_user_anlegen($UserID);

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);



function wartfinanzen_parser($UserID){
    return null;
}

function section_wartkasse($UserID){

    $Konto = lade_konto_user($UserID);
    $HTML = '<h5 class="center-align">Dein aktueller Wartkontostand: '.$Konto['wert_aktuell'].'&euro;</h5>';

    return section_builder($HTML);
}

function section_vergangene_transaktionen($UserID){
    return null;
}

function section_forderung_an_user_anlegen($UserID){
    return null;
}