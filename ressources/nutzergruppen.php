<?php

function add_nutzergruppe_form(){

    $parser = add_nutzergruppe_form_parser();


    $TableHTML = table_form_string_item('Name der Nutzergruppe', 'name_nutzergruppe', $_POST['name_nutzergruppe'], false);
    $TableHTML .= table_form_string_item('Erkl&auml;render Text zur Nutzergruppe', 'erklaerung_nutzergruppe', $_POST['erklaerung_nutzergruppe'], false);
    $TableHTML .= table_form_nutzergruppe_verification_mode_select('Verifizierung der Zugeh&ouml;rigkeit', 'verification_mode', $_POST['verification_mode'], $Disabled=false, $SpecialMode='');
    $TableHTML .= table_form_swich_item('Sichtbar f&uuml;r User', 'user_visibility', 'Ja', 'Nein', 'true', false);
    $TableHTML .= table_form_swich_item('Nutzergruppe f&auml;hrt stets gratis', 'alle_res_gratis', 'Ja', 'Nein', 'true', false);
    $TableHTML .= table_form_select_item('Nutzergruppe hat Freifahrten pro Jahr', 'hat_freifahrten_pro_jahr', 0, 12, $_POST['hat_freifahrten_pro_jahr'], '', '', '');
    $TableHTML .= table_form_swich_item('Nutzergruppe kann last Minute buchen', 'darf_last_minute_res', 'Ja', 'Nein', 'true', false);
    $TableHTML = table_builder($TableHTML);

    $HTML = $TableHTML;
    return $HTML;
}

function add_nutzergruppe_form_parser(){

    return null;

}

function add_nutzergruppe($name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res, $array_kosten_pro_stunde){

    $link = connect_db();
    if (!($stmt = $link->prepare("INSERT INTO nutzergruppen (name,erklaertext,req_verify,visible_for_user,alle_res_gratis,hat_freifahrten_pro_jahr,darf_last_minute_res,delete_user,delete_timestamp) VALUES (?,?,?,?,?,?,?,0,'0000-00-00 00:00:00')"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE name = ? AND delete_user = 0"))) {
            $Antwort = false;
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
            $Antwort = false;
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            $Antwort = false;
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Ergebnis = mysqli_fetch_assoc($res);

            //Kostentabelle in nutzer_meta reinhacken
            foreach ($array_kosten_pro_stunde as $Kosten_Stunde_Paar){
                add_nutzergruppe_meta($Ergebnis['id'], $Kosten_Stunde_Paar['stunde'], $Kosten_Stunde_Paar['kosten']);
            }

            $Antwort = true;
        }
    }

    return $Antwort;
}

function add_nutzergruppe_meta($NutzergruppeID, $Schluessel, $Wert){

    $link = connect_db();
    if (!($stmt = $link->prepare("INSERT INTO nutzergruppe_meta (nutzergruppe, schluessel, wert) VALUES ?,?,?)"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("iss", $NutzergruppeID, $Schluessel, $Wert)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $Antwort = true;
    }

    return $Antwort;
}

function lade_nutzergruppe_infos($ID){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT * FROM nutzergruppen WHERE id = ?"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->bind_param("i", $ID)) {
        $Antwort = false;
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {
        $res = $stmt->get_result();
        $Antwort = mysqli_fetch_assoc($res);
    }

    return $Antwort;
}

function form_nutzergruppe_select($ItemName, $StartValue, $Mode='normaluser', $Disabled=false, $SpecialMode=''){

    $link = connect_db();

    if($Mode=='normaluser'){    //Kein Multiselect möglich

        $HTML = "<div class='input-field' ".$SpecialMode.">";
        $HTML .= "<select id='".$ItemName."' name='".$ItemName."'>";

        if ($Disabled == false){
            $DisabledCommand = '';
        } elseif ($Disabled == true){
            $DisabledCommand = 'disabled';
        }

        if($StartValue == ''){
            $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        } else {
            $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
        }

        //Lade alle Nutzergruppen, die für den User auswählbar sind
        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE multiselect_possible = 'false' AND delete_user = 0 ORDER BY name ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Anzahl = mysqli_num_rows($res);

            for ($x = 1; $x <= $Anzahl; $x++) {

                $Ergebnis = mysqli_fetch_assoc($res);

                if ($StartValue == $Ergebnis['id']) {
                    $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . " selected>" . $Ergebnis['name'] . "</option>";
                } else {
                    $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . ">" . $Ergebnis['name'] . "</option>";
                }
            }

            $HTML .= "</select>";
            $HTML .= "</div>";

        }

    } elseif($Mode=='wart') {    //Multiselect möglich

        $HTML = "<div class='input-field' " . $SpecialMode . ">";
        $HTML .= "<select multiple id='" . $ItemName . "' name='" . $ItemName . "'>";

        if ($Disabled == false) {
            $DisabledCommand = '';
        } elseif ($Disabled == true) {
            $DisabledCommand = 'disabled';
        }

        if ($StartValue == '') {
            $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        } else {
            $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
        }

        //Lade alle Nutzergruppen, die für den User auswählbar sind
        //Lade ID
        if (!($stmt = $link->prepare("SELECT id FROM nutzergruppen WHERE delete_user = 0 ORDER BY name ASC"))) {
            echo "Prepare failed: (" . $link->errno . ") " . $link->error;
        }
        if (!$stmt->bind_param("sssssis", $name, $erklaerung, $verification_rule, $visibility_for_user, $Alle_res_gratis, $Anz_gratis_res, $last_minute_res)) {
            echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
        }
        if (!$stmt->execute()) {
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
        } else {
            $res = $stmt->get_result();
            $Anzahl = mysqli_num_rows($res);

            for ($x = 1; $x <= $Anzahl; $x++) {

                $Ergebnis = mysqli_fetch_assoc($res);
                $StartValues = explode(', ',$StartValue);

                foreach ($StartValues as $SV){
                    if ($SV == $Ergebnis['id']) {
                        $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . " selected>" . $Ergebnis['name'] . "</option>";
                    } else {
                        $HTML .= "<option value='" . $Ergebnis['id'] . "' " . $DisabledCommand . ">" . $Ergebnis['name'] . "</option>";
                    }
                }
            }

            $HTML .= "</select>";
            $HTML .= "</div>";

        }

    }
        return $HTML;
}

function form_nutzergruppe_verification_mode_select($ItemName, $StartValue, $Disabled=false, $SpecialMode=''){

    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<select id='".$ItemName."' name='".$ItemName."'>";

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if($StartValue == ''){
        $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
    } else {
        $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
    }

    $Optionen = array("false"=>"Keine", "once"=>"Einmalig", "yearly"=>"J&auml;hrlich");

    foreach($Optionen as $Option => $Value){
        if ($StartValue == $Option) {
            $HTML .= "<option value='" . $Option . "' " . $DisabledCommand . " selected>" . $Value . "</option>";
        } else {
            $HTML .= "<option value='" . $Option . "' " . $DisabledCommand . ">" . $Value . "</option>";
        }
    }

    $HTML .= "</select>";
    $HTML .= "</div>";

    return $HTML;
}