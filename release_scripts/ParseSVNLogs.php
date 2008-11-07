<?php
/**
 * Parsing svn log --xml output to build changelog
 */

/**
 * We have to make sure the number of variables
 * - arguments passed to the script is 3 (at least)
 */
if ( (isset($argv[1]) && $argv[1] == '--help') || count($argv) < 2) {
    die("Usage: php {$argv[0]} logfile.xml changelogfile.txt \n");
}

$file     = $argv[1];
$newFile  = $argv[2];

/**
 * Check if the file exists
 */
try {
    if (!file_exists($file)) {
        throw new Exception("File : $file does not exist");
    }
} catch (Exception $e) {
    die($e->getMessage());
}

$xml = new SimpleXMLElement(file_get_contents($file));

/**
 * Initialize array $entries
 */
$entries = array();

foreach ($xml->logentry as $entry) {
	$tmp = array();
        $tmp['author'] = $entry->author;
        $tmp['date']   = $entry->date;
        $tmp['msg']    = explode("\n",trim($entry->msg));
        $entries[] = $tmp;
}


$str = '';
foreach ($entries as $key => $entry) {
    foreach ($entry['msg'] as $line)
    {
	    if (preg_match("/^Update/i",$line))
	    {
		    $str[]= "#".$line." (".$entry['author'].")\n";
	    }
	    elseif (preg_match("/^New/i",$line))
	    {
		    $str[]= "+".$line." (".$entry['author'].")\n";
	    }
	    //elseif (preg_match("/^Dev/i",$line) == 0)
            elseif (preg_match("/^Fix/i",$line))
	    {
		    $str[]="-".$line." (".$entry['author'].")\n";
	    }
    }

}

// Now sort the resulting array and remove duplicates
sort($str);
$str = array_unique($str);


// Now write output to file
file_put_contents($newFile, $str, false);

?>
