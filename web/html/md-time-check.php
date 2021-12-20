<?php

if (isset($_GET["file"])) {
	switch ($_GET["file"]) {
		case 'eduGAIN':
			$match="swamid-edugain-1.0.xml</a";
			$name="eduGAIN";
			break;
		case 'swamid-idp-transitive':
			$match="swamid-idp-transitive.xml</a";
			$name="swamid-idp-transitive";
			break;
		case 'swamid-sp-transitive':
			$match="swamid-sp-transitive.xml</a";
			$name="swamid-sp-transitive";
			break;
		default:
			$match="swamid-2.0.xml</a";
			$name="swamid-2.0";
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://mds.swamid.se/md/");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$output = curl_exec($ch);

	foreach (explode("\n",$output) as $row) {
		$row_array = explode(">",$row);
		if ($row_array[0] == "<tr"  && $row_array[6] == $match )
			checkTime($row_array[9],$name);
	}
	curl_close($ch);
}

function checkTime($value,$name) {
	$fileTime = new DateTime(substr($value,0,16));
	$now = new DateTime("");
	$diff = $now->diff($fileTime);
	if ( $diff->y > 0 || $diff->m > 0 || $diff->d > 0 || $diff->h > 0 || $diff->i > 30 )
		print "$name FAIL\n";
	else
		print "$name OK\n";
}
?>
