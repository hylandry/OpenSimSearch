<?php
//////////////////////////////////////////////////////////////////////////////
// register.php                                                             //
// (C) 2008, Fly-man-                                                       //
// This file contains the registration of a simulator to the database       //
// and checks if the simulator is new in the database or a reconnected one  //
//                                                                          //
// If the simulator is old, check if the nextcheck date > registration      //
// When the date is older, make a request to the Parser to grab new data    //
//////////////////////////////////////////////////////////////////////////////

//
//		Modified by Hy  to work with PHP7 / MySQLi
//


include("databaseinfo.php");
//establish connection to master db server
global $db;

if (!isset($db)) $db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

//mysqli_select_db ($db,$DB_NAME);

$hostname = $_GET['host'];
$port = $_GET['port'];
$service = $_GET['service'];

if ($hostname != "" && $port != "" && $service == "online")
{
    // Check if there is already a database row for this host
    $checkhost = mysqli_query("SELECT register FROM hostsregister WHERE " .
            "host = '" . mysqli_real_escape_string($db,$hostname) . "' AND " .
            "port = '" . mysqli_real_escape_string($db,$port) . "'");

    // Get the request time as a timestamp for later
    $timestamp = $_SERVER['REQUEST_TIME'];

    // if greater than 1, check the nextcheck date
    if (mysqli_num_rows($checkhost) > 0)
    {
        $update = "UPDATE hostsregister SET " .
                "register = '" . mysqli_real_escape_string($db,$timestamp) . "', " . 
                "nextcheck = '0', checked = '0', " .
                "failcounter = '0' " .  
                "WHERE host = '" . mysqli_real_escape_string($db,$hostname) . "' AND " .
                "port = '" . mysqli_real_escape_string($port) . "'";

        $runupdate = mysqli_query($db, $update);
    }
    else
    {
        $register = "INSERT INTO hostsregister VALUES ".
                    "('" . mysqli_real_escape_string($db,$hostname) . "', " .
                    "'" . mysqli_real_escape_string($db,$port) . "', " .
                    "'" . mysqli_real_escape_string($db,$timestamp) . "', 0, 0, 0)";

        $runupdate = mysqli_query($db, $register);
    }
}
elseif ($hostname != "" && $port != "" && $service = "offline")
{
        $delete = "DELETE FROM hostsregister " .
                "WHERE host = '" . mysqli_real_escape_string($db,$hostname) . "' AND " .
                "port = '" . mysqli_real_escape_string($db,$port) . "'";

        $rundelete = mysqli_query($db, $delete);
}
?>
