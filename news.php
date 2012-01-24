<?php
@include_once("../../inc/functions.inc.php");
session_start();

// Last modified: 27012011

// Defaults
$display_login = true;
$display_overview = false;
$display_editview = false;
$display_deleteview = false;
$display_newview = false;
$display_resultpage = false;

// Start UseNet
$wwnl = new WoWNL();
$wwnl->__construct();

if (@$_GET['act'] == "logout")
{
	session_unregister("adminId");
	session_destroy();
	header("Location: /mgr/");
}
 elseif (@$_SESSION["adminId"])
{
	$display_login = false;
    $display_overview = true;
	$loggedin = true;
	$adminId = $_SESSION["adminId"];
    $resulttext = "";
    $vat = "1.".VAT;
    $blockReaderDef = BLOCKREADERDEF;
    
    // Check if we have to do some actions
    if (isset($_POST['act']))
    {
        $act = $_POST['act'];
    } else {
	if (isset($_GET['act']))
	{
        	$act = $_GET['act'];
	}
	 else
	{
		$act = "";
	}
    }
    
    switch ($act) {
        case "edit":
            $display_overview = false;
            $packageID = stripslashes($_GET['id']);
            $display_editview = true;
            $packageDetails = $wwnl->GetPackageDetails($packageID);
            break;;
        case "processedit":
            $display_overview = false;
            $display_resultpage = true;
            $packageID = stripslashes($_POST['id']);
            $packageDetails = $wwnl->GetPackageDetails($packageID);
            $packageName = stripslashes($_POST['pkgName']);
            $packagePrice = stripslashes($_POST['pkgPrice']);
            $packageActie = stripslashes($_POST['pkgActie']);
            $packageQuota = stripslashes($_POST['pkgQuota']);
            
            if ($packageActie == "")
            {
                $packageActie = "0.000";
            }
            
            // Check if name is different            
            if ($packageDetails['pkgName'] != $packageName)
            {
                $updQry = "UPDATE Packages SET pkgName = '$packageName' WHERE pkgId = '$packageID'";
                if (@mysql_query($updQry, $wwnl->DB_Conn()))
                {
                    $resulttext .= "Updating packageName: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating packageName: Failed query.<BR>";
                }
            }
            
            // Check if price is different
            if ($packageDetails['pkgPrice'] != $packagePrice)
            {
                $updQry = "UPDATE Packages SET pkgPrice = '$packagePrice' WHERE pkgId = '$packageID'";
                if (@mysql_query($updQry, $wwnl->DB_Conn()))
                {
                    $resulttext .= "Updating packagePrice: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating packagePrice: Failed query.<BR>";
                }
            }
            
            // Check if actie is different and defined
            if ($packageDetails['pkgActie'] != $packageActie)
            {
                $updQry = "UPDATE Packages SET pkgActie = '$packageActie' WHERE pkgId = '$packageID'";
                if (@mysql_query($updQry, $wwnl->DB_Conn()))
                {
                    $resulttext .= "Updating packageActie: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating packageActie: Failed query.<BR>";
                }
            }
            
            // Process Quota
            if (!empty($packageQuota) && $packageQuota != "")
            {
                $pkgDef = $packageDetails['pkgDef'];
                $pkgSplit = explode(":", $pkgDef);
                $baseDef = $pkgSplit[0];
                $newDef = $baseDef . ":" . $packageQuota;
                $updQry = "UPDATE Packages SET pkgDef = '$newDef', pkgQuota = '$packageQuota' WHERE pkgId = '$packageID'";
                if (@mysql_query($updQry, $wwnl->DB_Conn()))
                {
                    $resulttext .= "Updating packageQuota: OK<BR>";
                    
                    $uUpdQry = "UPDATE Accounts SET AccountType = '$newDef' WHERE AccountType = '$pkgDef'";
                    if (@mysql_query($uUpdQry, $wwnl->DB_Conn()))
                    {
                        $resulttext .= "Updating Accounts renaming old AccountType $pkgDef to $newDef&nbsp;: OK<BR>";
                    }
                     else
                    {
                        $resulttext .= "Updating Accounts renaming old AccountType $pkgDef to $newDef&nbsp;: Query Failed.<BR>";
                    }
                    
                }
                 else
                {
                    $resulttext .= "Updating packageQuota: Failed query<BR>";
                }
            }
            
            // Process options
            $optRows = stripslashes($_POST['packageOptions']);
            if ($optRows > 0)
            {
                for ($x = 0; $x < $optRows; $x++)
                {
                    $optionID = stripslashes($_POST['row_'.$x]);
                    $optionPrice = stripslashes($_POST['option_'.$optionID]);
                    $optionActie = stripslashes($_POST['option_actie_'.$optionID]);
                    
                    if ($optionPrice == "")
                    {
                        $optionPrice = "0.000";
                    }                    
                    
                    $optTemp = @mysql_query("SELECT optionPrice, optionActie FROM packageOptions WHERE optionID = '$optionID'", $wwnl->DB_Conn());
                    $optTempDat = @mysql_fetch_assoc($optTemp);
                    
                    if ($optTempDat['optionPrice'] != $optionPrice)
                    {
                        // Option price updaten
                        $updQry = "UPDATE packageOptions SET optionPrice = '$optionPrice' WHERE optionID = '$optionID'";
                        if (@mysql_query($updQry, $wwnl->DB_Conn()))
                        {
                            $resulttext .= "Updating optionID $optionID: optionPrice Updated.<BR>";
                        }
                         else
                        {
                            $resulttext .= "Updating optionID $optionID: optionPrice update failed.<BR>";
                        }
                    }
                    
                    if ($optTempDat['optionActie'] != $optionActie)
                    {
                        // optionActie updaten
                        $updQry = "UPDATE packageOptions SET optionActie = '$optionACtie' WHERE optionID = '$optionID'";
                        if (@mysql_query($updQry, $wwnl->DB_Conn()))
                        {
                            $resulttext .= "Updating optionID $optionID: optionActie Updated.<BR>";
                        }
                         else
                        {
                            $resulttext .= "Updating optionID $optionID: optionActie failed to update.<BR>";
                        }
                    }
                }
            }
            
            break;;
        case "delete":
            $display_overview = false;
            $display_deleteview = true;
            $packageID = stripslashes($_GET['id']);
            $packageDetails = $wwnl->GetPackageDetails($packageID);
            
            $hashFrom = $packageID . "-" . $packageDetails['pkgName'];
            $hashToken = md5($hashFrom);            
            
            break;;
        case "processdelete":
            $display_overview = false;
            $display_resultpage = true;
            $packageID = stripslashes($_GET['id']);
            $packageDetails = $wwnl->GetPackageDetails($packageID);
            $token = stripslashes($_GET['token']);
            
            $tempToken = md5($packageID . "-" . $packageDetails['pkgName']);
            
            if ($token == $tempToken)
            {
                // Delete authorization OK
                $pkgDef = $packageDetails['pkgDef'];
                
                $usersWithDef = @mysql_num_rows(@mysql_query("SELECT userId FROM Accounts WHERE AccountType = '$pkgDef'", $wwnl->DB_Conn()));
                
                if ($usersWithDef > 0)
                {
                    $resulttext .= "Deleting packageID $packageID&nbsp;: Failed, there are still <B>$usersWithDef</B> user(s) with this packageID.<BR>";                 
                }
                 else
                {
                    // We can delete it!
                    $delQry = "DELETE FROM Packages WHERE pkgId = '$packageID'";
                    if (@mysql_query($delQry, $wwnl->DB_Conn()))
                    {
                        $resulttext .= "Deleting packageID $packageID&nbsp;: OK";
                    }
                     else
                    {
                        $resulttext .= "Deleting packageID $packageID&nbsp;: Failed Query<BR>";
                    }
                }
            }
             else
            {
                $resulttext .= "Deleting packageID $packageID&nbsp;: Failed due to unauthorized attempt.<BR>";
            }
            
            break;;
        case "newpackage":
            $display_newview = true;
            $display_overview = false;
            
            break;;
        case "processnew":
            $display_overview = false;
            $display_resultpage = true;
            
            // Get variables
            $packageName = stripslashes($_POST['pkgName']);
            $packagePrice = stripslashes($_POST['pkgPrice']);
            $packageActie = stripslashes($_POST['pkgActie']);
            $packageQuota = stripslashes($_POST['pkgQuota']);
            
            $newReaderDef = $blockReaderDef . ":" . $packageQuota;
            
            if ($packageActie == "" || $packageActie == "0")
            {
                $packageActie = "0.000";
            }
            
            $newQry = "INSERT INTO Packages VALUES ";
            $newQry .= "('', '$packageName', '$newReaderDef', '$packageQuota', '$packagePrice', 'xsnews', '$packageActie')";
           
            if (@mysql_query($newQry, $wwnl->DB_Conn()))
            {
                $resulttext .= "Creating new block account: OK<BR>";
            }
             else
            {
                $resulttext .= "Creating new block account: Failed query<BR>";
            }
            
            break;;
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
	
	$loginResult = $wwnl->AuthAdminUser($userName, $userPass);
	
	if ($loginResult != "fail")
	{
		$_SESSION['adminId'] = $loginResult['userId'];
		header("Location: /mgr/index.php");
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
		<title><?php echo $wwnl->siteName; ?> | News</title>
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
				<?php echo $wwnl->siteName; ?> - Admin Control Panel
			</div>
			<!-- / logo -->
			
			<!-- navigation -->
			<div class="gt-nav">
				<ul>
					<?php
					if ($loggedin == true)
					{
					?>
                                        <li><a href="/mgr/index.php">Home</a></li>
                                        <li><a href="/mgr/accounts.php">Accounts</a></li>
                                        <li><a href="/mgr/char_r1.php">Chars Nebuchadnezzar</a></li>
                                        <li><a href="/mgr/char_r2.php">Chars Icharus</a></li>
                                        <li><a href="/mgr/news.php"><font color=red>Site News</font></a></li>
                                        <li><a href="/mgr/newsletter.php">Newsletters</a></li>
                                        <li><a href="/mgr/logs.php">Logs</a></li>
                                        <li><a href="/mgr/index.php?act=logout">Logout</a></li>
					<?php
					}
					 else
					{
					?>
					<li><a href="/mgr/index.php"><font color=red>Login</font></a></li>
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
				<form action="/mgr/index.php" method="POST">
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
                    if ($blockReaderDef != "BLOCKREADERDEF")
                    {
					    echo "<h3>Product overview</h3>-&nbsp;<a href=\"/mgr/products.php?act=newpackage\">New Block Account</a><BR><BR>\n";
                    }
                     else
                    {
                        echo "<h3>Product overview</h3>\n";
                    }
					echo "<table border=1 cellspacing=2 cellpadding=2>";
					echo "<tr>";
					echo "<th><B>productID</B>&nbsp;</th>";
					echo "<th><B>Description</B>&nbsp;</th>";
					echo "<th><B>Package definition</B>&nbsp;</th>";
					echo "<th><B>Package Quota</B>&nbsp;</th>";
					echo "<th><B>Package price ex.vat</B>&nbsp;</th>";
					echo "<th><B>Package Provider</B>&nbsp;</th>";
					echo "<th><B>Package promotion price ex.vat</B>&nbsp;</th>";
					echo "<th><B>SortID</B>&nbsp;</th>";
					echo "<th>&nbsp;</th>";
					echo "</tr>";
					
					// Query
					$sql = "SELECT pkgId, pkgName, pkgDef, pkgQuota, pkgPrice, provider, pkgActie, sortID ";
					$sql .= "FROM Packages ORDER BY pkgId";
					$res = @mysql_query($sql, $wwnl->DB_Conn());
					$row = @mysql_num_rows($res);
					
					for ($x = 0; $x < $row; $x++)
					{
						$dat = @mysql_fetch_assoc($res);
						echo "<tr>";
						echo "<td nowrap align=left>";
						echo $dat['pkgId'];
						echo "</td><td nowrap align=left>";
						echo $dat['pkgName'];
						echo "</td><td nowrap align=left>";
						$pkgDef = $dat['pkgDef'];
						echo $pkgDef;
						echo "</td><td nowrap align=right>";
						if (!$dat['pkgQuota'] == "0")
						{
							echo $dat['pkgQuota'] . " GB";
						}
						 else
						{
							echo "&nbsp;";
						}
						echo "</td><td nowrap align=right>";
						echo "&euro; " . round($dat['pkgPrice'], 2);
						echo "</td><td nowrap align=right>";
						echo $dat['provider'];
						echo "</td><td nowrap align=right>";
						if ($dat['pkgActie'] != "0.000")
						{
							echo "&euro; " . round($dat['pkgActie'], 2);
						}
						 else
						{
							echo "&nbsp;";
						}
						echo "</td><td nowrap align=right>";
						echo $dat['sortID'];
						echo "</td><td nowrap align=left>";
						echo "<a href=\"/mgr/products.php?act=edit&id=".$dat['pkgId']."\"><img border=0 src=\"/mgr/images/edit.gif\" width=20></a>";
						echo "&nbsp;";
						echo "<a href=\"/mgr/products.php?act=delete&id=".$dat['pkgId']."\"><img border=0 src=\"/mgr/images/delete.gif\" width=20></a>";
						echo "</td>";
						echo "</tr>";
					}
					echo "</table>";
				}	
                 elseif ($display_editview == true)
                {
                        echo "&nbsp;<h4>Edit packageID $packageID</h4><BR><BR>";
                        echo "<form action=\"/mgr/products.php\" method=\"POST\">\n";
                        echo "<input type=\"hidden\" name=\"id\" value=\"".$packageID."\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processedit\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2 style=\"table.width: 200px;\">\n";
                        echo "<tr><td>Package Name</td><td>&nbsp;<input type=\"text\" size=\"50\" name=\"pkgName\" value=\"".$packageDetails['pkgName']."\"></td></tr>\n";
                        
                        $pkgPriceIncl = round(($packageDetails['pkgPrice'] * $vat), 2);
                        $pkgActieIncl = round(($packageDetails['pkgActie'] * $vat), 2);
                        echo "<tr><td>Package Price</td><td>&nbsp;<input type=\"text\" size=\"15\" id=\"pkgPrice\" name=\"pkgPrice\" value=\"".$packageDetails['pkgPrice']."\" onChange=\"JavaScript:calculateVat('$vat', 'pkgPrice', 'pkgPriceIncl');\">&nbsp;<input type=\"text\" name=\"pkgPriceIncl\" size=\"10\" id=\"pkgPriceIncl\" value=\"".$pkgPriceIncl."\" DISABLED> Incl. VAT</td></tr>\n";
                        echo "<tr><td>Promotional Price</td><td>&nbsp;<input type=\"text\" size=\"15\" id=\"pkgActie\" name=\"pkgActie\" value=\"".$packageDetails['pkgActie']."\" onChange=\"JavaScript:calculateVat('$vat', 'pkgActie', 'pkgActieIncl');\">&nbsp;<input type=\"text\" name=\"pkgActieIncl\" size=\"10\" id=\"pkgActieIncl\" value=\"".$pkgActieIncl."\" DISABLED> Incl. VAT&nbsp;(<font color=red>*</font>)</td></tr>\n";
                        
                        // Check if we got a Quota, if so, lets bring it on!
                        if ($packageDetails['pkgQuota'] != "")
                        {
                            // We got quota
                            echo "<tr><td>Package Quota</td><td>\n";
                            echo "&nbsp;<input type=\"text\" size=\"10\" name=\"pkgQuota\" value=\"".$packageDetails['pkgQuota']."\"> in full GBs\n";
                            echo "</td><tr>\n";
                        }
                        
                        // Check for options
                        $optSQL = "SELECT optionDesc, optionPrice, optionActie, optionID FROM packageOptions WHERE packageID = '$packageID'";
                        $optRES = @mysql_query($optSQL, $wwnl->DB_Conn());
                        $optROW = @mysql_num_rows($optRES);
                        
                        echo "<tr><td colspan=2>&nbsp;";
                        echo "<input type=\"hidden\" name=\"packageOptions\" value=\"$optROW\">";
                        echo "</td></tr>\n";
                        
                        if ($optROW > 0)
                        {
                            echo "<tr><td colspan=2>&nbsp;packageOptions:<BR>";
                            echo "<center>";
                            echo "&nbsp;<table border=1 cellspacing=2 cellpadding=2>\n";
                            echo "<tr><th><B>optionDescription</B></th><th><B>option Price</B></th><th><b>optionPrice Incl. VAT</B></th><th><B>option Promo Price</B></th><th><B>option Promo Price Incl. VAT</B></th></tr>\n";
                            for ($x = 0; $x < $optROW; $x++)
                            {
                                $optDAT = @mysql_fetch_assoc($optRES);
                                $optID = $optDAT['optionID'];
                                
                                echo "<tr><td>\n";
                                $rowIdent = "row_".$x;
                                echo "<input type=\"hidden\" name=\"$rowIdent\" value=\"".$optID."\">\n";
                                echo $optDAT['optionDesc'];
                                echo "</td><td>\n";
                                $fieldName = "option_".$optID;
                                echo "&nbsp;&euro;<input type=\"text\" size=\"15\" id=\"$fieldName\" name=\"$fieldName\" value=\"".$optDAT['optionPrice']."\" onChange=\"JavaScript:calculateVat('$vat', '$fieldName', 'inc_$fieldName');\">\n";
                                echo "</td><td align=center>\n";
                                $optionPriceIncl = round(($optDAT['optionPrice'] * $vat), 2);
                                echo "&nbsp;&euro;<input type=\"text\" size=\"10\" id=\"inc_".$fieldName."\" name=\"inc_".$fieldName."\" value=\"".$optionPriceIncl."\" DISABLED\n";
                                echo "</td><td>\n";
                                $fieldActieName = "option_actie_".$optID;
                                echo "&nbsp;&euro;<input type=\"text\" size=\"15\" id=\"$fieldActieName\" name=\"$fieldActieName\" value=\"".$optDAT['optionActie']."\" onChange=\"JavaScript:calculateVat('$vat', '$fieldActieName', 'inc_$fieldActieName');\">\n";
                                echo "</td><td align=center>\n";
                                $optionActieIncl = round(($optDAT['optionActie'] * $vat), 2);
                                echo "&nbsp;&euro;<input type=\"text\" size=\"10\" id=\"inc_".$fieldActieName."\" name=\"inc_".$fieldActieName."\" value=\"".$optionActieIncl."\" DISABLED\n";                                
                                echo "</td></tr>\n";
                                
                            }
                            
                            echo "</table>\n";
                            echo "</center>\n";
                            echo "</td></tr>\n";
                        }
                        
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Commit changes\">\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2>(<font color=red>*</font>) Set to 0.00 to disable promotion or leave empty.</td></tr>";
                        echo "</td></tr>\n";
                        echo "</table></form>\n";                        
                        
                }
                 elseif ($display_newview == true)
                {
                        echo "&nbsp;<h4>Create new Block Account</h4><BR><BR>";
                        echo "<form action=\"/mgr/products.php\" method=\"POST\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processnew\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2 style=\"table.width: 200px;\">\n";
                        echo "<tr><td>Package Name</td><td>&nbsp;<input type=\"text\" size=\"50\" name=\"pkgName\"></td></tr>\n";
                                                
                        echo "<tr><td>Package Price</td><td>&nbsp;<input type=\"text\" size=\"15\" id=\"pkgPrice\" name=\"pkgPrice\" value=\"0.000\" onChange=\"JavaScript:calculateVat('$vat', 'pkgPrice', 'pkgPriceIncl');\">&nbsp;<input type=\"text\" name=\"pkgPriceIncl\" size=\"10\" id=\"pkgPriceIncl\" value=\"0.00\" DISABLED> Incl. VAT</td></tr>\n";
                        echo "<tr><td>Promotional Price</td><td>&nbsp;<input type=\"text\" size=\"15\" id=\"pkgActie\" name=\"pkgActie\" value=\"0.000\" onChange=\"JavaScript:calculateVat('$vat', 'pkgActie', 'pkgActieIncl');\">&nbsp;<input type=\"text\" name=\"pkgActieIncl\" size=\"10\" id=\"pkgActieIncl\" value=\"0.00\" DISABLED> Incl. VAT&nbsp;(<font color=red>*</font>)</td></tr>\n";
                        
                        echo "<tr><td>Package Quota</td><td>\n";
                        echo "&nbsp;<input type=\"text\" size=\"10\" name=\"pkgQuota\" value=\"0\"> in full GBs\n";
                        echo "</td><tr>\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>";    
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Create block account\">\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2>(<font color=red>*</font>) Set to 0.00 to disable promotion or leave empty.</td></tr>";
                        echo "</td></tr>\n";
                        echo "</table></form>\n";                    
                }
                 elseif ($display_deleteview == true)
                {
                        echo "&nbsp;<h4>Delete productID $custID</h4><BR><BR>";
                        echo "<center>";
                        if ($packageDetails['pkgQuota'] != "0")
                        {
                            echo "<h3>Are you sure you want to delete product ".$packageDetails['pkgName']." ?</h3><BR><BR>";
                            echo "<a href=\"/mgr/products.php?act=processdelete&id=".$packageID."&token=".$hashToken."\">Yes</a>";
                            echo "&nbsp;|&nbsp;";
                            echo "<a href=\"/mgr/products.php\">No</a>";                    
                        }
                         else
                        {
                            echo "<h3>You are NOT allowed to delete FLAT Accounts</h3><BR>";
                            echo "<BR>";
                            echo "<a href=\"/mgr/products.php\">Back</a>";                    
                        }
                }
                 elseif ($display_resultpage == true)
                {
                        echo "&nbsp;<h4>Resultpage</h4><BR><BR>";
                        echo "&nbsp;Stays empty after an edit if nothing was changed.<BR><BR>";
                        echo $resulttext;
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
			<p>Copyright &copy; <?php echo $wwnl->copyRight; ?> <?php echo $wwnl->siteName; ?></p>
		</div>
		<!-- /footer -->
	</body>
</html>
