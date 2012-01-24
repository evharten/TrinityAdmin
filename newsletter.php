<?php
@include_once("inc/functions.inc.php");
session_start();

// Last modified: 13042011

// Defaults
$display_login = true;
$display_overview = false;
$display_editview = false;
$display_deleteview = false;
$display_newview = false;
$display_resultpage = false;

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
        case "edit":
            $display_overview = false;
            $couponID = $_GET['id'];
            $display_editview = true;
            
            // Get Coupon Data
            $couponSQL = "SELECT * FROM Coupons WHERE couponID = '$couponID'";
            $couponRES = @mysql_query($couponSQL, $tadm->DB_Conn());
            $couponDAT = @mysql_fetch_assoc($couponRES);
            
            break;;
        case "processedit":
            $display_overview = false;
            $display_resultpage = true;
            $couponID = stripslashes($_POST['id']);
            
            $couponCode = stripslashes($_POST['couponCode']);
            $couponPercentage = stripslashes($_POST['couponPercentage']);
            $couponAmount = stripslashes($_POST['couponAmount']);
            $couponPackage = stripslashes($_POST['couponPackage']);
            $couponComment = stripslashes($_POST['couponComment']);
	    $couponUsertype = stripslashes($_POST['couponUsertype']);
            
            // Get Coupon Data
            $couponSQL = "SELECT * FROM Coupons WHERE couponID = '$couponID'";
            $couponRES = @mysql_query($couponSQL, $tadm->DB_Conn());
            $couponDAT = @mysql_fetch_assoc($couponRES);

            if ($couponDAT['couponCode'] != $couponCode)
            {
                // Update code
                $updQry = "UPDATE Coupons SET couponCode = '$couponCode' WHERE couponID='$couponID'";
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Updating couponCode: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating couponCode: Failed query.<BR>";
                }
            }            
	
	    if ($couponDAT['couponUsertype'] != $couponUsertype)
	    {
		// Update code
		$updQry = "UPDATE Coupons SET couponUsertype = '$couponUsertype' WHERE couponID='$couponID'";
		if (@mysql_query($updQry, $tadm->DB_Conn()))
		{
		    $resulttext .= "Updating couponUsertype: OK<BR>";
		}
		 else
		{
		    $resulttext .= "Updating couponUsertype: Failed query.<BR>";
		}
	    }
            
            if (($couponPercentage != "" || $couponPercentage > 0) && ($couponDAT['couponPercentage'] != $couponPercentage))
            {
                // Update Couponpercentage
                $updQry = "UPDATE Coupons SET couponPercentage = '$couponPercentage', couponAmount = '0.00' WHERE couponID = '$couponID'";
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Updating couponPercentage: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating couponPercentage: Failed query<BR>";
                }                
            }
             else
            {
                // Update couponAmount ( if needed )
                if ($couponDAT['couponAmount'] != $couponAmount)
                {
                    $updQry = "UPDATE Coupons SET couponPercentage = '0', couponAmount = '$couponAmount' WHERE couponID = '$couponID'";
                    if (@mysql_query($updQry, $tadm->DB_Conn()))
                    {
                        $resulttext .= "Updating couponAmount: OK<BR>";                       
                    }
                     else
                    {
                        $resulttext .= "Updating couponAmount: Failed query<BR>";
                    }
                }
            }
            
            if ($couponDAT['couponPackage'] != $couponPackage)
            {
                // Update valid on
                $updQry = "UPDATE Coupons SET couponPackage = '$couponPackage' WHERE couponID = '$couponID'";
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Updating couponPackage: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating couponPackage: Failed query<BR>";
                }
            }           
            
            if ($couponDAT['couponComment'] != $couponComment)
            {
                // Update comment
                $updQry = "UPDATE Coupons SET couponComment = '$couponComment' WHERE couponID = '$couponID'";
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Updating couponComment: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating couponComment: Failed query<BR>";
                }
            }
            
            break;;
        case "delete":
            $display_overview = false;
            $display_deleteview = true;
            $couponID = $_GET['id'];

            // Get Coupon Data
            $couponSQL = "SELECT couponCode FROM Coupons WHERE couponID = '$couponID'";
            $couponRES = @mysql_query($couponSQL, $tadm->DB_Conn());
            $couponDAT = @mysql_fetch_assoc($couponRES);
                       
            $hashFrom = $couponID . "-" . $couponDAT['couponCode'];;
            $hashToken = md5($hashFrom);
            
            break;;
        case "processdelete":
            $display_overview = false;
            $display_resultpage = true;
            $couponID = $_GET['id'];
            $couponHash = $_GET['token'];
            
            // Get Coupon Data
            $couponSQL = "SELECT * FROM Coupons WHERE couponID = '$couponID'";
            $couponRES = @mysql_query($couponSQL, $tadm->DB_Conn());
            $couponDAT = @mysql_fetch_assoc($couponRES);
            
            $checkHash = md5($couponID . "-" . $couponDAT['couponCode']);
            
            if ($couponHash == $checkHash)
            {
                $delQry = "DELETE FROM Coupons WHERE couponID = '$couponID'";
                if (@mysql_query($delQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Deleting coupon: Deleted!<BR>";                    
                }
                 else
                {
                    $resulttext .= "Deleting coupon: Failed to execute query.<BR>";
                }
            }            
             else
            {
                $resulttext .= "Deleting coupon: Failed due to unauthorized attempt.<BR>";
            }
            
            break;;
        case "newcoupon":
            $display_newview = true;
            $display_overview = false;
            
            break;;
        case "processnew":
            $display_overview = false;
            $display_resultpage = true;
            
            $couponCode = stripslashes($_POST['couponCode']);
            $couponPercentage = stripslashes($_POST['couponPercentage']);
            $couponAmount = stripslashes($_POST['couponAmount']);
            $couponPackage = stripslashes($_POST['couponPackage']);
            $couponUsertype = stripslashes($_POST['couponUsertype']);
            $couponComment = stripslashes($_POST['couponComment']);
            
            if (!empty($couponCode) && (!empty($couponPercentage) || !empty($couponAmount)))
            {
                if ($couponPercentage != "" && $couponPercentage > 0)
                {
                    // Percentage gebruiken
                    $insQry = "INSERT INTO Coupons VALUES ('', '$couponCode', '$couponPercentage', '0.00', '$couponPackage', '$couponUsertype', '$couponComment')";
                }
                 else
                {
                    // Amount gebruiken
                    $insQry = "INSERT INTO Coupons VALUES ('', '$couponCode', '0', '$couponAmount', '$couponPackage', '$couponUsertype', '$couponComment')";
                }
                
                if (@mysql_query($insQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Create coupon: Coupon created.<BR>";
                }
                 else
                {
                    $resulttext .= "Create coupon: Failed to execute insert query<BR>";
                }
            }
             else
            {
                $resulttext .= "Create coupon: Failed, no couponCode given and/or no Percentage and/or no Amount<BR>";
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
		<title><?php echo $tadm->siteName; ?> | Newsletters</title>
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
                                        <li><a href="newsletter.php"><font color=red>Newsletters</font></a></li>
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
				 elseif ($display_overview == true)
				{
					echo "<h3>Discount codes overview</h3>-&nbsp;<a href=\"coupons.php?act=newcoupon\">New Discount Code</a><BR><BR>";
					echo "<table border=1 cellspacing=2 cellpadding=2>";
					echo "<tr>";
					echo "<th><B>couponID</B>&nbsp;</th>";
					echo "<th><B>Description</B>&nbsp;</th>";
					echo "<th><B>Discount code</B>&nbsp;</th>";
					echo "<th><B>Discount percentage</B>&nbsp;</th>";
					echo "<th><B>Discount amount ex.vat</B>&nbsp;</th>";
					echo "<th><B>Valid on</B>&nbsp;</th>";
                    echo "<th><B>Customertype</B>&nbsp;</th>";
					echo "<th>&nbsp;</th>";
					echo "</tr>";
					
					// Query
					$sql = "SELECT couponID, couponComment, couponCode, couponPercentage, couponAmount, couponPackage, couponUsertype ";
					$sql .= "FROM Coupons";
					$res = @mysql_query($sql, $tadm->DB_Conn());
					$row = @mysql_num_rows($res);
					
					for ($x = 0; $x < $row; $x++)
					{
						$dat = @mysql_fetch_assoc($res);
						echo "<tr>";
						echo "<td nowrap align=left>";
						echo $dat['couponID'];
						echo "</td><td nowrap align=left>";
						echo $dat['couponComment'] . "&nbsp;";
						echo "</td><td nowrap align=right>";
						echo $dat['couponCode'];
						echo "</td><td nowrap align=right>";
						if ($dat['couponPercentage'] != "0")
						{
							echo $dat['couponPercentage'] . " %";
						}
						 else
						{
							echo "&nbsp;";
						}
						echo "</td><td nowrap align=right>";
						if ($dat['couponAmount'] != "0")
						{
							echo "&euro; " . round($dat['couponAmount'], 2);
						}
						 else
						{
							echo "&nbsp;";
						}
						echo "</td><td nowrap align=right>&nbsp;";
						if ($dat['couponPackage'] != "0")
						{
							$packageDetails = $tadm->GetPackageDetails($dat['couponPackage']);
							echo $packageDetails['pkgName'];
						}
						 else
						{
							echo "All products";
						}
                        echo "</td><td nowrap align=right>&nbsp;";
                        if ($dat['couponUsertype'] == "e")
                        {
                            echo "Existing customers";
                        }
                         elseif ($dat['couponUsertype'] == "n")
                        {
                            echo "New Customers";
                        }
                         else
                        {
                            echo "All customers";
                        }
						echo "</td><td nowrap align=left>";
						echo "<a href=\"coupons.php?act=edit&id=".$dat['couponID']."\"><img border=0 src=\"/mgr/images/edit.gif\" width=20></a>";
						echo "&nbsp;";
						echo "<a href=\"coupons.php?act=delete&id=".$dat['couponID']."\"><img border=0 src=\"/mgr/images/delete.gif\" width=20></a>";
						echo "</td>";
						echo "</tr>";
					}
					echo "</table>";
					
				}
                 elseif ($display_editview == true)
                {
                        echo "&nbsp;<h4>Edit couponID $couponID</h4><BR><BR>";
                        echo "<form action=\"coupons.php\" method=\"POST\">\n";
                        echo "<input type=\"hidden\" name=\"id\" value=\"".$couponID."\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processedit\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2>\n";
                        
                        echo "<tr><td>couponCode</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponCode\" value=\"".$couponDAT['couponCode']."\"></td></tr>\n";
                        echo "<tr><td>Discount percentage</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponPercentage\" value=\"".$couponDAT['couponPercentage']."\">(<font color=red>*</font>)</td></tr>\n";
                        
                        $vat = "1.".VAT;
                        
                        echo "<tr><td>Discount amount</td><td>&nbsp;&nbsp;<input id=\"amountBox\" type=\"text\" size=\"40\" name=\"couponAmount\" value=\"".$couponDAT['couponAmount']."\" onChange=\"JavaScript:calculateVat('$vat', 'amountBox', 'inclBox');\"> ex. vat (<font color=red>*</font>)&nbsp;&nbsp;&nbsp;EUR <input type=\"text\" size=\"10\" name=\"inclVAT\" id=\"inclBox\" value=\"" . round(($couponDAT['couponAmount'] * $vat), 2). "\" DISABLED> incl. VAT</td></tr>\n";
                        echo "<tr><td>Valid on</td><td>&nbsp;&nbsp;<select name=\"couponPackage\">\n";
                        
                        $couponPackage = $couponDAT['couponPackage'];
                        
                        $pSQL = "SELECT pkgId, pkgName FROM Packages ORDER BY pkgId ASC";
                        $pRES = @mysql_query($pSQL, $tadm->DB_Conn());
                        $pROW = @mysql_num_rows($pRES);
                        
                        if ($couponPackage == "0")
                        {
                            echo " <option value=\"0\" selected>All packages</option>\n";
                        }
                         else
                        {
                            echo " <option value=\"0\">All packages</option>\n";
                        }
                        
                        for ($x = 0; $x < $pROW; $x++)
                        {
                            $pDAT = @mysql_fetch_assoc($pRES);
                            if ($pDAT['pkgId'] == $couponPackage)
                            {
                                echo " <option value=\"".$pDAT['pkgId']."\" selected>".$pDAT['pkgName']."</option>\n";
                            }
                             else 
                            {
                                echo " <option value=\"".$pDAT['pkgId']."\">".$pDAT['pkgName']."</option>\n";     
                            }
                        }
                        echo "</select></td></tr>\n";
                        
                        $couponUsertype = $couponDAT['couponUsertype'];
                        
                        echo "<tr><td>Customertype</td><td>&nbsp;&nbsp;<select name=\"couponUsertype\">\n";
                        if ($couponUsertype == "n")
                        {
                            echo " <option value=\"n\" selected>New customers</option>\n";
                            echo " <option value=\"e\">Existing customers</option>\n";
                            echo " <option value=\"a\">All customers</option\n";
                        }
                         elseif ($couponUsertype == "e")
                        {
                            echo " <option value=\"n\">New customers</option>\n";
                            echo " <option value=\"e\" selected>Existing customers</option>\n";
                            echo " <option value=\"a\">All customers</option\n";                            
                        }
                         else
                        {
                            echo " <option value=\"n\">New customers</option>\n";
                            echo " <option value=\"e\">Existing customers</option>\n";
                            echo " <option value=\"a\" selected>All customers</option\n";
                        }
                        echo "</select></td></tr>\n";
                        echo "<tr><td>coupon Comment</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponComment\" value=\"".$couponDAT['couponComment']."\"></td></tr>\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Commit changes\">\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2>(<font color=red>*</font>) <B>Only use one field, if couponPercentage is filled in couponAmount WILL NOT be used!</B></td></tr>";
                        echo "</td></tr>\n";
                        echo "</table></form>\n";                        
                }
                 elseif ($display_deleteview == true)
                {
                        echo "&nbsp;<h4>Delete couponID $couponID</h4><BR><BR>";
                        echo "<center>";
                        echo "<h3>Are you sure you want to delete couponCode ".$couponDAT['couponCode']." ?</h3><BR><BR>";
                        echo "<a href=\"coupons.php?act=processdelete&id=".$couponID."&token=".$hashToken."\">Yes</a>";
                        echo "&nbsp;|&nbsp;";
                        echo "<a href=\"coupons.php\">No</a>";
                }                                    
                 elseif ($display_newview == true)
                {
                        echo "&nbsp;<h4>Create new Coupon</h4><BR><BR>";
                        echo "<form action=\"coupons.php\" method=\"POST\" name=\"coupons\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processnew\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2>\n";
                        
                        $tempCode = $tadm->GeneratePass(15);
                        
                        echo "<tr><td>couponCode</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponCode\" value=\"".$tempCode."\"></td></tr>\n";
                        echo "<tr><td>Discount percentage</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponPercentage\" value=\"0\">(<font color=red>*</font>)</td></tr>\n";
                        
                        $vat = "1.".VAT;
                        
                        echo "<tr><td>Discount amount</td><td>&nbsp;&nbsp;<input id=\"amountBox\" type=\"text\" size=\"15\" name=\"couponAmount\" value=\"0.00\" onChange=\"JavaScript:calculateVat('$vat', 'amountBox', 'inclBox');\"> ex. vat (<font color=red>*</font>)&nbsp;&nbsp;&nbsp;EUR <input type=\"text\" size=\"10\" name=\"inclVAT\" id=\"inclBox\" DISABLED> incl. VAT</td></tr>\n";
                        echo "<tr><td>Valid on</td><td>&nbsp;&nbsp;<select name=\"couponPackage\">\n";
                        
                        $pSQL = "SELECT pkgId, pkgName FROM Packages ORDER BY pkgId ASC";
                        $pRES = @mysql_query($pSQL, $tadm->DB_Conn());
                        $pROW = @mysql_num_rows($pRES);
                        
                        echo " <option value=\"0\">All packages</option>\n";
                        
                        for ($x = 0; $x < $pROW; $x++)
                        {
                            $pDAT = @mysql_fetch_assoc($pRES);
                            echo " <option value=\"".$pDAT['pkgId']."\">".$pDAT['pkgName']."</option>\n";     
                        }
                        echo "</select></td></tr>\n";
                        echo "<tr><td>Customertype</td><td>&nbsp;&nbsp;<select name=\"couponUsertype\">\n";
                        echo " <option value=\"n\" selected>New customers</option>\n";
                        echo " <option value=\"e\">Existing customers</option>\n";
                        echo " <option value=\"a\">All customers</option>\n";
                        echo "</select></td></tr>\n";
                        echo "<tr><td>coupon Comment</td><td>&nbsp;&nbsp;<input type=\"text\" size=\"40\" name=\"couponComment\" value=\"\"></td></tr>\n";                     
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Create new coupon\">\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2>(<font color=red>*</font>) <B>Only use one field, if couponPercentage is filled in couponAmount WILL NOT be used!</B></td></tr>";
                        echo "</td></tr>\n";
                        echo "</table></form>\n";                     
                }
                 elseif ($display_resultpage == true)
                {
                        echo "&nbsp;<h4>Resultpage</h4><BR><BR>";
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
			<p>Copyright &copy; <?php echo $tadm->copyRight; ?> <?php echo $tadm->siteName; ?></p>
		</div>
		<!-- /footer -->
	</body>
</html>
