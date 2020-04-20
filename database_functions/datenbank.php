<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 14.06.18
 * Time: 23:31
 */

function connect_db(){

    $host = lade_xml_einstellung('db_host', 'db');
    $user = lade_xml_einstellung('db_user', 'db');
    $pswd = lade_xml_einstellung('db_pswd', 'db');
    $name = lade_xml_einstellung('db_dbname', 'db');

    $sql = new mysqli($host,$user,$pswd,$name);
    #$sql->set_charset('utf8');

    /* check for an error code */
    if ( $sql->connect_errno ) {
      /* oh no! there was an error code, what's the problem?! */
      die($sql->connect_errno);
    }

    return $sql;
}
