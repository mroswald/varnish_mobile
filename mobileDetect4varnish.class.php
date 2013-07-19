<?php

    /**
     * mobileDetect4varnish.class.php
     * @author Mark Oswald <oswald@cbr.de>
     * @version 0.1
     *
     * Class to convert Mobile_Detect.php in varnish VCL include file
     * needs Mobile_Detect.php http://mobiledetect.net/
     *
     * in this state only the function for checking the user-agent header
     * will be converted
     *
     * @todo implement checkHttpHeadersForMobile
     */


    /**
     * define Path to Mobile_Detect.php relatively or absolutely
     * (optional, just better performance in case of large subdirs)
     */
    $sClassFile = dirname(__FILE__) . '/path/to/Mobile_Detect.php';

    if (!file_exists($sClassFile))
    {
        // try to find file recursively
        $mRet = exec("find ./ -name Mobile_Detect.php");

        if (!is_string($mRet) || !file_exists($mRet))
            die('file with Mobile_Detect not found');

        $sClassFile = $mRet;
    }

    require_once($sClassFile);

    if (!class_exists('Mobile_Detect'))
        die('class Mobile_Detect not found');

    if (!is_writable(dirname(__FILE__)))
        die('output dir ' . dirname(__FILE__) . ' is not writable');

    define('LOGLINELENGHT', 30);

    /**
     * Class mobileDetect4Varnish
     *
     * in first step we only use user-agents to check if mobile
     * @todo read function from class file and rewrite functions like checkHttpHeadersForMobile
     *
     */
    class mobileDetect4Varnish extends Mobile_Detect
    {
        /**
         *
         */
        public function __construct()
        {
            // override standard - we don't need the execution stuff
            // parent::_construct();

            $this->setMobileDetectionRules();
            $this->setMobileDetectionRulesExtended();
        }

        /**
         * @return string
         */
        public function convert4varnish()
        {
            $sVCL = "sub moSetMobileHeader {\n\n";
            $aMobile = array();
            $aTablet = array();
            foreach ($this->getRules() as $sRegex)
            {
                $aMobile[] = "req.http.user-agent ~ \"$sRegex\"\n";
            }

            foreach($this->tabletDevices as $sRegex){
                $aTablet[] = "req.http.user-agent ~ \"$sRegex\"\n";
            }

            if (count($aMobile))
            {
                $sVCL .= "\tif ( " . implode(" || ", $aMobile) . " ) {\n";
                $sVCL .= "\t\tset req.http.x-device = \"Phone\";\n";
            }

            if (count($aTablet))
            {
                $sVCL .= "\t" . (count($aMobile)?"} else ":"") . "if ( " . implode(" || ", $aTablet) . " ) {\n";
                $sVCL .= "\t\tset req.http.x-device = \"Tablet\";\n";
            }

            if (count($aMobile)||count($aTablet))
            {
                $sVCL .= "\t} else {\n";
                $sVCL .= "\t\tset req.http.x-device = \"Desktop\";\n";
                $sVCL .= "\t}\n";
            }

            $sVCL .= "}\n";

            return $sVCL;
        }
    }

    function printlogln( $sStr, $iLength = LOGLINELENGHT )
    {
        $sOutput = "#";
        $sOutput .= str_repeat(" ", ($iLength - strlen($sStr) - 2) / 2);
        $sOutput .= $sStr;
        $sOutput .= str_repeat(" ", $iLength - strlen($sOutput) - 1);
        $sOutput .= "#\n";
        print $sOutput;
    }

    print str_repeat("#", LOGLINELENGHT) . "\n";
    printlogln("");
    printlogln("");
    printlogln("Starting conversion");
    printlogln("");
    printlogln("");
    print str_repeat("#", LOGLINELENGHT) . "\n";

    $oConvert = new mobileDetect4Varnish();
    $sVCL = $oConvert->convert4varnish();

    file_put_contents(dirname(__FILE__) . '/mobiledetect.vcl', $sVCL);

    print "\n\nOutput file: ". dirname(__FILE__) . '/mobiledetect.vcl' ."\n\n";
    print "\ndone\n\n";