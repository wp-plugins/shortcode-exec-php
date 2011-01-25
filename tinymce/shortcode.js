(function() {
	tinymce.create('tinymce.plugins.ShortcodePlugin', {
		init: function(ed, url) {
			// Register command
			ed.addCommand('mceShortcode', function() {
				ed.windowManager.open({
					file: ajaxurl + '?action=scep_ajax&scep_action=tinymce',
					width: 320 + ed.getLang('Shortcode.delta_width', 0),
					height: 120 + ed.getLang('Shortcode.delta_height', 0),
					inline: 1
				}, {
					plugin_url: url // Plugin absolute URL
				});
			});

			// Register button
			ed.addButton('Shortcode', {
				title: 'Shortcode.desc',
				cmd: 'mceShortcode',
				image: url + '/shortcode.gif'
			});

			// Add a node change handler, selects the button in the UI when a image is selected
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('Shortcode', n.nodeName == 'IMG');
			});
		},

		createControl: function(n, cm) {
			return null;
		},

		getInfo: function() {
			return {
				longname : 'Shortcode plugin',
				author : 'Marcel Bokhorst',
				authorurl : 'http://blog.bokhorst.biz/about/',
				infourl : 'http://blog.bokhorst.biz/3626/computers-en-internet/wordpress-plugin-shortcode-exec-php/',
				version : '1.0'
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('Shortcode', tinymce.plugins.ShortcodePlugin);
})();
