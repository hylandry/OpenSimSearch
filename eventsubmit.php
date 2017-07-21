<?php header("content-type: text/plain; charset=utf-8"); ?>
Headers received:
<?php

include("databaseinfo.php");

$currkey="CHANGEME";     //   change this to match the one in LSL script.   Does not need to be uuid..  can be "mickeymouse" if you want.

if (isset($_POST["me"])) {$mk=$_POST["me"]; }

if ($mk != $currkey) { 
	echo "Sorry you have an outdated version of our update client.\nVisit region Your Grid's welcome center to obtain an update.";
	exit();
}



if (!isset($db)) $db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$curvers=array("0.31a","0.31b");

$ratings = array("General","Mature","Adult");
$cats=array(40);

$cats[0]="Any";
$cats[18]="Discussion";
$cats[19]="Sports";
$cats[20]="Live Music";
$cats[22]="Commercial";
$cats[23]="Nightlife/Entertainment";
$cats[24]="Games/Contests";
$cats[25]="Pageants";
$cats[26]="Education";
$cats[27]="Arts and Culture";
$cats[28]="Charity/Support Groups";
$cats[29]="Miscellaneous";



 $evownerid=$_SERVER["HTTP_X_SECONDLIFE_OWNER_KEY"];
 
    if (isset($_POST["evscript"])) {$evscript=$_POST["evscript"]; }
    
    if (isset($_POST["evname"])) {$evname=$_POST["evname"]; }
    if (isset($_POST["evdesc"])) {$evdesc=$_POST["evdesc"]; }    
    if (isset($_POST["evdate"])) {$evdate=$_POST["evdate"]; }
    if (isset($_POST["evtime"])) {$evtime=$_POST["evtime"]; }
    if (isset($_POST["evhglink"])) {$evhglink=$_POST["evhglink"]; }    
    if (isset($_POST["evobjpos"])) {$evobjpos=$_POST["evobjpos"]; }    
    if (isset($_POST["evownerid"])) {$evownerid=$_POST["evownerid"]; }
	if (isset($_POST["evcategory"])) {$evcategory=$_POST["evcategory"]; }    
    if (isset($_POST["evrating"])) {$evrating=$_POST["evrating"]; }    
    if (isset($_POST["evcover"])) {$evcover=$_POST["evcover"]; }    
    if (isset($_POST["evduration"])) {$evduration=$_POST["evduration"]; }    
    if (isset($_POST["evPersistentID"])) {$evPersistentID=$_POST["evPersistentID"]; }
    
    list($scr,$ver)=explode(":",$evscript);
    

    
    
if (isset($_POST["delrec"])) {
	$r=$_POST["delrec"];
	$k=$_SERVER["HTTP_X_SECONDLIFE_OWNER_KEY"];
	$sql="SELECT eventid, creatoruuid FROM `events` where eventid='".$r."'";
	$result=mysqli_query($db,$sql);
	$row=mysqli_fetch_assoc($result);
	if ($row["creatoruuid"] != $k) { echo $row["creatoruuid"]."|".$k."|".$sql."|Not your listing.   This incident will be reported in the log.";  exit(); }
	mysqli_free_result($result);
	$sql="DELETE FROM `events` WHERE eventid='".$r."'";
	$result=mysqli_query($db,$sql);
	echo "Listing ID $r deleted.";
	exit();
	
}
    
    
    if (strlen($evhglink)>0) {$evdesc=$evdesc."\n\r\n\r HG Link: ".$evhglink; }
    
    
 
   $f=fopen ("testoutput-eventsubmit.htm","w");    ///   This gives a log of the last event submit with headers and such.
     
 
foreach ($_SERVER as $k => $v)
{
//    if( substr($k, 0, 5) == 'HTTP_')
//    {
        fwrite($f, "\n". $k. "\t". $v."\n<br>");
//    }
}

$evtimestamp=strtotime($evdate. " ". $evtime);

$ri=$_SERVER["HTTP_X_SECONDLIFE_REGION"];
list($rn,$rc)=explode("(",$ri);
$rc=str_replace(")","", $rc);

list($rx,$ry)=explode(",",$rc);

$lp=$_SERVER["HTTP_X_SECONDLIFE_LOCAL_POSITION"]; // obsolete
$lp=$evobjpos;
$lp=str_replace(")","", $lp);
$lp=str_replace("(","", $lp);
$lp=str_replace(">","", $lp);
$lp=str_replace("<","", $lp);
 

$lpv=explode(",",$lp);

$evLocation=(floor(($rx*256)+$lpv[0]).",".floor(($ry*256)+$lpv[1]).",".ceil($lpv[2]));
if ($evPersistentID == 0) { $evPersistentID=time(); }

$evcb=0; if ($evcover > 0) $evcb=1;

$sql="";
$sql=$sql."owneruuid, ";
$sql=$sql."name, ";
$sql=$sql."eventid, ";
$sql=$sql."creatoruuid, ";
$sql=$sql."category, ";
$sql=$sql."description, ";
$sql=$sql."dateUTC, ";
$sql=$sql."duration, ";
$sql=$sql."covercharge, ";
$sql=$sql."coveramount, ";
$sql=$sql."simname, ";
$sql=$sql."globalPos, ";
$sql=$sql."eventFlags";

$sql=$sql.") VALUES ( ";

$sql=$sql."\"".$evownerid."\" , ";
$sql=$sql."\"". mysqli_real_escape_string($db,$evname) ."\" , ";
$sql=$sql."\"".$evPersistentID."\" , ";
$sql=$sql."\"".$evownerid."\" , ";
$sql=$sql."\"".$evcategory."\" , ";
$sql=$sql."\"". mysqli_real_escape_string($db,$evdesc) ."\" , ";
$sql=$sql."\"".$evtimestamp."\" , ";
$sql=$sql."\"".$evduration."\" , ";
$sql=$sql."\"".$evcb."\" , ";
$sql=$sql."\"".$evcover."\" , ";
$sql=$sql."\"".$rn."\" , ";
$sql=$sql."\"".$evLocation."\" , ";
$sql=$sql."\"".$evrating."\" ";
$sql=$sql.")";


$usql=$usql."owneruuid=\"".$evownerid."\" , ";
$usql=$usql."name=\"".$evname."\" , ";
$usql=$usql."eventid=\"".$evPersistentID."\" , ";
$usql=$usql."creatoruuid=\"".$evownerid."\" , ";
$usql=$usql."category=\"".$evcategory."\" , ";
$usql=$usql."description=\"".$evdesc."\" , ";
$usql=$usql."dateUTC=\"".$evtimestamp."\" , ";
$usql=$usql."duration=\"".$evduration."\" , ";
$usql=$usql."covercharge=\"".$evcb."\" , ";
$usql=$usql."coveramount=\"".$evcover."\" , ";
$usql=$usql."simname=\"".$rn."\" , ";
$usql=$usql."globalPos=\"".$evLocation."\" , ";
$usql=$usql."eventFlags=\"".$evrating."\" , ";





fwrite($f, "Name: $evname \n<br>");
fwrite($f, "Date: $evdate \n<br>");
fwrite($f, "Time: $evtime \n<br>");
fwrite($f, "<pre>ObjPos: $evobjpos</pre> \n<br>");
fwrite($f, "UNIXTime: $evtimestamp \n<br>");
fwrite($f, "Duration: $evduration \n<br>");
fwrite($f, "Description: $evdesc \n<br>");
fwrite($f, "Cover: $evcover \n<br>");
fwrite($f, "Rating: $evrating (".$ratings[$evrating].")<br>\n");
fwrite($f, "Category: $evcategory (".$cats[$evcategory].")<br>\n");
fwrite($f, "PersistentID: $evPersistentID \n<br>");
fwrite($f, "Global Location: $evLocation ($lp)\n<br>");


#$register = "INSERT INTO hostsregister VALUES ".
#                    "('" . mysqli_real_escape_string($db,$hostname) . "', " .
#                    "'" . mysqli_real_escape_string($db,$port) . "', " .
#                    "'" . mysqli_real_escape_string($db,$timestamp) . "', 0, 0, 0)";

$register="INSERT INTO events (".$sql;


        $runupdate = mysqli_query($db, $register);
        
	$updatestr="*Updates available at GinBlossom*\r\n";
	foreach ($curvers as $v) 
		{ if ($v == $ver) $updatestr=""; }
	
	
fwrite($f, "<br><br>SQL: INSERT INTO events ( ".$sql."\n<br>".mysqli_error()."\n<br>");

fclose($f);


echo $updatestr."Success!  PersistentID=$evPersistentID";
?>