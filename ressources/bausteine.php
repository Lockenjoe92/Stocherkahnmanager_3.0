<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 13.06.18
 * Time: 15:28
 */

function parallax_container($ContentHTML, $ID='', $SpecialMode=''){

    if ($ID!=''){
        $HTML = '<div id="'.$ID.'" class="parallax-container '.$SpecialMode.'">';
    } else {
        $HTML = '<div class="parallax-container '.$SpecialMode.'">';
    }
    $HTML .= $ContentHTML;
    $HTML .= '</div>';

    return $HTML;
}

function parallax_content_builder($ContentHTML, $ID='', $SpecialMode=''){

    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="parallax '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="parallax '.$SpecialMode.'">';
    }

    $HTML .= $ContentHTML;
    $HTML .= '</div>';

    return $HTML;
}

function row_builder($ContentHTML, $ID='', $SpecialMode=''){

    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="row '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="row '.$SpecialMode.'">';
    }

    $HTML .= $ContentHTML;
    $HTML .= '</div>';

    return $HTML;

}

function section_builder($ContentHTML, $ID='', $SpecialMode=''){

    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="section '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="section '.$SpecialMode.'">';
    }

    $HTML .= $ContentHTML;
    $HTML .= '</div>';

    return $HTML;
}

function container_builder($ContentHTML, $ID='', $SpecialMode=''){

    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="container '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="container '.$SpecialMode.'">';
    }

    $HTML .= $ContentHTML;
    $HTML .= '</div>';

    return $HTML;

}

function form_builder($ContentHTML, $ActionPageLink, $FormMode='post', $ID='', $EncMode=''){

    if($EncMode!=''){
        $Enctype = "enctype='".$EncMode."'";
    }

    if ($ID == ''){
        $HTML = "<form action='".$ActionPageLink."' method='".$FormMode."' ".$Enctype.">";
    } else {
        $HTML = "<form action='".$ActionPageLink."' method='".$FormMode."' id='".$ID."' ".$Enctype.">";
    }

    $HTML .= $ContentHTML;
    $HTML .= "</form>";

    return $HTML;

}

function collection_builder($ListElements){

    $HTML = "<ul class='collection'>";
    $HTML .= $ListElements;
    $HTML .= "</ul>";

    return $HTML;

}

function collection_with_header_builder($Header, $ListElements){

    $HTML = "<ul class='collection with-header'>";
    $HTML .= "<li class='collection-header'><h5>".$Header."</h5></li>";
    $HTML .= $ListElements;
    $HTML .= "</ul>";

    return $HTML;

}

function collection_item_builder($ItemContent){

    $HTML = "<li class='collection-item'>".$ItemContent."</li>";
    return $HTML;

}

function collapsible_builder($ListElements){

    $HTML = "<ul class='collapsible'>";
    $HTML .= $ListElements;
    $HTML .= "</ul>";

    return $HTML;

}

function collapsible_item_builder($Title, $Content, $Icon, $IconColor='', $Selected=''){

    if($Icon == ''){$IconHTML = '';} else {$IconHTML = "<i class='material-icons ".$IconColor."'>".$Icon."</i>";}

    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header' ".$Selected.">".$IconHTML."".$Title."</div>";
    $HTML .= "<div class='collapsible-body'><span>".$Content."</span></div>";
    $HTML .= "</li>";

    return $HTML;

}

function table_builder($ContentHTML){

    $HTML = "<table>";
    $HTML .= $ContentHTML;
    $HTML .= "</table>";

    return $HTML;
}

function table_row_builder($ContentHTML){

    return "<tr>".$ContentHTML."</tr>";
}

function table_data_builder($ContentHTML){

    return "<td>".$ContentHTML."</td>";
}

function table_header_builder($ContentHTML){

    return "<th>".$ContentHTML."</th>";
}

function form_button_builder($ButtonName, $ButtonMessage, $ButtonMode, $Icon, $SpecialMode=''){

    return "<button class='btn waves-effect waves-light ".lade_db_einstellung('site_buttons_color')." ".$SpecialMode."' type='".$ButtonMode."' name='".$ButtonName."'>".$ButtonMessage."<i class='material-icons left'>".$Icon."</i></button>";

}

function form_mediapicker_dropdown($ItemName, $StartValue, $Directory, $Label, $SpecialMode){

    $HTML = "<div class='input-field' ".$SpecialMode.">";
   $HTML .= "<select name='".$ItemName."' id='".$ItemName."'>";

   $dirPath = dir($Directory);
   $DataArray = array();

   while (($file = $dirPath->read()) !== false)
   {
       $DataArray[ ] = trim($file);
    }

    $dirPath->close();
    sort($DataArray);
    $c = count($DataArray);

    if($StartValue == ''){
        $HTML .= "<option value='' selected>Bitte wählen...</option>";
    }

    for($i=2; $i<$c; $i++)  //Skip the dots
    {
        $SelectDirectory = $Directory . "/" . $DataArray[$i];

        if($SelectDirectory != $StartValue){
            $HTML .= "<option value='" . $SelectDirectory . "'>" . $DataArray[$i] . "</option>";
        } elseif($SelectDirectory == $StartValue){
            $HTML .= "<option value='" . $SelectDirectory . "' selected>" . $DataArray[$i] . "</option>";
        }
    }

    $HTML .= "</select>";

    if ($Label!=''){
        $HTML .= "<label>".$Label."</label>";
    }

    $HTML .= "</div>";

    return $HTML;
}

function dropdown_schluesselorte($NameElement, $Ort){

    $Ausgabe = "<select name='" .$NameElement. "' size='4' id='".$NameElement."'>";

    if ($Ort == ''){
        $Ausgabe .= "<option value='' selected>Ort zuweisen</option>";
    } else {
        $Ausgabe .= "<option value=''>Ort zuweisen</option>";
    }

    $MoeglicheSchluesselorte = lade_xml_einstellung('moegliche_schluesselorte');
    $MoeglicheSchluesselorte = explode(',', $MoeglicheSchluesselorte);

    foreach ($MoeglicheSchluesselorte as $Schluesselort){
        if ($Schluesselort == $Ort) {
            $Ausgabe .= "<option value='" . $Schluesselort . "' selected>" . $Schluesselort . "</option>";
        } else {
            $Ausgabe .= "<option value='" . $Schluesselort . "'>" . $Schluesselort . "</option>";
        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;
}

function dropdown_menu_wart($NameElement, $PreselectWart){

    $Ausgabe = "<select name='" .$NameElement. "' size='4' id='".$NameElement."'>";

    if ($PreselectWart == ""){
        $Ausgabe .= "<option value='' selected>Wart auswählen</option>";
    }

    $Users = get_sorted_user_array_with_user_meta_fields('nachname');
    $Counter = 0;
    foreach ($Users as $User){

        if ($User['ist_wart'] == true) {
            if ($User['id'] == $PreselectWart) {
                $Ausgabe .= "<option value='" . $User['id'] . "' selected>" . $User['vorname'] . " " . $User['nachname'] . "</option>";
            } else {
                $Ausgabe .= "<option value='" . $User['id'] . "'>" . $User['vorname'] . " " . $User['nachname'] . "</option>";
            }
            $Counter++;
        }

    }

    if ($Counter == 0){
        $Ausgabe .= "<option>Bislang kein User mit Wartrolle angelegt!</option>";
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;


}

function form_switch_item($ItemName, $OptionLeft='off', $OptionRight='on', $BooleanText='off', $Disabled=false){

    $HTML = "<div class='switch'>";
    $HTML .= "<label>";
    $HTML .= $OptionLeft;

    if ($BooleanText == 'off'){
        $PresetMode = '';
    } elseif ($BooleanText == 'on'){
        $PresetMode = 'checked';
    }

    if ($Disabled == true){
        $HTML .= "<input name='".$ItemName."' id='".$ItemName."' disabled type='checkbox' ".$PresetMode.">";
    } elseif($Disabled == false) {
        $HTML .= "<input name='".$ItemName."' id='".$ItemName."' type='checkbox' ".$PresetMode.">";
    }

    $HTML .= "<span class='lever'></span>";

    $HTML .= $OptionRight;
    $HTML .= "</label>";
    $HTML .= "</div>";

    return $HTML;
}

function form_string_item($ItemName, $Placeholdertext='', $Disabled=false){

    if ($Disabled == false) {
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if ($Placeholdertext==''){
        return "<input ".$DisabledCommand." id='".$ItemName."' name='".$ItemName."' type='text' class='validate'>";
    } else {
        return "<input ".$DisabledCommand." value='".$Placeholdertext."' id='".$ItemName."' name='".$ItemName."' type='text' class='validate'>";
    }

}

function form_email_item($ItemName, $Placeholdertext='', $Disabled=false){

    if ($Disabled == false) {
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if ($Placeholdertext==''){
        return "<input ".$DisabledCommand." id='".$ItemName."' name='".$ItemName."' type='email' class='validate'>";
    } else {
        return "<input ".$DisabledCommand." value='".$Placeholdertext."' id='".$ItemName."' name='".$ItemName."' type='email' class='validate'>";
    }

}

function form_password_item($ItemName, $Placeholdertext='', $Disabled=false){

    if ($Disabled == false) {
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if ($Placeholdertext==''){
        return "<input ".$DisabledCommand." id='".$ItemName."' name='".$ItemName."' type='password' class='validate'>";
    } else {
        return "<input ".$DisabledCommand." value='".$Placeholdertext."' id='".$ItemName."' name='".$ItemName."' type='password' class='validate'>";
    }

}

function form_range_item($ItemName, $Min, $Max, $StartValue, $Disabled=false){

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    $HTML = "<p class='range-field'>";
    $HTML .= "<input ".$DisabledCommand." type='range' id='".$ItemName."' value='".$StartValue."' min='".$Min."' max='".$Max."'/>";
    $HTML .= "</p>";

    return $HTML;

}

function form_select_item($ItemName, $Min=0, $Max=0, $StartValue='', $Einheit='', $Label='', $SpecialMode='', $Disabled=false){

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

    for ($x=$Min;$x<=$Max;$x++) {

        if ($StartValue == $x) {
            $HTML .= "<option value='" . $x . "' " . $DisabledCommand . " selected>" . $x . " " . $Einheit . "</option>";
        } else {
            $HTML .= "<option value='" . $x . "' " . $DisabledCommand . ">" . $x . " " . $Einheit . "</option>";
        }
    }

    $HTML .= "</select>";

    if ($Label!=''){
        $HTML .= "<label>".$Label."</label>";
    }

    $HTML .= "</div>";

    return $HTML;
}

function form_datepicker_reservation_item($ItemTitle, $ItemName, $value='', $Disabled=false, $Required=true, $SpecialMode = ''){

    if ($Disabled){
        $Disabled = 'disabled';
    } else {
        $Disabled = '';
    }

    return "<div class='input-field ".$SpecialMode."'><input class='datepicker_new_res' type='text' class='validate' name='".$ItemName."' id='".$ItemName."' ".$Disabled." value='" . $value  . "' " .
        ($Required ? "required" : "") ."><label for='".$ItemName."'>".$ItemTitle."</label></div>";

}

function form_dropdown_menu_user($ItemName, $PresetValue){

    $link = connect_db();

    //Lade ID
    if (!($stmt = $link->prepare("SELECT id FROM users"))) {
        $Antwort = false;
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }
    if (!$stmt->execute()) {
        $Antwort = false;
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    } else {

        $res = $stmt->get_result();
        $num = mysqli_num_rows($res);
        $UsersArray = array();

        for ($a=1;$a<=$num;$a++){
            $User = mysqli_fetch_assoc($res);
            $UserMeta = lade_user_meta($User['id']);
            $Zwischenarray = array('nachname'=>$UserMeta['nachname'], 'vorname'=>$UserMeta['vorname'], 'id'=>$User['id']);
            array_push($UsersArray, $Zwischenarray);
        }

        asort($UsersArray);

        $Select = "<select id='".$ItemName."' name='".$ItemName."'>";

        if($PresetValue == ''){
            $Select .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        } else {
            $Select .= "<option value='' disabled>Bitte w&auml;hlen</option>";
        }

        foreach($UsersArray as $User){
            if($User['id'] == $PresetValue){
                $Select .= "<option value='".$User['id']."' selected>".$User['nachname'].", ".$User['vorname']."</option>";
            } else {
                $Select .= "<option value='".$User['id']."'>".$User['nachname'].", ".$User['vorname']."</option>";
            }
        }
        $Select .= "</select>";

        return $Select;
    }
}

function form_html_area_item($ItemName, $Placeholdertext='', $Disabled=false){

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif($Disabled == true) {
        $DisabledCommand = 'disabled';
    }

    $HTML = "<div class='input-field col s12'>";
    $HTML .= "<textarea id='".$ItemName."' name='".$ItemName."' class='materialize-textarea' placeholder='".$Placeholdertext."' ".$DisabledCommand.">";
    $HTML .= "<pre><code>";
    $HTML .= $Placeholdertext;
    $HTML .= "</code></pre>";
    $HTML .= "</textarea>";
    $HTML .= "</div>";

    return $HTML;

}

function table_form_datepicker_reservation_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false, $Required=true, $SpecialMode = ''){

    return "<tr><th>".$ItemTitle."</th><td>".form_datepicker_reservation_item($ItemTitle, $ItemName, $Placeholdertext, $Disabled, $Required, $SpecialMode)."</td></tr>";

}

function table_form_file_upload_builder($ItemTitle, $ItemName){

    return "<tr><th>".$ItemTitle."</th><td><input type='file' name='".$ItemName."' id='".$ItemName."'></td></tr>";

}

function table_form_dropdown_menu_user($ItemTitle, $ItemName, $PresetValue){
    return "<tr><th>".$ItemTitle."</th><td>".form_dropdown_menu_user($ItemName, $PresetValue)."</td></tr>";
}

function table_form_file_upload_directory_chooser_builder($ItemTitle, $ItemName){

    $Select = "<select id='".$ItemName."' name='".$ItemName."'>";
    $Select .= "<option value='media/documents/'>/media/documents/</option>";
    $Select .= "<option value='media/pictures/'>/media/pictures/</option>";
    $Select .= "</select>";

    return "<tr><th>".$ItemTitle."</th><td>".$Select."</td></tr>";

}

function table_form_swich_item($ItemTitle, $ItemName, $OptionLeft='off', $OptionRight='on', $BooleanText='false', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_switch_item($ItemName, $OptionLeft, $OptionRight, $BooleanText, $Disabled)."</td></tr>";

}

function table_form_string_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_string_item($ItemName, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_email_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_email_item($ItemName, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_password_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_password_item($ItemName, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_range_item($ItemTitle, $ItemName, $Min, $Max, $StartValue, $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_range_item($ItemName, $Min, $Max, $StartValue, $Disabled)."</td></tr>";

}

function table_form_select_item($ItemTitle, $ItemName, $Min, $Max, $StartValue, $Einheit, $Label, $SpecialMode, $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_select_item($ItemName, $Min, $Max, $StartValue, $Einheit, $Label, $SpecialMode, $Disabled)."</td></tr>";

}

function table_form_nutzergruppe_select($ItemTitle, $ItemName, $StartValue, $Mode='normaluser', $Disabled=false, $SpecialMode=''){

    return "<tr><th>".$ItemTitle."</th><td>".form_nutzergruppe_select($ItemName, $StartValue, $Mode, $Disabled, $SpecialMode)."</td></tr>";

}

function table_form_nutzergruppe_verification_mode_select($ItemTitle, $ItemName, $StartValue, $Disabled=false, $SpecialMode=''){

    return "<tr><th>".$ItemTitle."</th><td>".form_nutzergruppe_verification_mode_select($ItemName, $StartValue, $Disabled, $SpecialMode)."</td></tr>";

}

function table_form_timepicker_item($ItemTitle, $ItemName, $StartValue, $Disabled=false, $Required=false, $SpecialMode=''){

    return "<tr><th>".$ItemTitle."</th><td>".form_timepicker_item($ItemTitle, $ItemName, $StartValue, $Disabled, $Required, $SpecialMode)."</td></tr>";

}

function form_timepicker_item($ItemTitle, $ItemName, $value='', $Disabled=false, $Required=true, $SpecialMode = ''){

    if ($Disabled){
        $Disabled = 'disabled';
    } else {
        $Disabled = '';
    }

    return "<div class='input-field ".$SpecialMode."'><input class='timepicker' type='text' class='validate' name='".$ItemName."' id='".$ItemName."' ".$Disabled." value='" . $value  . "' " .
        ($Required ? "required" : "") ."><label for='".$ItemName."'>".$ItemTitle."</label></div>";

}

function table_form_html_area_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_html_area_item($ItemName, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_mediapicker_dropdown($ItemTitle, $ItemName, $StartValue, $Directory, $Label, $SpecialMode){

    $TableRowContents = table_header_builder($ItemTitle);
    $TableRowContents .= table_data_builder(form_mediapicker_dropdown($ItemName, $StartValue, $Directory, $Label, $SpecialMode));
    $TableRow = table_row_builder($TableRowContents);

    return $TableRow;
}

function button_link_creator($ButtonMessage, $ButtonLink, $Icon, $SpecialMode){

    return "<a href='".$ButtonLink."' class='waves-effect waves-light btn ".lade_db_einstellung('site_buttons_color')." ".$SpecialMode."'><i class='material-icons left'>".$Icon."</i>".$ButtonMessage."</a>";

}

function error_button_creator($ButtonMessage, $Icon, $SpecialMode){

    if($SpecialMode == ''){
        $SpecialMode = lade_db_einstellung('site_error_buttons_color');
    }

    return "<a href='#' class='waves-effect waves-light btn ".$SpecialMode."'><i class='material-icons left'>".$Icon."</i>".$ButtonMessage."</a>";

}

function divider_builder(){

    $HTML = "<div class='divider'></div>";

    return $HTML;
}

function toast($Message){

    return "<script> Materialize.toast('$Message', 6000) </script>";

}

function lade_baustein($BausteinID){

    $link = connect_db();
    if (!($stmt = $link->prepare("SELECT * FROM homepage_bausteine WHERE id = ?"))) {
        echo "Prepare failed: (" . $link->errno . ") " . $link->error;
    }

    if (!$stmt->bind_param("i",$BausteinID)) {
        echo "Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    if (!$stmt->execute()) {
        echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
    }

    $res = $stmt->get_result();
    $Array = mysqli_fetch_assoc($res);

    return $Array;
}

function table_form_dropdown_terminzeitfenster_generieren($TitelElement, $NameElement, $IDtermin, $ZeitfensterSelected){


    return "<tr><th>".$TitelElement."</th><td>".dropdown_terminzeitfenster_generieren($NameElement, $IDtermin, $ZeitfensterSelected)."</td></tr>";

}

function table_form_dropdown_aktive_res_spontanuebergabe($TitelElement, $NameElement){


    return "<tr><th>".$TitelElement."</th><td>".dropdown_aktive_res_spontanuebergabe($NameElement)."</td></tr>";

}

function dropdown_vorlagen_ortsangaben($NameElement, $IDuser, $OrtSelected){

    $link = connect_db();
    $Anfrage = "SELECT angabe FROM vorlagen_ortsangaben WHERE wart = '$IDuser' AND delete_user = '0' ORDER BY angabe ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    $Ausgabe = "<select name='" .$NameElement. "' size='4' id='".$NameElement."'>";

    if ($Anzahl == 0){
        $Ausgabe .= "<option value='' selected>keine Ortsvorlagen angelegt</option>";
    } else if ($Anzahl > 0) {

        if ($OrtSelected == ""){
            $Ausgabe .= "<option value='' selected>Ortsvorlage w&auml;hlen</option>";
        }

        for ($a = 1; $a <= $Anzahl; $a++){

            $Vorlage = mysqli_fetch_assoc($Abfrage);

            if ($OrtSelected == $Vorlage['angabe']){
                $Ausgabe .= "<option value='" .$Vorlage['angabe']. "' selected>" .$Vorlage['angabe']. "</option>";
            } else {
                $Ausgabe .= "<option value='" .$Vorlage['angabe']. "'>" .$Vorlage['angabe']. "</option>";
            }
        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;

}

function dropdown_aktive_res_spontanuebergabe($NameElement){

    $link = connect_db();
    $ZeitKommando = "+ ".lade_xml_einstellung('tage-spontanuebergabe-reservierungen-zukunft-dropdown')." days";
    $ZeitKommandoZwei = "- ".lade_xml_einstellung('tage-spontanuebergabe-reservierungen-vergangenheit-dropdown')." days";
    $Grenzzeit = date("Y-m-d G:i:s", strtotime($ZeitKommando));
    $GrenzzeitZwei = date("Y-m-d G:i:s", strtotime($ZeitKommandoZwei));

    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    $AnfrageLadeMoeglicheResSpontanuebergabe = "SELECT * FROM reservierungen WHERE beginn > '$GrenzzeitZwei' AND beginn < '$Grenzzeit' AND storno_user = '0' ORDER BY beginn ASC";
    $AbfrageLadeMoeglicheResSpontanuebergabe = mysqli_query($link, $AnfrageLadeMoeglicheResSpontanuebergabe);
    $AnzahlLadeMoeglicheResSpontanuebergabe = mysqli_num_rows($AbfrageLadeMoeglicheResSpontanuebergabe);

    if ($AnzahlLadeMoeglicheResSpontanuebergabe == 0){

        $Ausgabe .= "<option value='' selected>keine offene Reservierung</option>";

    } else if ($AnzahlLadeMoeglicheResSpontanuebergabe > 0){

        $Counter = 0;
        for ($a = 1; $a <= $AnzahlLadeMoeglicheResSpontanuebergabe; $a++){

            $Reservierung = mysqli_fetch_assoc($AbfrageLadeMoeglicheResSpontanuebergabe);
            $Schluesselrolle = lade_user_meta($Reservierung['user']);

            if (($Schluesselrolle['hat_eig_schluessel'] == "true") OR ($Schluesselrolle['wg_hat_schluessel'] == "true")){

                //User braucht keinen Schlüssel

            } else {
                //Diese user brauchen einen Schlüssel
                //Nachsehen ob eine Schlüsselausgabe schon erfolgt ist
                $AnfrageSchluelsselausgabe = "SELECT id FROM schluesselausgabe WHERE reservierung = '".$Reservierung['id']."' AND storno_user = '0'";
                $AbfrageSchluelsselausgabe = mysqli_query($link, $AnfrageSchluelsselausgabe);
                $AnzahlSchluelsselausgabe = mysqli_num_rows($AbfrageSchluelsselausgabe);

                if ($AnzahlSchluelsselausgabe == 0){
                    $Counter++;
                    if ($Counter == 1){
                        $Ausgabe .= "<option value='' selected>Reservierung w&auml;hlen</option>";
                    }

                    $Ausgabe .= "<option value='".$Reservierung['id']."'>Res. #".$Reservierung['id']." - ".$Schluesselrolle['vorname']." ".$Schluesselrolle['nachname']." - ".kosten_reservierung($Reservierung['id'])."&euro;</option>";
                }
            }
        }

        if ($Counter == 0){
            $Ausgabe .= "<option value='' selected>keine offene Reservierung</option>";
        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;

}

function dropdown_terminzeitfenster_generieren($NameElement, $IDtermin, $ZeitfensterSelected){

    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    if ($ZeitfensterSelected == ""){
        $Ausgabe .= "<option value='' selected>Zeitfenster w&auml;hlen</option>";
    }

    $link = connect_db();
    zeitformat();

    $Anfrage = "SELECT * FROM terminangebote WHERE id = '$IDtermin'";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Angebot = mysqli_fetch_assoc($Abfrage);

    //Minuten zwischen Anfang und Ende berechnen
    $Anfang = new DateTime($Angebot['von']);
    $Differenz = $Anfang->diff(new DateTime($Angebot['bis']));

    $Minuten = $Differenz->days * 24 * 60;
    $Minuten += $Differenz->h * 60;
    $Minuten += $Differenz->i;

    $UebergabedauerEinstellung = lade_xml_einstellung('dauer-uebergabe-minuten');
    if(($UebergabedauerEinstellung == "") OR (intval($UebergabedauerEinstellung) < 5)){
        $Einstellung = 10;
    } else {
        $Einstellung = lade_xml_einstellung('dauer-uebergabe-minuten');
    }
    $Zyklen = round($Minuten/intval($Einstellung));

    for($a = 0; $a < $Zyklen; $a++){

        $MinutenschalterAnfang = $a * intval($Einstellung);
        $MinutenschlatenEnde = ($a * intval($Einstellung)) + intval($Einstellung);
        $BefehlTimestampAnfangFenster = "+ ".$MinutenschalterAnfang." minutes";
        $BefehlTimestampEndeFenster = "+ ".$MinutenschlatenEnde." minutes";
        $ZeitBeginn = strtotime($BefehlTimestampAnfangFenster, strtotime($Angebot['von']));
        $ZeitEnde = strtotime($BefehlTimestampEndeFenster, strtotime($Angebot['von']));

        if ($ZeitEnde > time()){

            if ((date("Y-m-d G:i:s", $ZeitBeginn)) === $ZeitfensterSelected){
                $Ausgabe .= "<option value='" .date("Y-m-d G:i:s", $ZeitBeginn). "' selected>" .date("G:i", $ZeitBeginn). " bis ".date("G:i", $ZeitEnde)." Uhr</option>";
            } else {
                $Ausgabe .= "<option value='" .date("Y-m-d G:i:s", $ZeitBeginn). "'>" .date("G:i", $ZeitBeginn). " bis ".date("G:i", $ZeitEnde)." Uhr</option>";
            }

        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;
}

function dropdown_verfuegbare_schluessel_wart($NameElement, $Wart){

    $link = connect_db();
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    $AnfrageLadeSchluesselWart = "SELECT * FROM schluessel WHERE akt_user = '$Wart' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeSchluesselWart = mysqli_query($link, $AnfrageLadeSchluesselWart);
    $AnzahlLadeSchluesselWart = mysqli_num_rows($AbfrageLadeSchluesselWart);

    if ($AnzahlLadeSchluesselWart == 0){

        $Ausgabe .= "<option value='' selected>kein Schl&uuml;ssel mehr</option>";

    } else if ($AnzahlLadeSchluesselWart > 0){
        $Ausgabe .= "<option value='' selected>Schl&uuml;ssel w&auml;hlen</option>";

        for ($a = 1; $a <= $AnzahlLadeSchluesselWart; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeSchluesselWart);
            $Ausgabe .= "<option value='".$Schluessel['id']."'>Schl&uuml;ssel ".$Schluessel['id']." - ".$Schluessel['farbe']."</option>";

        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;
}

function dropdown_aktive_schluessel($NameElement){

    $link = connect_db();
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    $AnfrageLadeSchluessel = "SELECT * FROM schluessel WHERE delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeSchluessel = mysqli_query($link, $AnfrageLadeSchluessel);
    $AnzahlLadeSchluessel = mysqli_num_rows($AbfrageLadeSchluessel);

    if ($AnzahlLadeSchluessel == 0){

        $Ausgabe .= "<option value='' selected>Keine angelegt!</option>";

    } else if ($AnzahlLadeSchluessel > 0){
        $Ausgabe .= "<option value='' selected>Schl&uuml;ssel w&auml;hlen</option>";

        for ($a = 1; $a <= $AnzahlLadeSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeSchluessel);
            $Ausgabe .= "<option value='".$Schluessel['id']."'>Schl&uuml;ssel ".$Schluessel['id']." - ".$Schluessel['farbe']."</option>";

        }
    }

    $Ausgabe .= "</select>";

    return $Ausgabe;
}

function zurueck_karte_generieren($Erfolg, $WeitereInfo, $zurueckURI){

    if ($Erfolg == FALSE){
        $Meldung = "Fehler beim speichern des Vorgangs!";
    } else if ($Erfolg == TRUE){
        $Meldung = "Vorgang erfolgreich durchgef&uuml;hrt!";
    }

    $HTML = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= "<p><b>".$Meldung."</b></p>";
    $HTML .= "<p>".$WeitereInfo."</p>";
    $HTML .= "<p>".button_link_creator('Zurück', $zurueckURI, 'arrow_back', '')."</p>";
    $HTML .= "</div>";

    return $HTML;
}

function dropdown_beginn_reservierung_verschieben($NameElement, $MoegicheStundenFrueherBeginn, $MoegicheStundenSpaeterBeginn){
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Optionen nach vorne
    if ($MoegicheStundenFrueherBeginn == false){
    } else {
        for ($a = 1; $a <= $MoegicheStundenFrueherBeginn; $a++){
            $StundeAktuell = $MoegicheStundenFrueherBeginn - ($a - 1);
            $Ausgabe .= "<option value='- ".$StundeAktuell."'>-".$StundeAktuell." h</option>";
        }
    }

    //Startwert
    $Ausgabe .= "<option value='' selected>Beginn verschieben</option>";

    //Optionen nach hinten
    if ($MoegicheStundenSpaeterBeginn == false){
    } else {
        for ($b = 1; $b <= $MoegicheStundenSpaeterBeginn; $b++){
            $Ausgabe .= "<option value='+ ".$b."'>+".$b." h</option>";
        }
    }

    $Ausgabe .= "</select>";
    return $Ausgabe;
}

function dropdown_ende_reservierung_verschieben($NameElement, $MoegicheStundenFrueherEnde, $MoegicheStundenSpaeterEnde){
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Optionen nach vorne
    if ($MoegicheStundenFrueherEnde == false){
    } else {
        for ($a = 1; $a <= $MoegicheStundenFrueherEnde; $a++){
            $StundeAktuell = $MoegicheStundenFrueherEnde - ($a - 1);
            $Ausgabe .= "<option value='- ".$StundeAktuell."'>-".$StundeAktuell." h</option>";
        }
    }

    //Startwert
    $Ausgabe .= "<option value='' selected>Ende verschieben</option>";

    //Optionen nach hinten
    if ($MoegicheStundenSpaeterEnde == false){
    } else {
        for ($b = 1; $b <= $MoegicheStundenSpaeterEnde; $b++){
            $Ausgabe .= "<option value='+ ".$b."'>+".$b." h</option>";
        }
    }

    $Ausgabe .= "</select>";
    return $Ausgabe;
}

function dropdown_nutzergruppen_waehlen($NameElement, $Selected, $Mode='user'){
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    $Nutzergruppen = lade_alle_nutzgruppen();
    foreach ($Nutzergruppen as $Nutzergruppe){
        $Name = $Nutzergruppe['name'];
        $ID = $Nutzergruppe['id'];
        $UserVisible = $Nutzergruppe['visible_for_user'];

        if($UserVisible == 'true'){
            if(($Mode=='wart_visibles') OR ($Mode=='user')){
                if($ID == $Selected){
                    $Ausgabe .= "<option value='".$ID."' selected>".$Name."</option>";
                } else {
                    $Ausgabe .= "<option value='".$ID."'>".$Name."</option>";
                }
            }
        } elseif ($UserVisible == 'false'){
            if($Mode=='wart_unvisibles'){
                if($ID == $Selected){
                    $Ausgabe .= "<option value='".$ID."' selected>".$Name."</option>";
                } else {
                    $Ausgabe .= "<option value='".$ID."'>".$Name."</option>";
                }
            }
        }
    }

    $Ausgabe .= "</select>";
    return $Ausgabe;
}

function table_form_dropdown_nutzergruppen_waehlen($ItemTitle, $NameElement, $Selected, $Mode){

    $TableRowContents = table_header_builder($ItemTitle);
    $TableRowContents .= table_data_builder(dropdown_nutzergruppen_waehlen($NameElement, $Selected, $Mode));
    $TableRow = table_row_builder($TableRowContents);

    return $TableRow;
}

function prompt_karte_generieren($NameActionButton, $TextJA, $URIzurueck, $TextZurueck, $TextPrompt, $KommentarFeld, $NameKommentarfeld){

    $HTML = "<div class='card-panel " .lade_xml_einstellung('card_panel_hintergrund'). " z-depth-3'>";
    $HTML .= "<p>".$TextPrompt."</p>";

    $HTML .= "<form method='post'>";

    if ($KommentarFeld == TRUE){

        $HTML .= "<div class='input-field'>";
        $HTML .= "<textarea name='".$NameKommentarfeld."' id='".$NameKommentarfeld."' data-length='500'></textarea><label for='".$NameKommentarfeld."'>Platz f&uuml;r Kommentar</label>";
        $HTML .= "</div>";

        $HTML .= divider_builder();
    }

    $HTML .= "<div class='section'>";

    $HTML .= "<div class='input-field'>";
    $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='".$NameActionButton."'>".$TextJA."</button>";
    $HTML .= "</div>";
    $HTML .= "<div class='input-field'>";
    $HTML .= "<a class='btn waves-effect waves-light' href='".$URIzurueck."'>".$TextZurueck."</a>";
    $HTML .= "</div>";

    $HTML .= "</div>";


    $HTML .= "</form>";

    $HTML .= "</div>";

    return $HTML;

}

function dropdown_buchungstoolgruppe_waehlen($NameElement, $Selected){
    $Ausgabe = "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    $Nutzergruppen = array('ist_admin','ist_wart');

    foreach ($Nutzergruppen as $Nutzergruppe){
        if($Nutzergruppe == $Selected){
            $Ausgabe .= "<option value='".$Nutzergruppe."' selected>".$Nutzergruppe."</option>";
        } else {
            $Ausgabe .= "<option value='".$Nutzergruppe."'>".$Nutzergruppe."</option>";
        }
    }

    $Ausgabe .= "</select>";
    return $Ausgabe;
}

function table_form_dropdown_termintyp_waehlen($Titel, $NameElement, $Selected){

    $Ausgabe = "<tr><th>".$Titel."</th><td>";
    $Ausgabe .= "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    $Nutzergruppen = explode(',', lade_xml_einstellung('termin_typen'));
    if(lade_xml_einstellung('grill-global-aktiv')=='true'){
        array_push($Nutzergruppen, array('Grillausgabe','Grillrückgabe'));
    }

    foreach ($Nutzergruppen as $Nutzergruppe){
        if($Nutzergruppe == $Selected){
            $Ausgabe .= "<option value='".$Nutzergruppe."' selected>".$Nutzergruppe."</option>";
        } else {
            $Ausgabe .= "<option value='".$Nutzergruppe."'>".$Nutzergruppe."</option>";
        }
    }

    $Ausgabe .= "</select></td></tr>";
    return $Ausgabe;
}

function table_form_terminangebote_user($Titel, $NameElement, $Selected){

    $Ausgabe = "<tr><th>".$Titel."</th><td>";
    $Ausgabe .= "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    zeitformat();
    $link = connect_db();
    $Anfrage = "SELECT id, von, bis FROM terminangebote WHERE wart = '".lade_user_id()."' AND bis > '".timestamp()."' AND storno_user = 0";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for ($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        $TimeInfos = strftime("%A, %d. %B %G * %H:%M - ", strtotime($Ergebnis['von'])).strftime("%H:%M Uhr", strtotime($Ergebnis['bis']));
        if($a == $Selected){
            $Ausgabe .= "<option value='".$Ergebnis['id']."' selected>".$TimeInfos."</option>";
        } else {
            $Ausgabe .= "<option value='".$Ergebnis['id']."'>".$TimeInfos."</option>";
        }
    }

    $Ausgabe .= "</select></td></tr>";
    return $Ausgabe;

}

function table_form_terminangebote_fuer_termine($Titel, $NameElement, $Selected){

    $Ausgabe = "<tr><th>".$Titel."</th><td>";
    $Ausgabe .= "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    zeitformat();
    $link = connect_db();
    $HrsMaxBeforeTermin = lade_xml_einstellung('max-stunden-vor-abfahrt-buchbar');
    $Grenztimestamp = date("Y-m-d G:i:s", strtotime('+ '.$HrsMaxBeforeTermin.' hours'));
    $Anfrage = "SELECT id, von, bis, terminierung FROM terminangebote WHERE bis > '".$Grenztimestamp."' AND storno_user = 0 ORDER BY von ASC";
    $Abfrage = mysqli_query($link, $Anfrage);
    $Anzahl = mysqli_num_rows($Abfrage);

    for ($a=1;$a<=$Anzahl;$a++){
        $Ergebnis = mysqli_fetch_assoc($Abfrage);
        if($Ergebnis['terminierung']=='0000-00-00 00:00:00'){
            $TimeInfos = strftime("%A, %d. %B %G * %H:%M - ", strtotime($Ergebnis['von'])).strftime("%H:%M Uhr", strtotime($Ergebnis['bis']));
            if($Ergebnis['id'] == $Selected){
                $Ausgabe .= "<option value='".$Ergebnis['id']."' selected>".$TimeInfos."</option>";
            } else {
                $Ausgabe .= "<option value='".$Ergebnis['id']."'>".$TimeInfos."</option>";
            }
        } else {
            if(strtotime($Ergebnis['terminierung'])>strtotime('+ '.$HrsMaxBeforeTermin.' hours')){
                $TimeInfos = strftime("%A, %d. %B %G * %H:%M - ", strtotime($Ergebnis['von'])).strftime("%H:%M Uhr", strtotime($Ergebnis['bis']));
                if($a == $Selected){
                    $Ausgabe .= "<option value='".$Ergebnis['id']."' selected>".$TimeInfos."</option>";
                } else {
                    $Ausgabe .= "<option value='".$Ergebnis['id']."'>".$TimeInfos."</option>";
                }
            }
        }
    }

    $Ausgabe .= "</select></td></tr>";
    return $Ausgabe;

}

function table_form_res_mit_ausgleichen($Titel, $NameElement, $UserID, $Selected){

    $Ausgabe = "<tr><th>".$Titel."</th><td>";
    $Ausgabe .= "<select name='" .$NameElement. "' id='".$NameElement."'>";

    //Startwert
    if($Selected == ''){
        $Ausgabe .= "<option value='' selected>wählen</option>";
    } else {
        $Ausgabe .= "<option value=''>wählen</option>";
    }

    $Reservierungen = lade_alle_reservierungen_eines_users($UserID, true);
    foreach ($Reservierungen as $Reservierung){
        $Ausgleiche = lade_offene_ausgleiche_res($Reservierung['id']);
        if(sizeof($Ausgleiche)>0){
            $Rueckzahlungswert = 0;
            foreach ($Ausgleiche as $Ausgleich){
                #var_dump($Ausgleich);
                $Auszahlung = lade_gezahlte_betraege_ausgleich($Ausgleich['id']);
                if($Auszahlung<$Ausgleich['betrag']){
                    $Rueckzahlungswert += $Ausgleich['betrag']-$Auszahlung;
                }
            }
            if($Rueckzahlungswert>0){
                $TimeInfos = strftime("%A, %d. %B %G * %H:%M - ", strtotime($Reservierung['beginn'])).strftime("%H:%M Uhr", strtotime($Reservierung['ende']));
                if($Reservierung['id'] == $Selected){
                    $Ausgabe .= "<option value='".$Reservierung['id']."' selected>".$TimeInfos." ".$Rueckzahlungswert."&euro;</option>";
                } else {
                    $Ausgabe .= "<option value='".$Reservierung['id']."'>".$TimeInfos." ".$Rueckzahlungswert."&euro;</option>";
                }
            }
        }
    }

    $Ausgabe .= "</select></td></tr>";
    return $Ausgabe;

}

function listenelement_offene_forderung_generieren($Forderung){

    $User = lade_user_meta($Forderung['bucher']);
    $Titel = $Forderung['referenz'].' - '.$Forderung['betrag'].'&euro;';
    $Content = table_row_builder(table_header_builder('Forderung').table_data_builder($Forderung['referenz']));
    $Content .= table_row_builder(table_header_builder('Betrag').table_data_builder($Forderung['betrag'].'&euro;'));
    $Content .= table_row_builder(table_header_builder('Zahlbar bis').table_data_builder(date('d.m.Y', strtotime($Forderung['zahlbar_bis']))));
    $Content .= table_row_builder(table_header_builder('Wie zahlen?').table_data_builder(lade_xml_einstellung('erklaerung-forderung-zahlen-user')));
    $Content .= table_row_builder(table_header_builder('Kontakt bei Rückfragen').table_data_builder('<a href="mailto:'.$User['mail'].'">'.$User['vorname'].' '.$User['nachname'].'</a>'));
    $Icon = 'payment';
    return collapsible_item_builder($Titel, $Content, $Icon);
}
?>