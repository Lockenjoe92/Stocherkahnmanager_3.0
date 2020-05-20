<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
$Header = "Datenschutz - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$DSE = lade_ds(aktuelle_ds_id_laden());
$HTML = section_builder($DSE['inhalt']);
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);

?>