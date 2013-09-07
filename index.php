<?php
// True Combat PHP Stats
//
// https://github.com/firefly2442/tcstats

// ---------------------------------------------------------------------
// Portions of code adapted from "systates"
// http://systates.sourceforge.net
// ---------------------------------------------------------------------


////////////////////////////////////////////////////////////////////////////////
// Edit this -->
$host = "127.0.0.1";			//IP address or hostname of True Combat server ("127.0.0.1" is local computer)
$port = 27960;				//port that server is running on (default 27960)
$website = "http://wob.050104.com";	//Your website, leave blank "" if none
// End here.
////////////////////////////////////////////////////////////////////////////////



// Do not edit anything below this line unless you know what you are doing!
// ----------------------------------------------------------------------

$version = 0.3;
$timeout = 15;                                // Default timeout for the php socket (seconds)
$length = 4096;                               // Packet length
$protocol = 'udp';                            // Default protocol for sending query
$magic = "\377\377\377\377";                  // Magic string to send via UDP
$pattern = "/$magic" . "print\n/";
$pattern2 = "/$magic" . "statusResponse\n/";

$players = array(); // List of players
$params = array();  // Game parameters

//turn off warnings so that if it cannot connect it still displays output
error_reporting(!E_WARNING);

// Create the UDP socket
$socket = socket_create (AF_INET, SOCK_DGRAM, getprotobyname ($protocol));
if ($socket)
{
	if (socket_set_nonblock ($socket))
	{
		$time = time();
		$error = "";
		while (!@socket_connect ($socket, $host, $port ))
		{	
			$err = socket_last_error ($socket);
			if ($err == 115 || $err == 114)
			{
				if ((time () - $time) >= $timeout)
				{
					socket_close ($socket);
					echo "Error! Connection timed out.";
				}
				sleep(1);
				continue;
			}
		}

		// Verify if an error occured
		if( strlen($error) == 0 )
		{
			socket_write ($socket, $magic . "getstatus\n");
			$read = array ($socket);
			$out = "";
			
			while (socket_select ($read, $write = NULL, $except = NULL, 1))
			{
				$out .= socket_read ($socket, $length, PHP_BINARY_READ);
			}

			if ($out == "")
				echo "<center><font color=red><h2>Unable to connect to server...</h2></font></center>";
			
			socket_close ($socket);
			$out = preg_replace ($pattern, "", $out);
			$out = preg_replace ($pattern2, "", $out);
				$all = explode( "\n", $out );
			$params = explode( "\\", $all[0] );
			array_shift( $params );
			for( $i = 0; $i < count($params); $i++ )
			{
				$params[ strtolower($params[$i]) ] = $params[++$i];
			}
				
			for( $i = 1; $i < count($all) - 1; $i++ )
			{
				$pos = strpos( $all[$i], " " );
				$score = substr( $all[$i], 0, $pos );
				$pos2 = strpos( $all[$i], " ", $pos + 1 );
				$ping = substr( $all[$i], $pos + 1, $pos2 - $pos - 1 );
				$name = substr( $all[$i], $pos2 + 2 );
				$name = substr( $name, 0, strlen( $name ) - 1);

				$player = array( $name, $score, $ping );
				$players[] = $player;
			}
		}
		else
		{
			echo "Unable to connect to server.";
		}
	}
	else 
	{
		echo "Error! Unable to set nonblock on socket.";
	}
}
else 
{
	echo "The server is DOWN!";
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<link rel="stylesheet" type="text/css" href="./stylesheets/default.css">
<title>True Combat Server Stats</title>
</head>
<body>
<center>
<img src="truecombat.jpg"></img>
<hr>
<br>

<table border=0>
<tr class="box_titles">
<td>
<?php echo $params['sv_hostname']; ?>
</td>
</tr>
<tr>
<td>
<?php
//for ($i = 0; $i < 100; $i++)
//{
//	echo "<br><b>" . $params[$i] . "</b>";
//}


//map information
echo "<b>Map: </b>" . $params['mapname'] . "<br>";
echo count($players) . " out of " . $params['sv_maxclients'] . " currently playing<br><br>";
if (file_exists("./images/" . $params['mapname'] . ".jpg"))
{
	echo "<img src='./images/" . $params['mapname'] . ".jpg'</img>";
}
else
{
	echo "<img src='./images/no_image.jpg'</img>";
}

?>
</td>
<td>
<table border=0>
<tr class="box_titles">
<td><b>Player</b></td>
<td><b>Score</b></td>
<td><b>Ping</b></td>
</tr>
<?php //players information
for ($j = 0; $j < count($players); $j++)
{
	echo "<tr class='general_row'>";
	echo "<td>" . $players[$j][0] . "</td>\n";
	echo "<td>" . $players[$j][1] . "</td>\n";
	if ($players[$j][2] == 999)
		echo "<td>Connecting...</td>\n";
	else
		echo "<td>" . $players[$j][2] . "</td>\n";
	echo "</tr>";
}
echo "</table><br>";
//Note:
//These variables were taken out in version 0.49 so I can't track who is on each team.
//
//Display Team Sizes
//
//echo "<b>Specops Players: </b>";
//if ($params['players_allies'] == "(None)")
//	echo "0";
//else
//	echo "'" . $params['players_allies'] . "'";
//echo "<br>";
//echo "<b>Terrorist Players: </b>";
//if ($params['players_axis'] == "(None)")
//	echo "0";
//else
//	echo $params['players_axis'];
//echo "<br>";

?>
</td>
<td>
<table border=0>
<tr class="box_titles">
<td><b>Rules</b></td>
<td><b>Setting</b></td>
<?php //server information
echo "<tr class='general_row'>";
echo "<td>True Combat Version</td>";
echo "<td>" . $params['tce_version'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Enemy Territory Version</td>";
echo "<td>" . $params['version'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>PunkBuster</td>";
echo "<td>" . $params['sv_punkbuster'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Friendly Fire</td>";
echo "<td>" . $params['g_friendlyfire'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Balanced Teams</td>";
echo "<td>" . $params['g_balancedteams'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Anti-Lag</td>";
echo "<td>" . $params['g_antilag'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Maximum Ping</td>";
echo "<td>" . $params['sv_maxping'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Minimum Ping</td>";
echo "<td>" . $params['sv_minping'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Password Protected</td>";
echo "<td>" . $params['g_needpass'] . "</td>";
echo "</tr>";
echo "<tr class='general_row'>";
echo "<td>Website</td>";
echo "<td>";
if ($website == "")
	echo "None";
else
	echo "<a href=" . $website . " target=_blank>" . $website . "</a>";
echo "</td></tr>";
?>
</table>
</td>
</tr>
<tr class="box_titles">
<td>
<?php
echo "Version: " . $version . " - ";
?>
<a href="https://github.com/firefly2442/tcstats" target=_blank>https://github.com/firefly2442/tcstats</a>
</td>
</tr>
</table>

</center>
</body>
</html>
