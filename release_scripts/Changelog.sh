#!/bin/sh
# 
#---------------------------------------------------------------
# This is a dirty and quick script to automate changelog
# generation from XML SVN logs
# This will output a txt file wich can be added to the relevant
# section in the release notes.
#----------------------------------------------------------------
#
# Configuration section
#
# REPOSITORY_ROOT = SVN root of your local limesurvey repository
REPOSITORY_ROOT=/path/to/mysvn-directory/limesurvey

# TMPDIR is the directory where temporary and output files will be written
TMPDIR=`pwd`

# PATH to some important binaries
SVN=/usr/bin/svn
PHP=/usr/bin/php

# Let's update the repository first
echo "Updating SVN local repository"
CURRENTPATH=`pwd`
cd $REPOSITORY_ROOT
$SVN update -q
if [ $? -ne 0 ]
then
        echo "ERROR: SVN update failed"
        exit 1
fi

# Let's get the buildnumber
CURRENTBUILDNUM=`$SVN info | grep "Revision:" | awk '{print $2}'`
NEXTBULDNUM=`expr $CURRENTBUILDNUM + 1`

echo "Current Repository build is $CURRENTBUILDNUM,"
echo "  Let's assume you're preparing the build $NEXTBULDNUM release"

echo -n "Please enter the last release buildnumber: "
read OLDBUILD


echo "Getting SVN log in XML format from $OLDBUILD to $NEXTBULDNUM"
cd $REPOSITORY_ROOT/source/limesurvey
$SVN log --xml -r $OLDBUILD:$CURRENTBUILDNUM > $TMPDIR/SVNlog-LS-$OLDBUILD-$NEXTBULDNUM.xml


echo "Parsing SVN log in XML format ==> Changelog-LS-$OLDBUILD-$NEXTBULDNUM.txt"
$PHP $CURRENTPATH/ParseSVNLogs.php $TMPDIR/SVNlog-LS-$OLDBUILD-$NEXTBULDNUM.xml $TMPDIR/Changelog-LS-$OLDBUILD-$NEXTBULDNUM.txt

rm $TMPDIR/SVNlog-LS-$OLDBUILD-$NEXTBULDNUM.xml

echo "Now you have to:"
echo " * review the generated changelog in $TMPDIR/Changelog-LS-$OLDBUILD-$NEXTBULDNUM.txt"
echo " * then add it to the relevant section in the release-notes"
echo " * commit the new release notes"
echo " * and run the PackageLS.sh script to build and upload the packages to Sf.net"
