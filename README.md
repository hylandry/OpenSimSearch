This release is intended to work with  OpenSim 0.9x, the OpenSimSearch 3rd party module,  and PHP7 / MySQLi.

If you are not using PHP7 and MySQLi on your server, the orignial PHP5 / MySQL examples on the GitHub page should work fine.


PARSER.PHP!!   
-----------------------


For  parser.php   (goes to your individual sims and retrieves search data)    you will need the Curl PHP module, and php-cli.
in Ubuntu and Debian variants..     apt install php-curl php-cli


You will need to set up cron or your task scheduler of choice, to run  "php parser.php"    periodically, to check for changes.
It is reccomended you only do this every few hours, depending on the size of your grid, this can be very resource and time consuming.

-----------------------

File access errors.     Some of the scripts will write debugging logs.   If Apache does not have write-access to the /search/ folder, you will have to
give write access, or comment out the lines that write the log files.  (easy to find)