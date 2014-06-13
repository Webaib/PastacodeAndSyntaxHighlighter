<?php
/**
 * Class to load all SH resourses
 * 
 * @category Logic
 * @package  Logic
 * @author   JPavlov <webaib@gmail.com>
 * @license  URL http://www.overscale.net
 * @version  Release: <package_version>
 * @link     http://github.com/Webaib/PastacodeAndSyntaxHighlighter
 */
class SHLoader {
    
    private $brushMap = array();
    
    /**
     * TBA
     * 
     * @param array &$output tba
     * 
     * @return void
     */
    public function initSH(array &$output) {
        $output[] = '<script type="text/javascript" src="'
            . plugins_url('/js/shCore.js', __FILE__) . '"></script>';
        
        $output[] = '<script type="text/javascript" src="'
            . plugins_url('/js/shAutoloader.js', __FILE__) . '"></script>';
        
        $output[] = '<link href="' . plugins_url('css/shCore.css', __FILE__)
            . '" rel="stylesheet" type="text/css" />';
        
        $output[] = '<link href="' . plugins_url('css/shThemeDefault.css', __FILE__)
            . '" rel="stylesheet" type="text/css" />';
        
        $output[] = '<script type="syntaxhighlighter" class="brush: java"><![CDATA[java]]></script>';
        $output[] = '<script type="syntaxhighlighter" class="brush: js"><![CDATA[js]]></script>';
        
        
        $output[] = '<script type="text/javascript">'
            . 'SyntaxHighlighter.autoloader(
                "js ' . plugins_url('js/shBrushJScript.js', __FILE__) . '",
                "java ' . plugins_url('js/shBrushJava.js', __FILE__) . '"
            )</script>';
        
        $output[] = '<script type="text/javascript">'
            . 'SyntaxHighlighter.all()</script>';
    }
    
}

?>