<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.11.18
 * Time: 08:26
 */

# Include all ressources
include_once "./ressourcen.php";

#Generate Content
$Header = "Registrieren - " . lade_xml_einstellung('site_name');
$Parser = register_parser();
$HTML = register_formular($Parser);

#Container for all above
$HTML = container_builder($HTML, '', '');

# Output site
echo site_header($Header);
echo site_body($HTML);
