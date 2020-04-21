<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
#session_manager('ist_admin');

$Header = "Adminwiki - " . lade_db_einstellung('site_name');
$HTML = container_builder("<iframe src='".lade_xml_einstellung('url-admin-wiki')."'></iframe>");

# Output site
echo site_header($Header);
echo site_body($HTML);