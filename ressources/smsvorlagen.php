<?php

function lade_smsvorlage($name){

    $xml = simplexml_load_file("./ressourcen/smsvorlagen.xml");
    $Text = $xml->$name->text;

    $StrText = (string) $Text;
    //$StrText = htmlentities($StrText);

    $Antwort = $StrText;

    return $Antwort;
}
?>