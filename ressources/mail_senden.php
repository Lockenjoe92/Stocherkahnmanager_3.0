<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Created by PhpStorm.
 * User: marc
 * Date: 01.01.17
 * Time: 18:28
 */

function mail_senden($NameVorlage, $MailAdresse, $Bausteine)
{
    //Vorlage laden
    $Vorlage = lade_mailvorlage($NameVorlage);

    //Vorlagentext generieren
    $Mailtext = str_replace(array_keys($Bausteine), array_values($Bausteine), $Vorlage['text']);

    //Instanz von PHPMailer bilden
    $mail = new PHPMailer\PHPMailer\PHPMailer();

    //Absenderadresse der E-Mail setzen
    $mail->addReplyTo(lade_xml_einstellung('reply_mail'), lade_xml_einstellung('absender_name'));
    $mail->From = lade_xml_einstellung('absender_mail');
    $mail->Sender = lade_xml_einstellung('absender_mail');

    //Name des Abenders setzen
    $mail->FromName = lade_xml_einstellung('site_name');

    //Empfängeradresse setzen
    $mail->addAddress($MailAdresse);

    //Betreff der E-Mail setzen
    $mail->Subject = $Vorlage['betreff'];

    //Text der E-Mail setzen
    $mail->Body = html_entity_decode($Mailtext);

    //HTML-Format setzen
    $mail->IsHTML(true);

    //E-Mail senden
    if($mail->Send())
    {
        return true;
    }
}


    function mail_schon_gesendet($User, $Typ){

        $link = connect_db();

        $Anfrage = "SELECT id FROM mail_protokoll WHERE empfaenger = '$User' AND typ = '$Typ' AND erfolg = '1'";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl > 0){
            return true;
        } else if ($Anzahl == 0){
            return false;
        }
    }

    function timestamp_letzte_mail_gesendet($User, $Typ){

        $link = connect_db();

        $Anfrage = "SELECT id, timestamp FROM mail_protokoll WHERE empfaenger = '$User' AND typ = '$Typ' AND erfolg = '1' ORDER BY timestamp DESC";
        $Abfrage = mysqli_query($link, $Anfrage);
        $Anzahl = mysqli_num_rows($Abfrage);

        if ($Anzahl > 0){
            $Mail = mysqli_fetch_assoc($Abfrage);
            return $Mail['timestamp'];
        } else if ($Anzahl == 0){
            return false;
        }

    }

?>