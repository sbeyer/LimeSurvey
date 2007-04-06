<?php
/*
#############################################################
# >>> PHPSurveyor  										    #
#############################################################
#															#
# This set of scripts allows you to develop, publish and	#
# perform data-entry on surveys.							#
#############################################################
#															#
#	Copyright (C) 2007  PHPSurveyor community   			#
#															#
# This program is free software; you can redistribute 		#
# it and/or modify it under the terms of the GNU General 	#
# Public License Version 2 as published by the Free         #
# Software Foundation.										#
#															#
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

/* gen-new-po.php
Description: This file merges existing .po files in the checked out sources with the phpsurveyor.pot file, and generates new .po files in a temporary location for generating statistics.
*/

$podir = "/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/pofiles/";
$potfile = "/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/phpsurveyor.pot";

#Paths that will be excluded from the list
#No leading slashes
$excluded = array(0 => "classes/adodb", 1 => "classes/phpmailer", 2 => "classes/pear", 3 => "classes/php-gettext");

map_dirs("/srv/www/virtual/phpsurveyor.org/_demo-unstable/phpsurveyor",0);

function map_dirs($path,$level)
{
	global $file, $excluded, $podir, $potfile;
	if(is_dir($path)) {
		if($contents = opendir($path)) {
			while(($node = readdir($contents)) !== false) {
				if($node!="." && $node!=".." && substr($node,0,1) != ".") {
					if (substr($node,strlen($node)-3,3) == ".po")
					{
						$found = 0;
						for ($i=0; $i<count($excluded); $i++)
						{
							if (stristr($path,$excluded[$i])) $found = 1;
						}
						if (substr($path,strlen($path)-1,1) != "/")
						{
							$path2 = $path."/";
						} else {
							$path2 = $path;
						}
						if ($found == 0) exec('msgmerge --output-file='.$podir.$node.'.new '.$path2.$node.' '.$potfile);
					}
					map_dirs("$path/$node",$level+1);
				}
			}
		}
	}
}

?>
