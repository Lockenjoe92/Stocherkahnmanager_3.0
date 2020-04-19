<?php
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
  $mail->addReplyTo('studierenden-pool@med.uni-tuebingen.de', 'Studierenden-Pool');
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
