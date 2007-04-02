#!/bin/bash
#This file generates the .pot file template, to be used for new translations, and merging with existing .po files
#Change (in) potfiles.txt and (out) phpsurveyor.pot location

#Check Out PHPSurveyor
svn co https://phpsurveyor.svn.sourceforge.net/svnroot/phpsurveyor/source/phpsurveyor/ /srv/www/virtual/phpsurveyor.org/_demo-unstable/phpsurveyor/

#Run  gen-pot-src.php (CHMOD 700)
php5 /srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/gen-pot-src.php

#Generate Pot file, and do header replacement
echo Generating POT File;
xgettext --language=php --keyword=gT --from-code=UTF-8 --files-from=/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/potfiles.txt --output=/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/phpsurveyor.pot
echo updating CHARSET to UTF-8;
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/charset=CHARSET/charset=UTF-8/" > phpsurveyor.pot
echo updating Language-Team;
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/Language-Team: LANGUAGE <LL@li.org>/Language-Team: PHPSurveyor <c_schmitz@users.sourceforge.net>/" > phpsurveyor.pot
echo updating PACKAGE VERSION;
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/PACKAGE VERSION/PHPSurveyor language file/" > phpsurveyor.pot
echo updating HEADER;
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/SOME DESCRIPTIVE TITLE/PHPSURVEYOR LANGUAGE FILE/" > phpsurveyor.pot
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/Copyright (C) YEAR THE PACKAGE'S COPYRIGHT HOLDER/Copyright (C) 2007 PHPSurveyor Team/" > phpsurveyor.pot
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/This file is distributed under the same license as the PACKAGE package/This file is distributed under the same license as the PHPSurveyor package/" > phpsurveyor.pot
cp phpsurveyor.pot phpsurveyor.bak;
cat phpsurveyor.bak | sed "s/FIRST AUTHOR <EMAIL@ADDRESS>, YEAR/FIRST AUTHOR c_schmitz@users.sourceforge.net, 2007/" > phpsurveyor.pot
echo DONE
rm -f phpsurveyor.bak;
#Set Pot File Permissions
chown vu2002:www /srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/phpsurveyor.pot
chmod 644 /srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/phpsurveyor.pot
