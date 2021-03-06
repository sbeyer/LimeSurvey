<?php
/*
	#############################################################
	# >>> PHPSurveyor 	 										#
	#############################################################
	# > Author:  Jason Cleeland									#
	# > E-mail:  jason@cleeland.org								#
	# > Mail:    Box 99, Trades Hall, 54 Victoria St,			#
	# >          CARLTON SOUTH 3053, AUSTRALIA					#
	# > Date: 	 20 February 2003								#
	#															#
	# This set of scripts allows you to develop, publish and	#
	# perform data-entry on surveys.							#
	#############################################################
	#															#
	#	Copyright (C) 2003  Jason Cleeland						#
	#															#
	# This program is free software; you can redistribute 		#
	# it and/or modify it under the terms of the GNU General 	#
	# Public License as published by the Free Software 			#
	# Foundation; either version 2 of the License, or (at your 	#
	# option) any later version.								#
	#															#
	# This program is distributed in the hope that it will be 	#
	# useful, but WITHOUT ANY WARRANTY; without even the 		#
	# implied warranty of MERCHANTABILITY or FITNESS FOR A 		#
	# PARTICULAR PURPOSE.  See the GNU General Public License 	#
	# for more details.											#
	#															#
	# You should have received a copy of the GNU General 		#
	# Public License along with this program; if not, write to 	#
	# the Free Software Foundation, Inc., 59 Temple Place - 	#
	# Suite 330, Boston, MA  02111-1307, USA.					#
	#############################################################
*/

if (empty($homedir)) {die ("Cannot run this script directly");}
	
//Move current step ###########################################################################
if (!isset($_SESSION['step'])) {$_SESSION['step'] = 0;}
if (!isset($_POST['thisstep'])) {$_POST['thisstep'] = "";}
if (!isset($gl)) {$gl=array("null");}
if (isset($_POST['move']) && $_POST['move'] == " << "._PREV." ") {$_SESSION['step'] = $_POST['thisstep']-1;}
if (isset($_POST['move']) && $_POST['move'] == " "._NEXT." >> ") {$_SESSION['step']=$_POST['thisstep']+1;}
if (isset($_POST['move']) && $_POST['move'] == " "._LAST." ") {$_SESSION['step'] = $_POST['thisstep']+1;}
//CONVERT POSTED ANSWERS TO SESSION VARIABLES #################################################
if (isset($_POST['fieldnames']) && $_POST['fieldnames'])
	{
	$postedfieldnames=explode("|", $_POST['fieldnames']);
	foreach ($postedfieldnames as $pf)
		{
		if (isset($_POST[$pf])) {$_SESSION[$pf] = auto_unescape($_POST[$pf]);}
		if (!isset($_POST[$pf])) {$_SESSION[$pf] = "";} //delete any pre-existing values
		}
	}

//CHECK IF ALL MANDATORY QUESTIONS HAVE BEEN ANSWERED ############################################

//First, see if we are moving backwards, and its OK not to check:
if ($allowmandbackwards==1 && isset($_POST['move']) &&  $_POST['move'] == " << "._PREV." ") {$backok="Y";} else {$backok="N";}

//Now, we check mandatory questions if necessary
//CHECK IF ALL CONDITIONAL MANDATORY QUESTIONS THAT APPLY HAVE BEEN ANSWERED
$notanswered=addtoarray_single(checkmandatorys($backok),checkconditionalmandatorys($backok));

//CHECK PREGS
$notvalidated=checkpregs($backok);

// Check for session timeout

if (session_id()=='') 
 {
 	 echo "Sorry, your sessions seems to have expired. Please restart the survey. (1)";
 	 die();
 }

//SEE IF THIS GROUP SHOULD DISPLAY
if (isset($_POST['move']) && $_SESSION['step'] != 0 && $_POST['move'] != " "._LAST." " && $_POST['move'] != " "._SUBMIT." ")
	{
	while(checkgroupfordisplay($_SESSION['grouplist'][$_SESSION['step']-1][0]) === false)
		{
		if (isset($_POST['move']) && $_POST['move'] == " << "._PREV." ") {$_SESSION['step'] = $_SESSION['step']-1;}
		if (isset($_POST['move']) && $_POST['move'] == " "._NEXT." >> ") {$_SESSION['step']=$_SESSION['step']+1;}
		if ($_SESSION['step']-1 == $_SESSION['totalsteps'])
			{
		    $_POST['move'] = " "._LAST." ";
			break;
			}
		}
	}

//SUBMIT ###############################################################################
if (isset($_POST['move']) && $_POST['move'] == " "._SUBMIT." " && isset($_SESSION['insertarray']))
	{
	//If survey has datestamp turned on, add $localtimedate to sessions
	if ($thissurvey['datestamp'] == "Y")
		{
		if (!in_array("datestamp", $_SESSION['insertarray'])) //Only add this if it doesn't already exist
			{
		    $_SESSION['insertarray'][] = "datestamp";
			}
		$_SESSION['datestamp'] = $localtimedate;
		}

        //If survey has ipaddr turned on, add IP Address to sessions
        if ($thissurvey['ipaddr'] == "Y")                 {
                if (!in_array("ipaddr", $_SESSION['insertarray'])) //Only add this if it doesn't already exist
                        {                     $_SESSION['insertarray'][] = "ipaddr";
                        }
                $_SESSION['ipaddr'] = $_SERVER['REMOTE_ADDR'];                 }


	//DEVELOP SQL TO INSERT RESPONSES
	$subquery = createinsertquery();

	//COMMIT CHANGES TO DATABASE
	if ($thissurvey['active'] != "Y")
		{
		sendcacheheaders();
		doHeader();
		
		foreach(file("$thistpl/startpage.pstpl") as $op)
			{
			echo templatereplace($op);
			}

		//Check for assessments
		$assessments = doAssessment($surveyid);
		if ($assessments)
			{
			foreach(file("$thistpl/assessment.pstpl") as $op)
				{
				echo templatereplace($op);
				}
			}

		$completed = "<br /><strong><font size='2' color='red'>"._DIDNOTSAVE."</font></strong><br /><br />\n\n";
		$completed .= _NOTACTIVE1."<br /><br />\n";
		$completed .= "<a href='{$_SERVER['PHP_SELF']}?sid=$surveyid&amp;move=clearall'>"._CLEARRESP."</a><br /><br />\n";
		$completed .= "<font size='1'>$subquery</font>\n";
		}
	else
		{
		if (mysql_query($subquery)) //submit of responses was successful
			{
			//UPDATE COOKIE IF REQUIRED
			$idquerytext = "SELECT LAST_INSERT_ID()";
			$idquery = mysql_query ($idquerytext);
			$idquery_row = mysql_fetch_row ($idquery);
			$savedid=$idquery_row[0];
			
			if ($thissurvey['usecookie'] == "Y" && $tokensexist != 1)
				{
				$cookiename="PHPSID".returnglobal('sid')."STATUS";
				setcookie("$cookiename", "COMPLETE", time() + 31536000);
				}
			
			if (isset($_SESSION['savename'])) 
				{
				//Delete the saved survey
			    $query = "DELETE FROM {$dbprefix}saved\n"
						."WHERE sid=$surveyid\n"
						."AND identifier = '".$_SESSION['savename']."'";
				$result = mysql_query($query);
				//Should put an email to administrator here
				//if the delete doesn't work.
				}
            $content='';
			foreach(file("$thistpl/startpage.pstpl") as $op)
				{
				$content .= templatereplace($op);
				}
			//echo $thissurvey['url'];	
			//Check for assessments
			$assessments = doAssessment($surveyid);
			if ($assessments)
				{
				foreach(file("$thistpl/assessment.pstpl") as $op)
					{
				    $content .=templatereplace($op);
					}
				}

			$completed = "<br /><font size='2'><font color='green'><strong>"
						._THANKS."</strong></font><br /><br />\n\n"
						. _SURVEYREC."<br />\n"
						. "<a href='javascript:window.close()'>"
						._CLOSEWIN_PS."</a></font><br /><br />\n";

			//Update the token if needed and send a confirmation email
			if (isset($_POST['token']) && $_POST['token'])
				{
				submittokens();
				$tokenid=$_POST['token'];
				}

			//Send notification to survey administrator //Thanks to Jeff Clement http://jclement.ca
			if ($thissurvey['sendnotification'] > 0 && $thissurvey['adminemail'])
				{ 
				sendsubmitnotification($thissurvey['sendnotification']);
				}
			if (isset($_SESSION['scid'])) 
			{
				//Delete the saved survey
				$query = "DELETE FROM {$dbprefix}saved
				  	WHERE scid=".$_SESSION['scid'];
				$result=mysql_query($query);
				$query = "DELETE FROM {$dbprefix}saved_control
				  		WHERE scid=".$_SESSION['scid'];
				$result=mysql_query($query);
				//Should put an email to administrator here
				//if the delete doesn't work.
			}
			
			session_unset();
			session_destroy();


			sendcacheheaders();
			if (isset($thissurvey['autoredirect']) && $thissurvey['autoredirect'] == "Y" && $thissurvey['url'])
				{
			    //Automatically redirect the page to the "url" setting for the survey
				session_write_close();
				$redirectURL=$thissurvey['url'];
				if (isset($tokenid))
				{
				  $redirectURL=str_replace('{TOKENID}',$tokenid,$redirectURL);
				}
				header("Location: {$redirectURL}");
			    }

			doHeader();
            echo $content;

			}
		else
			{
			//Submit of Responses Failed
			$completed=submitfailed();
			}
		}
	
	foreach(file("$thistpl/completed.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	
	echo "\n<br />\n";
	foreach(file("$thistpl/endpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	exit;
	}

//LAST PHASE ###########################################################################
if (isset($_POST['move']) && $_POST['move'] == " "._LAST." " && (!isset($notanswered) || !$notanswered) && (!isset($notvalidated) && !$notvalidated))
	{
	//READ TEMPLATES, INSERT DATA AND PRESENT PAGE
    sendcacheheaders();
	doHeader();
	if ($thissurvey['private'] != "N")
		{
		$privacy="";
		foreach (file("$thistpl/privacy.pstpl") as $op)
			{
			$privacy .= templatereplace($op);
			}
		}
	foreach(file("$thistpl/startpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='phpsurveyor' name='phpsurveyor'>\n";
	
	echo "\n\n<!-- START THE SURVEY -->\n";
	foreach(file("$thistpl/survey.pstpl") as $op)
		{
		echo "\t\t".templatereplace($op);
		}
	//READ SUBMIT TEMPLATE
	foreach(file("$thistpl/submit.pstpl") as $op)
		{
		echo "\t\t\t".templatereplace($op);
		}
	
	$navigator = surveymover();
	echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
	foreach(file("$thistpl/navigator.pstpl") as $op)
		{
		echo "\t\t".templatereplace($op);
		}
	echo "\n";
	echo "\n<input type='hidden' name='thisstep' value='{$_SESSION['step']}' id='thisstep'>\n";
	echo "\n<input type='hidden' name='sid' value='$surveyid' id='sid'>\n";
	echo "\n<input type='hidden' name='token' value='$token' id='token'>\n";
	echo "\n</form>\n";
	echo "\n";
	foreach(file("$thistpl/endpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	doFooter();
	exit;
	}

//SEE IF $surveyid EXISTS ####################################################################
if ($surveyexists <1)
	{
	//SURVEY DOES NOT EXIST. POLITELY EXIT.
	foreach(file("$thistpl/startpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	echo "\t<center><br />\n";
	echo "\t"._SURVEYNOEXIST."<br />&nbsp;\n";	
	foreach(file("$thistpl/endpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	exit;
	}

//RUN THIS IF THIS IS THE FIRST TIME , OR THE FIRST PAGE ########################################
if (!isset($_SESSION['step']) || !$_SESSION['step'])
	{
	$totalquestions = buildsurveysession();
	sendcacheheaders();
	doHeader();
	foreach(file("$thistpl/startpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='phpsurveyor' name='phpsurveyor'>\n";
	
	echo "\n\n<!-- START THE SURVEY -->\n";

	foreach(file("$thistpl/welcome.pstpl") as $op)
		{
		echo "\t\t\t".templatereplace($op);
		}
	echo "\n";
	$navigator = surveymover();
	foreach(file("$thistpl/navigator.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	if ($thissurvey['active'] != "Y") 
		{
		echo "\t\t<center><font color='red' size='2'>"._NOTACTIVE."</font></center>\n";
		}
	echo "\n<input type='hidden' name='sid' value='$surveyid' id='sid'>\n";
	echo "\n<input type='hidden' name='token' value='$token' id='token'>\n";
	echo "\n</form>\n";
	foreach(file("$thistpl/endpage.pstpl") as $op)
		{
		echo templatereplace($op);
		}
	doFooter();
	exit;
	}

//******************************************************************************************************
//PRESENT SURVEY
//******************************************************************************************************

//GET GROUP DETAILS
$grouparrayno=$_SESSION['step']-1;
$gid=$_SESSION['grouplist'][$grouparrayno][0];
$groupname=$_SESSION['grouplist'][$grouparrayno][1];
$groupdescription=$_SESSION['grouplist'][$grouparrayno][2];

require_once("qanda.php"); //This should be qanda.php when finished

//Iterate through the questions about to be displayed:
$mandatorys=array();
$mandatoryfns=array();
$conmandatorys=array();
$conmandatoryfns=array();
$conditions=array();
$inputnames=array();
foreach ($_SESSION['fieldarray'] as $ia)
	{
	if ($ia[5] == $gid)
		{
		//Get the answers/inputnames
		list($plus_qanda, $plus_inputnames)=retrieveAnswers($ia);
		if ($plus_qanda)
			{
				$qanda[]=$plus_qanda;
			}
		if ($plus_inputnames)
			{
			$inputnames = addtoarray_single($inputnames, $plus_inputnames);
			}

		//Display the "mandatory" popup if necessary
		if (isset($notanswered)) 
			{
			list($mandatorypopup, $popup)=mandatory_popup($ia, $notanswered);
			}
		
		//Display the "validation" popup if necessary
		if (isset($notvalidated))
			{
			list($validationpopup, $vpopup)=validation_popup($ia, $notvalidated);
			}
		
		//Get list of mandatory questions
		list($plusman, $pluscon)=create_mandatorylist($ia);
		if ($plusman !== null)
			{
		    list($plus_man, $plus_manfns)=$plusman;
			$mandatorys=addtoarray_single($mandatorys, $plus_man);
			$mandatoryfns=addtoarray_single($mandatoryfns, $plus_manfns);
			}
		if ($pluscon !== null)
			{
		    list($plus_conman, $plus_conmanfns)=$pluscon;
			$conmandatorys=addtoarray_single($conmandatorys, $plus_conman);
			$conmandatoryfns=addtoarray_single($conmandatoryfns, $plus_conmanfns);
			}
		
		//Build an array containing the conditions that apply for this page
		$plus_conditions=retrieveConditionInfo($ia); //Returns false if no conditions
		if ($plus_conditions)
			{
		    $conditions = addtoarray_single($conditions, $plus_conditions);
			}
		} 
	} //end iteration


$percentcomplete = makegraph($_SESSION['step'], $_SESSION['totalsteps']);

//READ TEMPLATES, INSERT DATA AND PRESENT PAGE
sendcacheheaders();
doHeader();

if (isset($popup)) {echo $popup;}
if (isset($vpopup)) {echo $vpopup;}
foreach(file("$thistpl/startpage.pstpl") as $op)
	{
	echo templatereplace($op);
	}
echo "\n<form method='post' action='{$_SERVER['PHP_SELF']}' id='phpsurveyor' name='phpsurveyor'>\n";
//PUT LIST OF FIELDS INTO HIDDEN FORM ELEMENT
echo "\n\n<!-- INPUT NAMES -->\n";
echo "\t<input type='hidden' name='fieldnames' value='";
echo implode("|", $inputnames);
echo "' id='fieldnames'>\n";

echo "\n\n<!-- START THE SURVEY -->\n";
foreach(file("$thistpl/survey.pstpl") as $op)
	{
	echo "\t".templatereplace($op);
	}
echo "\n\n<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->\n";
echo "\t<script type='text/javascript'>\n";
echo "\t<!--\n";
echo "\t\tfunction checkconditions(value, name, type)\n";
echo "\t\t\t{\n";
if (isset($conditions) && is_array($conditions))
	{
	if (!isset($endzone)) {$endzone="";}
	echo "\t\t\tif (type == 'radio' || type == 'select-one')\n"
		."\t\t\t\t{\n"
		."\t\t\t\tvar hiddenformname='java'+name;\n"
		."\t\t\t\tdocument.getElementById(hiddenformname).value=value;\n"
		."\t\t\t\t}\n"
		."\t\t\tif (type == 'checkbox')\n"
		."\t\t\t\t{\n"
		."\t\t\t\tvar hiddenformname='java'+name;\n"
		."\t\t\t\tvar chkname='answer'+name;\n"
		."\t\t\t\tif (document.getElementById(chkname).checked) {\n"
		."\t\t\t\t\tdocument.getElementById(hiddenformname).value='Y';}\n"
		."\t\t\t\telse {\n"
		."\t\t\t\t\tdocument.getElementById(hiddenformname).value='';}\n"
		."\t\t\t\t}\n";
	$java="";
	$cqcount=1;
	foreach ($conditions as $cd)
		{
		if ((isset($oldq) && $oldq != $cd[0]) || !isset($oldq)) //New if statement
			{
			$java .= $endzone;
			$endzone = "";
			$cqcount=1;
			$java .= "\n\t\t\tif ((";
			}
		if (!isset($oldcq) || !$oldcq) {$oldcq = $cd[2];}
		if ($cd[4] == "L") //Just in case the dropdown threshold is being applied, check number of answers here
			{
			$cccquery="SELECT code FROM {$dbprefix}answers WHERE qid={$cd[1]}";
			$cccresult=mysql_query($cccquery);
			$cccount=mysql_num_rows($cccresult);
			}
		if ($cd[4] == "R") 	{$idname="fvalue_".$cd[1].substr($cd[2], strlen($cd[2])-1,1);}
		elseif ($cd[4] == "5" || $cd[4] == "A" || $cd[4] == "B" || $cd[4] == "C" || $cd[4] == "E" || $cd[4] == "F" || $cd[4] == "H" || $cd[4] == "G" || $cd[4] == "Y" || ($cd[4] == "L" && $cccount <= $dropdownthreshold))
							{$idname="java$cd[2]";}
		elseif ($cd[4] == "M" || $cd[4] == "P")
							{$idname="java$cd[2]$cd[3]";}
		else				{$idname="java".$cd[2];}
		if ($cqcount > 1 && $oldcq ==$cd[2]) {$java .= " || ";}
		elseif ($cqcount >1 && $oldcq != $cd[2]) {$java .= ") && (";}
		if ($cd[3] == '') 
			{
			$java .= "document.getElementById('$idname').value == ' ' || !document.getElementById('$idname').value";
			}
		elseif ($cd[4] == "M" || $cd[4] == "P")
			{
			$java .= "document.getElementById('$idname').value == 'Y'";
			}
		else 
			{
			$java .= "document.getElementById('$idname').value == '$cd[3]'";
			}
		if ((isset($oldq) && $oldq != $cd[0]) || !isset($oldq))//Close if statement
			{
			$endzone = "))\n";
			$endzone .= "\t\t\t\t{\n";
			$endzone .= "\t\t\t\tdocument.getElementById('question$cd[0]').style.display='';\n";
			$endzone .= "\t\t\t\tdocument.getElementById('display$cd[0]').value='on';\n";
			$endzone .= "\t\t\t\t}\n";
			$endzone .= "\t\t\telse\n";
			$endzone .= "\t\t\t\t{\n";
			$endzone .= "\t\t\t\tdocument.getElementById('question$cd[0]').style.display='none';\n";
			$endzone .= "\t\t\t\tdocument.getElementById('display$cd[0]').value='';\n";
			$endzone .= "\t\t\t\t}\n";
			$cqcount++;
			}
		$oldq = $cd[0]; //Update oldq for next loop
		$oldcq = $cd[2];  //Update oldcq for next loop
		}
	$java .= $endzone;
	}
if (isset($java)) {echo $java;}
echo "\t\t\t}\n"
	."\t//-->\n"
	."\t</script>\n\n";


echo "\n\n<!-- START THE GROUP -->\n";
foreach(file("$thistpl/startgroup.pstpl") as $op)
	{
	echo "\t".templatereplace($op);
	}
echo "\n";

if ($groupdescription)
	{
	foreach(file("$thistpl/groupdescription.pstpl") as $op)
		{
		echo "\t\t".templatereplace($op);
		}
	}
echo "\n";

echo "\n\n<!-- PRESENT THE QUESTIONS -->\n";
if (isset($qanda) && is_array($qanda))
	{
	foreach ($qanda as $qa)
		{
		echo "\n\t<!-- NEW QUESTION -->\n";
		echo "\t\t\t\t<div id='question$qa[4]'";
		if ($qa[3] != "Y") {echo ">\n";} else {echo " style='display: none'>\n";}
		$question="<label for='$qa[7]'>" . $qa[0] . "</label>";
		$answer=$qa[1];
		$help=$qa[2];
		$questioncode=$qa[5];
		foreach(file("$thistpl/question.pstpl") as $op)
			{
			echo "\t\t\t\t\t".templatereplace($op)."\n";
			}
		echo "\t\t\t\t</div>\n";
		}
	}
echo "\n\n<!-- END THE GROUP -->\n";
foreach(file("$thistpl/endgroup.pstpl") as $op)
	{
	echo "\t\t\t\t".templatereplace($op);
	}
echo "\n";

$navigator = surveymover(); //This gets globalised in the templatereplace function

echo "\n\n<!-- PRESENT THE NAVIGATOR -->\n";
foreach(file("$thistpl/navigator.pstpl") as $op)
	{
	echo "\t\t".templatereplace($op);
	}
echo "\n";

if ($thissurvey['active'] != "Y") 
	{
	echo "\t\t<center><font color='red' size='2'>"._NOTACTIVE."</font></center>\n";
	}



echo "<!-- group2.php -->\n"; //This can go eventually - it's redundent for debugging

if (isset($conditions) && is_array($conditions)) //if conditions exist, create hidden inputs for previously answered questions
	{
	foreach (array_keys($_SESSION) as $SESak)
		{
		if (in_array($SESak, $_SESSION['insertarray']))
			{
			echo "<input type='hidden' name='java$SESak' id='java$SESak' value='" . htmlspecialchars($_SESSION[$SESak],ENT_QUOTES). "'>\n";
			}
		}
	}
//SOME STUFF FOR MANDATORY QUESTIONS
if (remove_nulls_from_array($mandatorys))
	{
	$mandatory=implode("|", remove_nulls_from_array($mandatorys));
	echo "<input type='hidden' name='mandatory' value='$mandatory' id='mandatory'>\n";
	}
if (remove_nulls_from_array($conmandatorys))
	{
	$conmandatory=implode("|", remove_nulls_from_array($conmandatorys));
	echo "<input type='hidden' name='conmandatory' value='$conmandatory' id='conmandatory'>\n";
	}
if (remove_nulls_from_array($mandatoryfns))
	{
	$mandatoryfn=implode("|", remove_nulls_from_array($mandatoryfns));
	echo "<input type='hidden' name='mandatoryfn' value='$mandatoryfn' id='mandatoryfn'>\n";
	}
if (remove_nulls_from_array($conmandatoryfns))
	{
	$conmandatoryfn=implode("|", remove_nulls_from_array($conmandatoryfns));
	echo "<input type='hidden' name='conmandatoryfn' value='$conmandatoryfn' id='conmandatoryfn'>\n";
	}

echo "<input type='hidden' name='thisstep' value='{$_SESSION['step']}' id='thisstep'>\n";
echo "<input type='hidden' name='sid' value='$surveyid' id='sid'>\n";
echo "<input type='hidden' name='token' value='$token' id='token'>\n";
echo "</form>\n";
foreach(file("$thistpl/endpage.pstpl") as $op)
	{
	echo templatereplace($op);
	}
echo "\n";
doFooter();

?>
