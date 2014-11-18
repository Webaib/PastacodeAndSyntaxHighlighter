(function() {

	function fields(provider, pfields, values) {
		var fields = [];
		for ( var k in pfields) {
			// Push existing values
			if (typeof values != 'undefined' && typeof values[k] != 'undefined') {
				pfields[k].value = values[k];
			} else if (typeof pfields[k].text != 'null') {
					pfields[k].value = pfields[k].text;
			}
			if (typeof pfields[k]['classes'] != 'undefined') {
				if (pfields[k]['classes'].indexOf(provider) != -1) {
					fields.push(pfields[k]);
				}
			} else {
				if (pfields[k]['name'] == 'lang') {
					fields.push(pfields[k]);
				}
			}
		}

		fields.push({
			type : 'textbox',
			visible : false,
			value : provider,
			name : 'provider'
		});
		return fields;
	}

	function theFunction(key, editor, pvars) {
		fn = function() {
			editor.windowManager.open({
				title : pcshText['window-title'] + ' - ' + pvars[key],
				body : fields(key, pcshVars['fields']),
				onsubmit : function(e) {
					var out = '';
					if (e.data['provider'] == 'manual') {
						var manual = e.data.manual;
						delete e.data.manual;
						out += '[pcsh';
						for ( var attr in e.data) {
							out += ' ' + attr + '="' + e.data[attr] + '"';
						}
						out += ']<pre><code>' + pcsh_esc_html(manual)
								+ '</code></pre>[/pcsh]';
					} else {
						out += '[pcsh';
						for ( var attr in e.data) {
							out += ' ' + attr + '="' + e.data[attr] + '"';
						}
						out += '/]';
					}
					editor.insertContent(out);
				}
			});
		};
		return fn;
	}

	function providers(editor, pvars) {
		var providers = [];
		for ( var key in pvars) {
			var provider = new Object();
			provider.text = pvars[key];
			provider.onclick = theFunction(key, editor, pvars);
			providers.push(provider);
		}
		;
		return providers;
	}

	function pcsh_esc_html(str) {
		return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;')
				.replace(/>/g, '&gt;').replace(/"/g, '&#34;').replace(/'/g,
						'&#039;');
	}

	tinymce.PluginManager.add('pcb', function(editor, url) {

		editor.addButton('pcb', {
			icon : 'pcb-icon',
			type : 'menubutton',
			menu : providers(editor, pcshVars['providers'])
		});

		// Replace shortcode
		editor.on('BeforeSetContent', function(event) {
			event.content = replacePCSHShortcodes(event.content);
		});

		// Restore shortcode
		editor.on('PostProcess', function(event) {
			if (event.get) {
				event.content = restorePCSHShortcodes(event.content);
			}
		});

		// Edit shortcode
		editor
				.on('mouseup',
						function(event) {
							var dom = editor.dom, node = event.target;

							function unselect() {
								dom.removeClass(dom
										.select('div.wp-pcsh-selected'),
										'wp-pcsh-selected');
							}

							if ((node.nodeName === 'DIV' && dom.getAttrib(node,
									'data-wp-pcsh'))
									|| (node.nodeName === 'SPAN' && dom
											.getAttrib(dom.getParents(node)[1],
													'data-wp-pcsh'))) {
								// Don't trigger on right-click
								if (event.button !== 2) {
									if (dom.hasClass(node,
											'wp-pcsh-selected')
											|| dom.hasClass(dom
													.getParents(node)[1],
													'wp-pcsh-selected')) {
										if (node.nodeName === 'DIV')
											editPCSH(node, editor);
										if (node.nodeName === 'SPAN')
											editPCSH(
													dom.getParents(node)[1],
													editor);
									} else {
										unselect();
										if (node.nodeName === 'DIV')
											dom.addClass(node,
													'wp-pcsh-selected');
										if (node.nodeName === 'SPAN')
											dom.addClass(
													dom.getParents(node)[1],
													'wp-pcsh-selected');
									}
								}
							} else {
								if (node.nodeName === 'BUTTON'
										&& dom.hasClass(
												dom.getParents(node)[1],
												'wp-pcsh-selected')
										&& event.button !== 2) { //
									if (dom.hasClass(node, 'remove')) {
										dom.remove(dom.getParents(node)[1]);
									} else {
										editPCSH(dom.getParents(node)[1],
												editor);
									}
								} else {
									unselect();
								}
							}
						});
	});

	var styleDiv = ' contenteditable="false"';

	// Replace shortcode
	function replacePCSHShortcodes(content) {
		var pcshShortcodeRegex = new RegExp(
				'\\[(\\[?)(pcsh)(?![\\w-])([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)(?:(\\/)\\]|\\](?:([^\\[]*(?:\\[(?!\\/\\2\\])[^\\[]*)*)(\\[\\/\\2\\]))?)(\\]?)',
				'g');
		return content.replace(pcshShortcodeRegex, function(match) {
			return htmlPCSH('wp-pcsh', match);
		});
	}

	function htmlPCSH(cls, data) {
		switch (getAttr(data, 'provider')) {
		case 'manual':
			var titre = getAttr(data, 'message');
			break;
		default:
			var titre = getAttr(data, 'path_id');
		}
		var l = getAttr(data, 'lines')
		if (l)
			titre += ' (' + l + ')';
		data = window.encodeURIComponent(data);
		return '<div style="background-image:url('
				+ pcshText['image-placeholder']
				+ ');" '
				+ styleDiv
				+ ' class="pasta-item wp-media mceItem '
				+ cls
				+ '" '
				+ 'data-wp-pcsh="'
				+ data
				+ '" data-mce-resize="false" data-mce-placeholder="1" ><button class="dashicons dashicons-edit edit">x</button><button class="dashicons dashicons-no-alt remove">x</button><span class="pcsh-shortcode-title">'
				+ titre + '</span></div>';
	}

	// Restore shortcode
	function restorePCSHShortcodes(content) {

		return content.replace(
				/(?:<p(?: [^>]+)?>)*(<div [^>]+>)(.*?)<\/div>(?:<\/p>)*/g,
				function(match, image) {
					var data = getAttr(image, 'data-wp-pcsh');

					if (data) {
						return '<p>' + data + '</p>';
					}

					return match;
				});
	}

	function getAttr(str, name) {
		name = new RegExp(name + '=\"([^\"]+)\"').exec(str);
		return name ? window.decodeURIComponent(name[1]) : '';
	}

	function getShortcodeContent(str) {
		var content = new RegExp("<pre><code>([^<]+)<\/code><\/pre>").exec(str);
		return content ? content[1].replace(/&amp;/g, '&')
				.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&#34;/g,
						'"').replace(/&#039;/g, "'") : '';
	}

	// Edit shortcode
	function editPCSH(node, editor) {
		var gallery, frame, data;

		if (node.nodeName !== 'DIV') {
			return;
		}

		data = window.decodeURIComponent(editor.dom.getAttrib(node,
				'data-wp-pcsh'));

		// Make sure we've selected a Pastacode node.
		if (editor.dom.hasClass(node, 'wp-pcsh')) {
			var provider = getAttr(data, 'provider');
			var values = [];
			for ( var field in pcshVars['fields']) {
				if (pcshVars['fields'][field].name == 'manual') {
					values[field] = getShortcodeContent(data);
				} else {
					values[field] = getAttr(data,
							pcshVars['fields'][field].name);
				}
			}

			var fn = theFunction(provider, editor, pcshVars['providers'],
					values);

			editor.windowManager.open({
				title : pcshText['window-title'] + ' - '
						+ pcshVars['providers'][provider],
				body : fields(provider, pcshVars['fields'], values),
				onsubmit : function(e) {
					var out = '';
					if (e.data['provider'] == 'manual') {
						var manual = pcsh_esc_html(e.data.manual);
						delete e.data.manual
						out += '[pcsh';
						for ( var attr in e.data) {
							out += ' ' + attr + '="' + e.data[attr] + '"';
						}
						out += ']<pre><code>' + manual
								+ '</code></pre>[/pcsh]';
					} else {
						out += '[pcsh';
						for ( var attr in e.data) {
							out += ' ' + attr + '="' + e.data[attr] + '"';
						}
						out += '/]';
					}
					var newNode = editor.dom
							.createFragment(replacePCSHShortcodes(out));
					editor.dom.replace(newNode, node);
				}
			});

		}
	}

})();