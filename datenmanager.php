<?php
/**
 * Created by PhpStorm.
 * User: marc
 * Date: 03.06.19
 * Time: 13:59
 */

include_once "./ressources/ressourcen.php";
session_manager();
$Parser = parse_datei_upload_form();
$Header = "Datei hochladen - " . lade_db_einstellung('site_name');

#Generate content
# Page Title
$PageTitle = '<h1>Datei hochladen</h1>';
$HTML = section_builder($PageTitle);
$HTML .= generate_datei_upload_form($Parser);
$Container = container_builder($HTML);

# Output site
echo site_header($Header);
echo site_body($Container);

function generate_datei_upload_form($Parser){

    $TableRows = table_form_file_upload_builder('Datei auswählen', 'file_to_upload');
    $TableRows .= table_form_file_upload_directory_chooser_builder('Ort zum hochladen wählen', 'upload_dir');

    $TableRowContent = table_data_builder(button_link_creator('Zurück', './administration.php', 'arrow_back', ''));
    $TableRowContent .= table_header_builder(form_button_builder('action_upload_file', 'Hochladen', 'action', 'file_upload', ''));
    $TableRows .= table_row_builder($TableRowContent);

    if($Parser!=''){
        $TableRowContent = table_header_builder(error_button_creator($Parser,'announcement', ''));
        $TableRowContent .= table_data_builder('');
        $TableRows .= table_row_builder($TableRowContent);
    }

    $Table = table_builder($TableRows);
    $Form = form_builder($Table, '#', 'post', 'file_uploader', 'multipart/form-data');
    $Section = section_builder($Form);

    return $Section;
}

function parse_datei_upload_form(){

    if(isset($_POST['action_upload_file'])){

        $target_dir = $_POST['upload_dir'];
        $target_file = $target_dir . basename($_FILES["file_to_upload"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        if($_POST['upload_dir'] == 'media/pictures'){
            if(isset($_POST["action_upload_file"])) {
                $check = getimagesize($_FILES["file_to_upload"]["tmp_name"]);
                if($check !== false) {
                    $Antwort = "Datei ist eine valide Bilddatei - " . $check["mime"] . ".";
                    $uploadOk = 1;
                } else {
                    $Antwort = "Datei ist keine Bilddatei.";
                    $uploadOk = 0;
                }
            }
        }

        // Check if file already exists
        if (file_exists($target_file)) {
            $Antwort = "Sorry, die Datei existiert bereits.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["file_to_upload"]["size"] > lade_db_einstellung('max_size_file_upload')) {
            $Antwort = "Sorry, dei Datei ist zu groß!.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if($_POST['upload_dir'] == 'media/pictures') {
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
                && $imageFileType != "gif") {
                $Antwort = "Sorry, nur JPG, JPEG, PNG & GIF Dateien sind zulässig.";
                $uploadOk = 0;
            }
        }

        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            $Antwort = "Sorry, die Datei wurde nicht hochgeladen.";

            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["file_to_upload"]["tmp_name"], $target_file)) {
                $Antwort = "Die Datei ". basename( $_FILES["file_to_upload"]["name"]). " wurde hochgeladen.";
            } else {
                $Antwort = "Sorry, es gab einen Fehler beim Hochladen.";
            }
        }

        return $Antwort;
    }
}

?>