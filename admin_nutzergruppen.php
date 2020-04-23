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
$HTML .= active_nutzergruppen_form();

$HTML .= "<h3>Weitere Funktionen</h3>";
$HTML .= add_nutzergruppe_form($parser);

# Output site
echo site_header($Header);
echo site_body(container_builder($HTML));