#! /bin/sh
# Script based on Filezilla Project's updatelocales.sh
# This script distributes the pot, and po files to the public translations folder
# It also generates a statistics file for displaying.

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
    #cp $FILE.mo $WWWLOCALES/
    #chmod 775 $WWWLOCALES/$FILE.mo
    
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

  chmod 775 $WWWLOCALES/stats~
  mv $WWWLOCALES/stats~ $WWWLOCALES/stats

