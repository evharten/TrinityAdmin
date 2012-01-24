<?php
@include_once("inc/functions.inc.php");
session_start();

// Last modified: 27012011

// Defaults
$display_login = true;
$display_overview = false;
$display_editview = false;
$display_deleteview = false;
$display_newview = false;
$display_resultpage = false;
$loggedin = false;

// Start UseNet
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
        case "loginAsUser":
            $display_overview = false;
            $custID = stripslashes($_GET['id']);
            $display_resultpage = true;
            
            $loggedin = True;
            $userId = $custID;
            
            $_SESSION['loggedin'] = $loggedin;
            $_SESSION['userId'] = $userId;
            
            $goUrl = BASEURL . "/myaccount.php";
            
            $resulttext .= "<meta http-equiv=\"refresh\" content=\"0;URL=$goUrl\" />";
            
            break;;
	case "testuser":
	    $display_overview = false;
	    $display_resultpage = true;
	    $custID = stripslashes($_GET['id']);
	    $currentDetails = $tadm->GetUserDetails($custID);
	    $custUser = $currentDetails['Username'];
	    $custPass = $tadm->GetPass($custID, $custUser);
	    $resulttext = "Testing useraccount $custUser ..... ";
		
	    if (require_once('Net/NNTP/Client.php'))
	    {
		// We got the NNTP Class
		$nntp = new Net_NNTP_Client();
		$nntpconn = $nntp->connect(NNTPSERVER, false, NNTPPORT, 10);
		$nntpconn = True;
		if ($nntpconn == True)
		{
			if ($serverResult = $nntp->authenticate($custUser, $custPass))
			{
				// User Authed
				$nntp->quit();
				switch ($serverResult)
				{
					case "1":
						$resulttext .= "Username and password accepted, account active";
						break;;
					case "Authentication rejected":
						$resulttext .= "Username/password incorrect or inactive account";
						break;;
					default:
						$resulttext .= "Result: $serverResult";
						break;;
				}
			}
			 else
			{
				$resulttext .= "Unable to start authentication";
			}
	    	}
		 else
		{
			$resulttext .= "Failed to connect";
		}
	    }
	     else
	    {
		// We dont have the NNTP Class
		$result = "We are missing the Net_NNTP Class";
	    }
	
	    

	    break;;
        case "edit":
            $display_overview = false;
            $custID = stripslashes($_GET['id']);
            $display_editview = true;
            // Edit stuff
            break;;
        case "processedit":
            $display_overview = false;
            $display_editview = false;
            $display_resultpage = true;
            $resulttext = "";
            $custID = stripslashes($_POST['id']);
            $currentDetails = $tadm->GetUserDetails($custID);
	    $userComments = $tadm->getComments($custID);
            
            $username = $currentDetails['Username'];
	
	    $newComments = addslashes($_POST['comment']);
	    if ($userComments != $newComments)
	    {
		if ($userComments != "None")
		{
			$updQry = "UPDATE AccountComments SET comment = '$newComments' WHERE accountID = '$custID'";
		}
		 else
		{
			$updQry = "INSERT INTO AccountComments VALUES ('$custID', '$newComments')";
		}
		
		if (@mysql_query($updQry, $tadm->DB_Conn()))
		{
			$resulttext .= "Updating comments: OK<BR>";
		}
		 else
		{
			$resulttext .= "Updating comments: Query failed<BR>";
		}

	    }
            
            // Email update
            $newEmail = stripslashes($_POST['custEmail']);
            if ($currentDetails['Email'] != $newEmail)
            {
                // Update Email address
                $updQry = "UPDATE Accounts SET Email = '$newEmail' WHERE userId = '$custID'";
                
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    $resulttext .= "Updating email address: OK<BR>";
                }
                 else
                {
                    $resulttext .= "Updating email address: Query failed.<BR>";
                }
                
            }
            
            // Include usenet provider for updates
            $pkgDetail = $tadm->GetPackageDetails(stripslashes($_POST['pkgId']));
            $newPackageType = $pkgDetail['pkgDef'];
            
            $Pakket_Provider = $tadm->GetProvider($currentDetails['atype']);
            $Pakket_temp = explode(":", $newPackageType);
            $Pakket = $Pakket_temp[0];
            if (!empty($Pakket_temp[1]))
            {
                $Pakket_Quota = $Pakket_temp[1];
            }
             else
            {
                $Pakket_Quota = "";
            }
                       
            // Include Package Provider
            if ($Pakket_Provider != "NA")
            {
                include_once("../../inc/providers/".$Pakket_Provider.".inc.php");
                $unet = new $Pakket_Provider();
            }
             else
            {
                $resulttext .= "<B>FATAL ERROR: Couldnt include Package Provider</B><BR>";
            }
            
            // Account Type Update
            if ($currentDetails['atype'] != $newPackageType)
            {
                // Update accounttype
                $resulttext .= "Updating account type:<br>";
                
                if ($Pakket_Quota == "")
                {
                    // Change zonder quota
                    
                    // Oude gegevens opsnorren
                    $oldQry = "SELECT Username, Password FROM Accounts WHERE userId = '$custID'";
                    $oldRes = @mysql_query($oldQry, $tadm->DB_Conn());
                    $oldDat = @mysql_fetch_assoc($oldRes);
                    $oldUser = $oldDat['Username'];
                    $oldPass = $oldDat['Password'];
                    
                    $removeResult = $unet->deluser($oldUser);
                    
                    if ($removeResult == "OK")
                    {
                        $resulttext .= "-- Removing user: OK<BR>";
                        $expire = (time() + (30*86400)); // 1 Maand, daarna kan je aanpassen uiteraard.
                        $addResult = $unet->newuser($oldUser, $oldPass, $expire, $Pakket);
                        
                        if ($addResult == "OK")
                        {
                            $resulttext .= "-- Creating new user: OK<BR>";
                            $newQry = "UPDATE Accounts SET AccountType = '$newPackageType', Expiry = '$expire' WHERE userId = '$custID'";
                            if (@mysql_query($newQry, $tadm->DB_Conn()))
                            {
                                $resulttext .= "-- Updating local DB: OK<BR>";
                            }
                             else
                            {
                                $resulttext .= "-- Updating local DB: Failed to update local database<BR>";
                            }
                        
                            
                        }
                         else
                        {
                            $resulttext .= "-- Creating new user: Failed to create new user ($addResult)<BR>";
                        }
                        
                    }
                     else
                    {
                        $resulttext .= "-- Removing user: failed to remove old user ($removeResult)<BR>";
                    }
                }
                 else
                {
                    // Change met Quota, get previous Quota (if any)
                    $prevBlock = stripslashes($_POST['prevBlock']);
                    
                    // Check if previous account was block account, if not then we'll change the user at usenet provider
                    if ($prevBlock != "yes")
                    {
                        // Oude gegevens opsnorren
                        $oldQry = "SELECT Username, Password FROM Accounts WHERE userId = '$custID'";
                        $oldRes = @mysql_query($oldQry, $tadm->DB_Conn());
                        $oldDat = @mysql_fetch_assoc($oldRes);
                        $oldUser = $oldDat['Username'];
                        $oldPass = $oldDat['Password'];
                    
                        $removeResult = $unet->deluser($oldUser);
                    
                        if ($removeResult == "OK")
                        {
                            $resulttext .= "-- Removing user: OK<BR>";
                            $now = time();
                            $addResult = $unet->newuser($oldUser, $oldPass, $now, $Pakket, $Pakket_Quota);
                        
                            if ($addResult == "OK")
                            {
                                $resulttext .= "-- Creating new user: OK<BR>";
                                $newQry = "UPDATE Accounts SET AccountType = '$newPackageType', Expiry = '0' WHERE userId = '$custID'";
                                if (@mysql_query($newQry, $tadm->DB_Conn()))
                                {
                                    $resulttext .= "-- Updating local DB: OK<BR>";
                                }
                                 else
                                {
                                    $resulttext .= "-- Updating local DB: Failed to update local database<BR>";
                                }
                            }
                             else
                            {
                                $resulttext .= "-- Creating new user: Failed to create new user ($addResult)<BR>";
                            }
                        
                        }
                         else
                        {
                            $resulttext .= "-- Removing user: failed to remove old user ($removeResult)<BR>";
                        }                    
                    }
                     else
                    {
                        // Alleen readerdef bijwerken
                        $updQry = "UPDATE Accounts SET AccountType = '$newPackageType', Expiry = '0' WHERE userId = '$custID'";
                        if (@mysql_query($updQry, $tadm->DB_Conn()))
                        {
                            $resulttext .= "Updating users AccountType to $newPackageType&nbsp;: OK<BR>";
                        }
                         else
                        {
                            $resulttext .= "Updating users AccountType to $newPackageType&nbsp;: Failed query<BR>";
                        }
                    }
                }
            } else {
                // Check if we need to alter 
                if ($currentDetails['Block'] == "yes")
                {
                    // Check for quota
                    if ($_POST['prevQuota'] != $_POST['quota'])
                    {
                        // Update Quota
                        $newQuota = stripslashes($_POST['quota']);
                        
                        // Update At provider
                        $quotaResult = $unet->updateuser($username, "addquota", $newQuota);
                        
                        if ($quotaResult == "OK")
                        {
                            $resulttext .= "Updating quota: OK<BR>";
                        }
                         else
                        {
                            $resulttext .= "Updating quota: Failed to update quota ($quotaResult)<BR>";
                        }
                    }
                } else {
                    // Check for expire
                    if ($_POST['expireDate'] != $_POST['prevExpireDate'])
                    {
                        // Update Expire
                        $newExpire = strtotime($_POST['expireDate']);
                        $updQry = "UPDATE Accounts SET Expiry = '$newExpire' WHERE userId = '$custID'";
                        
                        if (@mysql_query($updQry, $tadm->DB_Conn()))
                        {
                            // Update at provider
                            $result = $unet->updateuser($username, "expiry", $newExpire);
                        
                            $resulttext .= "Updating expiry: $result<BR>";
                        }
                         else
                        {
                            $resulttext .=  "Updating expiry failed in dbQuery.<BR>";
                        }
                    }
                }
            }
            
            // Suspend/Unsuspend Update
            if ($_POST['suspend'] == "on")
            {
                $newSuspend = "Y";
            }
             else
            {
                $newSuspend = "N";
            }
            
            if ($currentDetails['Suspended'] != $newSuspend)
            {
                // Update Suspend
                $updQry = "UPDATE Accounts SET accountSuspended = '$newSuspend' WHERE userId = '$custID'";
                
                if (@mysql_query($updQry, $tadm->DB_Conn()))
                {
                    if ($newSuspend == "N")
                    {
                        // Unsuspend user
                        $result = $unet->unsuspend($username);
                        $resulttext .= "Unsuspending user: $result<BR>";
                    }
                     else
                    {
                        // Suspend User
                        $result = $unet->suspend($username);
                        $resulttext .= "Suspending user: $result<BR>";
                    }
                }
                 else
                {
                    $resulttext .= "Updating suspend state: Query failed<BR>";
                }
            }       
            
            
            
            // Process form
            break;;
        case "delete":
            $display_overview = false;
            $custID = stripslashes($_GET['id']);
            $display_deleteview = true;
            $currentDetails = $tadm->GetUserDetails($custID);
            
            $hashFrom = $custID . "-" . $currentDetails['Username'];
            $hashToken = md5($hashFrom);
            
            break;;
        case "resend":
            $display_overview = false;
            $custID = stripslashes($_GET['id']);
            $display_resultpage = true;
            
            $usQry = "SELECT Username, Password, Email FROM Accounts WHERE userId = '$custID'";
            
            if ($usRes = @mysql_query($usQry, $tadm->DB_Conn()))
            {
                // Details mailen   
                $custDetails = @mysql_fetch_assoc($usRes);
                $custUsername = $custDetails['Username'];
                $custPassword = $custDetails['Password'];
                $custEmail = $custDetails['Email'];
                
                if ($tadm->MailDetails($custUsername, $custPassword, $custEmail))
                {
                    $resulttext .= "Resending details to user: Account details Send.<BR>";
                }
                 else
                {
                    $resulttext .= "Resending details to user: Failed to send details<BR>";
                }
            }
             else
            {
                $resulttext .= "Resending details to user: Failed to query details from the database.<BR>";
            }
            break;;
        case "processdelete":
            $display_overview = false;
            $display_resultpage = true;
            $resulttext = "";
            
            // Get params
            $custID = $_GET['id'];
            $custHash = $_GET['token'];
            
            // Get details
            $currentDetails = $tadm->GetUserDetails($custID);
            $Pakket_Provider = $tadm->GetProvider($currentDetails['atype']);
            
            // Eerst checken of de hash klopt
            $checkHash = md5($custID . "-" . $currentDetails['Username']);
            
            if ($checkHash == $custHash)
            {
                // Check if provider exists.
                if ($Pakket_Provider != "NA")
                {
                    // Verder met verwijderen, include provider functions
                    include_once("../../inc/providers/".$Pakket_Provider.".inc.php");
                    $unet = new $Pakket_Provider();
                
                    // Delete user at usenet provider
                    $deleteResult = $unet->deluser($currentDetails['Username']);
                    
                    if ($deleteResult == "OK")
                    {
                        // Delete from DB
                        $delQry = "DELETE FROM Accounts WHERE userId = '$custID' LIMIT 1";
                        
                        if (@mysql_query($delQry, $tadm->DB_Conn()))
                        {
                            // Delete OK
                            $resulttext .= "Deleting user: User removed from provider and our DB<BR>";
                        }
                         else
                        {
                            // Delete failed
                            $resulttext .= "Deleting user: Failed to delete user from our DB<BR>";
                        }
                        
                    }
                     else
                    {
                        $resulttext .= "Deleting user: Failed to delete user at usenet provider ($deleteResult)";
                    }
                
                }
                 else
                {
                    $resulttext .= "Deleting user: Failed, couldnt include the usenet provider<BR>";
                }
            }
             else
            {
                $resulttext .= "Deleting user: Failed, Hash didnt match, unauthorized deletion.<BR>";
            }
            break;;
        case "newuser":
            $display_overview = false;            
            $display_newview = true;
            break;;
        case "processnewuser":
            $display_overview = false;
            $display_resultpage = true;
            $resulttext = "";
            
            $custUsername = stripslashes($_POST['custUsername']);
            $custEmail = stripslashes($_POST['custEmail']);
            $custPackageID = stripslashes($_POST['pkgId']);
            $custPassword = $tadm->GeneratePass();
            
            // Check if user exists
            if ($tadm->validUser($custUsername))
            {
                // Alter username with prefix
                $custUsername = USERNAME_PREFIX . $custUsername;
                
                // Valid user go on checking email adres
                if ($tadm->validEmail($custEmail))
                {
                    // Valid Email
                    $packageDetails = $tadm->GetPackageDetails($custPackageID);
                    
                    $pkgDef = $packageDetails['pkgDef'];
                    
                    $Pakket_temp = explode(":", $pkgDef);
                    $Pakket = $Pakket_temp[0];
                    
                    if (!empty($Pakket_temp[1]))
                    {
                        $Pakket_Quota = $Pakket_temp[1];
                    }
                     else
                    {
                        $Pakket_Quota = "";
                    }

                    $Pakket_Provider = $tadm->GetProvider($pkgDef);
                    
                    // try to include Package provider
                    if ($Pakket_Provider != "NA")
                    {
                        // go on
                        include_once("../../inc/providers/".$Pakket_Provider.".inc.php");
                        $unet = new $Pakket_Provider();
                        
                        if ($Pakket_Quota == "")
                        {
                            // Aanmaken zonder Quota
                            $expire = (time() + (30*86400));
                            $createResult = $unet->newuser($custUsername, $custPassword, $expire, $Pakket);
                            
                        }
                         else
                        {
                            // Aanmaken met Quota
                            $expire = '0';
                            $createResult = $unet->newuser($custUsername, $custPassword, $expire, $Pakket, $Pakket_Quota);
                        }
                        
                        if ($createResult == "OK")
                        {
                            // User OK
                            $qry = "INSERT INTO Accounts (Username, Password, Email, AccountType, Expiry) VALUES ";
                            $qry .= "('$custUsername', '$custPassword', '$custEmail', '$pkgDef', '$expire')";
                            if (@mysql_query($qry, $tadm->DB_Conn()))
                            {
                                // User ok into DB
                                $resulttext .= "Creating user: Created user.<BR>";
                                $resulttext .= "Username: $custUsername<BR>";
                                $resulttext .= "Password: $custPassword<BR>";
                                $resulttext .= "<BR>";
                                $resulttext .= "Emailing new user account details to $custEmail.<BR>";
                                $tadm->MailDetails($custUsername, $custPassword, $custEmail);
                            }
                             else
                            {
                                $resulttext .= "Creating user: Failed to create user in local Database <BR>";
                            }
                        }
                         else
                        {
                            $resulttext .= "Creating user: Failed to create user at usenet provider ($createResult)<BR>";
                        }
                    }
                     else
                    {
                        $resulttext .= "Creating user: Failed to include package provider<BR>";
                    }
                    
                }
                 else
                {
                    $resulttext .= "Creating user: Invalid email address or user with this email adres already exists.<BR>";
                }
                
            }
             else
            {
                $resulttext .= "Creating user: username already exists<BR>";
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
		<title><?php echo $tadm->siteName; ?> | GM Accounts</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
		<meta http-equiv="Pragma" content="no-cache" />
		<link rel="stylesheet" href="css/reset-fonts.css" type="text/css" media="screen, projection" />
		<link rel="stylesheet" href="css/gt-styles.css" type="text/css" media="screen, projection" />
        <link rel="stylesheet" type="text/css" media="all" href="/css/jsDatePick_ltr.min.css" />
        <script type="text/javascript" src="/js/jquery.1.4.2.js"></script>
        <script type="text/javascript" src="/js/jsDatePick.jquery.min.1.3.js"></script>
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
                                        <li><a href="char_r2.php"><font color=red>Chars Icharus</font></a></li>
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
                    if ($display_overview == true)
                    {
                        // Params
                        if (!empty($_GET['pStart']))
                        {
                            $pStart = $_GET['pStart'];
                            } else {
                            $pStart = 0;
                            }
                    
                        $pStop = ($pStart + USERSPERPAGE);
                    
                        // Get Total user count
                        $tRes = @mysql_query("SELECT COUNT(userId) AS Total FROM Accounts", $tadm->DB_Conn());
                        $tDat = @mysql_fetch_assoc($tRes);
                        $tUsers = $tDat['Total'];
                    
                        $cFirst = '0'; 
                        $cNext = $pStart + USERSPERPAGE;
                        $cPrev = $pStart - USERSPERPAGE;
                    
					    // User list
					    $sql = "SELECT userId, Username, Email, AccountType, Expiry, LastLogin, LoginFrom, Reminded ";
					    $sql .= "FROM Accounts WHERE Email != '' ";
                    
                        // Process Search
                        if (!empty($_GET['search']))
                        {
                            $searchText = stripslashes($_GET['search']);
                            
                            // Lets see what it is email adres perhaps ?
                            $isEmail = strrpos($searchText, "@");
                            
                            if (is_bool($isEmail) && !$isEmail)
                            {
                                // No email
                                if ((substr_count($searchText,".") == 3) || (substr_count($searchText,":") > 2))
                                {
                                    // IP
                                    $sql .= "AND LoginFrom LIKE '%$searchText%' ";
                                }
                                 else
                                {
                                    // Username then
                                    $sql .= "AND Username LIKE '%$searchText%' ";
                                }
                            }
                             else
                            {
                                $sql .= "AND Email = '$searchText' ";
                            }
                            
                        }
                    
                        // Add sort
			if (isset($_GET['dir']))
			{
				$getDir = $_GET['dir'];
			}
			 else
			{
				$getDir = "";
			}
                        if ($getDir == "ASC")
                        {
                            $oldDirection = "ASC";
                            $direction = "DESC";
                        }
                         else
                        {
                            $oldDirection = "DESC";
                            $direction = "ASC";
                        }
                        
                        if (!empty($_GET['sort']))
                        {
                            $sort = stripslashes($_GET['sort']);
                            
                            $sql .= "ORDER BY $sort $oldDirection ";
                        }
                         else
                        {
                            $sql .= "ORDER BY userId ASC ";
                        }
                    
                        // Add Limit
                        $limit = USERSPERPAGE;
                        $sql .= "LIMIT $pStart, $limit";
                    
			$res = @mysql_query($sql, $tadm->DB_Conn());
			$row = @mysql_num_rows($res);
					                   
			echo "<h3>User overview</h3>-&nbsp;<a href=\"users.php?act=newuser\">New User</a><BR><BR>";
                        echo "<form action=\"users.php\" method=\"GET\">";
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
                        	echo "<B>Search:</B>&nbsp;<input type=\"text\" name=\"search\" value=\"$searchText\">\n";
			}
			 else
			{
                        	echo "<B>Search:</B>&nbsp;<input type=\"text\" name=\"search\" value=\"\">\n";
			}
                        echo "</form><BR><BR>";
					    echo "<table border=1 cellspacing=2 cellpadding=2>";
                        if (!empty($_GET['pStart']))
                        {
                            $startLine = "&pStart=".$pStart;
                        }
                         else
                        {
                            $startLine = "";
                        }
                        
                        if (!empty($_GET['search']))
                        {
                            $startLine .= "&search=".$searchText;
                        }
					    echo "<tr><th nowrap><b><a href=\"users.php?sort=userId&dir=".$direction.$startLine."\">userID</a></b>&nbsp;</th>";
                        echo "<th nowrap><b><a href=\"users.php?sort=Username&dir=".$direction.$startLine."\">Username</a></b>&nbsp;</th>";
                        echo "<th nowrap><b><a href=\"users.php?sort=Email&dir=".$direction.$startLine."\">E-mail Address</a></b>&nbsp;</th>";
                        echo "<th nowrap><b><a href=\"users.php?sort=AccountType&dir=".$direction.$startLine."\">Account Type</a></b>&nbsp;</th>";
                        echo "<th nowrap><b>Expires on / Quota remaining</b>&nbsp;</th>";
                        echo "<th nowrap><b><a href=\"users.php?sort=accountSuspended&dir=".$direction.$startLine."\">Is Suspended ?</a></b>&nbsp;</th>";
                        echo "<th nowrap><b><a href=\"users.php?sort=LastLogin&dir=".$direction.$startLine."\">Last login</a></b>&nbsp;</th>";
                        echo "<th nowrap><b>Login from</b>&nbsp;</th>";
                        echo "<th>&nbsp;</th></tr>";
	
					    // display users
					    for ($x = 0; $x < $row; $x++)
					    {
						    $dat = @mysql_fetch_assoc($res);
						    $userDetails = $tadm->GetUserDetails($dat['userId']);
						    echo "<tr>";
						    echo "<td nowrap align=right>";
						    echo $dat['userId'];
						    echo "&nbsp;</td><td nowrap align=left>";
						    echo $dat['Username'];
						    echo "&nbsp;</td><td nowrap align=left>";
						    echo $dat['Email'];
						    echo "&nbsp;</td><td nowrap align=right>";
						    $pkgDef = $dat['AccountType'];
						    $pkgId = $tadm->GetPackageID($pkgDef);
						    $pkgDetails = $tadm->GetPackageDetails($pkgId);
						    echo $pkgDetails['pkgName'];
						    echo "&nbsp;</td><td nowrap align=right>";
						    if ($userDetails['Block'] == "yes")
						    {
							    echo $userDetails['quota_remaining'];
						    }
						     else
						    {
							    if (time() < $dat['Expiry'])
							    {
								    echo "<font color=green>";
							    }
							     else
							    {
								    echo "<font color=red>";
							    }
							    echo strftime("%H:%M:%S - %d/%m/%Y", $dat['Expiry']);
							    echo "</font>";
						    }
                            echo "&nbsp;</td><td nowrap align=center>";
                            if ($userDetails['Suspended'] == "Y")
                            {
                                echo "<B>Yes</B>";
                            } else {
                                echo "";
                            }
						    echo "&nbsp;</td><td nowrap align=right>";
                            if (!empty($dat['LastLogin']))
                            {
						        echo strftime("%H:%M:%S - %d/%m/%Y", $dat['LastLogin']);
                            } else {
                                echo "Never";
                            }
						    echo "&nbsp;</td><td nowrap align=right>";
						    echo $dat['LoginFrom'];
						    echo "&nbsp;</td><td nowrap align=right>";
						    echo "<a href=\"users.php?act=edit&id=".$dat['userId']."\"><img border=0 src=\"/mgr/images/edit.gif\" width=20></a>";
                            echo "&nbsp;";
                            echo "<a href=\"users.php?act=testuser&id=".$dat['userId']."\"><img border=0 src=\"/mgr/images/testUser.jpg\" width=20 alt=\"Test User Account Details\"></a>";
                            echo "&nbsp;";
                            echo "<a href=\"users.php?act=resend&id=".$dat['userId']."\"><img border=0 src=\"/mgr/images/email.jpg\" width=20 alt=\"Resend Account Details\"></a>";
                            echo "&nbsp;";
                            echo "<a href=\"users.php?act=loginAsUser&id=".$dat['userId']."\"><img border=0 src=\"/mgr/images/key.jpg\" width=20 alt=\"Login to frontpage as this user\"></a>";
						    echo "&nbsp;";
						    echo "<a href=\"users.php?act=delete&id=".$dat['userId']."\"><img border=0 src=\"/mgr/images/delete.gif\" width=20></a>";
						    echo "</td>";
						    echo "</tr>"; 
					    }
                    
                        echo "<tr><td colspan=8 align=\"center\">";
                        if (!empty($sort))
                        {
                            $sortLine = "&sort=$sort&dir=$oldDirection";
                            if ($searchText != "")
                            {
                                $sortLine .= "&search=".$searchText;
                            }
                        }
			 else
			{
				$sortLine = "";
			}
                        // First
                        echo "<a href=\"users.php?pStart=".$cFirst.$sortLine."\"><img src=\"/mgr/images/first.gif\" border=\"0\"></a>";
                        // Prev
                        if ($pStart != 0)
                        {
                            if (!($cPrev < 0))
                            {
                                echo "<a href=\"users.php?pStart=".$cPrev.$sortLine."\"><img src=\"/mgr/images/prev.gif\" border=\"0\"></a>";
                            }
                        }
                        // Next
                        if (!($cNext > $tUsers))
                        {
                            echo "<a href=\"users.php?pStart=".$cNext.$sortLine."\"><img src=\"/mgr/images/next.gif\" border=\"0\"></a>";
                        }
                        // Last
                        echo "</td></tr>";
					    echo "</table>";

				    }
                     elseif ($display_editview == true)
                    {
                        // Request current details
                        $userDetails = $tadm->GetUserDetails($custID);
			$userComments = $tadm->getComments($custID);
                        
                        echo "<BR><h2>Edit userID $custID</h2><BR><BR>\n";
                        echo "<form action=\"users.php\" method=\"POST\">\n";
                        echo "<input type=\"hidden\" name=\"id\" value=\"$custID\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processedit\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2>\n";
                        
                        echo "<tr><td>Username</td><td>&nbsp;&nbsp;" . $userDetails['Username'] . "</td></tr>\n";
                        echo "<tr><td>E-Mail Address</td><td>&nbsp;&nbsp;<input size=\"40\" type=\"text\" name=\"custEmail\" value=\"" . $userDetails['Email'] . "\"></td></tr>\n";
                        echo "<tr><td>Account Type</td><td>&nbsp;&nbsp;<select name=\"pkgId\">\n";
                        
                        $pkgDef = $userDetails['atype'];
                        
                        $pSQL = "SELECT pkgId, pkgDef, pkgName FROM Packages ORDER BY pkgId ASC";
                        $pRES = @mysql_query($pSQL, $tadm->DB_Conn());
                        $pROW = @mysql_num_rows($pRES);
                        
                        for ($x = 0; $x < $pROW; $x++)
                        {
                            $pDAT = @mysql_fetch_assoc($pRES);
                            if ($pDAT['pkgDef'] == $pkgDef)
                            {
                                echo " <option value=\"".$pDAT['pkgId']."\" selected>".$pDAT['pkgName']."</option>\n";
                            }
                             else 
                            {
                                echo " <option value=\"".$pDAT['pkgId']."\">".$pDAT['pkgName']."</option>\n";     
                            }
                        }
                        echo "</select></td></tr>\n";
                        
                        if ($userDetails['Block'] == "yes")
                        {
                            // Block Account
                            echo "<tr><td>Quota Remaining</td><td>&nbsp;\n";
                            $quotaRemaining = round(((($userDetails['quotaRemaining']/1024)/1024)/1024), 2);
                            echo "<input type=\"hidden\" name=\"prevBlock\" value=\"yes\">\n";
                            echo "<input type=\"hidden\" name=\"prevQuota\" value=\"$quotaRemaining\">\n";
                            echo "<input type=\"text\" size=\"12\" name=\"quota\" value=\"$quotaRemaining\"> GB (digits after the . are in 100MB ( eg. 0.1 is 100MB ))\n";
                            echo "</td></tr>\n";
			    echo "<tr><td colspan=\"2\"><font style=\"color:red\">If adding quota, just fill in the extra amount!</font></td></tr>\n";
                        }
                         else
                        {
                            // Normal Account
                            echo "<tr><td>Expire date</td><td>&nbsp;\n";
                            echo "<input type=\"hidden\" name=\"prevBlock\" value=\"no\">\n";
                            
                            $expireDay = date("d", $userDetails['aexpire']);
                            $expireMonth = date("m", $userDetails['aexpire']);
                            $expireYear = date("Y", $userDetails['aexpire']);
                            
                            echo "<input type=\"hidden\" name=\"prevExpireDate\" value=\"$expireDay-$expireMonth-$expireYear\">\n";
                            echo "<input type=\"text\" name=\"expireDate\" value=\"$expireDay-$expireMonth-$expireYear\" id=\"expireDate\">\n";
                            // date picker
                            echo "<script type=\"text/javascript\">\n";
                            echo "    window.onload = function()\n";
                            echo "    {\n";
                            echo "      new JsDatePick(\n";
                            echo "      {\n";
                            echo "          useMode:2,\n";
                            echo "          target:\"expireDate\",\n";
                            echo "          dateFormat:\"%d-%m-%Y\",\n";
                            echo "          selectedDate:{\n";
                            echo "              day:".$expireDay.",\n";
                            echo "              month:".$expireMonth.",\n";
                            echo "              year:".$expireYear."\n";
                            echo "          },\n";
                            echo "          yearsRange:[".date("Y").",".(date("Y")+5)."],\n";
                            echo "          limitToToday:false,\n";
                            echo "          /*cellColorScheme:\"beige\",*/\n";
                            echo "          dateFormat:\"%d-%m-%Y\",\n";
                            echo "          imgPath:\"/img/datepicker/\",\n";
                            echo "          weekStartDay:1\n";
                            echo "      });\n";
                            echo "    };\n";
                            echo "    </script>\n";
                            echo "</td></tr>\n";
                        }
                                                
                        echo "<tr><td>Suspend?</td><td>&nbsp;\n";
                        if ($userDetails['Suspended'] == "Y")
                        {
                            // Suspended
                            echo "<input type=\"checkbox\" name=\"suspend\" checked>";
                        } else {
                            // Not Suspended
                            echo "<input type=\"checkbox\" name=\"suspend\">";
                        }
                        echo "</td></tr>\n";                                                
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
			echo "<tr><td colspan=2><B>Comments</B>:</td></tr>";
			echo "<tr><td colspan=2><TEXTAREA NAME=\"comment\" ROWS=\"4\" COLS=\"40\">".$userComments."</TEXTAREA></td></tr>";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Commit changes\">\n";
                        echo "</td></tr>\n";
                        echo "</table></form>\n";
                    }
                     elseif ($display_deleteview == true)
                    {
                        echo "&nbsp;<h4>Delete userID $custID</h4><BR><BR>";
                        echo "<center>";
                        echo "<h3>Are you sure you want to delete user ".$currentDetails['Username']." ?</h3><BR><BR>";
                        echo "<a href=\"users.php?act=processdelete&id=".$custID."&token=".$hashToken."\">Yes</a>";
                        echo "&nbsp;|&nbsp;";
                        echo "<a href=\"users.php\">No</a>";
                    }                    
                     elseif ($display_newview == true)
                    {
                        echo "&nbsp;<h3>Create new user</h4><BR><BR>";
                        echo "<form action=\"users.php\" method=\"POST\">\n";
                        echo "<input type=\"hidden\" name=\"act\" value=\"processnewuser\">\n";
                        echo "<table border=1 cellspacing=2 cellpadding=2>\n";
                        
                        echo "<tr><td>Username</td><td>&nbsp;&nbsp;<input size=\"40\" type=\"text\" name=\"custUsername\"> (prefix ".USERNAME_PREFIX." will be added automaticly.)</td></tr>\n";
                        echo "<tr><td>E-Mail Address</td><td>&nbsp;&nbsp;<input size=\"40\" type=\"text\" name=\"custEmail\"></td></tr>\n";
                        echo "<tr><td>Account Type</td><td>&nbsp;&nbsp;<select name=\"pkgId\">\n";
                                               
                        $pSQL = "SELECT pkgId, pkgDef, pkgName FROM Packages ORDER BY pkgId ASC";
                        $pRES = @mysql_query($pSQL, $tadm->DB_Conn());
                        $pROW = @mysql_num_rows($pRES);
                        
                        for ($x = 0; $x < $pROW; $x++)
                        {
                            $pDAT = @mysql_fetch_assoc($pRES);
                            echo " <option value=\"".$pDAT['pkgId']."\">".$pDAT['pkgName']."</option>\n";     
                        }
                        echo "</select>(*)</td></tr>\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2 align=\"center\">\n";
                        echo "<input type=\"submit\" name=\"commit\" value=\"Create User\">\n";
                        echo "</td></tr>\n";
                        echo "<tr><td colspan=2>&nbsp;</td></tr>\n";
                        echo "<tr><td colspan=2>(*) <B>Accounts will be created in defaults,<BR>this means block with their normal size and flat accounts with 1 Month of expire.</B></td></tr>";
                        echo "</table></form>\n";
                    }
                     elseif ($display_resultpage == true)
                    {
                        echo "&nbsp;<h4>Resultpage</h4><BR><BR>";
                        echo $resulttext;
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
			<p>Copyright &copy; <?php echo $tadm->copyRight; ?> <?php echo $wwnl->siteName; ?></p>
		</div>
		<!-- /footer -->
	</body>
</html>
