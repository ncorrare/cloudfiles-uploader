<html>
<head>
	<title> CloudFiles Uploader POC </title>
</head>
<?php
	require ('cloudfiles.php');
	// Please populate the following variables:
	$mysqlhost=""; // The server in which MySQL is running
	$mysqluser=""; // The MySQL username
	$mysqlpassword=""; // The MySQL password
	$mysqldatabase=""; // The MySQL DB
	$cfuser=""; // CloudFiles username
	$cfapikey=""; // CloudFiles API Key
	$cftempkey=""; // CloudFiles TempURL Key obtained from: curl -iH 'X-Auth-Token: TOKEN' https://storage101.lon3.clouddrive.com/v1/auth_ACCOUNT 
	$cfauthurl="https://lon.auth.api.rackspacecloud.com/"; // Use https://lon.auth.api.rackspacecloud.com/ for UK, https://auth.api.rackspacecloud.com/ for US
	function format_bytes($a_bytes)
	{
    	if ($a_bytes < 1024) {
	        return $a_bytes .' B';
	    } elseif ($a_bytes < 1048576) {
	        return round($a_bytes / 1024, 2) .' KB';
	    } elseif ($a_bytes < 1073741824) {
	        return round($a_bytes / 1048576, 2) . ' MB';
	    } elseif ($a_bytes < 1099511627776) {
	        return round($a_bytes / 1073741824, 2) . ' GB';
	    } elseif ($a_bytes < 1125899906842624) {
	        return round($a_bytes / 1099511627776, 2) .' TB';
	    } elseif ($a_bytes < 1152921504606846976) {
	        return round($a_bytes / 1125899906842624, 2) .' PB';
	    } elseif ($a_bytes < 1180591620717411303424) {
	        return round($a_bytes / 1152921504606846976, 2) .' EB';
	    } elseif ($a_bytes < 1208925819614629174706176) {
	        return round($a_bytes / 1180591620717411303424, 2) .' ZB';
	    } else {
        	return round($a_bytes / 1208925819614629174706176, 2) .' YB';
    	}
	}
	if (isset($_POST["do"])) {
	$do=$_POST["do"];
	switch ($do)
		{
		case "upload":
			$target_path = "uploads/";

			$target_path = $target_path . basename( $_FILES['upfile']['name']); 

			if(move_uploaded_file($_FILES['upfile']['tmp_name'], $target_path)) {
		    		$filename=basename( $_FILES['upfile']['name']);
				$desc=$_POST["desc"];
				$msg="The file ".$filename." has been uploaded";
				mysql_connect($mysqlhost, $mysqluser, $mysqlpassword) or
    				die("Could not connect: " . mysql_error());
				mysql_select_db($mysqldatabase);
				mysql_query("INSERT into files VALUES (NULL, '$filename', '$desc')");
				echo mysql_error();
				$cfauth = new CF_Authentication($cfuser, $cfapikey, NULL, $cfauthurl);
				$cfauth->authenticate();
				$cfconn = new CF_Connection($cfauth);
				$ccont = $cfconn->get_container("testcontainer");
				$cfile = $ccont->create_object("$filename");
				$size = (float) sprintf("%u", filesize($target_path));
				$fp = fopen($target_path,"r");
				$cfile->write($fp, $size);
				unlink($target_path);
			} else{
		    		$msg="There was an error uploading the file, please try again!";
			}
		break;
		case "delete":
			mysql_connect($mysqlhost, $mysqluser, $mysqlpassword) or
                        die("Could not connect: " . mysql_error());
                        mysql_select_db($mysqldatabase);
			$fileid=$_POST["id"];
                        $query="SELECT * from files WHERE id=$fileid;";
			$result=mysql_query($query);
			$row=mysql_fetch_array($result);
			$cfauth = new CF_Authentication($cfuser, $cfapikey, NULL, $cfauthurl);
                        $cfauth->authenticate();
                        $cfconn = new CF_Connection($cfauth);
                        $ccont = $cfconn->get_container("testcontainer");
			$filename=$row["fname"];
			if ($ccont->delete_object($filename))
					{ 
					$query="DELETE from files WHERE id=$fileid;";
					mysql_query($query);
					$msg="The file ".$filename." has been deleted";
					}
			break;
		case "download":
			mysql_connect($mysqlhost, $mysqluser, $mysqlpassword) or
                        die("Could not connect: " . mysql_error());
                        mysql_select_db($mysqldatabase);
                        $fileid=$_POST["id"];
                        $query="SELECT * from files WHERE id=$fileid;";
                        $result=mysql_query($query);
                        $row=mysql_fetch_array($result);
			$filename=$row["fname"];
			$cfauth = new CF_Authentication($cfuser, $cfapikey, NULL, $cfauthurl);
                        $cfauth->authenticate();
                        $cfconn = new CF_Connection($cfauth);
                        $ccont = $cfconn->get_container("testcontainer");
                        $cfile = $ccont->get_object("$filename");
			$url=$cfile->get_temp_url($cftempkey, 60, "GET");
			
			
			break;
		case "stream":
			mysql_connect($mysqlhost, $mysqluser, $mysqlpassword) or
                        die("Could not connect: " . mysql_error());
                        mysql_select_db($mysqldatabase);
                        $filestreamid=$_POST["id"];
                        $query="SELECT * from files WHERE id=$filestreamid;";
                        $result=mysql_query($query);
                        $row=mysql_fetch_array($result);
			$filename=$row["fname"];
			$cfauth = new CF_Authentication($cfuser, $cfapikey, NULL, $cfauthurl);
                        $cfauth->authenticate();
                        $cfconn = new CF_Connection($cfauth);
                        $ccont = $cfconn->get_container("testcontainer");
                        $cfile = $ccont->get_object("$filename");
			$url=$cfile->public_streaming_uri();
			
			break;

		}
	}
	if (isset($msg)) {
		echo "<body onLoad=\"javascript:alert('". $msg ."');\">\n"; 
	} else {
		echo "</body>\n";
	}
	?>
	<h1> CloudFiles Uploader </h1>
	<h2> This is a proof of concept! </h2>
	<h3> Upload a File... </h3>
	<form enctype="multipart/form-data" action="index.php" method="post">
	<input type="hidden" name="do" value="upload" />
	Choose a file: <input type="file" name="upfile" />
	Add a Description: <input name="desc" />

	<input type="submit" value="Upload to CDN">
	</form>
	<h3> Or download a previously uploaded file through the CDN </h3>
	<table border="1">
		<tr>
			<td>X</td>
			<td>Name</td>
			<td>Description</td>
			<td>Size</td>
			<td>Download</td>
			<td>Stream</td>
		</tr>
		<?php
			mysql_connect($mysqlhost, $mysqluser, $mysqlpassword) or
                        die("Could not connect: " . mysql_error());
                        mysql_select_db($mysqldatabase);
			$query="SELECT * from files;";
	                $cfauth = new CF_Authentication($cfuser, $cfapikey, NULL, $cfauthurl);
              	        $cfauth->authenticate();
                      	$cfconn = new CF_Connection($cfauth);
			$result=mysql_query($query);
			while ($row=mysql_fetch_array($result)) {
				$thisid=$row["id"];
				echo "			<tr>\n";
				echo "				<td><form action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"do\" value=\"delete\" /><input type=\"hidden\" name=\"id\" value=\"".$thisid."\" /> <input type=\"submit\" value=\"X\" /> </form> </td>\n";
				$filename=$row["fname"];
				echo "				<td>".$filename."</td> \n";
				echo "				<td>".$row["desc"]."</td> \n";
       		                $ccont = $cfconn->get_container("testcontainer");
        	                $cfile = $ccont->get_object("$filename");
				$filesize = $cfile->content_length;
				$propersize=format_bytes($filesize);
				echo "				<td>".$propersize."</td>\n";
					if ($thisid==$fileid)
						{

							echo "<td> <a href=\"$url\">Download</a> Valid for: <div id=\"javascript_countdown_time\"></div> </td>\n";
						}
						else
						{
							echo "				<td><form action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"do\" value=\"download\" /><input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\" /> <input type=\"submit\" value=\"Download\" /> </form> </td>\n";
						
						}
                                        if ($thisid==$filestreamid)
                                                {

                                                        echo "<td> 

							<script type='text/javascript' src='jwplayer.js'></script>

							<div id='mediaspace'>Loading...</div>

							<script type='text/javascript'>
							  jwplayer('mediaspace').setup({
							    'flashplayer': 'player.swf',
							    'file': '".$url."',
							    'controlbar': 'bottom',
							    'width': '470',
							    'height': '320'
							  });
							</script>\n";

                                                }
                                                else
                                                {
				echo "				<td><form action=\"index.php\" method=\"POST\"><input type=\"hidden\" name=\"do\" value=\"stream\" /><input type=\"hidden\" name=\"id\" value=\"".$row["id"]."\" /> <input type=\"submit\" value=\"Stream\" /> </form> </td>\n";
						}
				echo "				</tr>\n";	
			}
		?>
	<script type="text/javascript">
	var javascript_countdown = function () {
	var time_left = 10; //number of seconds for countdown
	var output_element_id = 'javascript_countdown_time';
	var keep_counting = 1;
	var no_time_left_message = 'Time is up. Download link now invalid.';
 
	function countdown() {
		if(time_left < 2) {
			keep_counting = 0;
		}
 
		time_left = time_left - 1;
	}
 
	function add_leading_zero(n) {
		if(n.toString().length < 2) {
			return '0' + n;
		} else {
			return n;
		}
	}
 
	function format_output() {
		var hours, minutes, seconds;
		seconds = time_left % 60;
		minutes = Math.floor(time_left / 60) % 60;
		hours = Math.floor(time_left / 3600);
 
		seconds = add_leading_zero( seconds );
		minutes = add_leading_zero( minutes );
		hours = add_leading_zero( hours );
 
		return hours + ':' + minutes + ':' + seconds;
	}
 
	function show_time_left() {
		document.getElementById(output_element_id).innerHTML = format_output();//time_left;
	}
 
	function no_time_left() {
		document.getElementById(output_element_id).innerHTML = no_time_left_message;
	}
 
	return {
		count: function () {
			countdown();
			show_time_left();
		},
		timer: function () {
			javascript_countdown.count();
 
			if(keep_counting) {
				setTimeout("javascript_countdown.timer();", 1000);
			} else {
				no_time_left();
			}
		},
		//Kristian Messer requested recalculation of time that is left
		setTimeLeft: function (t) {
			time_left = t;
			if(keep_counting == 0) {
				javascript_countdown.timer();
			}
		},
		init: function (t, element_id) {
			time_left = t;
			output_element_id = element_id;
			javascript_countdown.timer();
		}
	};
}();
 
//time to countdown in seconds, and element ID
javascript_countdown.init(90, 'javascript_countdown_time');	
	</script>
</body>
</html>
