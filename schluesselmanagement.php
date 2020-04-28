<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager('ist_wart');
$Header = "Schl&uuml;sselverwaltung - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1 class="center-align hide-on-med-and-down">Schl&uuml;sselverwaltung</h1>';
$PageTitle .= '<h1 class="center-align hide-on-large-only">Schl&uuml;ssel verwalten</h1>';
$HTML = section_builder($PageTitle);

#ParserStuff
$Parser = parser_schluesselmanagement();
if(isset($Parser['meldung'])){
    $HTML .= "<h5>".$Parser['meldung']."</h5>";
}

# Content
$HTML .= spalte_anstehende_rueckgaben();
$HTML .= spalte_verfuegbare_schluessel();
$HTML .= spalte_dir_zugeteilte_schluessel();
$HTML .= spalte_schluessel_verwalten();

# Put it all into a container
$HTML = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($HTML);


function spalte_anstehende_rueckgaben(){

    $link = connect_db();
    zeitformat();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Anstehende R&uuml;ckgaben</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Anstehende R&uuml;ckgaben</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

    if ($AnzahlLadeAlleSchluesselausgaben == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeAlleSchluesselausgaben > 0){

        $Counter = 0;

        for($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

            $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

            //Reservierung vorbei oder storniert?
            $Reservierung = lade_reservierung($Ausgabe['reservierung']);

            if ((strtotime($Reservierung['ende']) < time()) OR ($Reservierung['storno_user'] != "0")){
                //darf er dan Schlüssel weiter behalten?
                $AnfrageWeitereReservierungenMitDiesemSchluessel = "SELECT id, reservierung FROM schluesselausgabe WHERE user = '".$Ausgabe['user']."' AND schluessel = '".$Ausgabe['schluessel']."' AND storno_user = '0' AND rueckgabe = '0000-00-00 00:00:00' AND id <> '".$Ausgabe['id']."'";
                $AbfrageWeitereReservierungenMitDiesemSchluessel = mysqli_query($link, $AnfrageWeitereReservierungenMitDiesemSchluessel);
                $AnzahlWeitereReservierungenMitDiesemSchluessel = mysqli_num_rows($AbfrageWeitereReservierungenMitDiesemSchluessel);

                if ($AnzahlWeitereReservierungenMitDiesemSchluessel > 0){

                    //Er darf den schlüssel noch weiter behalte

                } else if ($AnzahlWeitereReservierungenMitDiesemSchluessel == 0){

                    $Counter++;

                    $Schluessel = lade_schluesseldaten($Ausgabe['schluessel']);

                    $FahrtZuendeSeit = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($Reservierung['ende']));
                    $LetzteUsererinnerungLaden = lade_letze_erinnerung_schluesselrueckgabe($Ausgabe['user']);
                    if($LetzteUsererinnerungLaden == NULL){
                        $LetzeErinnerung = "Nie erfolgt.";
                    } else {
                        $LetzeErinnerung = strftime("%A, %d. %B %G - %H:%M Uhr", strtotime($LetzteUsererinnerungLaden));
                    }

                    //Er soll den schlüssel zurück geben
                    $HTML .= "<li>";
                    $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."</div>";
                    $HTML .= "<div class='collapsible-body'>";
                    $HTML .= "<div class='container'>";
                    $HTML .= "<form method='post'>";
                    $HTML .= "<ul class='collection'>";
                    $HTML .= "<li class='collection-item'>Fahrtende: ".$FahrtZuendeSeit."</li>";
                    $HTML .= "<li class='collection-item'>Letzte Erinnerung: ".$LetzeErinnerung."</li>";
                    $HTML .= "<li class='collection-item'>
                                        <div class='input-field'>
                                        <button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_rueckgabe_festhalten'>R&uuml;ckgabe festhalten</button>
                                        </div>
                                        <div class='input-field'>
                                        <button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_erinnerung_senden'>Erinnerung senden</button>
                                        </div>
                                        </li>";
                    $HTML .= "</ul>";
                    $HTML .= "</form>";
                    $HTML .= "</div>";
                    $HTML .= "</div>";
                    $HTML .= "</li>";
                }
            }
        }

        if ($Counter == 0){
            $HTML .= "<li>";
            $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Keine anstehenden R&uuml;ckgaben!</div>";
            $HTML .= "</li>";
        }
    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_verfuegbare_schluessel(){

    $link = connect_db();
    spalte_verfuegbare_schluessel_parser();

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Verf&uuml;gbare Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    if ($AnzahlLadeVerfuegbareSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit keine Schl&uuml;ssel im R&uuml;ckgabekasten!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>R&uuml;ckgabekasten leer!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeVerfuegbareSchluessel > 0){

        for ($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);

            $HTML .= "<li>";
            $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."</div>";
            $HTML .= "<div class='collapsible-body'>";

            $HTML .= "<div class='section hide-on-med-and-down'>";
            $HTML .= "<form method='POST'>";
            $HTML .= "<div class='container'>";
            $HTML .= "<div class='row'>";

            $HTML .= "<div class=\"input-field\">";
            $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_herausnehmen'><i class=\"material-icons left\">send</i>Schl&uuml;ssel herausnehmen</button>";
            $HTML .= "</div>";

            $HTML .= "</div>";
            $HTML .= "</div>";
            $HTML .= "</form>";
            $HTML .= "</div>";

            $HTML .= "<div class='section hide-on-large-only'>";
            $HTML .= "<form method='POST'>";
            $HTML .= "<div class=\"input-field\">";
            $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_herausnehmen'><i class=\"material-icons left\">send</i>Herausnehmen</button>";
            $HTML .= "</div>";
            $HTML .= "</form>";
            $HTML .= "</div>";

            $HTML .= "</div>";
            $HTML .= "</li>";
        }

    }

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_dir_zugeteilte_schluessel(){

    $link = connect_db();
    $Parser = spalte_dir_zugeteilte_schluessel_parser();
    $VerfuegbareSchluessel = wart_verfuegbare_schluessel(lade_user_id());

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Dir zugeteilte Schl&uuml;ssel</h5>";
    $HTML .= "<h5 class='header center-align hide-on-large-only'>Dir zugeteilte Schl&uuml;ssel</h5>";
    $HTML .= "<div class='section'>";

    if(isset($Parser['meldung'])){
        $HTML .= "<h5>".$Parser['meldung']."</h5>";
    }

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $AnfrageLadeZugeteilteSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeZugeteilteSchluessel = mysqli_query($link, $AnfrageLadeZugeteilteSchluessel);
    $AnzahlLadeZugeteilteSchluessel = mysqli_num_rows($AbfrageLadeZugeteilteSchluessel);

    if ($AnzahlLadeZugeteilteSchluessel == 0){

        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-med-and-down'><i class='large material-icons icon-red'>info</i>Derzeit sind dir keine Schl&uuml;ssel zugeteilt!</div>";
        $HTML .= "</li>";
        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header hide-on-large-only'><i class='large material-icons icon-red'>info</i>Du bist Schl&uuml;ssellos!</div>";
        $HTML .= "</li>";

    } else if ($AnzahlLadeZugeteilteSchluessel > 0){

        for ($a = 1; $a <= $AnzahlLadeZugeteilteSchluessel; $a++){

            $Schluessel = mysqli_fetch_assoc($AbfrageLadeZugeteilteSchluessel);

            $HTML .= "<li>";
            $HTML .= "<div class='collapsible-header'><i class='large material-icons ".$Schluessel['farbe_materialize']."'>vpn_key</i>Schl&uumlssel #".$Schluessel['id']." - ".$Schluessel['farbe']."</div>";
            $HTML .= "<div class='collapsible-body'>";
            if ($VerfuegbareSchluessel > 0){
                $HTML .= "<div class='section hide-on-med-and-down'>";
                $HTML .= "<form method='POST'>";
                $HTML .= "<div class='container'>";
                $HTML .= "<div class='row'>";

                $HTML .= "<div class=\"input-field\">";
                $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_zuruecklegen'><i class=\"material-icons left\">send</i>Schl&uuml;ssel zur&uuml;cklegen</button>";
                $HTML .= "</div>";

                $HTML .= "</div>";
                $HTML .= "</div>";
                $HTML .= "</form>";
                $HTML .= "</div>";

                $HTML .= "<div class='section hide-on-large-only'>";
                $HTML .= "<form method='POST'>";
                $HTML .= "<div class='container'>";
                $HTML .= "<div class='container'>";

                $HTML .= "<div class=\"input-field\">";
                $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_".$Schluessel['id']."_zuruecklegen'><i class=\"material-icons left\">send</i>Zur&uuml;cklegen</button>";
                $HTML .= "</div>";

                $HTML .= "</div>";
                $HTML .= "</div>";
                $HTML .= "</form>";
                $HTML .= "</div>";
            }
            $HTML .= "</div>";
            $HTML .= "</li>";
        }

    }

    if($AnzahlLadeZugeteilteSchluessel > wart_verfuegbare_schluessel(lade_user_id())){

        $Differenz = $AnzahlLadeZugeteilteSchluessel - wart_verfuegbare_schluessel(lade_user_id());
        if ($Differenz == 1){
            $Grammatik = "ist bereits einer ";
        } else if ($Differenz > 1){
            $Grammatik = "sind bereits ".$Differenz." ";
        }


        $HTML .= "<li>";
        $HTML .= "<div class='collapsible-header'><i class='large material-icons'>info</i>Achtung!</div>";
        $HTML .= "<div class='collapsible-body'>";

            if ($AnzahlLadeZugeteilteSchluessel == 1){
                $HTML .= "<p>Der dir zugeteilte Schl&uuml;ssel ist bereits f&uuml;r eine &Uuml;bergabe eingeplant!</p>";
            } else if ($AnzahlLadeZugeteilteSchluessel > 1){
                $HTML .= "<p>Von den ".$AnzahlLadeZugeteilteSchluessel." dir zugeteilten Schl&uuml;sseln, ".$Grammatik."f&uuml;r &Uuml;bergaben eingeplant!</p>";
            }

        $HTML .= "</div>";
        $HTML .= "</li>";

    }


    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}
function spalte_schluessel_verwalten(){

    $HTML = "<div class='section'>";
    $HTML .= "<h5 class='header'>Schl&uuml;ssel verwalten</h5>";
    $HTML .= "<div class='section'>";

    $HTML .= "<ul class='collapsible popout' data-collapsible='accordion'>";

    $HTML .= schluessel_umbuchen_listenelement_generieren();
    $HTML .= schluessel_bearbeiten_listenelement_generieren();
    $HTML .= schluessel_hinzufuegen_listenelement_generieren();

    $HTML .= "</ul>";

    $HTML .= "</div>";
    $HTML .= "</div>";

    return $HTML;
}


function schluessel_umbuchen_listenelement_generieren(){

    //CHeckbox rückgabekasten parsen
    if((isset($_POST['rueckgabekasten'])) OR (isset($_POST['rueckgabekasten_mobil']))){
        $Checked = "checked";
    } else {
        $Checked = "unchecked";
    }

    $HTML = "<li>";
    $HTML .= "<div class='collapsible-header'><i class='large material-icons'>swap_calls</i>Schl&uuml;ssel umbuchen</div>";
    $HTML .= "<div class='collapsible-body'>";

    //Großer Screen
    $HTML .= "<div class='section hide-on-med-and-down'>";
    $HTML .= "<form method='POST'>";
    $HTML .= "<div class='container'>";
    $HTML .= "<div class='row'>";
    $HTML .= "<div class='input-field col s4'>";
    $HTML .= "<i class=\"material-icons prefix\">vpn_key</i>";
    $HTML .= dropdown_aktive_schluessel('id_schluessel_umbuchen');
    $HTML .= "</div>";
    $HTML .= "<div class=\"input-field col s4\">";
    $HTML .= "<i class=\"material-icons prefix\">android</i>";
    $HTML .= dropdown_menu_wart('an_wart_umbuchen', $_POST['an_wart_umbuchen']);
    $HTML .= "<label for=\"an_wart_umbuchen\">An Wart umbuchen</label>";
    $HTML .= "</div>";
    //echo "<div class=\"input-field col s3\">";
    //echo "<i class=\"material-icons prefix\">perm_identity</i>";
    //echo dropdown_menu_user('an_user_umbuchen', $_POST['an_user_umbuchen']);
    //echo "<label for=\"an_user_umbuchen\">An User umbuchen</label>";
    //echo "</div>";
    $HTML .= "<div class=\"input-field col s4\">";
    $HTML .= "<i class=\"material-icons prefix\">play_for_work</i>";
    $HTML .= "<input type='checkbox' id='rueckgabekasten' name='rueckgabekasten' ".$Checked.">";
    $HTML .= "<label for=\"rueckgabekasten\">In den R&uuml;ckgabekasten buchen!</label>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "<div class='divider'></div>";
    $HTML .= "<div class='row'>";
    $HTML .= "<div class=\"input-field col s4\">";
    $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_umbuchen' value=''><i class=\"material-icons left\">swap_calls</i>Umbuchen</button>";
    $HTML .= "</div>";
    $HTML .= "<div class=\"input-field col s4\">";
    $HTML .= "<button class='btn waves-effect waves-light' type='reset' name='action_schluessel_umbuchen_reset' value=''><i class=\"material-icons left\">clear_all</i>Formular leeren</button>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</form>";
    $HTML .= "</div>";

    //kleiner Screen
    $HTML .= "<div class='section hide-on-large-only'>";
    $HTML .= "<form method='POST'>";
    $HTML .= "<div class='container'>";
    $HTML .= "<div class=\"input-field\">";
    $HTML .= "<i class=\"material-icons prefix\">vpn_key</i>";
    $HTML .= dropdown_aktive_schluessel('id_schluessel_umbuchen_mobil');
    $HTML .= "</div>";
    $HTML .= "<div class=\"input-field\">";
    $HTML .= "<i class=\"material-icons prefix\">android</i>";
    $HTML .= dropdown_menu_wart('an_wart_umbuchen_mobil', $_POST['an_wart_umbuchen_mobil']);
    $HTML .= "<label for=\"an_wart_umbuchen_mobil\">An Wart umbuchen</label>";
    $HTML .= "</div>";
    //echo "<div class=\"input-field\">";
    //echo "<i class=\"material-icons prefix\">perm_identity</i>";
    //echo dropdown_menu_user('an_user_umbuchen_mobil', $_POST['an_user_umbuchen_mobil']);
    //echo "<label for=\"an_user_umbuchen_mobil\">An User umbuchen</label>";
    //echo "</div>";
    $HTML .= "<div class=\"input-field\">";
    $HTML .= "<i class=\"material-icons prefix\">play_for_work</i>";
    $HTML .= "<input type='checkbox' id='rueckgabekasten_mobil' name='rueckgabekasten_mobil' ".$Checked.">";
    $HTML .= "<label for=\"rueckgabekasten_mobil\">In den R&uuml;ckgabekasten buchen!</label>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "<div class='divider'></div>";
    $HTML .= "<div class='container'>";
    $HTML .= "<div class=\"input-field\">";
    $HTML .= "<button class='btn waves-effect waves-light' type='submit' name='action_schluessel_umbuchen_mobil' value=''><i class=\"material-icons left\">swap_calls</i>Umbuchen</button>";
    $HTML .= "</div>";
    $HTML .= "<div class=\"input-field\">";
    $HTML .= "<button class='btn waves-effect waves-light red' type='submit' name='action_schluessel_umbuchen_reset_mobil' value=''><i class=\"material-icons left\">delete</i>L&ouml;schen</button>";
    $HTML .= "</div>";
    $HTML .= "</div>";
    $HTML .= "</form>";
    $HTML .= "</div>";

    $HTML .= "</div>";
    $HTML .= "</li>";

    return $HTML;
}
function schluessel_bearbeiten_listenelement_generieren(){

    $FormHTML = table_row_builder(table_header_builder('Schlüssel auswählen').table_data_builder(dropdown_aktive_schluessel('id_schluessel_bearbeiten')));
    $FormHTML .= table_form_select_item('Schlüsselnummer ändern', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel', $_POST['farbe_schluessel'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat', $_POST['farbe_schluessel_mat'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code', $_POST['rfid_code'], false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_bearbeiten', 'Eintragen', 'submit', 'send', '')." ".form_button_builder('action_schluessel_loeschen', 'Löschen', 'action', 'delete', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel bearbeiten', $FormHTML, 'edit');

    return $HTML;
}
function schluessel_hinzufuegen_listenelement_generieren(){

    if(isset($_POST['is_wartschluessel'])){
        $AnAusWart = 'on';
    } else {
        $AnAusWart = 'off';
    }

    $FormHTML = table_form_select_item('Schlüsselnummer', 'schluessel_id', 1, 50, $_POST['schluessel_id'], '', 'Schlüsselnummer', '', false);
    $FormHTML .= table_form_string_item('Farbe', 'farbe_schluessel', $_POST['farbe_schluessel'], false);
    $FormHTML .= table_form_string_item('Farbe in materialize.css', 'farbe_schluessel_mat', $_POST['farbe_schluessel_mat'], false);
    $FormHTML .= table_form_string_item('RFID Code', 'rfid_code', $_POST['rfid_code'], false);
    $FormHTML .= table_form_swich_item('Ist ein Wartschlüssel', 'is_wartschluessel', 'Nein', 'Ja', $AnAusWart, false);
    $FormHTML .= table_row_builder(table_header_builder(form_button_builder('action_schluessel_hinzufuegen', 'Eintragen', 'submit', 'send', '')).table_data_builder(''));
    $FormHTML = table_builder($FormHTML);
    $FormHTML = form_builder($FormHTML, '#', 'post', '', '');

    $HTML = collapsible_item_builder('Schlüssel anlegen', $FormHTML, 'library_add');

    return $HTML;
}


function parser_schluesselmanagement(){

    $Parser = spalte_anstehende_rueckgaben_parser();

    if (isset($_POST['action_schluessel_hinzufuegen'])){
        $Parser = schluessel_hinzufuegen($_POST['schluessel_id'], $_POST['farbe_schluessel'], $_POST['farbe_schluessel_mat'], $_POST['rfid_code']);
    }

    if(isset($_POST['action_schluessel_umbuchen'])){

        if (isset($_POST['rueckgabekasten'])){
            $AngabeRueckgabekasten = "rueckgabekasten";
        } else {
            $AngabeRueckgabekasten = "";
        }

        $Parser = schluessel_umbuchen_listenelement_parser($_POST['id_schluessel_umbuchen'], $_POST['an_user_umbuchen'], $AngabeRueckgabekasten, $_POST['an_wart_umbuchen']);
    }

    if(isset($_POST['rueckgabekasten_mobil'])){

        if (isset($_POST['action_schluessel_umbuchen_mobil'])){
            $AngabeRueckgabekasten = "rueckgabekasten";
        } else {
            $AngabeRueckgabekasten = "";
        }

        $Parser = schluessel_umbuchen_listenelement_parser($_POST['id_schluessel_umbuchen_mobil'], $_POST['an_user_umbuchen_mobil'], $AngabeRueckgabekasten, $_POST['an_wart_umbuchen_mobil']);
    }

    if (isset($_POST['action_schluessel_bearbeiten'])){
        $Parser = schluessel_bearbeiten($_POST['id_schluessel_bearbeiten'], $_POST['schluessel_id'], $_POST['farbe_schluessel_bearbeiten'], $_POST['farbe_schluessel_mat_bearbeiten'], $_POST['rfid_code_bearbeiten']);
    }

    if (isset($_POST['action_schluessel_loeschen'])){
        $Parser = schluessel_loeschen($_POST['id_schluessel_bearbeiten']);
    }

    return $Parser;
}

function spalte_anstehende_rueckgaben_parser(){

    $link = connect_db();

    $AnfrageLadeAlleSchluesselausgaben = "SELECT * FROM schluesselausgabe WHERE storno_user = '0' AND ausgabe <> '0000-00-00 00:00:00' AND rueckgabe = '0000-00-00 00:00:00' ORDER BY schluessel ASC";
    $AbfrageLadeAlleSchluesselausgaben = mysqli_query($link, $AnfrageLadeAlleSchluesselausgaben);
    $AnzahlLadeAlleSchluesselausgaben = mysqli_num_rows($AbfrageLadeAlleSchluesselausgaben);

    for ($a = 1; $a <= $AnzahlLadeAlleSchluesselausgaben; $a++){

        $Ausgabe = mysqli_fetch_assoc($AbfrageLadeAlleSchluesselausgaben);

        $ActionName = "action_schluessel_".$Ausgabe['schluessel']."_rueckgabe_festhalten";
        $ErinnerungName = "action_schluessel_".$Ausgabe['schluessel']."_erinnerung_senden";

        if (isset($_POST[$ActionName])){

            $Ergebnis = schluesselrueckgabe_festhalten($Ausgabe['schluessel']);

            if ($Ergebnis == TRUE){
                $Ergebnis['success'] = true;
                $Ergebnis['meldung'] = 'Schl&uuml;sselr&uuml;ckgabe erfolgreich festgehalten!';
            } else if ($Ergebnis == FALSE){
                $Ergebnis['success'] = false;
                $Ergebnis['meldung'] = 'Fehler bei Schl&uuml;sselr&uuml;ckgabe - Admin kontaktieren!';
            }

        }

        if (isset($_POST[$ErinnerungName])){
            $Ergebnis['success'] = false;
            $Ergebnis['meldung'] = 'Diese Funktion muss noch implementiert werden!';
        }

    }

    return $Ergebnis;
}
function spalte_verfuegbare_schluessel_parser(){

    $link = connect_db();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id, farbe, farbe_materialize FROM schluessel WHERE akt_ort = 'rueckgabekasten' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_herausnehmen";

        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'], 'rueckgabekasten', lade_user_id(), '', lade_user_id());
            $Event = "Schl&uuml;ssel ".$Schluessel['id']." von ".lade_user_id()." aus R&uuml;ckgabekasten genommen";
            add_protocol_entry(lade_user_id(), $Event, 'schluessel');
        }
    }

    return $Antwort;
}
function spalte_dir_zugeteilte_schluessel_parser(){

    $link = connect_db();
    $Antwort = array();

    $AnfrageLadeVerfuegbareSchluessel = "SELECT id FROM schluessel WHERE akt_user = '".lade_user_id()."' AND delete_user = '0' ORDER BY id ASC";
    $AbfrageLadeVerfuegbareSchluessel = mysqli_query($link, $AnfrageLadeVerfuegbareSchluessel);
    $AnzahlLadeVerfuegbareSchluessel = mysqli_num_rows($AbfrageLadeVerfuegbareSchluessel);

    for($a = 1; $a <= $AnzahlLadeVerfuegbareSchluessel; $a++){

        $Schluessel = mysqli_fetch_assoc($AbfrageLadeVerfuegbareSchluessel);
        $PostNameGenerieren = "action_schluessel_".$Schluessel['id']."_zuruecklegen";
        if(isset($_POST[$PostNameGenerieren])){
            $Antwort = schluessel_umbuchen($Schluessel['id'], lade_user_id(), '', 'rueckgabekasten', lade_user_id());
        }
    }

    return $Antwort;
}







?>