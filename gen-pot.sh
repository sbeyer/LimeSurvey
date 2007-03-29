#!/bin/bash
#This file generates the .pot file template, to be used for new translations, and merging with existing .po files
#Change (in) potfiles.txt and (out) phpsurveyor.pot location
echo Generating POT File;
xgettext --language=php --keyword=gT --from-code=UTF-8 --files-from=potfiles.txt --output=phpsurveyor.pot
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
