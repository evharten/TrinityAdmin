<?php
@include_once("inc/functions.inc.php");
session_start();

// Last modified: 24012011
$loggedin = false;
$display_login = true;

// Start UseNet
$tadm = new TrinityAdmin();
$tadm->__construct();

if (@$_GET['act'] == "logout")
{
	session_destroy();
	header("Location: ");
}
 elseif (@$_SESSION["adminId"])
{
	$display_login = false;
	$loggedin = true;
	$adminId = $_SESSION["adminId"];
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
		<title><?php echo $tadm->siteName; ?> | GM Home</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
		<meta http-equiv="Pragma" content="no-cache" />
		<link rel="stylesheet" href="css/reset-fonts.css" type="text/css" media="screen, projection" />
		<link rel="stylesheet" href="css/gt-styles.css" type="text/css" media="screen, projection" />
	</head>
	<body>
		<!-- head -->
		<div class="gt-hd clearfix">
			<!-- logo -->
			<div class="gt-logo">
				<?php echo $tadm->siteName; ?> - GM Control Panel
			</div>
			<!-- / logo -->
			
			<!-- navigation -->
			<div class="gt-nav">
				<ul>
					<?php
					if ($loggedin == true)
					{
					?>
					<li><a href="index.php"><font color=red>Home</font></a></li>
					<li><a href="accounts.php">Accounts</a></li>
					<li><a href="char_r1.php">Chars Nebuchadnezzar</a></li>
					<li><a href="char_r2.php">Chars Icharus</a></li>
					<li><a href="news.php">Site News</a></li>
					<li><a href="newsletter.php">Newsletters</a></li>
					<li><a href="logs.php">Logs</a></li>
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
				 else
				{
					if (!empty($_SESSION["adminId"]))
					{
						echo "<h3>Statistics</h3>";
						echo "<BR>";
						echo "Currently we have:<BR><BR>";
						$resU = @mysql_query("SELECT count(id) AS accounts FROM ".$tadm->logondb.".account", $tadm->DBConn());
						$datU = @mysql_fetch_assoc($resU);
						echo "<B>" . $datU['accounts'] . "</B> Accounts<BR>";
						echo "<BR>";
						echo "<table><tr><th>Realm Name</th><th>Characters</th></tr>";
						$realmQuery = @mysql_query("SELECT id, name FROM ".$tadm->logondb.".realmlist", $tadm->DBConn());
						$realmCount = @mysql_num_rows($realmQuery);
						for ($x = 0; $x < $realmCount; $x++)
						{
							$realmDAT = @mysql_fetch_assoc($realmQuery);
							if ($realmDAT['id'] == "1")
							{
								$realmDB = $tadm->char1db;
							}
							 else
							{
								$realmDB = $tadm->char2db;
							}
							$countQRY = @mysql_query("SELECT count(guid) AS chars FROM ".$realmDB.".characters", $tadm->DBConn());
							$countDAT = @mysql_fetch_assoc($countQRY);
							$charCount = $countDAT['chars'];
							echo "<tr><td>".$realmDAT['name']."</td><td>$charCount</td></tr>";
						}
						echo "</table>";
						echo "<BR>";
                        echo "<table>";
                        echo "<tr><td>";
						echo "This month we invoiced:";
                        echo "</td><td>";
						$ym = date("Ym");
                        $iStart = mktime(0, 0, 0, date("m"), 1, date("Y"));
                        $iStop = mktime(0, 0, 0, date("m"), date("t"), date("Y"));
						$resI = @mysql_query("SELECT SUM(InvoiceAmount) AS Totaal FROM Invoices WHERE InvoiceDate BETWEEN '$iStart' AND '$iStop'", $tadm->DB_Conn());
						$datI = @mysql_fetch_assoc($resI);
						echo "<B>&euro; " . $datI['Totaal'] . "</B>";					
                        echo "</td></tr>";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>";
                        echo "<tr><td>Today we had a total of </td><td>";
                        
                        $start_today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
                        $end_today = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
                        
                        $resT = @mysql_query("SELECT COUNT(InvoiceID) AS TotalOrders, SUM(InvoiceAmount) AS TotalAmount FROM Invoices WHERE InvoiceDate BETWEEN '$start_today' AND '$end_today'", $tadm->DB_Conn());
                        $datT = @mysql_fetch_assoc($resT);
                        
                        echo "<B>".$datT['TotalOrders']."</B> orders.</td></tr>";
                        if ($datT['TotalAmount'] == "")
                        {
                            $todayAmount = "0.00";
                        }
                         else
                        {
                            $todayAmount = $datT['TotalAmount'];
                        }
                        echo "<tr><td>Total amount of invoices today:</td><td><B>&euro; ".$todayAmount."</B></td></tr>";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>";
                        
                        $yesterday = date("Y", strtotime("-1 day"));
                        
                        $start_yesterday = mktime(0, 0, 0, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));
                        $end_yesterday = mktime(23, 59, 59, date("m", strtotime("-1 day")), date("d", strtotime("-1 day")), date("Y", strtotime("-1 day")));
                        
                        $resY = @mysql_query("SELECT COUNT(InvoiceID) AS TotalOrders, SUM(InvoiceAmount) AS TotalAmount FROM Invoices WHERE InvoiceDate BETWEEN '$start_yesterday' AND '$end_yesterday'", $tadm->DB_Conn());
                        $datY = @mysql_fetch_assoc($resY);
                        
                        echo "<tr><td>Yesterday we had a total of </td><td><B>".$datY['TotalOrders']."</B> orders</td></tr>";
                        if ($datY['TotalAmount'] == "")
                        {
                            $yesterdayAmount = "0.00";
                        }
                         else
                        {
                            $yesterdayAmount = $datY['TotalAmount'];
                        }
                        echo "<tr><td>Total amount of invoices yesterday:</td><td><B>&euro; ".$yesterdayAmount."</B></td></tr>";
                        
                        echo "</table>";
                            
                    
					}
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
