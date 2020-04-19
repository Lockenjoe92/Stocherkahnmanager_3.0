<?php

# Include all ressources
include_once "./ressourcen.php";

#Generate Content
$header = "Impressum - " . lade_xml_einstellung('site_name');

$html = "<h2>Impressum</h2>";

$html .= lade_xml_einstellung('impressum-content');

#Container for all above
$html = container_builder($html, '', '');

# Output site
echo site_header($header);
echo site_body($html);
