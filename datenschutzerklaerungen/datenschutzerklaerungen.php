<?php

function user_needs_dse($UserID){

    $link = connect_db();

    $stmt = $link->prepare("SELECT id, wert FROM user_meta WHERE nutzer = ? AND schluessel = 'dse_accept_date'");

    $stmt->bind_param("i",$UserID);

    $stmt->execute();

    $res = $stmt->get_result();

    $Result = mysqli_fetch_assoc($res);

    if($Result['wert'] == ""){
        return true;
    } else {
        return false;
    }
}

function add_dse_entry($User, $AcceptExternal=false){

    $dbcon = connect_db();
    $Timestamp = timestamp();

    if($AcceptExternal == false){

        if(user_meta_exists($User, 'dse_accept_date')) {
            if (!($stmt = $dbcon->prepare("UPDATE user_meta SET wert = ? WHERE nutzer = ? AND schluessel = 'dse_accept_date'"))) {
                return false;
            }
            if (!$stmt->bind_param("si", $Timestamp, $UserID)) {
                return false;
            }
            if (!$stmt->execute()) {
                return false;
            } else {
                add_protocol_entry('user_meta', 'DSE bei User ' . $User . ' festgehalten');
                return true;
            }
        } else {
            add_user_meta($User, 'dse_accept_date', $Timestamp, $dbcon);
            add_user_meta($User, 'accept_data_transfer', 'false', $dbcon);
            add_protocol_entry('user_meta', 'DSE bei User ' . $User . ' festgehalten');
            return true;
        }

    } elseif ($AcceptExternal == true) {

        if(user_meta_exists($User, 'dse_accept_date')) {
            if (!($stmt = $dbcon->prepare("UPDATE user_meta SET wert = ? WHERE nutzer = ? AND schluessel = 'dse_accept_date'"))) {
                return false;
            }
            if (!$stmt->bind_param("si", $Timestamp, $UserID)) {
                return false;
            }
            if (!$stmt->execute()) {
                return false;
            } else {
                add_protocol_entry('user_meta', 'DSE bei User ' . $User . ' festgehalten');
                add_user_meta($User, 'accept_data_transfer', 'true', $dbcon);
                add_user_meta($User, 'accept_data_transfer_date', $Timestamp, $dbcon);
                return true;
            }
        } else {
            add_user_meta($User, 'dse_accept_date', $Timestamp, $dbcon);
            add_user_meta($User, 'accept_data_transfer', 'true', $dbcon);
            add_user_meta($User, 'accept_data_transfer_date', $Timestamp, $dbcon);
            add_protocol_entry('user_meta', 'DSE bei User ' . $User . ' festgehalten');
            return true;
        }

    }
}