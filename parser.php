<?php

//
//		Modified for  PHP7 and MySQLi
//
//		This needs php-curl   installed
//

include("databaseinfo.php");

//Supress all Warnings/Errors
//error_reporting(0);

$now = time();

//
// Search DB
//
//
//mysqli_select_db ($db, $DB_NAME);

if (!isset($db)) $db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

function GetURL($host, $port, $url)
{
    $url = "http://$host:$port/$url";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $data = curl_exec($ch);
    if (curl_errno($ch) == 0)
    {
        curl_close($ch);
        return $data;
    }

    curl_close($ch);
    return "";
}

function CheckHost($hostname, $port)
{
if (!isset($db)) $db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    global $now;

    $xml = GetURL($hostname, $port, "?method=collector");
    if ($xml == "")	//No data was retrieved? (CURL may have timed out)
        {
        $failcounter = "failcounter + 1";
        echo "fail: ".$failcounter."<br>\n";
        }
    else
        $failcounter = "0";

    //Update nextcheck to be 10 minutes from now. The current OS instance
    //won't be checked again until at least this much time has gone by.
    $next = $now + 600;

    mysqli_query($db, "UPDATE hostsregister SET nextcheck = $next," .
                " checked = 1, failcounter = " . $failcounter .
                " WHERE host = '" . mysqli_real_escape_string($db, $hostname) . "'" . 
                " AND port = '" . mysqli_real_escape_string($db, $port) . "'");

    if ($xml != "") 
        parse($hostname, $port, $xml);
}

function parse($hostname, $port, $xml)
{
    global $now, $db;
    
	if (!isset($db)) $db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
	
    ///////////////////////////////////////////////////////////////////////
    //
    // Search engine sim scanner
    //

    //
    // Load XML doc from URL
    //
    $objDOM = new DOMDocument();
    $objDOM->resolveExternals = false;

    //Don't try and parse if XML is invalid or we got an HTML 404 error.
    if ($objDOM->loadXML($xml) == False)
        return;

    //
    // Get the region data to update
    //
    $regiondata = $objDOM->getElementsByTagName("regiondata");

    //If returned length is 0, collector method may have returned an error
    if ($regiondata->length == 0)
        return;

    $regiondata = $regiondata->item(0);

    //
    // Update nextcheck so this host entry won't be checked again until after
    // the DataSnapshot module has generated a new set of data to be parsed.
    //
    $expire = $regiondata->getElementsByTagName("expire")->item(0)->nodeValue;
    $next = $now + $expire;

    $updater = mysqli_query($db, "UPDATE hostsregister SET nextcheck = $next " .
            "WHERE host = '" . mysqli_real_escape_string($db, $hostname) . "' AND " .
            "port = '" . mysqli_real_escape_string($db, $port) . "'");

    //
    // Get the region data to be saved in the database
    //
    $regionlist = $regiondata->getElementsByTagName("region");

    foreach ($regionlist as $region)
    {
        $regioncategory = $region->getAttributeNode("category")->nodeValue;

        //
        // Start reading the Region info
        //
        $info = $region->getElementsByTagName("info")->item(0);

        $regionuuid = $info->getElementsByTagName("uuid")->item(0)->nodeValue;

        $regionname = $info->getElementsByTagName("name")->item(0)->nodeValue;

        $regionhandle = $info->getElementsByTagName("handle")->item(0)->nodeValue;

        $url = $info->getElementsByTagName("url")->item(0)->nodeValue;

        //
        // First, check if we already have a region that is the same
        //
        $check = mysqli_query($db, "SELECT * FROM regions WHERE regionuuid = '" .
                mysqli_real_escape_string($db, $regionuuid) . "'");

        if (mysqli_num_rows($check) > 0)
        {
            mysqli_query($db, "DELETE FROM regions WHERE regionuuid = '" .
                    mysqli_real_escape_string($db, $regionuuid) . "'");
            mysqli_query($db, "DELETE FROM parcels WHERE regionuuid = '" .
                    mysqli_real_escape_string($db, $regionuuid) . "'");
            mysqli_query($db, "DELETE FROM allparcels WHERE regionUUID = '" .
                    mysqli_real_escape_string($db, $regionuuid) . "'");
            mysqli_query($db, "DELETE FROM parcelsales WHERE regionUUID = '" .
                    mysqli_real_escape_string($db, $regionuuid) . "'");
            mysqli_query($db, "DELETE FROM objects WHERE regionuuid = '" .
                    mysqli_real_escape_string($db, $regionuuid) . "'");
        }

        $data = $region->getElementsByTagName("data")->item(0);
        $estate = $data->getElementsByTagName("estate")->item(0);

        $username = $estate->getElementsByTagName("name")->item(0)->nodeValue;
        $useruuid = $estate->getElementsByTagName("uuid")->item(0)->nodeValue;

        $estateid = $estate->getElementsByTagName("id")->item(0)->nodeValue;

        //
        // Second, add the new info to the database
        //
        $sql = "INSERT INTO regions VALUES('" .
                mysqli_real_escape_string($db, $regionname) . "','" .
                mysqli_real_escape_string($db, $regionuuid) . "','" .
                mysqli_real_escape_string($db, $regionhandle) . "','" .
                mysqli_real_escape_string($db, $url) . "','" .
                mysqli_real_escape_string($db, $username) ."','" .
                mysqli_real_escape_string($db, $useruuid) ."')";

        mysqli_query($db, $sql);

        //
        // Start reading the parcel info
        //
        $parcel = $data->getElementsByTagName("parcel");

        foreach ($parcel as $value)
        {
            $parcelname = $value->getElementsByTagName("name")->item(0)->nodeValue;

            $parceluuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;

            $infouuid = $value->getElementsByTagName("infouuid")->item(0)->nodeValue;

            $parcellanding = $value->getElementsByTagName("location")->item(0)->nodeValue;

            $parceldescription = $value->getElementsByTagName("description")->item(0)->nodeValue;

            $parcelarea = $value->getElementsByTagName("area")->item(0)->nodeValue;

            $parcelcategory = $value->getAttributeNode("category")->nodeValue;

            $parcelsaleprice = $value->getAttributeNode("salesprice")->nodeValue;

            $dwell = $value->getElementsByTagName("dwell")->item(0)->nodeValue;

            $owner = $value->getElementsByTagName("owner")->item(0);

            $owneruuid = $owner->getElementsByTagName("uuid")->item(0)->nodeValue;

            // Adding support for groups

            $group = $value->getElementsByTagName("group")->item(0);
            
            if ($group != "")
            {
                $groupuuid = $group->getElementsByTagName("groupuuid")->item(0)->nodeValue;
            }
            else
            {
                $groupuuid = "00000000-0000-0000-0000-000000000000";
            }

            //
            // Check bits on Public, Build, Script
            //
            $parcelforsale = $value->getAttributeNode("forsale")->nodeValue;
            $parceldirectory = $value->getAttributeNode("showinsearch")->nodeValue;
            $parcelbuild = $value->getAttributeNode("build")->nodeValue;
            $parcelscript = $value->getAttributeNode("scripts")->nodeValue;
            $parcelpublic = $value->getAttributeNode("public")->nodeValue;

            //
            // Save
            //
            //$db=mysqli_connect ($DB_HOST, $DB_USER, $DB_PASSWORD);
            $sql = "INSERT INTO allparcels VALUES('" .
                    mysqli_real_escape_string($db, $regionuuid) . "','" .
                    mysqli_real_escape_string($db, $parcelname) . "','" .
                    mysqli_real_escape_string($db, $owneruuid) . "','" .
                    mysqli_real_escape_string($db, $groupuuid) . "','" .
                    mysqli_real_escape_string($db, $parcellanding) . "','" .
                    mysqli_real_escape_string($db, $parceluuid) . "','" .
                    mysqli_real_escape_string($db, $infouuid) . "','" .
                    mysqli_real_escape_string($db, $parcelarea) . "' )";

            mysqli_query($db,$sql);

            if ($parceldirectory == "true")
            {
                $sql = "INSERT INTO parcels VALUES('" .
                        mysqli_real_escape_string($db, $regionuuid) . "','" .
                        mysqli_real_escape_string($db, $parcelname) . "','" .
                        mysqli_real_escape_string($db, $parceluuid) . "','" .
                        mysqli_real_escape_string($db, $parcellanding) . "','" .
                        mysqli_real_escape_string($db, $parceldescription) . "','" .
                        mysqli_real_escape_string($db, $parcelcategory) . "','" .
                        mysqli_real_escape_string($db, $parcelbuild) . "','" .
                        mysqli_real_escape_string($db, $parcelscript) . "','" .
                        mysqli_real_escape_string($db, $parcelpublic) . "','".
                        mysqli_real_escape_string($db, $dwell) . "','" .
                        mysqli_real_escape_string($db, $infouuid) . "','" .
                        mysqli_real_escape_string($db, $regioncategory) . "')";

                mysqli_query($db, $sql);
            }

            if ($parcelforsale == "true")
            {
                $sql = "INSERT INTO parcelsales VALUES('" .
                        mysqli_real_escape_string($db, $regionuuid) . "','" .
                        mysqli_real_escape_string($db, $parcelname) . "','" .
                        mysqli_real_escape_string($db, $parceluuid) . "','" .
                        mysqli_real_escape_string($db, $parcelarea) . "','" .
                        mysqli_real_escape_string($db, $parcelsaleprice) . "','" .
                        mysqli_real_escape_string($db, $parcellanding) . "','" .
                        mysqli_real_escape_string($db, $infouuid) . "', '" .
                        mysqli_real_escape_string($db, $dwell) . "', '" .
                        mysqli_real_escape_string($db, $estateid) . "', '" .
                        mysqli_real_escape_string($db, $regioncategory) . "')";

                mysqli_query($db, $sql);
            }
        }

        //
        // Handle objects
        //
        $objects = $data->getElementsByTagName("object");

        foreach ($objects as $value)
        {
            $uuid = $value->getElementsByTagName("uuid")->item(0)->nodeValue;

            $regionuuid = $value->getElementsByTagName("regionuuid")->item(0)->nodeValue;

            $parceluuid = $value->getElementsByTagName("parceluuid")->item(0)->nodeValue;

            $location = $value->getElementsByTagName("location")->item(0)->nodeValue;

            $title = $value->getElementsByTagName("title")->item(0)->nodeValue;

            $description = $value->getElementsByTagName("description")->item(0)->nodeValue;

            $flags = $value->getElementsByTagName("flags")->item(0)->nodeValue;

            mysqli_query($db, "INSERT INTO objects VALUES('" .
                    mysqli_real_escape_string($db, $uuid) . "','" .
                    mysqli_real_escape_string($db, $parceluuid) . "','" .
                    mysqli_real_escape_string($db, $location) . "','" .
                    mysqli_real_escape_string($db, $title) . "','" .
                    mysqli_real_escape_string($db, $description) . "','" .
                    mysqli_real_escape_string($db, $regionuuid) . "')");
        }
    }
}

$sql = "SELECT host, port FROM hostsregister " .
        "WHERE nextcheck < $now AND checked = 0 LIMIT 0,10";

$jobsearch = mysqli_query($db,$sql);

//
// If the sql query returns no rows, all entries in the hostsregister
// table have been checked. Reset the checked flag and re-run the
// query to select the next set of hosts to be checked.
//
if (mysqli_num_rows($jobsearch) == 0)
{
    mysqli_query($db, "UPDATE hostsregister SET checked = 0");
    $jobsearch = mysqli_query($db,$sql);
}

while ($jobs = mysqli_fetch_row($jobsearch))
    CheckHost($jobs[0], $jobs[1]);
?>
