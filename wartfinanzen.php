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

    $link = connect_db();
    $Wartkonto = lade_konto_user($UserID);
    $Grenze = date('Y-m-d G:i:s', strtotime('- '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' weeks'));
    $HTML = '';

    $AnfrageEinnahmen = "SELECT * FROM finanz_einnahmen WHERE konto_id = ".$Wartkonto['id']." AND timestamp >= '".$Grenze."' ORDER BY timestamp DESC";
    $AbfrageEinnahmen = mysqli_query($link, $AnfrageEinnahmen);
    $AnzahlEinnahmen = mysqli_num_rows($AbfrageEinnahmen);
    $EinnahmenItems = '';
    for($a=1;$a<=$AnzahlEinnahmen;$a++){
        $ErgebnisEinnahmen = mysqli_fetch_assoc($AbfrageEinnahmen);
        $Forderung = lade_forderung($ErgebnisEinnahmen['forderung_id']);
        $ForderungUser = lade_user_meta($Forderung['von_user']);
        if(intval($Forderung['referenz_res'])>0){
            $ForString = 'Res. #'.$Forderung['referenz_res'];
        }else{
            $ForString = $Forderung['referenz'];
        }
        $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisEinnahmen['timestamp'])).' - '.$ErgebnisEinnahmen['betrag'].'&euro; von '.$ForderungUser['vorname'].' '.$ForderungUser['nachname'].' für '.$ForString.'<br>';
        if(intval($Forderung['referenz_res'])>0){
            //Buttons link to delete übergabe so we can take care of stuff there
            $Anfrage = "SELECT id FROM uebergaben WHERE res = ".$Forderung['referenz_res']." AND durchfuehrung != '0000-00-00 00:00:00'";
            $Abfrage = mysqli_query($link, $Anfrage);
            $Ergebnis = mysqli_fetch_assoc($Abfrage);
            $Content = button_link_creator('löschen', "undo_uebergabe.php?uebergabe=".$Ergebnis['id']."", 'delete_forever', '');
        } else {
            //Just delete it
            $Content = form_button_builder('delete_einnahme_'.$ErgebnisEinnahmen['id'].'', 'löschen', 'action', 'delete_forever');

        }
        $EinnahmenItems .= collapsible_item_builder($Title, $Content, '');
    }

    if($AnzahlEinnahmen>0){
        $HTML .= '<h3 class="center-align">Einnahmen der letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen</h3>';
        $HTML .= collapsible_builder($EinnahmenItems);
    }

    $AnfrageAusgaben = "SELECT * FROM finanz_ausgaben WHERE konto_id = ".$Wartkonto['id']." AND timestamp >= '".$Grenze."' ORDER BY timestamp DESC";
    $AbfrageAusgaben = mysqli_query($link, $AnfrageAusgaben);
    $AnzahlAusgaben = mysqli_num_rows($AbfrageAusgaben);
    $AusgabenItems = '';
    for($b=1;$b<=$AnzahlAusgaben;$b++){
        $ErgebnisAusgaben = mysqli_fetch_assoc($AbfrageAusgaben);
        $Ausgleich = lade_ausgleich($ErgebnisAusgaben['ausgleich_id']);
        $ForderungUser = lade_user_meta($Ausgleich['von_user']);
        if(intval($Ausgleich['referenz_res'])>0){
            $ForString = 'Res. #'.$Ausgleich['referenz_res'];
        }else{
            $ForString = $Ausgleich['referenz'];
        }
        $Title = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($ErgebnisAusgaben['timestamp'])).' - '.$ErgebnisAusgaben['betrag'].'&euro; von '.$ForderungUser['vorname'].' '.$ForderungUser['nachname'].' für '.$ForString.'<br>';
        //Just delete it
        $Content = form_button_builder('delete_ausgabe_'.$ErgebnisAusgaben['id'].'', 'löschen', 'action', 'delete_forever');
        $AusgabenItems .= collapsible_item_builder($Title, $Content, '');
    }

    if($AnzahlAusgaben>0){
        $HTML .= '<h3 class="center-align">Ausgaben der letzten '.lade_xml_einstellung('wochen-vergangenheit-durchgefuehrte-transaktionen').' Wochen</h3>';
        $HTML .= collapsible_builder($AusgabenItems);
    }

    return $HTML;
}

function section_forderung_an_user_anlegen($UserID){
    return null;
}