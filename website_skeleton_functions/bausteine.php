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

function col_s3_builder($content, $offset=NULL) {
    $offset_class = "";
    if (!is_null($offset)) {
        $offset_class = " offset-s" . $offset;
    }
    return "<div class='col s3" . $offset_class . "'>" . $content . "</div>";
}

function col_s4_builder($content, $offset=NULL) {
    $offset_class = "";
    if (!is_null($offset)) {
        $offset_class = " offset-s" . $offset;
    }
    return "<div class='col s4" . $offset_class . "'>" . $content . "</div>";
}

function col_s6_builder($content, $offset=NULL) {
    $offset_class = "";
    if (!is_null($offset)) {
        $offset_class = " offset-s" . $offset;
    }
    return "<div class='col s6" . $offset_class . "'>" . $content ."</div>";
}

function col_s8_builder($content, $offset=NULL) {
    $offset_class = "";
    if (!is_null($offset)) {
        $offset_class = " offset-s" . $offset;
    }
    return "<div class='col s8" . $offset_class . "'>" . $content ."</div>";
}

function col_s12_builder($content, $offset=NULL) {
    $offset_class = "";
    if (!is_null($offset)) {
        $offset_class = " offset-s" . $offset;
    }
    return "<div class='col s12" . $offset_class . "'>" . $content ."</div>";
}

function center_builder($ContentHTML) {
    $html = "<center>" . $ContentHTML . "</center>";
    return $html;
}

function box_builder($ContentHTML) {
    $html = '<div class="row">
                <div class="col s12">
                    <div class="card-panel ">'
                        . $ContentHTML .
                    '</div>
                </div>
            </div>';
    return $html;
}

function center_box_builder($ContentHTML) {
    $html = '<div class="row">
                <div class="col s12">
                    <div class="card-panel ">
                        <center> '. $ContentHTML . '</center>
                    </div>
                </div>
            </div>';
    return $html;
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

function form_select_builder($results, $display_name, $select_id, $get_par_name, $multiselect=false, $selected_id=NULL, $required=false) {

    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<span>".$display_name. " </span>";
    
    if ($required == true) {
        $required = "required";
    } else {
        $required = "";
    }

    if ($multiselect === true) {
        $HTML .= "<select multiple='multiple' name='".$get_par_name."[]' id='".$select_id."' ". $required .">";
    } else {
        $HTML .= "<select name='".$get_par_name."' id='".$select_id."' ". $required .">";
    }

    foreach ($results as $row) {

        if ($row["name"] != $_GET[$get_par_name]) {

            if (!is_null($selected_id) 
                & $row["id"] == $selected_id) {
                $HTML .= "<option value='".$row["id"]."' selected>".$row["name"]."</option>";
            } else {
                $HTML .= "<option value='".$row["id"]."'>".$row["name"]."</option>";
            }
        }
    }

    $HTML .= "</select>";
    $HTML .= "</div>";
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

function collapsible_item_builder($Title, $Content, $Icon){

    if($Icon == ''){$IconHTML = '';} else {$IconHTML = "<i class='material-icons'>".$Icon."</i>";}

    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'>".$IconHTML."".$Title."</div>";
    $HTML .= "<div class='collapsible-body'><span>".$Content."</span></div>";
    $HTML .= "</li>";

    return $HTML;

}

function text_table_builder($ContentHTML){

    $HTML = "<table class='responsive-table striped'>";
    $HTML .= $ContentHTML;
    $HTML .= "</table>";

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

    return "<button class='btn waves-effect waves-light ".lade_xml_einstellung('site_buttons_color')." ".$SpecialMode."' type='".$ButtonMode."' name='".$ButtonName."'>".$ButtonMessage."<i class='material-icons left'>".$Icon."</i></button>";
}

function form_checkbox_builder($ItemName, $DisplayName, $Checked=false) {

    if($Checked){
        $CheckedString = "checked='checked'";
    } else {
        $CheckedString = '';
    }

    $html = "<label>";
    $html .= "<input type='checkbox' name='".$ItemName."' value='1' ".$CheckedString."/><span> ". $DisplayName ."</span>";
    $html .= "</label>";
    return $html;
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

function form_string_item($ItemName, $value='', $placeholder='', $Disabled=false){

    if ($Disabled == false) {
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled ';
    }

    if ($value=='' && $placeholder==''){
        return "<input ".$DisabledCommand." id='".$ItemName."' name='".$ItemName."' type='text'>";
    } else {
        $show = ($value == '') ? " placeholder='" . $placeholder . "'" : " value='" . $value . "'";
        return "<input ".$DisabledCommand. $show . " id='".$ItemName."' name='".$ItemName."' type='text'>";
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

function simple_link_creator($Message, $Link){

    return "<a href='".$Link."'>".$Message."</a>";
}


function form_password_item($ItemTitle, $ItemName){

    return "<div class='input-field'><input type='password' class='validate' name='".$ItemName."'></div>";

}

function form_datepicker_item($ItemTitle, $ItemName, $value, $Disabled, $Required=true){

    if ($Disabled){
        $Disabled = 'disabled';
    } else {
        $Disabled = '';
    }

    return "<div class='input-field'><input class='datepicker' type='text' class='validate' name='".$ItemName."' id='".$ItemName."' ".$Disabled." value='" . $value  . "' " .
    ($Required ? "required" : "") ."><label for='".$ItemName."'>".$ItemTitle."</label></div>";

}

function form_select_item($ItemName, $Min=0, $Max=0, $StartValue='', $Einheit='', $Label='', $SpecialMode='', $Disabled=false, $Interval=1, $BitteWaehlenAktiv = false){

    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif ($Disabled == true){
        $DisabledCommand = 'disabled';
    }

    if($StartValue == ''){
        if ($BitteWaehlenAktiv) {
            $HTML .= "<option value='' selected>Bitte w&auml;hlen</option>";
        } else {
            $HTML .= "<option value='' disabled selected>Bitte w&auml;hlen</option>";
        }
    } else {
        $HTML .= "<option value='' disabled>Bitte w&auml;hlen</option>";
    }

    $x=$Min;
    while ($x<=$Max) {

        if ($StartValue == $x) {
            $HTML .= "<option value='" . $x . "' " . $DisabledCommand . " selected>" . $x . " " . $Einheit . "</option>";
        } else {
            $HTML .= "<option value='" . $x . "' " . $DisabledCommand . ">" . $x . " " . $Einheit . "</option>";
        }

        $x = $x + $Interval;
    }

    $HTML .= "</select>";

    if ($Label!=''){
        $HTML .= "<label>".$Label."</label>";
    }

    $HTML .= "</div>";

    return $HTML;
}

function form_select_array_item($ValuesArray, $ItemName, $StartValue, $SpecialMode='', $Disabled=false, $PleaseSelectText='Bitte w&auml;hlen') {
    $HTML = "<div class='input-field' ".$SpecialMode.">";
    $HTML .= "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";

    $DisabledCommand = ($Disabled == false) ? '' : 'disabled';

    if (($StartValue == '') || (! in_array($StartValue, $ValuesArray))) {
        $HTML .= "<option value='' disabled selected>".$PleaseSelectText."</option>";
    } else {
        $HTML .= "<option value='' disabled>".$PleaseSelectText."</option>";
    }

    foreach ($ValuesArray as $id => $value) {
        if ($StartValue == $id) {
            $HTML .= "<option value='" . $id . "' " . $DisabledCommand . " selected>" . $value . "</option>";
        } else {
            $HTML .= "<option value='" . $id . "' " . $DisabledCommand . ">" . $value . "</option>";
        }
    }

    $HTML .= "</select></div>";

    return  $HTML;
}

function form_html_area_item($ItemName, $Placeholdertext='', $Disabled=false){

    if ($Disabled == false){
        $DisabledCommand = '';
    } elseif($Disabled == true) {
        $DisabledCommand = 'disabled';
    }

    $HTML = "<div class='input-field col s12'>";
    $HTML .= "<textarea id='".$ItemName."' name='".$ItemName."' class='materialize-textarea' placeholder='".$Placeholdertext."' ".$DisabledCommand.">";
    #$HTML .= "<pre><code>";
    $HTML .= $Placeholdertext;
    #$HTML .= "</code></pre>";
    $HTML .= "</textarea>";
    $HTML .= "</div>";

    return $HTML;

}

function table_form_file_upload_builder($ItemTitle, $ItemName){

    return "<tr><th>".$ItemTitle."</th><td><input type='file' name='".$ItemName."' id='".$ItemName."'></td></tr>";

}

function table_form_file_upload_directory_chooser_builder($ItemTitle, $ItemName){

    $Select = "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";
    $Select .= "<option value='media/documents/'>/media/documents/</option>";
    $Select .= "<option value='media/pictures/'>/media/pictures/</option>";
    $Select .= "</select>";

    return "<tr><th>".$ItemTitle."</th><td>".$Select."</td></tr>";

}

function table_form_swich_item($ItemTitle, $ItemName, $OptionLeft='off', $OptionRight='on', $BooleanText='false', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_switch_item($ItemName, $OptionLeft, $OptionRight, $BooleanText, $Disabled)."</td></tr>";

}

function table_form_password_item($ItemTitle, $ItemName){

    return "<tr><th>".$ItemTitle."</th><td>".form_password_item($ItemName, $ItemName)."</td></tr>";

}

function table_form_string_item($ItemTitle, $ItemName, $value='', $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_string_item($ItemName, $value, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_range_item($ItemTitle, $ItemName, $Min, $Max, $StartValue, $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_range_item($ItemName, $Min, $Max, $StartValue, $Disabled)."</td></tr>";

}

function table_form_select_item($ItemTitle, $ItemName, $Min, $Max, $StartValue, $Einheit, $Label, $SpecialMode, $Disabled=false, $Interval=1, $BitteWaehlenAktiv=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_select_item($ItemName, $Min, $Max, $StartValue, $Einheit, $Label, $SpecialMode, $Disabled, $Interval, $BitteWaehlenAktiv)."</td></tr>";

}

function table_form_select_location_is_extern_item($ItemTitle, $ItemName, $StartValue, $Disabled=false){

    $HTML =  "<tr><th>".$ItemTitle."</th>";
    $HTML .= "<td><div class='input-field'>";
    $HTML .= "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";

    $DisabledCommand = ($Disabled == false) ? '' : 'disabled';

    if ($StartValue === 'false') {
        $HTML .= "<option value='false' ".$DisabledCommand." selected>Einsatzstelle ist lokal</option>";
    } else {
        $HTML .= "<option value='false' ".$DisabledCommand.">Einsatzstelle ist lokal</option>";
    }

    if ($StartValue === 'true') {
        $HTML .= "<option value='true' ".$DisabledCommand." selected>Einsatzstelle ist extern</option>";
    } else {
        $HTML .= "<option value='true' ".$DisabledCommand.">Einsatzstelle ist extern</option>";
    }

    $HTML .= "</select></div>";
    $HTML .= "</tr>";

    return $HTML;
}

function table_form_select_dse_externe_item($ItemTitle, $ItemName, $StartValue, $Disabled=false){

    $HTML =  "<tr><th>".$ItemTitle."</th>";
    $HTML .= "<td><div class='input-field'>";
    $HTML .= "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";

    $DisabledCommand = ($Disabled == false) ? '' : 'disabled';

    if ($StartValue == 'true') {
        $HTML .= "<option value='true' ".$DisabledCommand." selected>Datenweitergabe und Einsatz an externen Stellen zulassen</option>";
    } else {
        $HTML .= "<option value='true' ".$DisabledCommand.">Datenweitergabe und Einsatz an externen Stellen zulassen</option>";
    }

    if ($StartValue == 'false') {
        $HTML .= "<option value='false' ".$DisabledCommand." selected>Datenweitergabe und Einsatz an externen Stellen NICHT zulassen</option>";
    } else {
        $HTML .= "<option value='false' ".$DisabledCommand.">Datenweitergabe und Einsatz an externen Stellen NICHT zulassen</option>";
    }

    $HTML .= "</select></div>";
    $HTML .= "</tr>";

    return $HTML;
}

function table_form_html_area_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false){

    return "<tr><th>".$ItemTitle."</th><td>".form_html_area_item($ItemName, $Placeholdertext, $Disabled)."</td></tr>";

}

function table_form_datepicker_item($ItemTitle, $ItemName, $Placeholdertext='', $Disabled=false, $Required=true){

    return "<tr><th>".$ItemTitle."</th><td>".form_datepicker_item($ItemTitle, $ItemName, $Placeholdertext, $Disabled, $Required)."</td></tr>";

}

function table_form_mediapicker_dropdown($ItemTitle, $ItemName, $StartValue, $Directory, $Label, $SpecialMode){

    $TableRowContents = table_header_builder($ItemTitle);
    $TableRowContents .= table_data_builder(form_mediapicker_dropdown($ItemName, $StartValue, $Directory, $Label, $SpecialMode));
    $TableRow = table_row_builder($TableRowContents);

    return $TableRow;
}

function button_link_creator($ButtonMessage, $ButtonLink, $Icon, $SpecialMode){

    return "<a href='".$ButtonLink."' class='waves-effect waves-light btn ".lade_xml_einstellung('site_buttons_color')." ".$SpecialMode."'><i class='material-icons left'>".$Icon."</i>".$ButtonMessage."</a>";

}

function error_button_creator($ButtonMessage, $Icon = null, $SpecialMode = null){

    return "<a href='#' class='waves-effect waves-light btn ".lade_xml_einstellung('site_error_buttons_color')." ".$SpecialMode."'><i class='material-icons left'>".$Icon."</i>".$ButtonMessage."</a>";

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

    $stmt = $link->prepare("SELECT * FROM homepage_bausteine WHERE id = ?");

    $stmt->bind_param("i",$BausteinID);

    $stmt->execute();

    $res = $stmt->get_result();
    $Array = mysqli_fetch_assoc($res);

    return $Array;
}


function flexbox_builder($ContentHTML, $ID='', $SpecialMode=''){
    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="flexbox-container '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="flexbox-container '.$SpecialMode.'">';
    }
    $HTML .= $ContentHTML;
    $HTML .= '</div>';
    return $HTML;
}

function flexbox_item_builder($ContentHTML, $ID='', $SpecialMode=''){
    if ($ID!=''){
        $HTML = ' <div id="'.$ID.'" class="flexbox-item '.$SpecialMode.'">';
    } else {
        $HTML = ' <div class="flexbox-item '.$SpecialMode.'">';
    }
    $HTML .= $ContentHTML;
    $HTML .= '</div>';
    return $HTML;
}


function form_select_from_array($ItemName, $input_array, $defaultValue='', $StartValue='', $Disabled=false){

    $HTML = "<div class='input-field'>";
    $HTML .= "<select class='browser-default' id='".$ItemName."' name='".$ItemName."'>";
    
    $DisabledCommand = ($Disabled == false) ? '' : 'disabled';

    if ($StartValue !== '') {
        $defaultValue = $StartValue;
    }

    foreach($input_array as $key=>$value) {

        if (is_int($key)) {
            $key = $value;
        }

        if ($value == $defaultValue) {
            $HTML .= "<option value='$value' ".$DisabledCommand." selected>$key</option>";
        } else {
            $HTML .= "<option value='$value' ".$DisabledCommand." >$key</option>";
        }
    }
    
    $HTML .= "</select></div>";
   
    return $HTML;
    }

function light_text_builder($text) {
    return "<light class='grey-text lighten-2'> " . $text . " </light>";
}
