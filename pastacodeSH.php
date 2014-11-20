<?php
/**
 * Plugin Name: PCSH
 * Plugin URI: https://github.com/Webaib/PastacodeAndSyntaxHighlighter
 * Description: Insert a code from GitHub, Gist or whatever with SyntaxHighlighter. 
 * Version: 0.1 
 * Text Domain: pcsh
 * Author: Yury Pavlov, Willy Bahuaud, Julio Potier
 * Author URI: http://www.overscale.net  
 * Contributors: webaib, willybahuaud, juliobox
 */

require_once 'SHLoader.php';

const PCSH_VERSION = '0.1';

const INIT_SH = 'initSH';

$shState = array(INIT_SH => false);

add_action('plugins_loaded', 'pcsh_load_languages');

/**
 *  TBA
 * 
 *  @return unknown
 */
function pcsh_load_languages() {
    load_plugin_textdomain(
        'pcsh', 
        false, dirname(plugin_basename(__FILE__)) . '/languages/'
    ); 
}

//Register scripts
add_action('wp_enqueue_scripts', 'pcsh_enqueue_SyntaxHighlighterCSS');

/**
 * TBA
 *
 *  @return unknown
 */
function pcsh_enqueue_SyntaxHighlighterCSS() {
	wp_register_style(
		'SyntaxHighlighterCSS',
        plugins_url(
            '/css/' . get_option('pcsh_style', 'SyntaxHighlighter') . '.css', 
            __FILE__
        ),
        false, PCSH_VERSION, 'all'
    );
}

add_shortcode('pcsh', 'sc_pcsh');

/**
 * TBA
 * 
 * @param unknown $atts    tba
 * @param string  $content tba
 * 
 * @return multitype:|string
 */
function sc_pcsh($atts, $content = "") {
	$atts = shortcode_atts(
	    array (
            'provider'      => '',
            'user'          => '',
            'path_id'       => '',
            'repos'         => '',
            'revision'      => 'master',
            'lines'         => '',
            'lang'          => 'markup',
            'message'       => '',
            'linenumbers'   => 'n',
            'showinvisible' => 'n',
            'tab_size'       => '4',
	    	'hl_lines'       => ''
	  ), $atts, 'sc_pcsh'
	);
	
	if (empty($atts['provider']) && ! empty($content)) {
        $atts['provider'] = md5($content);
	}
	
    $code_embed_transient = 'pcsh_' . substr(md5(serialize($atts)), 0, 14);
	
	$time = get_option('pcsh_cache_duration', DAY_IN_SECONDS * 7);
	
	if ($atts['provider'] == 'manual') {
        $time = - 1;
	}
    
    if ($time == - 1 || ! $source = get_transient($code_embed_transient)) {
		$source = apply_filters(
		    'pcsh_' . $atts['provider'], array (), $atts, $content
		);
		
		if (!empty($source['code'])) {
			// Wrap lines
			if ($lines = $atts['lines']) {
				$lines = array_map('intval', explode('-', $lines));
				$source['code'] = implode(
				    "\n", 
                    array_slice(
				        preg_split('/\r\n|\r|\n/', $source['code']), 
				        $lines[0]- 1, ($lines[1]- $lines[0]) + 1
                    )
                );
			}
			
			if ($time > - 1) {
				set_transient($code_embed_transient, $source, $time);
			}
		}
	}
	
	if (!empty($source['code'])) {
		// Load scripts
		wp_enqueue_style('SyntaxHighlighterCSS');
		// TODO highlight
//		if (preg_match('/([0-9-,]+)/', $atts['highlight'])) {
		
		// Wrap
		$output = array ();
		$output[] = '<div class="code-embed-wrapper">';
		
		$brush = 'brush:' . $atts['lang'] . ';';
		$tabSize = 'tab-size:' . $atts['tab_size'] . ';';
		$hlLines = 'highlight:[' . $atts['hl_lines'] . '];';
        
        // Wrap
        $output = array ();
        $output[] = '<div class="code-embed-wrapper">';
        
        $output[] = '<pre class="' . $brush . $tabSize . $hlLines . '">';
        $output[] = $source['code'];
        $output[] = '</pre>';
		
		$output[] = '<div align="right" class="code-embed-infos">';

		if (isset($source['url'])) {
			$output[] = '<a href="' . esc_url($source['url']) . '" title="' 
                . sprintf(esc_attr__('See %s', 'pcsh'), $source['name']) 
                . '" target="_blank" class="code-embed-name">' 
                . esc_html($source['name']) . '</a>';
		}
		
		if (isset($source['raw'])) {
			$output[] = '<a href="' . esc_url($source['raw']) . '" title="' 
			    . sprintf(esc_attr__('Back to %s'), $source['name']) 
                . '" class="code-embed-raw" target="_blank">' 
                . __('view raw', 'pcsh') . '</a>';
		}
		
		if (!isset($source['url']) && !isset($source['raw']) 
            && isset($source['name'])
		) {
            $output[] = '<span class="code-embed-name">' . $source['name']
                . '</span>';
		}
		
		$output[] = '</div>';
		$output[] = '</div>';
		
		callSH($atts, $output);
		
		$output = implode("\n", $output);
		
		return $output;
	} elseif (!empty($atts['message'])) {
		return '<span class="pcsh_message">' . esc_html($atts['message']) 
		  . '</span>';
	}
}

/**
 * TBA
 * 
 * @param array &$atts   tba
 * @param array &$output tba
 * 
 * @return unknown
 */
function callSH(array &$atts, array &$output) {
    global $shState;
    
    $loader = new SHLoader();
    if (!$shState[INIT_SH]) {
        $shState[INIT_SH] = true;
        $loader->initSH($output);
    }
    if (!$shState[$atts['lang']]) {
        $shState[$atts['lang']] = true;
        $loader->initSHLang($atts['lang'], $output);
    }
}

add_filter('pcsh_github', '_pcsh_github', 10, 2);

/**
 * TBA
 * 
 * @param unknown $source tba
 * @param unknown $atts   tba
 * 
 * @return unknown
 */
function _pcsh_github($source, $atts) {
    extract($atts);
    if ($user && $repos && $path_id) {
        $b64dcd = 'b'.'a'.'s'.'e'.'6'.'4'.'_'.'d'.'e'.'c'.'o'.'d'.'e';
        $req  = wp_sprintf(
            'https://api.github.com/repos/%s/%s/contents/%s', $user, $repos, $path_id
        );
        $code = wp_remote_get($req);
        if (!is_wp_error($code) && 200 == wp_remote_retrieve_response_code($code)) {
            $data = json_decode(wp_remote_retrieve_body($code));
            $source['name'] = $data->name;
            $source['code'] = esc_html($b64dcd($data->content));
            $source['url'] = $data->html_url;
            $source['raw'] = wp_sprintf(
                'https://raw.github.com/%s/%s/%s/%s', $user, $repos, $revision,
                $path_id
            );
        } else {
            $req2 = wp_sprintf(
                'https://raw.github.com/%s/%s/%s/%s', $user, $repos, $revision,
                $path_id
            );
            $code = wp_remote_get($req2);
            if (!is_wp_error($code) 
                && 200 == wp_remote_retrieve_response_code($code)
            ) {
                $name = explode('/', $path_id);
                $source['name'] = $name[count($name) - 1];
                $source['code'] = esc_html(wp_remote_retrieve_body($code));
                $source['url'] = wp_sprintf(
                    'https://github.com/%s/%s/blob/%s/%s', $user, $repos, $revision,
                    $path_id 
                );
                $source['raw'] = $req2;
            }
        }
    }
    
    return $source;
}

add_filter('pcsh_gist', '_pcsh_gist', 10, 2);

/**
 * TBA
 * 
 * @param unknown $source tba
 * @param unknown $atts   tba
 * 
 * @return unknown
 */
function _pcsh_gist($source, $atts) {
    extract($atts);
    if ($path_id) {
        $req  = wp_sprintf('https://api.github.com/gists/%s', $path_id);
        $code = wp_remote_get($req);
        if (!is_wp_error($code) && 200 == wp_remote_retrieve_response_code($code)) {
            $data = json_decode(wp_remote_retrieve_body($code));
            $source['url'] = $data->html_url;
            $data = (array)$data->files;
            $data = reset($data);
            $source['name'] = $data->filename;
            $source['code'] = esc_html($data->content);                 
            $source['raw'] = $data->raw_url;
        }
    }
    
    return $source;
}

add_filter('pcsh_bitbucket', '_pcsh_bitbucket', 10, 2);

/**
 * TBA
 * 
 * @param unknown $source tba
 * @param unknown $atts   tba
 * 
 * @return unknown
 */
function _pcsh_bitbucket($source, $atts) {
    extract($atts);
    if ($user && $repos && $path_id) {
        $req  = wp_sprintf(
            'https://bitbucket.org/api/1.0/repositories/%s/%s/raw/%s/%s', $user, 
            $repos, $revision, $path_id
        );

        $code = wp_remote_get($req);
        if (!is_wp_error($code) && 200 == wp_remote_retrieve_response_code($code)) {
            $source['name'] = basename($path_id);
            $source['code'] = esc_html(wp_remote_retrieve_body($code));
            $source['url'] = wp_sprintf(
                'https://bitbucket.org/%s/%s/src/%s/%s', $user, $repos, $revision, 
                $path_id
            );
            $source['raw'] = $req;
        }
    }
    
    return $source;
}

add_filter('pcsh_file', '_pcsh_file', 10, 2);

/**
 * TBA
 * 
 * @param unknown $source tba
 * @param unknown $atts   tba
 * 
 * @return unknown
 */
function _pcsh_file($source, $atts) {
    extract($atts);
    if ($path_id) {
        $upload_dir = wp_upload_dir();
        $path_id = str_replace('../', '', $path_id);
        $req  = esc_url(trailingslashit($upload_dir['baseurl']) . $path_id);
        $code = wp_remote_get($req);
        if (!is_wp_error($code) && 200 == wp_remote_retrieve_response_code($code)) {

            $source['name'] = basename($path_id);
            $source['code'] = esc_html(wp_remote_retrieve_body($code));
            $source['url'] = $req;
        }
    }
    
    return $source;
}

add_filter('pcsh_pastebin', '_pcsh_pastebin', 10, 2);

/**
 * TBA
 * 
 * @param unknown $source tba
 * @param unknown $atts   tba
 * 
 * @return unknown
 */
function _pcsh_pastebin($source, $atts) {
    extract($atts);
    if ($path_id) {
        $req  = wp_sprintf('http://pastebin.com/raw.php?i=%s', $path_id);
        $code = wp_remote_get($req);
        if (!is_wp_error($code) && 200 == wp_remote_retrieve_response_code($code)) {
            $source['name'] = $path_id;
            $source['code'] = esc_html(wp_remote_retrieve_body($code));
            $source['url'] = wp_sprintf('http://pastebin.com/%s', $path_id);
            $source['raw'] = wp_sprintf(
                'http://pastebin.com/raw.php?i=%s', $path_id
            );
        }
    }
    return $source;
}

add_filter('pcsh_manual', '_pcsh_manual', 10, 3);

/**
 * TBA
 * 
 * @param unknown $source  tba
 * @param unknown $atts    tba
 * @param unknown $content tba
 * 
 * @return unknown
 */
function _pcsh_manual($source, $atts, $content) {
    extract($atts);
    if (!empty($content)) {
        $source['code'] = esc_html(
            str_replace(
                array('<br>','<br />', '<br/>','</p>'."\n".'<pre><code>',
                        '</code></pre>'."\n".'<p>'), 
                array(''), 
                $content
            )
        );
    }
    if (isset($atts['message'])) {
        $source['name'] = esc_html($message);
    }
    
    return $source;
}


add_filter(
    'plugin_action_links_' . plugin_basename(__FILE__),
    'pcsh_settings_action_links', 10, 2
);

/**
 * TBA
 * 
 * @param unknown $links tba
 * @param unknown $file  tba
 * 
 * @return unknown
 */
function pcsh_settings_action_links($links, $file) {
    if (current_user_can('manage_options')) {
        array_unshift(
            $links, 
            '<a href="' . admin_url('options-general.php?page=pcsh') . '">' 
            . __('Settings') . '</a>'
        );
    }
    
    return $links;
}

add_filter('plugin_row_meta', 'pcsh_plugin_row_meta', 10, 2);

/**
 * TBA
 * 
 * @param unknown $plugin_meta tba
 * @param unknown $plugin_file tba
 * 
 * @return mixed
 */
function pcsh_plugin_row_meta($plugin_meta, $plugin_file) {
    if (plugin_basename(__FILE__) == $plugin_file) {
        $last = end($plugin_meta);
        $plugin_meta = array_slice($plugin_meta, 0, -2);
        $a = array();
        $authors = array(
            array('name'=>'Yury Pavlov', 'url'=>'http://www.overscale.net'),
            array('name'=>'Willy Bahuaud', 'url'=>'http://wabeo.fr'),
            array('name'=>'Julio Potier', 'url'=>'http://www.boiteaweb.fr')
        );
        
        foreach ($authors as $author) {
            $a[] = '<a href="' . $author['url']. '" title="' 
                . esc_attr__('Visit author homepage') . '">' . $author['name']
                . '</a>';
        }
        
        $a = sprintf(__('By %s'), wp_sprintf('%l', $a));
        $plugin_meta[] = $a;
        $plugin_meta[] = $last;
    }
    
    return $plugin_meta;
}

add_filter(
    'admin_post_pcsh_drop_transients', 'pcsh_drop_transients', 10, 2
);

/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_drop_transients() {
    if (isset($_GET['_wpnonce']) 
        && wp_verify_nonce($_GET['_wpnonce'], 'pcsh_drop_transients')
    ) {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM $wpdb->options WHERE option_name" 
            . " LIKE '_transient_pcsh_%'"
        );
        wp_redirect(wp_get_referer());
    } else {
        wp_nonce_ays('');
    }
}

/**
//Admin Settings
*/
add_action('admin_menu', 'pcsh_create_menu');

/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_create_menu() {
    add_options_page(
        'PCSH '. __('Settings'), 
        'PCSH', 
        'manage_options', 
        'pcsh',
        'pcsh_settings_page'
    );
    
    register_setting('pcsh', 'pcsh_cache_duration');
    register_setting('pcsh', 'pcsh_style');
}

/**
 * TBA
 * 
 * @param unknown $args tba
 * 
 * @return void
 */
function settingCallbackSelect($args) {
    extract($args);

    $value_old = get_option($name);
    
    echo '<select name="' . $name . '" id="' . $name . '">';
    foreach ($options as $key => $option) {
        echo '<option value="' . $key . '" ' 
            . selected($value_old==$key, true, false) . '>' . esc_html($option) 
            . '</option>';
    }
    
    echo '</select>';
}

/**
 * TBA
 *
 * @param unknown $args tba
 *
 * @return void
 */
function settingCallbackInput($args) {
    extract($args);
    
    $value_old = get_option($name);
    
    echo '<input name="' . $name . '" id="' . $name . '"/>';
}


/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_settings_page() {
    ?>
    <div class="wrap">
        <?php screen_icon(); ?>
        <h2>Pastacode and SyntaxHighlighter v<?php echo PCSH_VERSION; ?></h2>
    
        <?php 
        add_settings_section(
            'pcsh_setting_section', __('General Settings', 'pcsh'),
            '__return_false',
            'pcsh'
        );
    
        add_settings_field(
            'pcsh_cache_duration',
            __('Caching duration', 'pcsh'),
            'settingCallbackSelect',
            'pcsh',
            'pcsh_setting_section',
            array(
                'options' => array(
                    HOUR_IN_SECONDS      => sprintf(__('%s hour'), '1'),
                    HOUR_IN_SECONDS * 12 => __('Twice Daily'),
                    DAY_IN_SECONDS       => __('Once Daily'),
                    DAY_IN_SECONDS * 7   => __('Once Weekly', 'pcsh'),
                    0                    => __('Never reload', 'pcsh'),
                    -1                   => __('No cache (dev mode)', 'pcsh'),
                   ),
                'name' => 'pcsh_cache_duration'
            )
        );
    
        //TODO
        add_settings_field(
            'pcsh_style',
            __('Syntax Coloration Style', 'pcsh'),
            'settingCallbackSelect',
            'pcsh',
            'pcsh_setting_section',
            array(
                'options' => array(
                    'shThemeDefault'    => 'Default',
                    'shThemeDjango'     => 'Django',
                    'shThemeEclipse'    => 'Eclipse',
                    'shThemeEmacs'      => 'Emacs',
                    'shThemeFadeToGrey' => 'FadeToGrey',
                    'shThemeMDUltra'    => 'MDUltra',
                    'shThemeMidnight'   => 'Midnight',
                    'shThemeRDark'      => 'RDark',
                   ),
                'name' => 'pcsh_style'
            )
        );    
        ?>
        
        <form method="post" action="options.php">
            <?php 
                settings_fields('pcsh');
                do_settings_sections('pcsh');
                $url = wp_nonce_url(
                    admin_url('admin-post.php?action=pcsh_drop_transients'), 
                    'pcsh_drop_transients'
                );
                
                global $wpdb;
                
                $transients = $wpdb->get_var(
                    "SELECT count(option_name) FROM $wpdb->options" 
                    . " WHERE option_name LIKE '_transient_pcsh_%'"
                );
                
                echo '<p class="submit">';
                    submit_button('', 'primary large', 'submit', false);
                    echo ' <a href="' . $url 
                        . '" class="button button-large button-secondary">'
                        . __('Purge cache', 'pcsh') . ' (' . (int)$transients 
                        . ')</a>';
                echo '</p>';
            ?>
        </form>
    </div>
    <?php
}

register_activation_hook(__FILE__, 'pcsh_activation');

/**
 * TBA
 * 
 * @return unknown 
 */
function pcsh_activation() {
    add_option('pcsh_cache_duration', DAY_IN_SECONDS * 7);
    add_option('pcsh_style', 'shCoreDefault');
}

register_uninstall_hook(__FILE__, 'pcsh_uninstaller');

/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_uninstaller() {
    delete_option('pcsh_cache_duration');
    delete_option('pcsh_style');
}

/**
Add button to tinymce
*/
//Button
add_action('admin_init', 'pcsh_button_editor');

/**
 * TBA
 * 
 * @return boolean
 */
function pcsh_button_editor() {
    // Don't bother doing this stuff if the current user lacks permissions
    if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
        return false;
    }

    if (get_user_option('rich_editing') == 'true') {
        add_filter('mce_external_plugins', 'pcsh_script_tiny');
        add_filter('mce_buttons', 'pcsh_register_button');
    }
}

/**
 * TBA
 * 
 * @param unknown $buttons tba
 * 
 * @return unknown
 */
function pcsh_register_button($buttons) {
    array_push($buttons, "|", "pcb");
    
    return $buttons;
}

/**
 * TBA
 * 
 * @param unknown $plugin_array tba
 * 
 * @return unknown
 */
function pcsh_script_tiny($plugin_array) {
    $plugin_array['pcb'] = plugins_url('/js/tinymce.js', __FILE__);
    
    return $plugin_array;
}

add_action('admin_enqueue_scripts', 'pcsh_shortcodes_mce_css');

/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_shortcodes_mce_css() {
    wp_enqueue_style(
        'pcsh-shortcode', plugins_url('/css/pcsh-tinymce.css', __FILE__)
    );
}

add_action('admin_init', 'add_pcsh_styles_to_editor');

/**
 * TBA
 * 
 * @return unknown
 */
function add_pcsh_styles_to_editor() {
    global $editor_styles;
    $editor_styles[] = plugins_url('/css/pcsh-tinymce.css', __FILE__);
}

add_action('before_wp_tiny_mce', 'pcsh_text');

/**
 * TBA
 * 
 * @return unknown
 */
function pcsh_text() {
    // I10n
    $text = json_encode(
        array(
            'window-title' => __('Past\'a code', 'pcsh'),
            'label-provider' => __('Select a provider', 'pcsh'),
            'label-langs' => __('Select a syntax', 'pcsh'),
            'image-placeholder' => plugins_url(
                '/images/pcsh-placeholder.png', __FILE__
            )
        )
    );

    // Services
    $services = array(
                    'manual' => __('Manual', 'pcsh'),
                    'github'    => 'Github',
                    'gist'      => 'Gist',
                    'bitbucket' => 'Bitbucket',
                    'pastebin'  => 'Pastebin',
                    'file'      => __('File from uploads', 'pcsh')
    );

    $services = apply_filters('pcsh_services', $services);

    // Languages
    $langs  = array(
        'applescript'        => 'AppleScript',
        'as3'       => 'ActionScript3',
        'bash'      => 'Bash',
        'cf'        => 'CoffeeScript',
        'cpp'       => 'C++',
        'csharp'    => 'C#',
        'css'       => 'CSS',
        'pascal'    => 'Pascal',
        'diff'      => 'Diff',
        'erlang'    => 'Erlang',
        'groovy'    => 'Groovy',
        'haxe'      => 'Haxe',
        'java'      => 'Java',
        'javafx'    => 'JavaFX',
        'js'        => 'JavaScript',
        'perl'      => 'Perl',
        'php'       => 'PHP',
        'plain'     => 'Plain',
        'python'    => 'Python',
        'ruby'      => 'Ruby',
        'scala'     => 'Scala',
        'sql'       => 'SQL',
        'vb'        => 'VisualBasic',
        'xml'       => 'XML'
    );
    $langs = apply_filters('pcsh_langs', $langs);
    
    // Other fields
    $fields = array(
        'username' => array(
            'classes'       => array('github','bitbucket'),
            'label'         => __('User of repository', 'pcsh'), 
            'placeholder'   => __('John Doe', 'pcsh'), 
            'name'          => 'user'
        ),
        'repository' => array(
            'classes'       => array('github','bitbucket'), 
            'label'         => __('Repository', 'pcsh'), 
            'placeholder'   => __('pcsh', 'pcsh'), 
            'name'          => 'repos'
        ),
        'path-id' => array(
            'classes'       => array('gist','pastebin'), 
            'label'         => __('Code ID', 'pcsh'), 
            'placeholder'   => '123456', 
            'name'          => 'path_id'
        ),
        'path-repo' => array(
            'classes'       => array('github','bitbucket'), 
            'label'         => __('File path inside the repository', 'pcsh'), 
            'placeholder'   => __('bin/foobar.php', 'pastebin'), 
            'name'          => 'path_id'
        ),
        'path-up' => array(
            'classes'       => array('file'), 
            'label'         => sprintf(
                __('File path relative to %s', 'pcsh'), 
                esc_html(WP_CONTENT_URL)
            ), 
            'placeholder'   => date('Y/m').'/source.txt', 
            'name'          => 'path_id'
        ),
        'revision' => array(
            'classes'       => array('github','bitbucket'),
            'label'         => __('Revision', 'pcsh'),
            'placeholder'   => __('master', 'pcsh'), 
            'name'          => 'revision'
        ),
        'manual' => array(
            'classes'       => array('manual'), 
            'label'         => __('Code', 'pcsh'), 
            'name'          => 'manual'
        ),
   		'pcsh-tabsize' => array(
			'classes' => array(
   					'github', 'gist', 'bitbucket', 'pastebin', 'file',
   					'manual'
			),
   				'label'         => __('Tab size', 'pcsh'),
   				'placeholder'   => '1-20',
   				'text'			=> '4',
   				'name'          => 'tab_size'
   		),
        'message' => array(
            'classes'       => array('manual'), 
            'label'         => __('Code title', 'pcsh'),
            'placeholder'   => __('title', 'pcsh'),
            'name'          => 'message'
        ),
        'pcsh-highlight' => array(
            'classes'       => array(
                'manual', 'github', 'gist', 'bitbucket', 'pastebin', 'file'
             ),
             'label'        => __('Highlited lines', 'pcsh'), 
             'placeholder'  => '1,2,3,6', 
             'name'         => 'hl_lines'
        )
    );
    
    $fields = apply_filters('pcsh_fields', $fields);

    $newFields = array();
    $newLangs = array();
    foreach ($langs as $k => $s) {
        $newLangs[] = array('text' => $s, 'value' => $k);
    }
    
    $newFields[] = array(
        'type'      => 'listbox', 
        'label'     => __('Select a syntax', 'pcsh'), 
        'name'      => 'lang', 
        'values'    => $newLangs
    );

    $pvars['providers'] = $services;

    foreach ($fields as $k => $f) {
        $field = array(
            'type' => 'textbox',
            'name' => $f['name'],
            'label' => $f['label'],
        	'text' => $f['text'],
            'classes' => 'field-to-test field pcsh-args ' 
                . implode(' ', $f['classes'])
        );

        if (!isset($f['placeholder'])) {
            $field['multiline'] = true;
            $field['minWidth'] = 300;
            $field['minHeight'] = 100;
        } else {
            $field['tooltip'] = $f['placeholder'];
        }
        
        $newFields[] = $field;
    }

    $pvars['fields'] = $newFields;

    // Print Vars
    $pvars = json_encode($pvars);
    echo '<script>var pcshText = ' . $text . ';var pcshVars = ' 
		. $pvars . ';</script>';
    
}
