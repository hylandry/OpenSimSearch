<?php

//
//      For event submit LSL script.    Recieves a date in a very flexible format like "5PM Saturday" and returns a good formatted date, or error if it's too crazy.
//


$d=$_GET["day"];

if (strtotime($d) < (time()-86400)) { echo "Sorry Either bad date format or in the past.  try again."; }
else {
echo "DATEOK ".date('Y-m-d H:i', strtotime($d));
}
?>