<?php

function forgot_password_parser(){

    if(isset($_POST['action_new_pass']) || isset($_GET['action_new_pass'])){
        $UserEmail = isset($_POST['mail']) ? $_POST['mail'] : $_GET['mail'];
        $link = connect_db();
        if (!($stmt = $link->prepare("SELECT id FROM users WHERE mail = ? AND hide = ''"))) {
            return false;
        }
        if (!$stmt->bind_param("s", $UserEmail)) {
            return false;
        }
        if (!$stmt->execute()) {
            return false;
        } else {
            $res = $stmt->get_result();
            $num = mysqli_num_rows($res);

            if ($num == 1){
                $array = mysqli_fetch_assoc($res);
                $RandomPassword = generateRandomString(14);
                if(update_user_password($array['id'], $RandomPassword, true)){
                    $UserMeta = lade_user_meta($array['id']);
                    if(mail_senden('password_reset_user', $UserEmail, array('[vorname]' => $UserMeta['vorname'], '[passwort]' => $RandomPassword))){
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }

    } else {
        return null;
    }
}

function forgot_password_content_generator($Parser){

    $HTML = "<h1>Passwort zur&uuml;cksetzen</h1>";

    if(($Parser == NULL)){
        $HTML .= passwort_zuruecksetzen_karte();
    } else if (($Parser == FALSE) OR ($Parser == TRUE)){
        $HTML .= passwort_zuruecksetzen_karte_erfolg();
    }

    return $HTML;
}

function passwort_zuruecksetzen_karte(){

    $HTML = section_builder("<p class='caption'>Wenn du dein Passwort vergessen haben solltest, ist das kein Problem!:) Gib einfach deine E-Mail-Adresse ein und wir schicken dir direkt ein neues Passwort zu.</p>");

    $HTMLtableRows = table_form_string_item('E-Mail', 'mail', '', 'Gib bitte deine E-Mail-Adresse ein.');
    $HTMLtable = table_builder($HTMLtableRows);
    $HTMLform = section_builder($HTMLtable);
    $HTMLButtons = table_data_builder(button_link_creator('Zur&uuml;ck', './login.php', 'arrow_back', ''));
    $HTMLButtons .= table_data_builder(form_button_builder('action_new_pass', 'Absenden', 'action', 'send'));
    $HTMLbuttontable = table_builder(table_row_builder($HTMLButtons));
    $HTMLform .= section_builder($HTMLbuttontable);
    $HTMLform = form_builder($HTMLform, './forgot_password.php', 'post', 'form_forgot_password', '');
    $HTML .= $HTMLform;

    return $HTML;

}

function passwort_zuruecksetzen_karte_erfolg(){

    $HTML = section_builder("<p class='caption'>Anfrage erfolgreich bearbeitet. Sollte ein Konto mit der angegebenen E-Mail existieren, erh&auml;ltst du umgehend eine E-Mail mit einem neuen tempor&auml;ren Passwort.</p>");
    $HTMLButtons = button_link_creator('Zur&uuml;ck', './login.php', 'arrow_back', '');
    $HTML .= section_builder($HTMLButtons);

    return $HTML;

}
