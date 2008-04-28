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

/* gen-pot-src.php
Description: This file generates a list of php files that are included for translation.
This list will be used by xgettext for generating a template pot file.
Change paths accordingly.
*/

$file = fopen("/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/potfiles.txt", "w+") or die("Can't open potfiles.txt");

#Paths that will be excluded from the list
#No leading slashes
$excluded = array(0 => "classes/adodb", 1 => "classes/phpmailer", 2 => "classes/pear", 3 => "classes/php-gettext");

map_dirs("/srv/www/virtual/phpsurveyor.org/_demo-unstable/phpsurveyor",0);

fclose($file);

function map_dirs($path,$level)
{
	global $file, $excluded;
	if(is_dir($path)) {
		if($contents = opendir($path)) {
			while(($node = readdir($contents)) !== false) {
				if($node!="." && $node!=".." && substr($node,0,1) != ".") {
					if (substr($node,strlen($node)-4,4) == ".php")
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
						if ($found == 0) fputs($file, $path2.$node."\n");
					}
					map_dirs("$path/$node",$level+1);
				}
			}
		}
	}
}

?>
