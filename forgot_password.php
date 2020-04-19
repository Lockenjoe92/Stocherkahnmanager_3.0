<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 12.06.18
 * Time: 18:13
 */

# Include all ressources
include_once "./ressourcen.php";
$Header = lade_xml_einstellung('site_name');

# Forgot PSWD Parser
$Parser = forgot_password_parser();

# Generate Content
$HTML = forgot_password_content_generator($Parser);

#Section for above
$HTML = section_builder($HTML, $ID = '', $SpecialMode = '');

# Container for HTML
$HTML= container_builder($HTML, $ID= '', $SpecialMode = '');

# Output site
echo site_header($Header);
echo site_body($HTML);
