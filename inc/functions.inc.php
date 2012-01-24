<?php
class TrinityAdmin
{
	// Set Private Vars
	private $dbHost = '';
	private $dbUser = '';
	private $dbPass = '';

	function __construct()
	{
		global $siteName, $copyRight, $logondb, $char1db, $char2db, $world1db, $world2db, $sitedb;
		@include_once("inc/conf.inc.php");
		
		// Set variables
		$this->dbHost = DBHOST;
		$this->dbUser = DBUSER;
		$this->dbPass = DBPASS;
		$this->siteName = SITENAME;
		$this->copyRight = COPYRIGHT;
		$this->logondb = LOGONDB;
		$this->char1db = CHAR1DB;
		$this->char2db = CHAR2DB;
		$this->world1db = WORLD1DB;
		$this->world2db = WORLD2DB;
		$this->sitedb = SITEDB;
	}

	function ExecuteSoap($remote, $user, $passwd, $cmd)
	{
        	try
        	{
                	$client = new SoapClient(NULL, array(
                        	                        "location"      => "http://$remote:7878/",
                               	                 	"uri"           => "urn:TC",
                               	                 	"style"         => SOAP_RPC,
                               	                 	"login"         => $user,
                                	                "password"      => $passwd)
                                        	);

                	$result = $client->executeCommand(new SoapParam($cmd, "command"));
        	}
        	 catch(Exception $e)
        	{
        	        return array('sent' => false, 'message' => $e->getMessage());
        	}

        	return array('sent' => true, 'message' => $result);
	}

	function MailUser($tomail, $subject, $mailData, $toUser)
	{
		$retval = false;
		$mailAddr = $tomail;

		// Define Vars
		$fromAddr = "gm@wownl.net";
		$eol = PHP_EOL;
		$seperator = md5(time());

		// Headers
		$headers = "From: \"WoWNL - GM Team\" <$fromAddr>".$eol;
		$headers .= "To: \"$toUser\" <$mailAddr>".$eol;
		$headers .= "Subject: $subject".$eol;
		$headers .= "MIME-Version: 1.0". $eol;	
		$headers .= "Content-Type: multipart/mixed; boundary=\"" . $seperator . "\"" . $eol . $eol;
		$headers .= "Content-Transfer-Encoding: 7bit" . $eol;
		$headers .= "This is a MIME encoded message." . $eol . $eol;
		
		// Message
		$headers .= "--" . $seperator . $eol;
		$headers .= "Content-Type: text/html; charset=\"iso-8859-1\"" . $eol;
		$headers .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
		$headers .= $mailData . $eol . $eol;
		$headers .= "--" . $seperator . "--";
		
		if (mail($mailAddr, $subject, "", $headers))
		{
			$retval = true;
		}

		return $retval;
	}

	function DBConn()
	{
		$dbl = mysql_connect($this->dbHost, $this->dbUser, $this->dbPass);
	
		return $dbl;
	}

	function AuthAdminUser($user, $pass)
        {
		if ($this->sitedb != "")
		{
			$qry = "SELECT id, acp ";
		}
		 else
		{
			$qry = "SELECT * ";
		}
		$qry .= "FROM ".$this->logondb.".account ";
		if ($this->sitedb == "")
		{
			$qry .= "LEFT JOIN ".$this->logondb.".account_access ON (account.id = account_access.id) ";
		}
		
		$qry .= "WHERE username = '$user' AND sha_pass_hash = SHA1(CONCAT(UPPER('$user'), ':', UPPER('$pass')))";
                $res = @mysql_query($qry, $this->DBConn());
	
                if (@mysql_num_rows($res) != "0")
                {
                        $dat = @mysql_fetch_assoc($res);
			if ($this->sitedb == "")
			{
				// NON Azer
				if ($dat['gmlevel'] >= 3)
				{
					$member['userId'] = $dat['id'];
					$result = $member;
				}
				 else
				{
					$result = "fail";
				}
			}
			 else
			{
				// Azer
				if ($dat['acp'] == "1")
				{
                        		$member['userId'] = $dat['id'];
                        		$result = $member;
				}
				 else
				{
					$result = "fail";
				}
			}
                }
                 else
                {
                        $result = "fail";
                }

                return $result;
        }
}
?>
