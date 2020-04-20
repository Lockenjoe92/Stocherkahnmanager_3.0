<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 15.06.18
 * Time: 15:04
 */

include_once "./ressourcen.php";

function lade_xml_einstellung($NameEinstellung, $mode='global'){

    if($mode == 'global'){
        $xml = simplexml_load_file("./ressources/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./ressources/local_db_settings.xml");
    }

    if (false === $xml) {
        // throw new Exception("Cannot load xml source.\n");
        $StrValue = false;

    } else {

        $Value = $xml->$NameEinstellung;
        $StrValue = (string) $Value;
        $StrValue = ($StrValue);

    }

    return $StrValue;

}

function update_xml_einstellung($NameEinstellung, $WertEinstellung, $mode='global'){

    $WertEinstellung = utf8_encode($WertEinstellung);

    if($mode == 'global'){
        $xml = simplexml_load_file("./ressources/settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./ressourcen/settings.xml");
    } elseif ($mode == 'db'){
        $xml = simplexml_load_file("./ressources/local_db_settings.xml");
        $xml->$NameEinstellung = $WertEinstellung;
        $xml->asXML("./ressourcen/local_db_settings.xml");
    }

}

function add_db_einstellung($NameEinstellung, $ValueEinstellung){

    $link = connect_db();

    if (!($stmt = $link->prepare("INSERT INTO settings (name,value) VALUES (?,?)"))) {
        $Antwort['erfolg'] = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("ss", $NameEinstellung, $ValueEinstellung)) {
        $Antwort['erfolg'] = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort['erfolg'] = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Message = 'Loaded Setting '.$NameEinstellung.' from settings.xml';
        add_protocol_entry(0, $Message, 'settings');
    }

}

function lade_db_einstellung($NameEinstellung){

    $link = connect_db();

    #Try to load Setting
    $Anfrage = "SELECT * FROM settings WHERE name = '".$NameEinstellung."'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    if ($Anzahl == 0){

        #Try to load the setting from the xml_file
        $XML = lade_xml_einstellung($NameEinstellung, $mode='global');

        if ($XML == false){
            $value = 'ERROR loading '.$NameEinstellung.'';
        } else {
            $value = $XML;
            add_db_einstellung($NameEinstellung, $value);
        }

    } elseif ($Anzahl == 1) {

        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $value = $Ergebnis['value'];

    }

    return $value;

}

function update_db_setting($Setting, $SettingValue){

    $link = connect_db();
    $CurrentSettingValue = lade_db_einstellung($Setting);

    if ($CurrentSettingValue != $SettingValue){

        if (!($stmt = $link->prepare("UPDATE settings SET value = ? WHERE name = ?"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("ss", strval($SettingValue), $Setting)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $Message = 'Updated Setting '.$Setting.' to '.$SettingValue.'';
            add_protocol_entry(lade_user_id(), $Message, 'settings');
        }

    }

}

function slider_setting_interpreter($SettingValue){

    if ($SettingValue == ''){
        return true;
    } elseif ($SettingValue == 'on'){
        return false;
    } else{
        return true;
    }

}

### Parser Logic
function admin_settings_parser($SettingsArray){

    if (isset($_POST['admin_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];
            $SettingValue = $_POST[$Setting];

            #Remove certain HTML Tags from HTML-Textarea-Input
            $SettingValue = str_replace('<pre>','',$SettingValue);
            $SettingValue = str_replace('<code>','',$SettingValue);
            $SettingValue = str_replace('</code>','',$SettingValue);
            $SettingValue = str_replace('</pre>','',$SettingValue);

            update_db_setting($Setting, $SettingValue);

        }

        #return toast('Einstellungen erfolgreich gespeichert.');
    }

}

function user_settings_parser($SettingsArray){

    if (isset($_POST['user_settings_action'])){

        for($x=0;$x<sizeof($SettingsArray);$x++){

            $Setting = $SettingsArray[$x];
            $SettingValue = $_POST[$Setting];

            update_user_meta(lade_user_id(), $Setting, $SettingValue);

        }

        #return toast('Einstellungen erfolgreich gespeichert.');
    }

}

?>