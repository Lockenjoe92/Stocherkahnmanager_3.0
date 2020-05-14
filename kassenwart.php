<?php
include_once "./ressources/ressourcen.php";
session_manager('ist_kasse');
$Header = "Vereinskasse - " . lade_db_einstellung('site_name');
$UserID = lade_user_id();
$HTML = section_builder("<h1 class='center-align'>Vereinskasse</h1>");

#ParserStuff
$Parser = vereinskasse_parser($UserID);
if(isset($Parser['meldung'])){
    $HTML .= "<h5 class='center-align'>".$Parser['meldung']."</h5>";
}

$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

function vereinskasse_parser(){
    return null;
}