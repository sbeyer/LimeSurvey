#!/bin/bash
#This is the main script which produces files needed for the translations web page.
# 1. Check out SVN Copy of PHPSurveyor
# 2. Generate a list of .php files
# 3. Create a .pot file
# 4. Generate new .po files, Copy to translations folder
# 5. Generate new .mo files, Copy to translations folder
# 6. Generate translation statistics to translations folder

echo "Script Run Time:"
date

#Check Out PHPSurveyor
echo "Checking out local Copy"
svn co https://phpsurveyor.svn.sourceforge.net/svnroot/phpsurveyor/source/phpsurveyor/ /srv/www/virtual/phpsurveyor.org/_demo-unstable/phpsurveyor/

#Run  gen-pot-src.php (CHMOD 700)
echo "Generating php file list"
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

#Run  gen-new-po.php (CHMOD 700)
php5 /srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts/gen-new-po.php

  WWWLOCALES="/srv/www/virtual/phpsurveyor.org/htdocs/translations"
  SCRIPTDIR="/srv/www/virtual/phpsurveyor.org/_demo-unstable/scripts"
  echo "Copying locales"

  # Copy pot template
  cp $SCRIPTDIR/phpsurveyor.pot $WWWLOCALES/phpsurveyor.pot
  chmod 775 $WWWLOCALES/phpsurveyor.pot

  rm -f $WWWLOCALES/stats~

  cd $SCRIPTDIR/pofiles/

  for i in *.po.new; do
  
    FILE=${i%%.*}
    PO=${i%.new}

    echo "Generating Statistics for $i"

    cp $i $WWWLOCALES/$PO
    chmod 775 $WWWLOCALES/$PO

    echo "Compiling $FILE.mo"
    msgfmt $i -o $WWWLOCALES/$FILE.mo
    chmod 775 $WWWLOCALES/$FILE.mo
    
    cp $i $i~

    cat >> $i~ << "EOF"

msgid "foobar1"
msgstr ""

msgid "foobar2"
msgstr "foobar2"

#, fuzzy
msgid "foobar3"
msgstr "foobar3"
EOF

    RES=`msgfmt $i~ -o /dev/null --statistics 2>&1`

    TR=$[`echo $RES | sed -e 's/^[^0-9]*\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+/\1/'` - 1]
    FZ=$[`echo $RES | sed -e 's/^[^0-9]*\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+/\2/'` - 1]
    UT=$[`echo $RES | sed -e 's/^[^0-9]*\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+\([0-9]\+\)[^0-9]\+/\3/'` - 1]

    rm $i~

    LANG=${FILE%-*}
    #COUNTRY=${FILE#*_}

    LANGNAME=`cat "$SCRIPTDIR/languagecodes" 2>/dev/null | grep "^$LANG "`
    if [ $? ]; then
      LANGNAME=${LANGNAME#* }
    else
      LANGNAME=
    fi

    # NO COUNTRY CODES USED AT THE MOMENT
    #COUNTRYNAME=
    #if [ ! -z "$COUNTRY" ]; then
    #  COUNTRYNAME=`cat "$DATADIR/countrycodes" 2>/dev/null | grep "^$COUNTRY "`
    #  if [ $? ]; then
    #    COUNTRYNAME=${COUNTRYNAME#* }
    #  fi
    #fi

    NAME=
    if [ ! -z "$LANGNAME" ]; then
      NAME="$LANGNAME " 
    fi
     
    #if [ ! -z "$COUNTRYNAME" ]; then
    #  NAME="$NAME($COUNTRYNAME) " 
    #fi

    echo "$FILE $TR $FZ $UT $NAME" >> $WWWLOCALES/stats~
    
  done

  chown vu2002:vu2002 -R $WWWLOCALES
  chmod 775 $WWWLOCALES/stats~
  mv $WWWLOCALES/stats~ $WWWLOCALES/stats
