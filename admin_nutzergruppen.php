<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_admin');
$parser = add_nutzergruppe_form_parser();

$HTML = "<h1>Nutzergruppen verwalten</h1>";

//Section add nutzergruppe
if($_GET['mode']=='delete_nutzergruppe'){
    $Nutzergruppe = lade_nutzergruppe_infos($_GET['nutzergruppe']);
    $ParseDeleteNutzergruppe = delete_nutzergruppe_parser($_GET['nutzergruppe']);
    if($ParseDeleteNutzergruppe==null){
        $HTML .= prompt_karte_generieren('delete_nutzergruppe_'.$_GET['nutzergruppe'].'', 'Löschen', 'admin_nutzergruppen.php', 'Abbrechen', '<h5 class="center-align">Willst du die Nutzergruppe <b>'.$Nutzergruppe['name'].'</b> wirklich löschen?</h5>', false, '');
    } elseif ($ParseDeleteNutzergruppe==true){
        $HTML .= zurueck_karte_generieren(true,'Nutzergruppe erfolgreich gelöscht!', './admin_nutzergruppen.php');
    } elseif ($ParseDeleteNutzergruppe==false){
        $HTML .= zurueck_karte_generieren(false,'Fehler beim Löschen der Nutzergruppe!', './admin_nutzergruppen.php');
    }
} else {
    $HTML .= active_nutzergruppen_form();
}

$HTML .= "<h3>Weitere Funktionen</h3>";
$HTML .= add_nutzergruppe_form($parser);

# Output site
echo site_header($Header);
echo site_body(container_builder($HTML));
