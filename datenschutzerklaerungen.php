<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 22.11.18
 * Time: 13:39
 */

include_once "./ressources/ressourcen.php";
session_manager();

$ParserAnlegen = ds_anlegen_parser();

echo ds_anlegen_formular($ParserAnlegen);


?>