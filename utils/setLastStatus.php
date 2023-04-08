<?php
/*
 * Created on 21.12.2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include_once('../function.php');
	$str = "SELECT st.status, st.usr_id FROM srv_userstatus AS st LEFT JOIN (SELECT s.usr_id, max(s.datetime) as statusdatetime FROM srv_userstatus as s GROUP BY s.usr_id ) AS s ON s.usr_id = st.usr_id ";
	$qry = sisplet_query($str);
	while ($row = mysqli_fetch_assoc($qry)) {
		$str2 = "UPDATE srv_user SET last_status = '".$row['status']."' WHERE id = '".$row['usr_id']."'";

		$updated = sisplet_query($str2);
		print_r($updated."<br>");
	} 			
?>
