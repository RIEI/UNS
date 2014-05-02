<?
$rss_file = $_GET['rss'];
$rss_feed = simplexml_load_file( $rss_file );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Language" content="en-us" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?= $rss_feed->channel->title?></title>
<style type="text/css">
.headertxt {
	text-align: center;
	font-size: 60pt;
	font-weight: bolder;
	color: #FFFFFF;
	background-color: #FF0000;
}
.alerttitle {
	text-align: center;
	font-size: 48pt;
	font-weight: bold;
	background-color: #DBDBFF;
}
.alertmessage {
	font-size: 42pt;
	font-weight: normal;
	text-align: center;
}
.alerttimestamp {
	font-size: 36pt;
	font-weight: normal;
	text-align: center;
	background-color: #DBDBFF;
}
.bottomfooterdiv{
	width:100%;
	position:absolute;
	bottom:0;
	text-align:center;
}
.center {
				text-align: center;
}
</style>

</head>

<body style="margin: 0">

<table style="width: 100%">
	<tr>
		<td style="width: 50px">
		<img alt="Alert" src="Blinking_exclamation_mark.gif" width="30" height="78" /></td>
		<td class="headertxt">ALERT</td>
		<td style="width: 50px">
		<img alt="Alert" src="Blinking_exclamation_mark.gif" width="30" height="78" /></td>
	</tr>
</table>
<br/>

<table style="width: 80%" align="center">
	<tr>
		<td>
			<div class="center"><img alt="logo" src="logo.png"></div>
			<br/>
<?
// Loop thru all the 'items' and print information for each
foreach( $rss_feed->channel->item as $item ) {
	print "<div class=\"alerttitle\">".$item->title."</div><br/>";
	print "<div class=\"alertmessage\">".$item->description."</div><br/>";
	//Convert time to local format
	$str = $item->pubDate;
	if (($timestamp = strtotime($str)) === false) {
		print "			<div class=\"alerttimestamp\">The date string (".$str.") is bogus</div><br/>";
	} else {
		print "			<div class=\"alerttimestamp\">".date('r', $timestamp)."</div><br/>";
	}
}
?>
		</td>
	</tr>
</table>	

<div class="bottomfooterdiv">
	<table style="width: 100%">
		<tr>
			<td style="width: 50px">
			<img alt="Alert" src="Blinking_exclamation_mark.gif" width="30" height="78" /></td>
			<td class="headertxt">ALERT</td>
			<td style="width: 50px">
			<img alt="Alert" src="Blinking_exclamation_mark.gif" width="30" height="78" /></td>
		</tr>
	</table>
</div>
</body>
</html>