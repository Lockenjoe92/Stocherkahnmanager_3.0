<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 31.05.19
 * Time: 20:58
 */

include_once "./ressourcen.php";

function add_protocol_entry($protocol_type, $message){

    $link = connect_db();

    $stmt = $link->prepare("INSERT INTO log (category,entry) VALUES (?,?)");
    
    $stmt->bind_param("ss", $protocol_type, $message);
    
    $stmt->execute();

}
