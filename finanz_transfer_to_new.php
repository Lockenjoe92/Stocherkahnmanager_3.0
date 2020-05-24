<?php
include_once "./ressources/ressourcen.php";

$linkNewDB = connect_db();
$linkOldDB = new mysqli('localhost','d024e117','K5.KU_Z5RXhM','d024e117');


transfer_stuff($linkOldDB,$linkNewDB);


function transfer_stuff($linkOldDB,$linkNewDB){

    //Lade alle alten Wartkonten
    $Anfrage1 = "SELECT * FROM finanz_konten WHERE typ = 'wartkonto' AND verstecker = 0";
    $Abfrage1 = mysqli_query($linkOldDB,$Anfrage1);
    $Anzahl1 = mysqli_num_rows($Abfrage1);
    $OldConfigArray = array();
    for($a=1;$a<=$Anzahl1;$a++){
        $Ergebnis1 = mysqli_fetch_assoc($Abfrage1);
        array_push($OldConfigArray, array('altes_konto_id'=>$Ergebnis1['id'],'user_id'=>$Ergebnis1['name']));
    }

    foreach ($OldConfigArray as $OldConfig) {

        $AktuellerNutzerkonto = lade_konto_user($OldConfig['user_id']);

        echo "ALT:".$OldConfig['altes_konto_id']." --- NEU: ".$AktuellerNutzerkonto['id'].'<br>';
    }

}



function ausgabenstuff($linkOldDB,$linkNewDB){

    //Lade alle alten Wartkonten
    $Anfrage1 = "SELECT * FROM finanz_konten WHERE typ = 'wartkonto' AND verstecker = 0";
    $Abfrage1 = mysqli_query($linkOldDB,$Anfrage1);
    $Anzahl1 = mysqli_num_rows($Abfrage1);
    $OldConfigArray = array();
    for($a=1;$a<=$Anzahl1;$a++){
        $Ergebnis1 = mysqli_fetch_assoc($Abfrage1);
        array_push($OldConfigArray, array('altes_konto_id'=>$Ergebnis1['id'],'user_id'=>$Ergebnis1['name']));
    }

    $WissensArray = array();
    foreach ($OldConfigArray as $OldConfig){

        $AktuellerNutzerkonto = lade_konto_user($OldConfig['user_id']);

        $Anfrage2 = "SELECT id FROM finanz_ausgaben WHERE konto_id = ".$OldConfig['altes_konto_id']."";
        $Abfrage2 = mysqli_query($linkNewDB, $Anfrage2);
        $Anzahl2 = mysqli_num_rows($Abfrage2);

        for($b=1;$b<=$Anzahl2;$b++){
            $Ergebnis2 = mysqli_fetch_assoc($Abfrage2);
            array_push($WissensArray, array('einnahme_id'=>$Ergebnis2['id'], 'neues_zielkonto_id'=>$AktuellerNutzerkonto['id']));
        }
    }

    $Erfolgcount=0;
    foreach ($WissensArray as $Paar){
        $Anfrage3 = "UPDATE finanz_ausgaben SET konto_id =".$Paar['neues_zielkonto_id'].", merge='true' WHERE id = ".$Paar['einnahme_id']."";
        $Abfrage3 = mysqli_query($linkNewDB, $Anfrage3);
        if($Abfrage3){$Erfolgcount++;}
    }

    var_dump(sizeof($WissensArray));
    var_dump($Erfolgcount);
}






function einnahmenstuff($linkOldDB,$linkNewDB){
    //Einnahmen   15->16!
//Lade alle alten Wartkonten
    $Anfrage1 = "SELECT * FROM finanz_konten WHERE typ = 'wartkonto' AND verstecker = 0";
    $Abfrage1 = mysqli_query($linkOldDB,$Anfrage1);
    $Anzahl1 = mysqli_num_rows($Abfrage1);
    $OldConfigArray = array();
    for($a=1;$a<=$Anzahl1;$a++){
        $Ergebnis1 = mysqli_fetch_assoc($Abfrage1);
        array_push($OldConfigArray, array('altes_konto_id'=>$Ergebnis1['id'],'user_id'=>$Ergebnis1['name']));
    }

//Lade jetzt alle Forderungen je Kombi und generiere Wissensarray
    $WissensArray = array();
    foreach ($OldConfigArray as $OldConfig){

        $AktuellerNutzerkonto = lade_konto_user($OldConfig['user_id']);

        $Anfrage2 = "SELECT id FROM finanz_einnahmen WHERE konto_id = ".$OldConfig['altes_konto_id']."";
        $Abfrage2 = mysqli_query($linkNewDB, $Anfrage2);
        $Anzahl2 = mysqli_num_rows($Abfrage2);

        for($b=1;$b<=$Anzahl2;$b++){
            $Ergebnis2 = mysqli_fetch_assoc($Abfrage2);
            array_push($WissensArray, array('einnahme_id'=>$Ergebnis2['id'], 'neues_zielkonto_id'=>$AktuellerNutzerkonto['id']));
        }
    }

//Umbau-Anfrage
    $Erfolgcount=0;
    foreach ($WissensArray as $Paar){
        $Anfrage3 = "UPDATE finanz_einnahmen SET konto_id =".$Paar['neues_zielkonto_id'].", merge='true' WHERE id = ".$Paar['einnahme_id']."";
        $Abfrage3 = mysqli_query($linkNewDB, $Anfrage3);
        if($Abfrage3){$Erfolgcount++;}
    }

    var_dump(sizeof($WissensArray));
    var_dump($Erfolgcount);
}
