<?php
include_once "./ressources/ressourcen.php";

$MIn = $_GET['min'];
$Max = $_GET['max'];

$link = connect_db();
$Anfrage = "SELECT id, mail FROM users WHERE deaktiviert = 0 AND id > '".$MIn."' AND id <= '".$Max."'";
$Abfrage = mysqli_query($link, $Anfrage);
$Anzahl = mysqli_num_rows($Abfrage);
echo "<h1>".$Anzahl." User gefunden!</h1>";

for($a=1;$a<=$Anzahl;$a++){
    $Ergebnis = mysqli_fetch_assoc($Abfrage);
    #$RandomString = generateRandomString(40);
    #$Anfrage3498 = "UPDATE users SET register_secret = '".$RandomString."' WHERE id = '".$Ergebnis['id']."'";
    #var_dump(mysqli_query($link,$Anfrage3498));

    var_dump(reset_user_pswd($Ergebnis['mail'], 'transfer'));

    echo $Ergebnis['id']."<br>";
}