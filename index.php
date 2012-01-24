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
						echo "<table cellspacing=\"4\" cellpadding=\"1\"><tr><th><B>Realm Name</B></th><th>&nbsp;</th><th><B>Characters</B></th></tr>";
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
							echo "<tr><td>".$realmDAT['name']."</td><td>&nbsp;&nbsp;</td><td>$charCount</td></tr>";
						}
						echo "</table>";
						echo "<BR>";
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
