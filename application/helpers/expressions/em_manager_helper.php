<?php
/**
 * Description of LimeExpressionManager
 * This is a wrapper class around ExpressionManager that implements a Singleton and eases
 * passing of LimeSurvey variable values into ExpressionManager
 *
 * @author Thomas M. White
 */
// TMSWhite:  LimeSurvey extensions to core Expression Manager - renamed to follow CI naming conventions
include_once('em_core_helper.php');

class LimeExpressionManager {
    private static $instance;
    private $em;    // Expression Manager
    private $groupRelevanceInfo;
    private $groupNum;
    private $debugLEM = true;   // set this to false to turn off debugging
    private $debugLEMonlyVars = true;   //set this to true to only show log replacements of questions (e.g. no tokens or templates)
    private $knownVars;
    private $pageRelevanceInfo;
    private $pageTailorInfo;
    private $allOnOnePage=false;    // internally set to true for survey.php so get group-specific logging but keep javascript variable namings consistent on the page.
    private $resetFunctions;
    private $qid2code;  // array of mappings of Question # to list of SGQA codes used within it
    private $jsVar2qid; // reverse mapping of JavaScript Variable name to Question
    private $alias2varName; // JavaScript array of mappings of aliases to the JavaScript variable names
    private $varNameAttr;   // JavaScript array of mappings of canonical JavaScript variable name to key attributes.
    
    // A private constructor; prevents direct creation of object
    private function __construct() 
    {
        $this->em = new ExpressionManager();
    }

    // The singleton method
    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }
        return self::$instance;
    }
    
    // Prevent users to clone the instance
    public function __clone()
    {
        trigger_error('Clone is not allowed.', E_USER_ERROR);
    }

    /**
     * Create the arrays needed by ExpressionManager to process LimeSurvey strings.
     * The long part of this function should only be called once per page display (e.g. only if $fieldMap changes)
     *
     * @param <type> $forceRefresh
     * @param <type> $anonymized
     * @return boolean - true if $fieldmap had been re-created, so ExpressionManager variables need to be re-set
     */

    public function setVariableAndTokenMappingsForExpressionManager($forceRefresh=false,$anonymized=false,$allOnOnePage=false,$surveyid=NULL)
    {
//        $surveyid = returnglobal('sid');

        //checks to see if fieldmap has already been built for this page.
//        if (isset($globalfieldmap[$surveyid]['expMgr_varMap'][$clang->langcode])&& !$forceRefresh) {
//            return false;   // means the mappings have already been set and don't need to be re-created
//        }

        $fieldmap=createFieldMap($surveyid,$style='full',$forceRefresh);
        if (!isset($fieldmap)) {
            return false; // implies an error occurred
        }

        $sgqaMap = array();  // mapping of SGQA to Value
        $knownVars = array();   // mapping of VarName to Value
        $debugLog = array();    // array of mappings among values to confirm their accuracy
        $qid2code = array();    // List of codes for each question - needed to know which to NULL if a question is irrelevant
        $jsVar2qid = array();
        $alias2varName = array();
        $varNameAttr = array();
        /*
        if ($this->debugLEM)
        {
            file_put_contents('/tmp/LimeExpressionManager_fieldmap.html', print_r($fieldmap,TRUE));
        }
         */
        foreach($fieldmap as $fielddata)
        {
            $code = $fielddata['fieldname'];
            if (!preg_match('#^\d+X\d+X\d+#',$code))
            {
                continue;   // not an SGQA value
            }
            $fieldNameParts = explode('X',$code);
            $groupNum = $fieldNameParts[1];
            $isOnCurrentPage = ($allOnOnePage || ($groupNum != NULL && $groupNum == $this->groupNum)) ? 'Y' : 'N';

            $questionId = $fieldNameParts[2];
            $questionNum = $fielddata['qid'];
            $questionAttributes = getQuestionAttributes($questionId,$fielddata['type']);
            $relevance = (isset($questionAttributes['relevance'])) ? $questionAttributes['relevance'] : 1;

            // Create list of codes associated with each question
            $codeList = (isset($qid2code[$questionNum]) ? $qid2code[$questionNum] : '');
            if ($codeList == '')
            {
                $codeList = $code;
            }
            else
            {
                $codeList .= '|' . $code;
            }
            $qid2code[$questionNum] = $codeList;

            // Check off-page relevance status
            if (isset($_SESSION['relevanceStatus'])) {
                $relStatus = (isset($_SESSION['relevanceStatus'][$questionId]) ? $_SESSION['relevanceStatus'][$questionId] : 1);
            }
            else {
                $relStatus = 1;
            }

            switch($fielddata['type'])
            {
                case '!': //List - dropdown
                case '5': //5 POINT CHOICE radio-buttons
                case 'D': //DATE
                case 'G': //GENDER drop-down list
                case 'I': //Language Question
                case 'L': //LIST drop-down/radio-button list
                case 'N': //NUMERICAL QUESTION TYPE
                case 'O': //LIST WITH COMMENT drop-down/radio-button list + textarea
                case 'S': //SHORT FREE TEXT
                case 'T': //LONG FREE TEXT
                case 'U': //HUGE FREE TEXT
                case 'X': //BOILERPLATE QUESTION
                case 'Y': //YES/NO radio-buttons
                case '|': //File Upload
                case '*': //Equation
                    $varName = $fielddata['title'];
                    $question = $fielddata['question'];
                    break;
                case '1': //Array (Flexible Labels) dual scale
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'] . '.' . $fielddata['scale_id'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion'] . ': ' . $fielddata['scale'];
                    break;
                case 'A': //ARRAY (5 POINT CHOICE) radio-buttons
                case 'B': //ARRAY (10 POINT CHOICE) radio-buttons
                case 'C': //ARRAY (YES/UNCERTAIN/NO) radio-buttons
                case 'E': //ARRAY (Increase/Same/Decrease) radio-buttons
                case 'F': //ARRAY (Flexible) - Row Format
                case 'H': //ARRAY (Flexible) - Column Format
                case 'K': //MULTIPLE NUMERICAL QUESTION
                case 'M': //Multiple choice checkbox
                case 'P': //Multiple choice with comments checkbox + text
                case 'Q': //MULTIPLE SHORT TEXT
                case 'R': //RANKING STYLE
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion'];
                    break;
                case ':': //ARRAY (Multi Flexi) 1 to 10
                case ';': //ARRAY (Multi Flexi) Text
                    $varName = $fielddata['title'] . '.' . $fielddata['aid'];
                    $question = $fielddata['question'] . ': ' . $fielddata['subquestion1'] . ': ' . $fielddata['subquestion2'];
                    break;
            }
            switch($fielddata['type'])
            {
                case 'R': //RANKING STYLE
                    if ($isOnCurrentPage=='Y')
                    {
                        $jsVarName = 'fvalue_' . $fieldNameParts[2];
                    }
                    else
                    {
                        $jsVarName = 'java' . $code;
                    }
                    break;
                case 'D': //DATE
                case 'N': //NUMERICAL QUESTION TYPE
                case 'S': //SHORT FREE TEXT
                case 'T': //LONG FREE TEXT
                case 'U': //HUGE FREE TEXT
                case 'Q': //MULTIPLE SHORT TEXT
                case 'K': //MULTIPLE NUMERICAL QUESTION
                case 'X': //BOILERPLATE QUESTION
                    if ($isOnCurrentPage=='Y')
                    {
                        $jsVarName = 'answer' . $code;
                    }
                    else
                    {
                        $jsVarName = 'java' . $code;
                    }
                    break;
                case '!': //List - dropdown
                case '5': //5 POINT CHOICE radio-buttons
                case 'G': //GENDER drop-down list
                case 'I': //Language Question
                case 'L': //LIST drop-down/radio-button list
                case 'Y': //YES/NO radio-buttons
                case '*': //Equation
                case '1': //Array (Flexible Labels) dual scale
                case 'A': //ARRAY (5 POINT CHOICE) radio-buttons
                case 'B': //ARRAY (10 POINT CHOICE) radio-buttons
                case 'C': //ARRAY (YES/UNCERTAIN/NO) radio-buttons
                case 'E': //ARRAY (Increase/Same/Decrease) radio-buttons
                case 'F': //ARRAY (Flexible) - Row Format
                case 'H': //ARRAY (Flexible) - Column Format
                case 'M': //Multiple choice checkbox
                case ':': //ARRAY (Multi Flexi) 1 to 10
                case ';': //ARRAY (Multi Flexi) Text
                    $jsVarName = 'java' . $code;
                    break;
                case 'O': //LIST WITH COMMENT drop-down/radio-button list + textarea
                    // Don't want to use the one that ends in 'comment'
                    $goodcode = preg_replace("/^(.*?)(comment)?$/","$1",$code);
                    $jsVarName = 'java' . $goodcode;
                    break;
                case '|': //File Upload
                    // Only want the use the one that ends in '_filecount'
                    $goodcode = preg_replace("/^(.*?)(_filecount)?$/","$1",$code);
                    $jsVarName = $goodcode . '_filecount';
                    break;
                case 'P': //Multiple choice with comments checkbox + text
                    if (preg_match("/comment$/",$code) && $isOnCurrentPage=='Y')
                    {
                        $jsVarName = 'answer' . $code;  // is this true for survey.php and not for group.php?
                    }
                    else
                    {
                        $jsVarName = 'java' . $code;
                    }
                    break;
            }
            $readWrite = 'N';
            if (isset($_SESSION[$code]))
            {
                $codeValue = $_SESSION[$code];
//                $displayValue= retrieve_Answer($code, $_SESSION['dateformats']['phpdate']);   // TODO - undefined in _ci
                $displayValue='Undefined';
            }
            else
            {
                $codeValue = '';
                $displayValue = '';
            }
            // Set mappings of variable names to needed attributes
            $varInfo_Code = array(
                'codeValue'=>$codeValue,
                'jsName'=>$jsVarName,
                'readWrite'=>$readWrite,
                'isOnCurrentPage'=>$isOnCurrentPage,
                'displayValue'=>$displayValue,
                'question'=>$question,
                'relevance'=>$relevance,
                'relevanceNum'=>'relevance' . $questionNum,
                'relevanceStatus'=>$relStatus,
                );
            $varInfo_DisplayVal = array(
                'codeValue'=>$displayValue,
                'jsName'=>'',
                'readWrite'=>'N',
                'isOnCurrentPage'=>$isOnCurrentPage,
                'relevanceNum'=>'relevance' . $questionNum,
                'relevanceStatus'=>$relStatus,
                );
            $varInfo_Question = array(
                'codeValue'=>$question,
                'jsName'=>'',
                'readWrite'=>'N',
                'isOnCurrentPage'=>$isOnCurrentPage,
                'relevanceNum'=>'relevance' . $questionNum,
                'relevanceStatus'=>$relStatus,
                );
            $varInfo_NAOK = array(
                'codeValue'=>$codeValue,
                'jsName'=>$jsVarName . '.NAOK',
                'readWrite'=>$readWrite,
                'isOnCurrentPage'=>$isOnCurrentPage,
                'displayValue'=>$displayValue,
                'question'=>$question,
                'relevance'=>'1',
                'relevanceNum'=>'',
                'relevanceStatus'=>'1',
                );
            $knownVars[$varName] = $varInfo_Code;
            $knownVars[$varName . '.shown'] = $varInfo_DisplayVal;
            $knownVars[$varName . '.question']= $varInfo_Question;
            $knownVars['INSERTANS:' . $code] = $varInfo_DisplayVal;
            $knownVars[$varName . '.NAOK'] = $varInfo_NAOK;

            $jsVar2qid[$jsVarName] = $questionNum;

            // Create JavaScript arrays
            $alias2varName[$varName] = array('jsName'=>$jsVarName, 'jsPart' => "'" . $varName . "':{'jsName':'" . $jsVarName . "'}");
            $alias2varName[$jsVarName] = array('jsName'=>$jsVarName, 'jsPart' => "'" . $jsVarName . "':{'jsName':'" . $jsVarName . "'}");
//            $alias2varName['INSERTANS:'.$code] = array('jsName'=>$jsVarName, 'jsPart'=> "'INSERTANS:" . $code . "':{'jsName':'" . $jsVarName . "'}");
//            $alias2varName[$varName . '.NAOK'] = array('jsName'=>$jsVarName, 'jsPart' => "'" . $varName . ".NAOK':{'jsName':'" . $jsVarName . ".NAOK'}");


            $varNameAttr[$jsVarName] = "'" . $jsVarName . "':{"
                . "'jsName':'" . $jsVarName
                . "','code':'" . $codeValue
//                . "','shown':'" . $displayValue
//                . "','question':'" . $question
                . "','qid':'" . $questionNum
                . "'}";
/*
            $varNameAttr[$jsVarName . '.NAOK'] = "'" . $jsVarName . ".NAOK':{"
                . "'jsName':'" . $jsVarName . '.NAOK'
                . "','code':'" . $codeValue
//                . "','shown':'" . $displayValue
//                . "','question':'" . $question
                . "','qid':''}";
 */

            if ($this->debugLEM)
            {
                $debugLog[] = array(
                    'code' => $code,
                    'type' => $fielddata['type'],
                    'varname' => $varName,
                    'jsName' => $jsVarName,
                    'question' => $question,
                    'codeValue' => ($codeValue=='') ? '&nbsp;' : $codeValue,
                    'displayValue' => ($displayValue=='') ? '&nbsp;' : $displayValue,
                    'readWrite' => $readWrite,
                    'isOnCurrentPage' => $isOnCurrentPage,
                    'relevance' => $relevance,
                    );
            }

        }

        // Now set tokens
        if (isset($_SESSION['token']) && $_SESSION['token'] != '')
        {
            //Gather survey data for tokenised surveys, for use in presenting questions
            $_SESSION['thistoken']=getTokenData($surveyid, $_SESSION['token']);
        }
        if (isset($_SESSION['thistoken']))
        {
            foreach (array_keys($_SESSION['thistoken']) as $tokenkey)
            {
                if ($anonymized)
                {
                    $val = "";
                }
                else
                {
                    $val = $_SESSION['thistoken'][$tokenkey];
                }
                $key = "TOKEN:" . strtoupper($tokenkey);
                $knownVars[$key] = array(
                    'codeValue'=>$val,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                    'relevanceNum'=>'',
                    'relevanceStatus'=>'1',
                    );

                if ($this->debugLEM)
                {
                    $debugLog[] = array(
                        'code' => $key,
                        'type' => '&nbsp;',
                        'varname' => '&nbsp;',
                        'jsName' => '&nbsp;',
                        'question' => '&nbsp;',
                        'codeValue' => '&nbsp;',
                        'displayValue' => $val,
                        'readWrite'=>'N',
                        'isOnCurrentPage'=>'N',
                        'relevance'=>'',
                    );
                }
            }
        }
        else
        {
            // Explicitly set all tokens to blank
            $blankVal = array(
                    'codeValue'=>'',
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                    'relevanceNum'=>'',
                    'relevanceStatus'=>'1',
                    );
            $knownVars['TOKEN:FIRSTNAME'] = $blankVal;
            $knownVars['TOKEN:LASTNAME'] = $blankVal;
            $knownVars['TOKEN:EMAIL'] = $blankVal;
            $knownVars['TOKEN:USESLEFT'] = $blankVal;
            for ($i=1;$i<=100;++$i) // TODO - is there a way to know  how many attributes are set?  Looks like max is 100
            {
                $knownVars['TOKEN:ATTRIBUTE_' . $i] = $blankVal;
            }
        }

        if ($this->debugLEM)
        {
            $debugLog_html = "<table border='1'>";
            $debugLog_html .= "<tr><th>Code</th><th>Type</th><th>VarName</th><th>CodeVal</th><th>DisplayVal</th><th>JSname</th><th>Writable?</th><th>Set On This Page?</th><th>Relevance</th><th>Question</th></tr>";
            foreach ($debugLog as $t)
            {
                $debugLog_html .= "<tr><td>" . $t['code']
                    . "</td><td>" . $t['type']
                    . "</td><td>" . $t['varname']
                    . "</td><td>" . $t['codeValue']
                    . "</td><td>" . $t['displayValue']
                    . "</td><td>" . $t['jsName']
                    . "</td><td>" . $t['readWrite']
                    . "</td><td>" . $t['isOnCurrentPage']
                    . "</td><td>" . $t['relevance']
                    . "</td><td>" . $t['question']
                    . "</td></tr>";
            }
            $debugLog_html .= "</table>";
            file_put_contents('/tmp/LimeExpressionManager-page.html',$debugLog_html);
        }
        
        $this->knownVars = $knownVars;
        $this->qid2code = $qid2code;
        $this->jsVar2qid = $jsVar2qid;
        $this->alias2varName = $alias2varName;
        $this->varNameAttr = $varNameAttr;

        return true;
    }

    /**
     * Translate all Expressions, Macros, registered variables, etc. in $string
     * @param <type> $string - the string to be replaced
     * @param <type> $replacementFields - optional replacement values
     * @param boolean $debug - if true,write translations for this page to html-formatted log file
     * @param <type> $numRecursionLevels - the number of times to recursively subtitute values in this string
     * @param <type> $whichPrettyPrintIteration - if want to pretty-print the source string, which recursion  level should be pretty-printed
     * @return <type> - the original $string with all replacements done.
     */

    static function ProcessString($string, $questionNum=NULL, $replacementFields=array(), $debug=false, $numRecursionLevels=1, $whichPrettyPrintIteration=1)
    {
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;

        if (isset($replacementFields) && is_array($replacementFields) && count($replacementFields) > 0)
        {
            $replaceArray = array();
            foreach ($replacementFields as $key => $value) {
                $replaceArray[$key] = array(
                    'codeValue'=>$value,
                    'jsName'=>'',
                    'readWrite'=>'N',
                    'isOnCurrentPage'=>'N',
                );
            }
            $em->RegisterVarnamesUsingMerge($replaceArray);   // TODO - is it safe to just merge these in each time, or should a refresh be forced?
        }
        $result = $em->sProcessStringContainingExpressions(htmlspecialchars_decode($string),(is_null($questionNum) ? 0 : $questionNum), $numRecursionLevels, $whichPrettyPrintIteration);

        if ($lem->debugLEM)
        {
            if ($lem->debugLEMonlyVars)
            {
                $varsUsed = $em->GetJSVarsUsed();
                if (is_array($varsUsed) and count($varsUsed) > 0) {
                    $debugLog_html = '<tr><td>' . $lem->groupNum . '</td><td>' . $string . '</td><td>' . $em->GetLastPrettyPrintExpression() . '</td><td>' . $result . "</td></tr>\n";
                    file_put_contents('/tmp/LimeExpressionManager-Debug-ThisPage.html',$debugLog_html,FILE_APPEND);
                }
            }
        }

        return $result;
    }


    /**
     * Compute Relevance, processing $eqn to get a boolean value.  If there are syntax errors, currently returns true.  My change to returning null so can look for errors?
     * @param <type> $eqn
     * @return <type>
     */
    static function ProcessRelevance($eqn,$questionNum=NULL,$jsResultVar=NULL,$type=NULL,$hidden=0)
    {
        // These will be called in the order that questions are supposed to be asked
        $lem = LimeExpressionManager::singleton();
        if (!isset($eqn) || trim($eqn=='') || trim($eqn)=='1')
        {
            $lem->groupRelevanceInfo[] = array(
                'qid' => $questionNum,
                'eqn' => $eqn,
                'result' => true,
                'numJsVars' => 0,
                'relevancejs' => '',
                'relevanceVars' => '',
                'jsResultVar'=> $jsResultVar,
                'type'=>$type,
                'hidden'=>$hidden,
            );
            return true;
        }
        $em = $lem->em;
        $result = $em->ProcessBooleanExpression(htmlspecialchars_decode($eqn));
        $jsVars = $em->GetJSVarsUsed();
        $relevanceVars = implode('|',$em->GetJSVarsUsed());
        $relevanceJS = $lem->em->GetJavaScriptEquivalentOfExpression();
        $lem->groupRelevanceInfo[] = array(
            'qid' => $questionNum,
            'eqn' => $eqn,
            'result' => $result,
            'numJsVars' => count($jsVars),
            'relevancejs' => $relevanceJS,
            'relevanceVars' => $relevanceVars,
            'jsResultVar' => $jsResultVar,
            'type'=>$type,
            'hidden'=>$hidden,
        );
        return $result;
    }

    /**
     * Used to show potential syntax errors of processing Relevance or Equations.
     * @return <type>
     */
    static function GetLastPrettyPrintExpression()
    {
        $lem = LimeExpressionManager::singleton();
        return $lem->em->GetLastPrettyPrintExpression();
    }

    static function StartProcessingPage($debug=true,$allOnOnePage=false)
    {
        $lem = LimeExpressionManager::singleton();
        $lem->pageRelevanceInfo=array();
        $lem->pageTailorInfo=array();
        $lem->resetFunctions=array();
        $lem->alias2varName=array();
        $lem->varNameAttr=array();
        $lem->allOnOnePage=$allOnOnePage;

        if ($debug && $lem->debugLEM)
        {
            $debugLog_html = '<tr><th>Group</th><th>Source</th><th>Pretty Print</th><th>Result</th></tr>';
            file_put_contents('/tmp/LimeExpressionManager-Debug-ThisPage.html',$debugLog_html); // replace the value
        }
    }

    static function StartProcessingGroup($groupNum=NULL,$anonymized=false,$surveyid=NULL)
    {
//        $surveyid = returnglobal('sid');

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->StartProcessingGroup();
        if (!is_null($groupNum))
        {
            $lem->groupNum = $groupNum;
            $lem->qid2code = array();   // List of codes for each question - needed to know which to NULL if a question is irrelevant
            $lem->jsVar2qid = array();

            if (!is_null($surveyid) && $lem->setVariableAndTokenMappingsForExpressionManager(true,$anonymized,$lem->allOnOnePage,$surveyid))
            {
                // means that some values changed, so need to update what was registered to ExpressionManager
//                $em->RegisterVarnamesUsingReplace($lem->knownVars);
                $em->RegisterVarnamesUsingMerge($lem->knownVars);
            }
        }
        $lem->groupRelevanceInfo = array();
    }

    static function FinishProcessingGroup()
    {
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $lem->pageTailorInfo[] = $em->GetCurrentSubstitutionInfo();
        $lem->pageRelevanceInfo[] = $lem->groupRelevanceInfo;
    }

    static function FinishProcessingPage()
    {

    }

    /*
     * Generate JavaScript needed to do dynamic relevance and tailoring
     * Also create list of variables that need to be declared
     */
    static function GetRelevanceAndTailoringJavaScript()
    {
        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;

        $knownVars = $lem->knownVars;

        $jsParts=array();
        $allJsVarsUsed=array();
        $jsParts[] = '<script type="text/javascript" src="' . base_url() . '/scripts/admin/expressions/em_javascript.js"></script>';
        $jsParts[] = "<script type='text/javascript'>\n<!--\n";
        $jsParts[] = "function ExprMgr_process_relevance_and_tailoring(){\n";

        // flatten relevance array, keeping proper order

        $pageRelevanceInfo=array();
        $qidList = array(); // list of questions used in relevance and tailoring

        if (is_array($lem->pageRelevanceInfo))
        {
            foreach($lem->pageRelevanceInfo as $prel)
            {
                foreach($prel as $rel)
                {
                    $pageRelevanceInfo[] = $rel;
                }
            }
        }


        if (is_array($pageRelevanceInfo))
        {
            foreach ($pageRelevanceInfo as $arg)
            {
                // First check if there is any tailoring  and construct the tailoring JavaScript if needed
                $tailorParts = array();
                foreach ($lem->pageTailorInfo as $tailor)
                {
                    if (is_array($tailor))
                    {
                        foreach ($tailor as $sub)
                        {
                            if ($sub['questionNum'] == $arg['qid'])
                            {
                                $tailorParts[] = $sub['js'];
                                $vars = explode('|',$sub['vars']);
                                if (is_array($vars))
                                {
                                    $allJsVarsUsed = array_merge($allJsVarsUsed,$vars);
                                }
                            }
                        }
                    }
                }
                $qidList[$arg['qid']] = $arg['qid'];

                $relevance = $arg['relevancejs'];
                if (($relevance == '' || $relevance == '1') && count($tailorParts) == 0)
                {
                    // Only show constitutively true relevances if there is tailoring that should be done.
                    $jsParts[] = "document.getElementById('relevance" . $arg['qid'] . "').value='1'; // always true\n";
                    continue;
                }
                $relevance = ($relevance == '') ? '1' : $relevance;
                $jsResultVar = $lem->em->GetJsVarFor($arg['jsResultVar']);
                $jsParts[] = "\n// Process Relevance for Question " . $arg['qid'] . "(" . $arg['jsResultVar'] . "=" . $jsResultVar . "): { " . $arg['eqn'] . " }\n";
                $jsParts[] = "if (\n";
                $jsParts[] = $relevance;
                $jsParts[] = "\n)\n{\n";
                // Do all tailoring
                $jsParts[] = implode("\n",$tailorParts);
                if ($arg['hidden'] == 1) {
                    $jsParts[] = "  // This question should always be hidden\n";
                    $jsParts[] = "  $('#question" . $arg['qid'] . "').hide();\n";
                    $jsParts[] = "  document.getElementById('display" . $arg['qid'] . "').value='';\n";
                }
                else {
                    $jsParts[] = "  $('#question" . $arg['qid'] . "').show();\n";
                    $jsParts[] = "  document.getElementById('display" . $arg['qid'] . "').value='on';\n";
                }
                // If it is an equation, and relevance is true, then write the value from the question to the answer field storing the result
                if ($arg['type'] == '*')
                {
                    $jsParts[] = "  // Write value from the question into the answer field\n";
                    $jsParts[] = "  document.getElementById('" . $jsResultVar . "').value=escape(jQuery.trim(LEMstrip_tags($('#question" . $arg['qid'] . " .questiontext').find('span').next().next().html()))).replace(/%20/g,' ');\n";

                }
                $jsParts[] = "  document.getElementById('relevance" . $arg['qid'] . "').value='1';\n";
                $jsParts[] = "}\nelse {\n";
                $jsParts[] = "  $('#question" . $arg['qid'] . "').hide();\n";
                $jsParts[] = "  document.getElementById('display" . $arg['qid'] . "').value='';\n";
                $jsParts[] = "  document.getElementById('relevance" . $arg['qid'] . "').value='0';\n";
                // Which variable needs to be blanked?  Depends on the type of question
                if (isset($lem->resetFunctions[$arg['qid']]))
                {
                    $jsParts[] = "  reset_question_" . $arg['qid'] . "();\n";
                }
                else
                {
                    // Function hasn't been defined yet
//                    $jsParts[] = "  try { reset_question_" . $arg['qid'] . "(); } catch (e) { }\n";
                }
                $jsParts[] = "}\n";

                $vars = explode('|',$arg['relevanceVars']);
                if (is_array($vars))
                {
                    $allJsVarsUsed = array_merge($allJsVarsUsed,$vars);
                }
            }
        }
        $jsParts[] = "}\n";

        $allJsVarsUsed = array_unique($allJsVarsUsed);

        foreach($lem->resetFunctions as $resetFn)
        {
            $jsParts[] = $resetFn;
        }

        // Add JavaScript Mapping Arrays
        if (isset($lem->alias2varName) && count($lem->alias2varName) > 0)
        {
            $neededAliases=array();
            $neededCanonical=array();
            $neededCanonicalAttr=array();
            foreach ($allJsVarsUsed as $jsVar)
            {
                if ($jsVar == '') {
                    continue;
                }
                if (preg_match("/^.*\.NAOK$/", $jsVar)) {
                    $jsVar = preg_replace("/\.NAOK$/","",$jsVar);
                }
                $neededCanonical[] = $jsVar;
                foreach ($lem->alias2varName as $key=>$value)
                {
                    if ($jsVar == $value['jsName'])
                    {
                        $neededAliases[] = $value['jsPart'];
                    }
                }
                $found = array_search($jsVar,$lem->alias2varName);
            }
            $neededCanonical = array_unique($neededCanonical);
            foreach ($neededCanonical as $nc)
            {
                $neededCanonicalAttr[] = $lem->varNameAttr[$nc];
            }
            $neededAliases = array_unique($neededAliases);
            if (count($neededAliases) > 0)
            {
                $jsParts[] = "var LEMalias2varName = {\n";
                $jsParts[] = implode(",\n",$neededAliases);
                $jsParts[] = "};\n";
            }
            if (count($neededCanonicalAttr) > 0)
            {
                $jsParts[] = "var LEMvarNameAttr = {\n";
                $jsParts[] = implode(",\n",$neededCanonicalAttr);
                $jsParts[] = "};\n";
            }
        }

        $jsParts[] = "//-->\n</script>\n";

        // Now figure out which variables have not been declared (those not on the current page)
        $undeclaredJsVars = array();
        $undeclaredVal = array();
        if (isset($knownVars) && is_array($knownVars))
        {
            foreach ($knownVars as $knownVar)
            {
                foreach ($allJsVarsUsed as $jsVar)
                {
                    if ($jsVar == $knownVar['jsName'])
                    {
                        if ($knownVar['isOnCurrentPage']=='N')
                        {
                            $undeclaredJsVars[] = $jsVar;
                            $undeclaredVal[$jsVar] = $knownVar['codeValue'];

                            if (isset($lem->jsVar2qid[$jsVar])) {
                                $qidList[$lem->jsVar2qid[$jsVar]] = $lem->jsVar2qid[$jsVar];
                            }
                            break;
                        }
                    }
                }
            }
            $undeclaredJsVars = array_unique($undeclaredJsVars);
            foreach ($undeclaredJsVars as $jsVar)
            {
                // TODO - is different type needed for text?  Or process value to striphtml?
                $jsParts[] = "<input type='hidden' id='" . $jsVar . "' name='" . $jsVar . "' value='" . htmlspecialchars($undeclaredVal[$jsVar]) . "'/>\n";
            }
        }
        sort($qidList,SORT_NUMERIC);
        foreach ($qidList as $qid)
        {
            if (isset($_SESSION['relevanceStatus'])) {
                $relStatus = (isset($_SESSION['relevanceStatus'][$qid]) ? $_SESSION['relevanceStatus'][$qid] : 1);
            }
            else {
                $relStatus = 1;
            }
            $jsParts[] = "<input type='hidden' id='relevance" . $qid . "' name='relevance" . $qid . "' value='" . $relStatus . "'/>\n";
            if (isset($lem->qid2code[$qid]))
            {
                $jsParts[] = "<input type='hidden' id='relevance" . $qid . "codes' name='relevance" . $qid . "codes' value='" . $lem->qid2code[$qid] . "'/>\n";
            }
        }
        
        return implode('',$jsParts);
    }

    static function SetResetFunction($questionNum, $functionContents)
    {
        $lem = LimeExpressionManager::singleton();
        $fn = "function reset_question_" . $questionNum . "() {\n" . $functionContents . "\n}\n";
        $lem->resetFunctions[$questionNum] = $fn;
    }

    /**
     * Unit test
     */
    static function UnitTestProcessStringContainingExpressions()
    {
        $vars = array(
'name' => array('codeValue'=>'Sergei', 'jsName'=>'java61764X1X1', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'age' => array('codeValue'=>45, 'jsName'=>'java61764X1X2', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'numKids' => array('codeValue'=>2, 'jsName'=>'java61764X1X3', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
'numPets' => array('codeValue'=>1, 'jsName'=>'java61764X1X4', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y'),
// Constants
'INSERTANS:61764X1X1'   => array('codeValue'=> 'Sergei', 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'Y'),
'INSERTANS:61764X1X2'   => array('codeValue'=> 45, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'Y'),
'INSERTANS:61764X1X3'   => array('codeValue'=> 2, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
'INSERTANS:61764X1X4'   => array('codeValue'=> 1, 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
'TOKEN:ATTRIBUTE_1'     => array('codeValue'=> 'worker', 'jsName'=>'', 'readWrite'=>'N', 'isOnCurrentPage'=>'N'),
        );

        $tests = <<<EOD
{name}
{age}
{numKids}
{numPets}
{INSERTANS:61764X1X1}
{INSERTANS:61764X1X2}
{INSERTANS:61764X1X3}
{INSERTANS:61764X1X4}
{TOKEN:ATTRIBUTE_1}
{name}, you said that you are {age} years old, and that you have {numKids} {if((numKids==1),'child','children')} and {numPets} {if((numPets==1),'pet','pets')} running around the house. So, you have {numKids + numPets} wild {if((numKids + numPets ==1),'beast','beasts')} to chase around every day.
Since you have more {if((numKids > numPets),'children','pets')} than you do {if((numKids > numPets),'pets','children')}, do you feel that the {if((numKids > numPets),'pets','children')} are at a disadvantage?
{INSERTANS:61764X1X1}, you said that you are {INSERTANS:61764X1X2} years old, and that you have {INSERTANS:61764X1X3} {if((INSERTANS:61764X1X3==1),'child','children')} and {INSERTANS:61764X1X4} {if((INSERTANS:61764X1X4==1),'pet','pets')} running around the house.  So, you have {INSERTANS:61764X1X3 + INSERTANS:61764X1X4} wild {if((INSERTANS:61764X1X3 + INSERTANS:61764X1X4 ==1),'beast','beasts')} to chase around every day.
Since you have more {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'children','pets')} than you do {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'pets','children')}, do you feel that the {if((INSERTANS:61764X1X3 > INSERTANS:61764X1X4),'pets','children')} are at a disadvantage?
{name2}, you said that you are {age + 5)} years old, and that you have {abs(numKids) -} {if((numKids==1),'child','children')} and {numPets} {if((numPets==1),'pet','pets')} running around the house. So, you have {numKids + numPets} wild {if((numKids + numPets ==1),'beast','beasts')} to chase around every day.
{INSERTANS:61764X1X1}, you said that you are {INSERTANS:61764X1X2} years old, and that you have {INSERTANS:61764X1X3} {if((INSERTANS:61764X1X3==1),'child','children','kiddies')} and {INSERTANS:61764X1X4} {if((INSERTANS:61764X1X4==1),'pet','pets')} running around the house.  So, you have {INSERTANS:61764X1X3 + INSERTANS:61764X1X4} wild {if((INSERTANS:61764X1X3 + INSERTANS:61764X1X4 ==1),'beast','beasts')} to chase around every day.
This line should throw errors since the curly-brace enclosed functions do not have linefeeds after them (and before the closing curly brace): var job='{TOKEN:ATTRIBUTE_1}'; if (job=='worker') { document.write('BOSSES') } else { document.write('WORKERS') }
This line has a script section, but if you look at the source, you will see that it has errors: <script type="text/javascript" language="Javascript">var job='{TOKEN:ATTRIBUTE_1}'; if (job=='worker') {document.write('BOSSES')} else {document.write('WORKERS')} </script>.
Substitions that begin or end with a space should be ignored: { name} {age }
EOD;
        $alltests = explode("\n",$tests);

        $javascript1 = <<<EOST
                    var job='{TOKEN:ATTRIBUTE_1}';
                    if (job=='worker') {
                    document.write('BOSSES')
                    } else {
                    document.write('WORKERS')
                    }
EOST;
        $javascript2 = <<<EOST
var job='{TOKEN:ATTRIBUTE_1}';
    if (job=='worker') {
       document.write('BOSSES')
    } else { document.write('WORKERS')  }
EOST;
        $alltests[] = 'This line should have no errors - the Javascript has curly braces followed by line feeds:' . $javascript1;
        $alltests[] = 'This line should also be OK: ' . $javascript2;
        $alltests[] = 'This line has a hidden script: <script type="text/javascript" language="Javascript">' . $javascript1 . '</script>';
        $alltests[] = 'This line has a hidden script: <script type="text/javascript" language="Javascript">' . $javascript2 . '</script>';

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->StartProcessingGroup();

        $em->RegisterVarnamesUsingMerge($vars);

        print '<table border="1"><tr><th>Test</th><th>Result</th><th>VarName(jsName, readWrite, isOnCurrentPage)</th></tr>';
        for ($i=0;$i<count($alltests);++$i)
        {
            $test = $alltests[$i];
            $result = $em->sProcessStringContainingExpressions($test,$i,2,1);
            $prettyPrint = $em->GetLastPrettyPrintExpression();
            print "<tr><td>" . $prettyPrint . "</td>\n";
            print "<td>" . $result . "</td>\n";
            $varsUsed = $em->getAllVarsUsed();
            if (is_array($varsUsed) and count($varsUsed) > 0) {
                $varDesc = array();
                foreach ($varsUsed as $v) {
                    $varInfo = $em->GetVarInfo($v);
                    $varDesc[] = $v . '(' . $varInfo['jsName'] . ',' . $varInfo['readWrite'] . ',' . $varInfo['isOnCurrentPage'] . ')';
                }
                print '<td>' . implode(',<br/>', $varDesc) . "</td>\n";
            }
            else {
                print "<td>&nbsp;</td>\n";
            }
            print "</tr>\n";
        }
        print '</table>';
    }

    static function UnitTestRelevance()
    {
        // Tests:  varName~relevance~inputType~message
        $tests = <<<EOT
name~1~text~What is your name?
age~1~text~How old are you?
badage~1~expr~{badage=((age<16) || (age>80))}
agestop~!is_empty(age) && ((age<16) || (age>80))~message~Sorry, {name}, you are too {if((age<16),'young',if((age>80),'old','middle-aged'))} for this test.
kids~!((age<16) || (age>80))~yesno~Do you have children?
parents~1~expr~{parents = (!badage && kids=='Y')}
numKids~kids=='Y'~text~How many children do you have?
kid1~numKids >= 1~text~How old is your first child?
kid2~numKids >= 2~text~How old is your second child?
kid3~numKids >= 3~text~How old is your third child?
kid4~numKids >= 4~text~How old is your fourth child?
kid5~numKids >= 5~text~How old is your fifth child?
sumage~1~expr~{sumage=sum(kid1.NAOK,kid2.NAOK,kid3.NAOK,kid4.NAOK,kid5.NAOK)}
report~numKids > 0~message~{name}, you said you are {age} and that you have {numKids} kids.  The sum of ages of your first {min(numKids,5)} kids is {sumage}.
EOT;

        $vars = array();
        $varsNAOK = array();
        $varSeq = array();
        $testArgs = array();
        $argInfo = array();

        // collect variables
        $i=0;
        foreach(explode("\n",$tests) as $test)
        {
            $args = explode("~",$test);
            $vars[$args[0]] = array('codeValue'=>'', 'jsName'=>'java_' . $args[0], 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y', 'relevanceNum'=>'relevance' . $i++, 'relevanceStatus'=>'1');
            $varsNAOK[$args[0] . '.NAOK'] = array('codeValue'=>'', 'jsName'=>'java_' . $args[0] . '.NAOK', 'readWrite'=>'Y', 'isOnCurrentPage'=>'Y', 'relevanceNum'=>'', 'relevanceStatus'=>'1');
            $varSeq[] = $args[0];
            $testArgs[] = $args;
        }

        LimeExpressionManager::StartProcessingPage(true,true);

        LimeExpressionManager::StartProcessingGroup();

        $lem = LimeExpressionManager::singleton();
        $em = $lem->em;
        $em->RegisterVarnamesUsingMerge($vars);
        $em->RegisterVarnamesUsingMerge($varsNAOK);

        // collect relevance
        $alias2varName = array();
        $varNameAttr = array();
        for ($i=0;$i<count($testArgs);++$i)
        {
            $testArg = $testArgs[$i];
            $var = $testArg[0];
            LimeExpressionManager::ProcessRelevance(htmlspecialchars_decode($testArg[1]),$i,$var);
            $question = LimeExpressionManager::ProcessString($testArg[3], $i, NULL, true, 1, 1);

            $jsVarName='java_' . $testArg[0];

            $argInfo[] = array(
                'num' => $i,
                'name' => $jsVarName,
                'type' => $testArg[2],
                'question' => $question,
            );
            $alias2varName[$var] = array('jsName'=>$jsVarName, 'jsPart' => "'" . $var . "':{'jsName':'" . $jsVarName . "'}");
            $alias2varName[$jsVarName] = array('jsName'=>$jsVarName, 'jsPart' => "'" . $jsVarName . "':{'jsName':'" . $jsVarName . "'}");
//            $alias2varName[$var . '.NAOK'] = array('jsName'=>$jsVarName . '.NAOK', 'jsPart' => "'" . $var . ".NAOK':{'jsName':'" . $jsVarName . ".NAOK'}");
//            $alias2varName[$jsVarName . '.NAOK'] = array('jsName'=>$jsVarName . '.NAOK', 'jsPart' => "'" . $jsVarName . ".NAOK':{'jsName':'" . $jsVarName . ".NAOK'}");
            $varNameAttr[$jsVarName] = "'" . $jsVarName . "':{"
                . "'jsName':'" . $jsVarName
                . "','qid':'" . $i
                . "'}";
            /*
            $varNameAttr[$jsVarName . '.NAOK'] = "'" . $jsVarName . ".NAOK':{"
                . "'jsName':'" . $jsVarName
                . "','qid':''}";
             */
        }
        $lem->alias2varName = $alias2varName;
        $lem->varNameAttr = $varNameAttr;
        LimeExpressionManager::FinishProcessingGroup();

        print LimeExpressionManager::GetRelevanceAndTailoringJavaScript();

        // Print Table of questions
        print "<table border='1'><tr><td>";
        foreach ($argInfo as $arg)
        {
            print "<input type='hidden' id='display" . $arg['num'] . "' value='on'/>\n";    // set all as  On by default - relevance processing will blank them as needed
            print "<input type='hidden' id='relevance" . $arg['num'] . "' value='1'/>\n";    // set all as  On by default - relevance processing will blank them as needed
            print "<div id='question" . $arg['num'] . "'>\n";
            if ($arg['type'] == 'expr')
            {
                // Hack for testing purposes - rather than using LimeSurvey internals to store the results of equations, process them via a hidden <div>
                print "<div style='display: none' name='hack_" . $arg['name'] . "' id='hack_" . $arg['name'] . "'>" . $arg['question'];
                print "<input type='hidden' name='" . $arg['name'] . "' id='" . $arg['name'] . "' value=''/></div>\n";
            }
            else {
                print "<table border='1' width='100%'>\n<tr>\n<td>[Q" . $arg['num'] . "] " . $arg['question'] . "</td>\n";
                switch($arg['type'])
                {
                    case 'yesno':
                    case 'text':
                        print "<td><input type='text' name='" . $arg['name'] . "' id='" . $arg['name'] . "' value='' onchange='ExprMgr_process_relevance_and_tailoring()'/></td>\n";
                        break;
                    case 'message':
                        print "<input type='hidden' name='" . $arg['name'] . "' id='" . $arg['name'] . "' value=''/>\n";
                        break;
                }
                print "</tr>\n</table>\n";
            }
            /*
            // Placeholder for function to explicitly reset the GUI widget and NULL the stored value
            print "<script type='text/javascript'>\n<!--\n";
            print "function reset_question_" . $arg['num'] . "() {\n";
            print "\tdocument.getElementById('" . $arg['name'] . "').value='';\n";
            print "\tdocument.getElementByid('display" . $arg['num'] . "').value='on';\n";
            print "}\n";
            print "// -->\n</script>\n";
             */
            print "</div>\n";
        }
        print "</table>";
    }
}
?>