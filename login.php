<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.10.18
 * Time: 18:04
 */

# Include all ressources
include_once "./ressources/ressourcen.php";

#Generate Content
$Header = "Login - " . lade_db_einstellung('site_name');
$SessionMessage = load_session_message();
$Parser = login_parser();
$HTML = login_formular($Parser, $SessionMessage);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>