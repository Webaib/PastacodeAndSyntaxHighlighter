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
    
    private $brushMap = array(
        'ap'        => 'js/shBrushAppleScript.js',
        'as3'       => 'js/shBrushAS3.js',
        'bash'      => 'js/shBrushBash.js',
        'cf'        => 'js/shBrushColdFusion.js',
        'cpp'       => 'js/shBrushCpp.js',
        'csharp'    => 'js/shBrushCSharp.js',
        'css'       => 'js/shBrushCss.js',
        'pascal'    => 'js/shBrushDelphi.js',
        'diff'      => 'js/shBrushDiff.js',
        'erlang'    => 'js/shBrushErlang.js',
        'groovy'    => 'js/shBrushGroovy.js',
        'haxe'      => 'js/shBrushHaxe.js',
        'java'      => 'js/shBrushJava.js',
        'javafx'    => 'js/shBrushJavaFX.js',
        'js'        => 'js/shBrushJScript.js',
        'perl'      => 'js/shBrushPerl.js',
        'php'       => 'js/shBrushPhp.js',
        'plain'     => 'js/shBrushPlain.js',
        'python'    => 'js/shBrushPython.js',
        'ruby'      => 'js/shBrushRuby.js',
        'scala'     => 'js/shBrushScala.js',
        'sql'       => 'js/shBrushSql.js',
        'vb'        => 'js/shBrushVb.js',
        'xml'       => 'js/shBrushXml.js'
    );
    
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
        
        $output[] = '<script type="text/javascript">'
            . 'SyntaxHighlighter.all()</script>';
    }
    
    /**
     * TBA
     * 
     * @param unknown $lang    tba
     * @param array   &$output tba
     * 
     * @return void
     */ 
    public function initSHLang($lang, array &$output) {
        $output[] = '<script type="text/javascript" src="' 
            . plugins_url($this->brushMap[$lang], __FILE__) . '"></script>';
    }
}

?>