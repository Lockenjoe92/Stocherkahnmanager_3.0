<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 18:13
 */
# Include all ressources
include_once "./ressources/ressourcen.php";

$RequireWartTabs = explode(',', lade_xml_einstellung('index_tabs_that_require_wart_rights'));
foreach ($RequireWartTabs as $requireWartTab) {
    if($_GET['tab']==$requireWartTab){
        session_manager('ist_wart');
    }
}

# Generate Content
$HTML = startseite_inhalt_home();
$Header = "Home - " . lade_xml_einstellung('site_name');

# Output site
echo site_header($Header);
echo site_body($HTML);

?>
