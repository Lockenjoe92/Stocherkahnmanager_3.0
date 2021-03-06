<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 06.06.19
 * Time: 10:06
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$Item = $_GET['item'];
if(intval($Item)>0){

    #Parse Input
    $Parser = parse_delete_website_item_page($Item);

    #Generate content
    # Page Title
    $Header = "Webseite Inhaltselement Löschen - " . lade_db_einstellung('site_name');
    $PageTitle = '<h1>Webseiteinhalt löschen</h1>';
    $HTML = section_builder($PageTitle);
    $HTML .= section_builder('<h5>Lokalisation</h5>');
    $HTML .= section_builder(website_item_info_table_generator($Item));

    if($Parser == NULL){
        $HTML .= prompt_karte_generieren('delete_website_item', 'Löschen', 'admin_edit_startpage.php', 'Abbrechen', 'Willst du das Element wirklich löschen?', false, '');
    } elseif ($Parser == TRUE){
        $HTML .= zurueck_karte_generieren(true, 'Element erfolgreich gelöscht', 'admin_edit_startpage.php');
    } elseif ($Parser == FALSE){
        $HTML .= zurueck_karte_generieren(false, 'Fehler beim Löschen!', 'admin_edit_startpage.php');
    }

    # Output site
    $HTML = container_builder($HTML, 'websiteinhalt_loeschen_container');
    echo site_header($Header);
    echo site_body($HTML);

} else {
    header("Location: ./admin_edit_startpage.php");
    die();
}

function parse_delete_website_item_page($Item){

    if(isset($_POST['delete_website_item'])){
        return delete_website_item_parser($Item);
    } else {
        return null;
    }
}