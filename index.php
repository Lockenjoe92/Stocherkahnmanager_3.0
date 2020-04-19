<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 18:13
 */

# Include all ressources
include_once "./ressourcen.php";

# Generate Content
$HTML = startseite_inhalt_home();

#Section for above
$HTML = section_builder($HTML, $ID = '', $SpecialMode = '');

# Container for HTML
$HTML= container_builder($HTML, $ID= '', $SpecialMode = '');

$Header = lade_xml_einstellung('site_name');

# Output site
echo site_header($Header);
echo site_body($HTML);
