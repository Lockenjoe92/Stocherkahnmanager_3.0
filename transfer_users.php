<?php
include_once "./ressources/ressourcen.php";

$link = connect_db();
$Anfrage = "SELECT * FROM user_old";
$Abfrage = mysqli_query($link, $Anfrage);
$Anzahl = mysqli_num_rows($Abfrage);
echo "<h1>".$Anzahl." User gefunden!</h1>";

for($a=1;$a<=$Anzahl;$a++){
    $Ergebnis = mysqli_fetch_assoc($Abfrage);

    $Anfrage3 = "SELECT id FROM users WHERE mail = '".$Ergebnis['mail']."'";
    $Abfrage3 = mysqli_query($link, $Anfrage3);
    $Anzahl3 = mysqli_num_rows($Abfrage3);

    if($Ergebnis['deaktiviert']=='0'){
        $deaktivate = 'false';
    }elseif ($Ergebnis['deaktiviert']=='1'){
        $deaktivate = 'true';
    }

    if($Anzahl3==0){
        $Anfrage2 = "SELECT * FROM user_rollen WHERE user = ".$Ergebnis['id']." AND storno_user = 0";
        $Abfrage2 = mysqli_query($link, $Anfrage2);
        $Anzahl2 = mysqli_num_rows($Abfrage2);
        $RollenArray = array('strasse'=>'','hausnummer'=>'','plz'=>'','stadt'=>'', 'ist_gesperrt'=>$deaktivate);
        $NutzergruppeCount = 0;
        for($b=1;$b<=$Anzahl2;$b++){
            $Ergebnis2 = mysqli_fetch_assoc($Abfrage2);
            if($Ergebnis2['recht']=='admin'){
                $RollenArray['ist_admin']='true';
            }
            if($Ergebnis2['recht']=='kassenwart'){
                $RollenArray['ist_kasse']='true';
            }
            if($Ergebnis2['recht']=='wart'){
                wartkonto_anlegen($Ergebnis2['id']);
                $RollenArray['ist_wart']='true';
            }
            if($Ergebnis2['recht']=='student'){
                $RollenArray['ist_nutzergruppe']='Student:in';
                $NutzergruppeCount++;
            }
        }
        if($NutzergruppeCount==0){
            $RollenArray['ist_nutzergruppe']='Nicht-Student:in';
        }

        $Result = add_new_user($Ergebnis['vorname'], $Ergebnis['nachname'], '', '', '', '', $Ergebnis['mail'], $Ergebnis['pswd'], $RollenArray, true);

        if($Result['erfolg']===true){
            $Result = reset_user_pswd($Ergebnis['mail'], 'transfer');
            if($Result){
                echo "Erfolg bei User ".$Ergebnis['id']."<br>";
            } else {
                echo "Fehler bei User ".$Ergebnis['id']."<br>";
            }
        }else{
            echo "Fehler bei User ".$Ergebnis['id'].": ".$Result['meldung']."<br>";
        }
    } else {
        echo "User ".$Ergebnis['id']." existiert bereits!<br>";
    }
}