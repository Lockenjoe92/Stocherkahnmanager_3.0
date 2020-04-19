<?php

# Include all ressources
include_once "./ressourcen.php";

#Generate Content
$header = "Datenschutzerklärung - " . lade_xml_einstellung('site_name');

$html = "<h2>Datenschutzerklärung</h2>";

$html .= lade_xml_einstellung('ds-erklaerung-content');

#Container for all above
$html = container_builder($html, '', '');

# Output site
echo site_header($header);
echo site_body($html);
