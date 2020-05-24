<?php

include_once "./ressources/ressourcen.php";

$linkNewDB = connect_db();
$linkOldDB = new mysqli('localhost','d024e117','K5.KU_Z5RXhM','d024e117');

$Anfrage1 = "SELECT id, referenz FROM finanz_forderungen";
$Abfrage1 = mysqli_query($linkOldDB, $Anfrage1);
$Anzahl1 = mysqli_num_rows($Abfrage1);

for($a=1;$a<=$Anzahl1;$a++){

    $Ergebnis1 = mysqli_fetch_assoc($Abfrage1);

    if($Ergebnis1['referenz']!=''){
        $Anfrage2 = "UPDATE finanz_forderungen SET referenz='".utf8_encode($Ergebnis1['referenz'])."' WHERE id = ".$Ergebnis1['id']."";
        var_dump(mysqli_query($linkNewDB, $Anfrage2));
    }

}
