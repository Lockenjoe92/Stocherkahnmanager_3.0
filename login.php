<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.10.18
 * Time: 18:04
 */

# Include all ressources
include_once "./ressourcen.php";

#Generate Content
$Header = "Login - " . lade_xml_einstellung('site_name');
$SessionMessage = load_session_message();
$Parser = login_parser();
$HTML = login_formular($Parser, $SessionMessage);

#container for all above
$HTML = container_builder($HTML, '', '');

# Output site
echo site_header($Header);
echo site_body($HTML);
