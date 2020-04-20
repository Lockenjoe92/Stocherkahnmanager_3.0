<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 13:24
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Header = "Hauptansicht - " . lade_db_einstellung('site_name');

$HTML = "Hello logged in user";
$HTML .= "<a href='./admin_settings.php'>Admin Settings</a>";

# Output site
echo site_header($Header);
echo site_body($HTML);

?>
