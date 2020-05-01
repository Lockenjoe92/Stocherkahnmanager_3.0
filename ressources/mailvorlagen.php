<?php

    function lade_mailvorlage($name){

        $xml = simplexml_load_file("./ressources/mailvorlagen.xml");
        $Betreff = $xml->$name->betreff;
        $Text = $xml->$name->text;

        $StrBetreff = (string) $Betreff;
        //$StrBetreff = htmlentities($StrBetreff);
        $StrText = (string) $Text;
        $StrText = htmlentities($StrText);

        $Antwort['betreff'] = $StrBetreff;
        $Antwort['text'] = $StrText;

        return $Antwort;
    }

?>