=== PCSH (PastaCode and SyntaxHighlighter) ===
Contributors: webaib, willybahuaud, juliobox
Tags: embed, code, version, github, bitbucket, gist, code, SyntaxHighlighter, color highlight, syntaxique coloration
Requires at least: 3.1
Tested up to: 4.0
Stable tag: 0.2
License: GPLv2 or later

Use PCSH to add code into your posts with the awesome SyntaxHighlighter plugin. So, past'a code!

== Description ==

With PCSH, you can easily add code into your posts with the awesome SyntaxHighlighter js-library.
You can insert source code into the post editor, from a file, or from webservices like GitHub, Gist, Pastebin or BitBucket. Webservices responses are cached in order to avoid too many HTTP requests.

Don't worry about posts updates while upgrading codes!

PCSH allows to enhance your snippets using SyntaxHighlighter plugins (highlightning lines, link functions...).

8 different color schemes are included, and you can build yours.

Available programming languages:

* AppleScript
* ActionScript3
* Bash
* CoffeeScript
* C++
* C#
* CSS
* Pascal
* Diff
* Erlang
* Groovy
* Haxe
* Java
* JavaFX
* JavaScript
* Perl
* PHP
* Plain
* Python
* Ruby
* Scala
* SQL
* VisualBasic
* XML


== Installation ==

1. Unzip PCSH into your plugin folder
2. Go to PCSH settings, and configure your color scheme and cache expiration
3. Host your snippets on repositories (or localy)
4. Editing a post, use *PCSH* button to embed your source code into articles

= Ajax compatibility =

To enable PCSH on ajax based websites, it need two steps :

1. Paste this line into your functions.php theme file : "add_filter( 'pcsh_ajax', '__return_true' );"
2. After each change on your DOM, you will have to run this javascript function : "SyntaxHighlighter.all();"

== Frequently Asked Questions ==

= How to setup a custom cache expiration ? =

Paste these lines into your functions.php theme file :
"add_filter( 'option_pcsh_cache_duration', 'my_pcsh_cache_duration' );
function my_pcsh_cache_duration( $duration ) {
    $duration = DAY_IN_SECOND*3; // 3 days
    return $duration;
}"

= How define a custom color scheme ? =

Paste these lines into your functions.php theme file :
"add_filter( 'option_pcsh_style', 'my_pcsh_style' );
function my_pcsh_style( $scheme ) {
    $scheme = 'my_awesome_style'; //CSS filename into the plugin css directory
    return $scheme;
}"

= How to filter supported languages ? =

Paste these lines into your functions.php theme file :
"//If you just want php, html, css and javascript support
add_filter( 'pcsh_langs', '_pcsh_langs' );
function _pcsh_langs( $langs ) {
    $langs  = array(
        'php'          => 'PHP',
        'markup'       => 'HTML',
        'css'          => 'CSS',
        'javascript'   => 'JavaScript'
    );
    
    return $langs;
}"

= How to add a new provider ? =

Paste these lines into your functions.php theme file :
"//Take WordPress SVN, for example
//register a provider
add_filter( 'pcsh_services', '_pcsh_services' );
function _pcsh_services( $services ) {
    $services['wordpress'] = 'core.svn.wordpress.org';
    return $services;
}

//Define pastabox lightbox inputs
add_action( 'pcsh_fields', '_pcsh_fields' );
function _pcsh_fields( $fields ) { 
    $fields['wordpress'] = array(  // 'wordpress' or 'whatever'
        'classes'     => array( 'wordpress' ), // same value as the key
        'label'       => sprintf( __('File path relative to %s', 'pcsh'), 'http://core.svn.wordpress.org/' ), 
        'placeholder' =>'trunk/wp-config-sample.php', //if placeholder isn't defined, it will be a textarea
        'name'        => 'path_id' //these value return shortcode attribute (path_id, repos, name, user, version)
        );
    return $fields;
}

//Build the function to retrieve the code
// "pcsh_wordpress" hook name (1st param) = "pcsh_" + "wordpress" or "whatever"
add_action( 'pcsh_wordpress', '_pcsh_wordpress', 10, 2 );
function _pcsh_wordpress( $source, $atts ) {
    extract( $atts );
    if( $path_id ) {
        $req  = wp_sprintf( 'http://core.svn.wordpress.org/%s', str_replace( 'http://core.svn.wordpress.org/', '', $path_id ) );
        $code = wp_remote_get( $req );
        if( ! is_wp_error( $code ) && 200 == wp_remote_retrieve_response_code( $code ) ) {
            $data = wp_remote_retrieve_body( $code );
            $source[ 'url' ]  = $req; //url to view source
            $source[ 'name' ] = basename( $req ); //filename
            $source[ 'code' ] = esc_html( $data ); //the code !!   
            //$source[ 'raw' ] contain raw source code. But there are no raw source code delivered by Wordpress SVN             
        }
    }
    return $source;
}"

Do not add you root website!! A contributor can add the shortcode to point your "wp-config.php" to read it!!

== Screenshots ==

1. View of the PCSH code lightbox
2. Default color scheme
3. *Django* color scheme
4. *Eclipse* color scheme
5. *Emacs* color scheme
6. *FadeToGrey* color scheme
7. *MDUltra* color scheme
8. *Midnight* color scheme
9. *RDark* color scheme

== Changelog ==
= 0.2 =
* 20 November 2014
* Add russian translation

= 0.1 =
* 19 November 2014
* Prism replaced by SyntaxHighlighter
* Renamed all settings, functions and co. to eliminate any conflicts with the original plugin.  

= 1.3* =
* 5 may 2014
* TinyMCE Editor support improvment (visual placeholder on editor mode, new tinyMCE button...)
* Github API restriction fallback (support now more than 30 requests / hour)
* New ajax compatibility (using hook pcsh_ajax)
* Fix bug: No more disgracefull linebreaks on code view.

= 1.2.1* =
* 21 nov 2013
* Fix bug: when manual provider is selected, no cache.

= 1.2* =
* 15 oct 2013
* The modification of the cache duration do not purge cache anymore
* New button "Purge Cache" in option page, use it to delete all transients (they contains the responded source codes)
* Fix bug when updating option

= 1.1* =
* 12 oct 2013
* Hooks, hooks and hooks.
* Update shortcode format ("type" became "provider", and add "/" before the closing tag)

= 1.0* =
* 10 oct 2013
* Initial release
* Insert codes using a nice lightbox
* Import codes from file, Github, Gist, Pastebin or BitBucket
* 13 languages available
* 6 color schemes
* Cache support for webservices (default duration : 1 week)