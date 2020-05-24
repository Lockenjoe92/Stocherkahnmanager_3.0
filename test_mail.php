<?php
include_once "./ressources/ressourcen.php";

$ID_hash = generateRandomString(32);
$MailArray['[vorname_empfaenger]'] = 'Marc';
$MailArray['[verify_link]'] = lade_xml_einstellung('site_url')."/login.php?register_code=".$ID_hash."";
var_dump(mail_senden('registrierung_user', 'marc@haefeker.de', $MailArray));
