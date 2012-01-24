<?php
@include_once("inc/functions.inc.php");
session_start();

// Last modified: 13042011

// Defaults
$display_login = true;
$display_overview = false;

$tadm = new TrinityAdmin();
$tadm->__construct();

if (@$_GET['act'] == "logout")
{
	session_unregister("adminId");
	session_destroy();
	header("Location: ");
}
 elseif (@$_SESSION["adminId"])
{
	$display_login = false;
    $display_overview = true;
	$loggedin = true;
	$adminId = $_SESSION["adminId"];
    $resulttext = "";
    
    // Check if we have to do some actions
    if (isset($_POST['act']))
    {
        $act = $_POST['act'];
    } else {
        $act = $_GET['act'];
    }
    
    switch ($act) {
        default:
            $display_overview = true;
            break;;             
    }
    
}
 elseif (@$_POST['doLogin'] == "Login!")
{
	// Process login
	$display_login = false;
	$userName = $_POST['userName'];
	$userPass = $_POST['userPass'];
	
	$loginResult = $tadm->AuthAdminUser($userName, $userPass);
	
	if ($loginResult != "fail")
	{
		$_SESSION['adminId'] = $loginResult['userId'];
		header("Location: index.php");
	}
	 else
	{
		$errmsg = "Access Denied";
	}
}
 else
{
	$display_login = true;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $tadm->siteName; ?> | Logs</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
		<meta http-equiv="Pragma" content="no-cache" />
		<link rel="stylesheet" href="css/reset-fonts.css" type="text/css" media="screen, projection" />
		<link rel="stylesheet" href="css/gt-styles.css" type="text/css" media="screen, projection" />
        <script type="text/javascript" src="/js/usenet.js"></script>
	</head>
	<body>
		<!-- head -->
		<div class="gt-hd clearfix">
			<!-- logo -->
			<div class="gt-logo">
				<?php echo $tadm->siteName; ?> - Admin Control Panel
			</div>
			<!-- / logo -->
			
			<!-- navigation -->
			<div class="gt-nav">
				<ul>
					<?php
					if ($loggedin == true)
					{
					?>
                                        <li><a href="index.php">Home</a></li>
                                        <li><a href="accounts.php">Accounts</a></li>
                                        <li><a href="char_r1.php">Chars Nebuchadnezzar</a></li>
                                        <li><a href="char_r2.php">Chars Icharus</a></li>
                                        <li><a href="news.php">Site News</a></li>
                                        <li><a href="newsletter.php">Newsletters</a></li>
                                        <li><a href="logs.php"><font color=red>Logs</font></a></li>
                                        <li><a href="index.php?act=logout">Logout</a></li>
					<?php
					}
					 else
					{
					?>
					<li><a href="index.php"><font color=red>Login</font></a></li>
					<?php
					}
					?>
				</ul>
			</div>
			<!-- / navigation -->
			
		</div>
		<!-- / head -->
		
		<!-- body -->
		<div class="gt-bd gt-cols clearfix">
			
			<!-- main content -->
			<div class="gt-content">
				<?php
				if (isset($errmsg))
				{
					echo "<B><font color=red>$errmsg</font></B>";
				}

				if ($display_login == true)
				{
				?>
				<BR><b>Login</b><BR><BR>
				<form action="index.php" method="POST">
				<table border=0 cellspacing=2 cellpadding=2>
				<tr><td>Username</td><td width=20>&nbsp;</td><td><input type="text" name="userName"></td></tr>
				<tr><td>Password</td><td width=20>&nbsp;</td><td><input type="password" name="userPass"></td></tr>
				<tr><td colspan=3>&nbsp;</td></tr>
				<tr><td colspan=3 align=center><input type="submit" name="doLogin" value="Login!"></td></tr>
				</table>
				</form>
				<?php
				}
				 elseif ($display_overview == true)
				{
					echo "<h3>Transaction overview</h3><br /><br />";
					echo "<form action=\"logs.php\" method=\"GET\">\n";
					if (!empty($sort))
					{
						echo "<input type=\"hidden\" name=\"sort\" value=\"".$sort."\">\n";
						echo "<input type=\"hidden\" name=\"dir\" value=\"".$oldDirection."\">\n";
					}
					if (!empty($_GET['pStart']))
					{
						echo "<input type=\"hidden\" name=\"pStart\" value=\"".$pStart."\">\n";
					}
					if (isset($searchText))
					{
						echo "<B>Search:</B>&nbsp;<input type=\"text\" name=\"search\" value=\"$searchText\"> Search on transactionID or paypalID\n";
					}
					 else
					{
						echo "<B>Search:</B>&nbsp;<input type=\"text\" name=\"search\" value=\"\"> Search on transactionID or paypalID\n";
					}
					echo "</form><BR><BR>";
					echo "<table border=1 cellspacing=2 cellpadding=2>";
					echo "<tr style=\"background-color: #8B8989;\">";
					echo "<th><B>logID</B>&nbsp;</th>";
					echo "<th><B>Username</B>&nbsp;</th>";
					echo "<th><B>Date/Time</B>&nbsp;</th>";
					echo "<th><B>Email</B>&nbsp;</th>";
					echo "<th><B>Package</B>&nbsp;</th>";
					echo "<th><B>Packageoption</B>&nbsp;</th>";
					echo "<th><B>PaymentProvider</B>&nbsp;</th>";
					echo "<th><B>Coupon</B>&nbsp;</th>";
					echo "<th><B>trxID</B>&nbsp;</th>";
					echo "<th><B>Status</B>&nbsp;</th>";
					echo "<th><B>Script</B>&nbsp;</th>";
					echo "<th><B>customValue</B>&nbsp;</th>";
					echo "<th><B>Remote Host</B>&nbsp;</th>";
					echo "<th>&nbsp;</th>";
					echo "</tr>\n";
					
					// Query
					$sql = "SELECT * ";
					$sql .= "FROM TransactionLog a ";
					$sql .= "LEFT JOIN Coupons ON (a.couponID = Coupons.couponID) ";
					$sql .= "LEFT JOIN Packages ON (a.pkgDef = Packages.pkgDef) ";
					$sql .= "LEFT JOIN packageOptions ON (a.oID = packageOptions.optionID) ";
					$sql .= "LEFT JOIN PaymentProviders ON (a.pID = PaymentProviders.pID) ";
					$sql .= "ORDER BY logID DESC";

					echo "Q: $sql<BR><BR>";
					$res = @mysql_query($sql, $tadm->DB_Conn());
					$row = @mysql_num_rows($res);
					
					for ($x = 0; $x < $row; $x++)
					{
						if ($x & 1)
						{
							$color = "#CDC9C9;";
						}
						 else
						{
							$color = "#EEE9E9;";
						}
						$dat = @mysql_fetch_assoc($res);
						echo "<tr style=\"background-color: $color\">";
						echo "<td nowrap align=left>";
						echo $dat['logID'];
						echo "</td><td nowrap align=left>";
						echo $dat['username'] . "&nbsp;";
						echo "</td><td nowrap align=right>";
						echo date("d-m-Y H:i:s", $dat['transTime']);
						echo "</td><td nowrap align=right>";
						echo $dat['email'];
						echo "</td><td nowrap align=right>&nbsp;";
						echo $dat['pkgName'];
						echo "</td><td nowrap align=right>&nbsp;";
						if ($dat['oID'] == "0")
						{
							echo "1 Maand";
						}
						 else
						{
							echo $dat['optionDesc'];
						}
						echo "</td><td nowrap align=left>";
						echo $dat['pName'];
						echo "</td><td nowrap align=left>";
						echo $dat['couponCode'];
						echo "</td><td nowrap align=left>";
						echo $dat['transID'];
						echo "</td><td nowrap align=left>";
						echo $dat['transStatus'];
						echo "</td><td nowrap align=left>";
						echo $dat['requestedScript'];
						echo "</td><td nowrap align=left>";
						echo $dat['customValue'];
						echo "</td><td nowrap align=left>";
						echo $dat['remoteHost'] . " (".$dat['remoteIP'].")";
						echo "</td><td nowrap align=left>";
						echo "&nbsp;";
						echo "</td>";
						echo "</tr>\n";
					}
					echo "</table>\n";
					
				}
				?>
			</div>
			<!-- / main content -->
			
			<!-- sidebar -->
			<div class="gt-sidebar">
				
			</div>
			<!-- / sidebar -->
			
		</div>
		<!-- / body -->
		
		<!-- footer -->
		<div class="gt-footer">
			<p>Copyright &copy; <?php echo $tadm->copyRight; ?> <?php echo $tadm->siteName; ?></p>
		</div>
		<!-- /footer -->
	</body>
</html>
