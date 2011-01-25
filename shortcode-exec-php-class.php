<?php

/*
	Support class Shortcode Exec PHP Plugin
	Copyright (c) 2010, 2011 by Marcel Bokhorst
*/

// Define constants
define('c_scep_option_global', 'scep_global');
define('c_scep_option_widget', 'scep_widget');
define('c_scep_option_excerpt', 'scep_excerpt');
define('c_scep_option_comment', 'scep_comment');
define('c_scep_option_rss', 'scep_rss');
define('c_scep_option_noent', 'scep_noent');
define('c_scep_option_cleanup', 'scep_cleanup');
define('c_scep_option_donated', 'scep_donated');
define('c_scep_option_codewidth', 'scep_codewidth');
define('c_scep_option_codeheight', 'scep_codeheight');
define('c_scep_option_backtrack_limit', 'scep_backtrack_limit');
define('c_scep_option_recursion_limit', 'scep_recursion_limit');
define('c_scep_option_editarea_later', 'scep_editarea_later');

define('c_scep_option_names', 'scep_names');
define('c_scep_option_deleted', 'scep_deleted');
define('c_scep_option_enabled', 'scep_enabled_');
define('c_scep_option_buffer', 'scep_buffer_');
define('c_scep_option_phpcode', 'scep_phpcode_');

define('c_scep_form_enabled', 'scep_enabled');
define('c_scep_form_buffer', 'scep_buffer');
define('c_scep_form_shortcode', 'scep_shortcode');
define('c_scep_form_phpcode', 'scep_phpcode');

define('c_scep_nonce_form', 'scep-nonce-form');
define('c_scep_text_domain', 'shortcode-exec-php');

define('c_scep_action_arg', 'scep_action');
define('c_scep_param_nonce', 'nonce');
define('c_scep_param_name', 'name');
define('c_scep_param_shortcode', 'shortcode');
define('c_scep_param_enabled', 'enabled');
define('c_scep_param_buffer', 'buffer');
define('c_scep_param_phpcode', 'phpcode');
define('c_scep_action_save', 'save');
define('c_scep_action_test', 'test');
define('c_scep_action_delete', 'delete');
define('c_scep_action_new', 'new');
define('c_scep_action_revert', 'revert');
define('c_scep_action_tinymce', 'tinymce');

define('c_scep_nonce_ajax', 'scep-nonce-ajax');

// Define class
if (!class_exists('WPShortcodeExecPHP')) {
	class WPShortcodeExecPHP {
		// Class variables
		var $main_file = null;
		var $plugin_url = null;
		var $default_backtrack_limit = null;
		var $default_recursion_limit = null;

		// Constructor
		function __construct() {
			$bt = debug_backtrace();
			$this->main_file = $bt[0]['file'];
			$this->plugin_url = WP_PLUGIN_URL . '/' . basename(dirname($this->main_file));
			if (strpos($this->plugin_url, 'http') === 0 && is_ssl())
				$this->plugin_url = str_replace('http://', 'https://', $this->plugin_url);

			// Register (de)activation hook
			register_activation_hook($this->main_file, array(&$this, 'Activate'));
			register_deactivation_hook($this->main_file, array(&$this, 'Deactivate'));

			// Register actions
			add_action('init', array(&$this, 'Init'), 0);
			if (is_admin()) {
				add_action('admin_menu', array(&$this, 'Admin_menu'));
				add_action('wp_ajax_scep_ajax', array(&$this, 'Check_ajax'));
			}

			// Enable shortcodes for widgets
			if (self::Get_option(c_scep_option_widget))
				add_filter('widget_text', 'do_shortcode');

			// Enable shortcodes for excerpts
			if (self::Get_option(c_scep_option_excerpt))
			{
				add_filter('the_excerpt', 'do_shortcode');
				if (self::Get_option(c_scep_option_comment))
					add_filter('comment_excerpt', 'do_shortcode');
			}

			// Enable shortcodes for comments
			if (self::Get_option(c_scep_option_comment))
				add_filter('comment_text', 'do_shortcode');

			// Enable shortcodes for RSS
			if (self::Get_option(c_scep_option_rss)) {
				add_filter('the_content_rss', 'do_shortcode');
				if (self::Get_option(c_scep_option_excerpt))
					add_filter('the_excerpt_rss', 'do_shortcode');
				if (self::Get_option(c_scep_option_comment))
					add_filter('comment_text_rss', 'do_shortcode');
			}

			// Wire shortcode handlers
			$name = self::Get_option(c_scep_option_names);
			for ($i = 0; $i < count($name); $i++)
				if (self::Get_option(c_scep_option_enabled . $name[$i]))
					add_shortcode($name[$i], array(&$this, 'Shortcode_handler'));

			$this->default_backtrack_limit = ini_get('pcre.backtrack_limit');
			$this->default_recursion_limit = ini_get('pcre.recursion_limit');
			$this->Configure_prce();
		}

		function Configure_prce() {
			$backtrack_limit = self::Get_option(c_scep_option_backtrack_limit);
			if ($backtrack_limit < $this->default_backtrack_limit)
				$backtrack_limit = $this->default_backtrack_limit;
			ini_set('pcre.backtrack_limit', $backtrack_limit);

			$recursion_limit = self::Get_option(c_scep_option_recursion_limit);
			if ($recursion_limit < $this->default_recursion_limit)
				$recursion_limit = $this->default_recursion_limit;
			ini_set('pcre.recursion_limit', $recursion_limit);
		}

		function Init() {
			if (is_admin()) {
				// I18n
				load_plugin_textdomain(c_scep_text_domain, false, dirname(plugin_basename(__FILE__)));

				// Enqueue scripts
				wp_enqueue_script('jquery');
				$plugin_dir = '/' . PLUGINDIR .  '/' . basename(dirname($this->main_file));
				wp_enqueue_script('editarea', $plugin_dir . '/editarea/edit_area/edit_area_full.js');

				// Enqueue style sheet
				$css_name = $this->Change_extension(basename($this->main_file), '.css');
				if (file_exists(WP_CONTENT_DIR . '/uploads/' . $css_name))
					$css_url = WP_CONTENT_URL . '/uploads/' . $css_name;
				else if (file_exists(TEMPLATEPATH . '/' . $css_name))
					$css_url = get_bloginfo('template_directory') . '/' . $css_name;
				else
					$css_url = $this->plugin_url . '/' . $css_name;
				wp_register_style('scep_style', $css_url);
				wp_enqueue_style('scep_style');

				// http://codex.wordpress.org/TinyMCE_Custom_Buttons
				if (current_user_can('edit_posts') || current_user_can('edit_pages'))
					if (get_user_option('rich_editing')) {
						add_filter('tiny_mce_version', array(&$this, 'TinyMCE_version') );
						add_filter('mce_external_plugins', array(&$this, 'TinyMCE_plugin'));
						add_filter('mce_buttons', array(&$this, 'TinyMCE_button'));
					}
			}
		}

		// Handle plugin activation
		function Activate() {
			if (!self::Get_option(c_scep_option_codewidth))
				self::Update_option(c_scep_option_codewidth,  600);
			if (!self::Get_option(c_scep_option_codeheight))
				self::Update_option(c_scep_option_codeheight, 200);

			if (!self::Get_option(c_scep_option_names)) {
				// Define example shortcode
				$name = array();
				$name[] = 'hello_world';
				self::Update_option(c_scep_option_names, $name);
				self::Update_option(c_scep_option_enabled . $name[0], true);
				self::Update_option(c_scep_option_buffer . $name[0], false);
				self::Update_option(c_scep_option_phpcode . $name[0], "return 'Hello world!';");
			}

			// Fix spelling mistake
			if (self::Get_option('scep_backtrace_limit')) {
				self::Update_option(c_scep_option_backtrack_limit, self::Get_option('scep_backtrace_limit'));
				self::Delete_option('scep_backtrace_limit');
			}
		}

		// Handle plugin deactivation
		function Deactivate() {
			// Cleanup if requested
			if (self::Get_option(c_scep_option_cleanup)) {
				delete_site_option(c_scep_option_global);
				self::Delete_option(c_scep_option_widget);
				self::Delete_option(c_scep_option_excerpt);
				self::Delete_option(c_scep_option_comment);
				self::Delete_option(c_scep_option_rss);
				self::Delete_option(c_scep_option_noent);
				self::Delete_option(c_scep_option_codewidth);
				self::Delete_option(c_scep_option_codeheight);
				self::Delete_option(c_scep_option_editarea_later);
				self::Delete_option(c_scep_option_backtrack_limit);
				self::Delete_option(c_scep_option_recursion_limit);
				self::Delete_option(c_scep_option_cleanup);
				self::Delete_option(c_scep_option_donated);

				$name = self::Get_option(c_scep_option_names);
				for ($i = 0; $i < count($name); $i++) {
					self::Delete_option(c_scep_option_enabled . $name[$i]);
					self::Delete_option(c_scep_option_buffer . $name[$i]);
					self::Delete_option(c_scep_option_phpcode . $name[$i]);
				}
				self::Delete_option(c_scep_option_names);
			}
		}

		// Admin head
		function Admin_head() {
			// Initialize EditArea
			$name = self::Get_option(c_scep_option_names);
			$display = self::Get_option(c_scep_option_editarea_later) ? 'later' : 'onload';

			echo '<script language="javascript" type="text/javascript">' . PHP_EOL;
			for ($i = 0; $i < count($name); $i++)
				echo 'editAreaLoader.init({id: "' . c_scep_form_phpcode . ($i + 1) . '", syntax: "php", start_highlight: true, display: "' . $display . '"});' . PHP_EOL;
			echo 'editAreaLoader.init({id: "' . c_scep_form_phpcode . '0", syntax: "php", start_highlight: true, display: "' . $display . '", EA_load_callback: "window.scrollTo(0,0);"});' . PHP_EOL;
			echo '</script>' . PHP_EOL;
		}

		// Register options page
		function Admin_menu() {
			if (self::Is_multisite()) {
				if (function_exists('add_submenu_page'))
					$plugin_page = add_submenu_page(
						'wpmu-admin.php',
						__('Shortcode Exec PHP Administration', c_scep_text_domain),
						__('Shortcode Exec PHP', c_scep_text_domain),
						'manage_network',
						$this->main_file,
						array(&$this, 'Administration'));
			}
			else
				if (function_exists('add_options_page'))
					$plugin_page = add_options_page(
						__('Shortcode Exec PHP Administration', c_scep_text_domain),
						__('Shortcode Exec PHP', c_scep_text_domain),
						'manage_options',
						$this->main_file,
						array(&$this, 'Administration'));

			// Hook admin head for option page
			add_action('admin_head-' . $plugin_page, array(&$this, 'Admin_head'));
		}

		// Handle option page
		function Administration() {
			if (self::Is_multisite()) {
				if (!current_user_can('manage_network'))
					die('Unauthorized');
			}
			else
				if (!current_user_can('manage_options'))
					die('Unauthorized');

			// Check post back
			if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
				// Check security
				check_admin_referer(c_scep_nonce_form);

				// Update settings
				if (self::Is_multisite() && function_exists('update_site_option'))
					update_site_option(c_scep_option_global, $_POST[c_scep_option_global]);
				self::Update_option(c_scep_option_widget, $_POST[c_scep_option_widget]);
				self::Update_option(c_scep_option_excerpt, $_POST[c_scep_option_excerpt]);
				self::Update_option(c_scep_option_comment, $_POST[c_scep_option_comment]);
				self::Update_option(c_scep_option_rss, $_POST[c_scep_option_rss]);
				self::Update_option(c_scep_option_noent, $_POST[c_scep_option_noent]);
				self::Update_option(c_scep_option_codewidth, $_POST[c_scep_option_codewidth]);
				self::Update_option(c_scep_option_codeheight, $_POST[c_scep_option_codeheight]);
				self::Update_option(c_scep_option_editarea_later, $_POST[c_scep_option_editarea_later]);
				self::Update_option(c_scep_option_backtrack_limit, $_POST[c_scep_option_backtrack_limit]);
				self::Update_option(c_scep_option_recursion_limit, $_POST[c_scep_option_recursion_limit]);
				self::Update_option(c_scep_option_cleanup, $_POST[c_scep_option_cleanup]);
				self::Update_option(c_scep_option_donated, $_POST[c_scep_option_donated]);

				$this->Configure_prce();

				// Copy options to site wide
				if ($_POST[c_scep_option_global] && self::Is_multisite() && function_exists('update_site_option')) {
					$name = get_option(c_scep_option_names);
					update_site_option(c_scep_option_names, $name);
					for ($i = 0; $i < count($name); $i++) {
						update_site_option(c_scep_option_enabled . $name[$i], get_option(c_scep_option_enabled . $name[$i]));
						update_site_option(c_scep_option_buffer . $name[$i], get_option(c_scep_option_buffer . $name[$i]));
						update_site_option(c_scep_option_phpcode . $name[$i], get_option(c_scep_option_phpcode . $name[$i]));
					}
				}

				echo '<div id="message" class="updated fade"><p><strong>' . __('Settings updated', c_scep_text_domain) . '</strong></p></div>';
			}

			// Sustainable Plugins Sponsorship Network
			$this->Render_pluginsponsor();

			echo '<div class="wrap">';

			// Render info panel
			$this->Render_info_panel();

			echo '<div id="scep_admin_panel">';

			// Render title
			echo '<h2>' . __('Shortcode Exec PHP Administration', c_scep_text_domain) . '</h2>';
			echo '<form method="post" action="">';

			// Security
			wp_nonce_field(c_scep_nonce_form);
			$nonce = wp_create_nonce(c_scep_nonce_ajax);

			// Get current settings
			$scep_global = (self::Is_multisite() && function_exists('get_site_option') && get_site_option(c_scep_option_global) ? 'checked="checked"' : '');
			$scep_widget = (self::Get_option(c_scep_option_widget) ? 'checked="checked"' : '');
			$scep_excerpt = (self::Get_option(c_scep_option_excerpt) ? 'checked="checked"' : '');
			$scep_comment = (self::Get_option(c_scep_option_comment) ? 'checked="checked"' : '');
			$scep_rss = (self::Get_option(c_scep_option_rss) ? 'checked="checked"' : '');
			$scep_noent	= (self::Get_option(c_scep_option_noent) ? 'checked="checked"' : '');
			$scep_width = (self::Get_option(c_scep_option_codewidth));
			$scep_height = (self::Get_option(c_scep_option_codeheight));
			$scep_editarea_later = (self::Get_option(c_scep_option_editarea_later) ? 'checked="checked"' : '');
			$scep_backtrack_limit = (self::Get_option(c_scep_option_backtrack_limit));
			$scep_recursion_limit = (self::Get_option(c_scep_option_recursion_limit));
			$scep_cleanup = (self::Get_option(c_scep_option_cleanup) ? 'checked="checked"' : '');
			$scep_donated = (self::Get_option(c_scep_option_donated) ? 'checked="checked"' : '');

			// Default size
			if ($scep_width <= 0)
				$scep_width = 600;
			if ($scep_height <= 0)
				$scep_height = 200;

			// Render options
?>
			<h3><?php _e('Options', c_scep_text_domain); ?></h3>
			<table id="scep_option_table" class="form-table">
<?php		if (self::Is_multisite() && function_exists('update_site_option')) { ?>
				<tr valign="top"><th scope="row">
					<label for="scep_option_global"><?php _e('Make shortcodes global', c_scep_text_domain); ?></label>
				</th><td>
					<input id="scep_option_global" name="<?php echo c_scep_option_global; ?>" type="checkbox"<?php echo $scep_global; ?> />
				</td></tr>
<?php		} ?>

			<tr valign="top"><th scope="row">
				<label for="scep_option_widget"><?php _e('Execute shortcodes in (sidebar) widgets', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_widget" name="<?php echo c_scep_option_widget; ?>" type="checkbox"<?php echo $scep_widget; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_excerpt"><?php _e('Execute shortcodes in excerpts', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_excerpt" name="<?php echo c_scep_option_excerpt; ?>" type="checkbox"<?php echo $scep_excerpt; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_comment"><?php _e('Execute shortcodes in comments', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_comment" name="<?php echo c_scep_option_comment; ?>" type="checkbox"<?php echo $scep_comment; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_rss"><?php _e('Execute shortcodes in RSS feeds', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_rss" name="<?php echo c_scep_option_rss; ?>" type="checkbox"<?php echo $scep_rss; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_noent"><?php _e('Disable html entity encoding', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_noent" name="<?php echo c_scep_option_noent; ?>" type="checkbox"<?php echo $scep_noent; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_width"><?php _e('Width of code box', c_scep_text_domain); ?></label>
			</th><td class="scep_cell_input">
				<input id="scep_option_width" name="<?php echo c_scep_option_codewidth; ?>" type="text" value="<?php echo $scep_width; ?>" />
				<span>px</span>
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_height"><?php _e('Height of code box', c_scep_text_domain); ?></label>
			</th><td class="scep_cell_input">
				<input id="scep_option_height" name="<?php echo c_scep_option_codeheight; ?>" type="text" value="<?php echo $scep_height; ?>" />
				<span>px</span>
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_editarea_later"><?php _e('Do not display code editor initially', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_editarea_later" name="<?php echo c_scep_option_editarea_later; ?>" type="checkbox"<?php echo $scep_editarea_later; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_backtrack"><a href="http://php.net/manual/en/pcre.configuration.php" target="_blank"><?php _e('PCRE backtrack limit', c_scep_text_domain); ?></a></label>
			</th><td class="scep_cell_input">
				<input id="scep_option_backtrack" name="<?php echo c_scep_option_backtrack_limit; ?>" type="text" value="<?php echo $scep_backtrack_limit; ?>" />
				<span>(<?php echo number_format(ini_get('pcre.backtrack_limit')); ?>)</span>
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_recursion"><a href="http://php.net/manual/en/pcre.configuration.php" target="_blank"><?php _e('PCRE recursion limit', c_scep_text_domain); ?></a></label>
			</th><td class="scep_cell_input">
				<input id="scep_option_recursion" name="<?php echo c_scep_option_recursion_limit; ?>" type="text" value="<?php echo $scep_recursion_limit; ?>" />
				<span>(<?php echo number_format(ini_get('pcre.recursion_limit')); ?>)</span>
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_cleanup"><?php _e('Delete options and shortcodes on deactivation (and when upgrading!)', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_cleanup" name="<?php echo c_scep_option_cleanup; ?>" type="checkbox"<?php echo $scep_cleanup; ?> />
			</td></tr>

			<tr valign="top"><th scope="row">
				<label for="scep_option_donated"><?php _e('I have donated to this plugin', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_donated" name="<?php echo c_scep_option_donated; ?>" type="checkbox"<?php echo $scep_donated; ?> />
			</td></tr>

			</table>

			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', c_scep_text_domain) ?>" />
			</p>

			</form>

			<h3><?php _e('Shortcodes', c_scep_text_domain); ?></h3>
<?php
			// Render shortcode definitions
			$name = self::Get_option(c_scep_option_names);
			self::Update_option(c_scep_option_deleted, 0);
			sort($name);
			for ($i = 0; $i < count($name); $i++) {
				$enabled = self::Get_option(c_scep_option_enabled . $name[$i]);
				$buffer = self::Get_option(c_scep_option_buffer . $name[$i]);
				$code = self::Get_option(c_scep_option_phpcode . $name[$i]);
				$this->Render_shortcode_form($name[$i], $i + 1, $enabled, $buffer, $code);
			}
?>
			<form method="post" action="" id="scep-new">
			<table>
			<tr><td>[<input name="<?php echo c_scep_form_shortcode; ?>0" type="text" value="">]</td></tr>
			<tr><td><textarea class="scep_table_code" name="<?php echo c_scep_form_phpcode; ?>0" id="<?php echo c_scep_form_phpcode; ?>0"
			style="width:<?php echo $scep_width; ?>px;height:<?php echo $scep_height; ?>px;" >
			</textarea></td></tr>
			<tr><td align="right">
			<span name="scep_message" class="scep_message"></span>
			<img src="<?php echo $this->plugin_url  . '/img/ajax-loader.gif'; ?>" alt="wait" name="scep_wait" style="display: none;" />
			<input type="submit" class="button-primary" value="<?php _e('Add', c_scep_text_domain); ?>" /></td></tr>
			</table>
			</form>

			<table id="scep_example_table" class="form-table">
				<tr><td class="scep_example_title">[shortcode arg="value"]</td>
				<td class="scep_example_explanation">extract(shortcode_atts(array('arg' =&gt; 'default'), $atts));</td></tr>
				<tr><td class="scep_example_title">[shortcode]content[/shortcode]</td>
				<td class="scep_example_explanation">$content</td></tr>
			</table>

			</div>
			</div>

			<script type="text/javascript">
			/* <![CDATA[ */
			jQuery(document).ready(function($) {
				/* new shortcode */
				$('#scep-new').submit(function() {
					editid = '<?php echo c_scep_form_phpcode; ?>0';
					shortcode = $('[name=<?php echo c_scep_form_shortcode; ?>0]').val();
					phpcode = editAreaLoader.getValue(editid);
					display = '<?php echo self::Get_option(c_scep_option_editarea_later) ? 'later' : 'onload'; ?>';
					msg = $('[name=scep_message]', this);
					wait = $('[name=scep_wait]', this);
					input = $('input,textarea', this);

					msg.text('');
					wait.show();
					input.attr('disabled', 'disabled');
					editAreaLoader.execCommand(editid, 'set_editable', false);

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'scep_ajax',
							<?php echo c_scep_param_nonce; ?>: '<?php echo $nonce; ?>',
							<?php echo c_scep_action_arg; ?>:  '<?php echo c_scep_action_new; ?>',
							<?php echo c_scep_param_shortcode; ?>: shortcode,
							<?php echo c_scep_param_phpcode; ?>: phpcode
						},
						dataType: 'text',
						cache: false,
						success: function(result) {
							wait.hide();
							input.removeAttr('disabled');
							editAreaLoader.execCommand(editid, 'set_editable', true);

							index = result.substring(0, result.indexOf('|'));
							html = result.substring(result.indexOf('|') + 1);
							if (index > 0) {
								$('#scep-new').before(html);
								editAreaLoader.init({id: '<?php echo c_scep_form_phpcode; ?>' + index, syntax: 'php', start_highlight: true, display: display});
								$('[name=<?php echo c_scep_form_shortcode; ?>0]').val('');
								editAreaLoader.setValue(editid, '');
							}
							else
								msg.text(html);
						},
						error: function(x, stat, e) {
							wait.hide();
							input.removeAttr('disabled');
							editAreaLoader.execCommand(editid, 'set_editable', true);
							msg.text('<?php _e('Error', c_scep_text_domain); ?>' + ' ' + x.status);
						}
					});
					return false;
				});

				/* test, save, revert, delete shortcode */
				$('.scep-update').live('click', function() {
					action = this.name;
					entry = this.form;
					orgname = this.form.name;
					editid = '<?php echo c_scep_form_phpcode; ?>' + this.form.id;
					shortcode = $('[name=<?php echo c_scep_form_shortcode; ?>' + this.form.id + ']').val();
					enabled = $('[name=<?php echo c_scep_form_enabled; ?>' + this.form.id + ']').attr('checked');
					buffer = $('[name=<?php echo c_scep_form_buffer; ?>' + this.form.id + ']').attr('checked');
					phpcode = editAreaLoader.getValue(editid);
					msg = $('[name=scep_message]', this.form);
					wait = $('[name=scep_wait]', this.form);
					input = $('input,textarea', this.form);

					if (action == '<?php echo c_scep_action_delete; ?>')
						if (!confirm('<?php _e('Are you sure to delete', c_scep_text_domain) ?> [' + orgname + ']?'))
							return false;

					input.attr('disabled', 'true');
					editAreaLoader.execCommand(editid, 'set_editable', false);
					msg.text('');
					wait.show();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'scep_ajax',
							<?php echo c_scep_param_nonce; ?>: '<?php echo $nonce; ?>',
							<?php echo c_scep_action_arg; ?>:  action,
							<?php echo c_scep_param_name; ?>: orgname,
							<?php echo c_scep_param_shortcode; ?>: shortcode,
							<?php echo c_scep_param_enabled; ?>: enabled,
							<?php echo c_scep_param_buffer; ?>: buffer,
							<?php echo c_scep_param_phpcode; ?>: phpcode
						},
						dataType: 'text',
						cache: false,
						success: function(result) {
							wait.hide();
							input.removeAttr('disabled');
							editAreaLoader.execCommand(editid, 'set_editable', true);

							if (action == '<?php echo c_scep_action_test; ?>')
								alert(result);
							else if (action == '<?php echo c_scep_action_revert; ?>')
								editAreaLoader.setValue(editid, result);
							else if (action == '<?php echo c_scep_action_delete; ?>')
								$(entry).remove();
							else
								msg.text(result);
						},
						error: function(x, stat, e) {
							wait.hide();
							input.removeAttr('disabled');
							editAreaLoader.execCommand(editid, 'set_editable', true);
							msg.text('<?php _e('Error', c_scep_text_domain); ?>' + ' ' + x.status);
						}
					});
					return false;
				});
			});
			/* ]]> */
			</script>
<?php
		}

		// Render shortcode edit form
		function Render_shortcode_form($name, $i, $enabled, $buffer, $code) {
			$scep_width = self::Get_option(c_scep_option_codewidth);
			$scep_height = self::Get_option(c_scep_option_codeheight);

			if ($scep_width <= 0)
				$scep_width = 600;
			if ($scep_height <= 0)
				$scep_height = 200;
?>
			<form method="post" action="" name="<?php echo $name; ?>" id="<?php echo $i; ?>">
			<table>
			<tr><td>[<input name="<?php echo c_scep_form_shortcode . $i; ?>" type="text" value="<?php echo $name; ?>">]
			<span><?php _e('Enabled', c_scep_text_domain) ?></span>
			<input name="<?php echo c_scep_form_enabled . $i; ?>" type="checkbox" <?php if ($enabled) echo 'checked="checked"'; ?>>
			<span><?php _e('Output echoed', c_scep_text_domain) ?></span>
			<input name="<?php echo c_scep_form_buffer . $i; ?>" type="checkbox" <?php if ($buffer) echo 'checked="checked"'; ?>></td></tr>
			<tr><td><textarea name="<?php echo c_scep_form_phpcode . $i; ?>" id="<?php echo c_scep_form_phpcode . $i; ?>"
			style="width: <?php echo $scep_width; ?>px;height: <?php echo $scep_height; ?>px;"
			><?php echo self::Get_option(c_scep_option_noent) ? $code : htmlentities($code, ENT_NOQUOTES, get_option('blog_charset')); ?></textarea></td></tr>
			<tr><td align="right">
			<span name="scep_message" class="scep_message"></span>
			<img src="<?php echo $this->plugin_url  . '/img/ajax-loader.gif'; ?>" alt="wait" name="scep_wait" style="display: none;" />
			<input type="button" class="button-primary scep-update" name="<?php echo c_scep_action_save; ?>" value="<?php _e('Save', c_scep_text_domain) ?>" />
			<input type="button" class="button-primary scep-update" name="<?php echo c_scep_action_test; ?>" value="<?php _e('Test', c_scep_text_domain) ?>" />
			<input type="button" class="button-primary scep-update" name="<?php echo c_scep_action_revert; ?>" value="<?php _e('Revert', c_scep_text_domain) ?>" />
			<input type="button" class="button-primary scep-update" name="<?php echo c_scep_action_delete; ?>" value="<?php _e('Delete', c_scep_text_domain) ?>" />
			</td></tr>
			</table>
			<hr />
			</form>
<?php
		}

		function Render_pluginsponsor() {
			if (!self::Get_option(c_scep_option_donated)) {
?>
				<script type="text/javascript">
				var psHost = (("https:" == document.location.protocol) ? "https://" : "http://");
				document.write(unescape("%3Cscript src='" + psHost + "pluginsponsors.com/direct/spsn/display.php?client=shortcode-exec-php&spot=' type='text/javascript'%3E%3C/script%3E"));
				</script>
				<a id="scep_sponsorship" href="http://pluginsponsors.com/privacy.html" target=_blank">
				<?php _e('Privacy in the Sustainable Plugins Sponsorship Network', c_scep_text_domain); ?></a>
<?php
			}
		}

		function Render_info_panel() {
?>
			<div id="scep_resources_panel">
			<h3><?php _e('Resources', c_scep_text_domain); ?></h3>
			<ul>
			<li><a href="http://wordpress.org/extend/plugins/shortcode-exec-php/faq/" target="_blank"><?php _e('Frequently asked questions', c_scep_text_domain); ?></a></li>
			<li><a href="http://codex.wordpress.org/Shortcode_API" target="_blank"><?php _e('Shortcode API', c_scep_text_domain); ?></a></li>
			<li><a href="http://www.php.net/manual/" target="_blank"><?php _e('PHP manual', c_scep_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/" target="_blank"><?php _e('Support page', c_scep_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/about/" target="_blank"><?php _e('About the author', c_scep_text_domain); ?></a></li>
			</ul>
<?php		if (!self::Get_option(c_scep_option_donated)) { ?>
			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>
<?php		} ?>
			</div>
<?php
		}

		// Shortcode execution
		function Shortcode_handler($atts, $content, $code) {
			$buffer = self::Get_option(c_scep_option_buffer . $code);
			if ($buffer)
				ob_start();
			$result = eval(self::Get_option(c_scep_option_phpcode . $code));
			if ($buffer) {
				$output = ob_get_contents();
				ob_end_clean();
			}
			else
				$output = '';

			return $output . $result;
		}

		// Handle ajax calls
		function Check_ajax() {
			if (isset($_REQUEST[c_scep_action_arg])) {
				if ($_REQUEST[c_scep_action_arg] == c_scep_action_tinymce) {
					$this->TinyMCE_handle();
					exit();
				}

				// Security check
				$nonce = $_REQUEST[c_scep_param_nonce];
				if (!wp_verify_nonce($nonce, c_scep_nonce_ajax))
					die('Unauthorized');

				// Send header
				header('Content-Type: text/html; charset=' . get_option('blog_charset'));

				// Load text domain
				load_plugin_textdomain(c_scep_text_domain, false, basename(dirname($this->main_file)));

				// Decode parameters
				$name = $_REQUEST[c_scep_param_name];
				$shortcode = trim($_REQUEST[c_scep_param_shortcode]);
				$enabled = ($_REQUEST[c_scep_param_enabled] == 'true');
				$buffer = ($_REQUEST[c_scep_param_buffer] == 'true');
				$phpcode = $_REQUEST[c_scep_param_phpcode];
				if (!self::Get_option(c_scep_option_noent))
					$phpcode = html_entity_decode($phpcode, ENT_NOQUOTES);
				$phpcode = stripslashes($phpcode);

				// Save, test
				if ($_POST[c_scep_action_arg] == c_scep_action_save || $_POST[c_scep_action_arg] == c_scep_action_test) {
					// Persist new definition
					$names = self::Get_option(c_scep_option_names);
					for ($i = 0; $i < count($names); $i++)
						if ($names[$i] == $name) {
							remove_shortcode($name[$i]);
							$names[$i] = $shortcode;
							break;
						}
					self::Update_option(c_scep_option_names, $names);
					self::Update_option(c_scep_option_enabled . $shortcode, $enabled);
					self::Update_option(c_scep_option_buffer . $shortcode, $buffer);
					self::Update_option(c_scep_option_phpcode . $shortcode, $phpcode);

					if ($_POST[c_scep_action_arg] == c_scep_action_save)
						echo __('Saved', c_scep_text_domain);
					else
						// Test shortcode
						if ($enabled) {
							ob_start();
							$result = eval($phpcode);
							$output = ob_get_contents();
							ob_end_clean();

							if ($buffer) {
								$result = $output . $result;
								$output = false;
							}

							echo '[' . $shortcode . ']="' . $result . '"';
							if ($output) {
								echo PHP_EOL . __('Unexpected output, do not use ECHO but RETURN', c_scep_text_domain);
								echo PHP_EOL . '"' . $output . '"';
							}
						}
						else
							echo '[' . $shortcode . '] ' . __('not enabled', c_scep_text_domain);
				}

				// Revert
				else if ($_POST[c_scep_action_arg] == c_scep_action_revert)
					echo self::Get_option(c_scep_option_phpcode . $name, $phpcode);

				// Delete
				else if ($_POST[c_scep_action_arg] == c_scep_action_delete) {
					$names = self::Get_option(c_scep_option_names);
					for ($i = 0; $i < count($names); $i++)
						if ($names[$i] == $name) {
							remove_shortcode($name[$i]);
							array_splice($names, $i, 1);
							break;
						}
					self::Update_option(c_scep_option_names, $names);
					self::Delete_option(c_scep_option_enabled . $name, $enabled);
					self::Delete_option(c_scep_option_buffer . $name, $buffer);
					self::Delete_option(c_scep_option_phpcode . $name, $phpcode);
					self::Update_option(c_scep_option_deleted, self::Get_option(c_scep_option_deleted) + 1);
				}

				// New
				else if ($_POST[c_scep_action_arg] == c_scep_action_new) {
					// Check unique
					$names = self::Get_option(c_scep_option_names);
					for ($i = 0; $i < count($names); $i++)
						if ($names[$i] == $shortcode) {
							echo '0|' . __('Shortcode exists', c_scep_text_domain);
							exit();
						}

					if ($shortcode) {
						$names = self::Get_option(c_scep_option_names);
						$names[] = $shortcode;
						self::Update_option(c_scep_option_names, $names);
						self::Add_option(c_scep_option_enabled . $shortcode, true);
						self::Add_option(c_scep_option_buffer . $shortcode, true);
						self::Add_option(c_scep_option_phpcode . $shortcode, $phpcode);
						$index = count($names) + self::Get_option(c_scep_option_deleted);
						echo $index . '|';
						echo $this->Render_shortcode_form($shortcode, $index, true, true, $phpcode);
					}
					else {
						echo '0|' . __('Name missing', c_scep_text_domain);
						exit();
					}
				}

				// Otherwise
				else
					die('Unknown request');

				exit();
			}
		}

		// TinyMCE integration

		function TinyMCE_version($version) {
			return $version . 's';
		}

		function TinyMCE_plugin($plugins) {
			$plugins['Shortcode'] =  $this->plugin_url . '/tinymce/shortcode.js';
			return $plugins;
		}

		function TinyMCE_button($buttons) {
			array_push($buttons, 'separator', 'Shortcode');
			return $buttons;
		}

		function TinyMCE_handle() {
			// Load text domain
			load_plugin_textdomain(c_scep_text_domain, false, basename(dirname($this->main_file)));

			// Send header
			header('Content-Type: text/html; charset=' . get_option('blog_charset'));
?>
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<title>Shortcode</title>
			<script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/jquery/jquery.js"></script>
			<script language="javascript" type="text/javascript" src="<?php echo site_url(); ?>/wp-includes/js/tinymce/tiny_mce_popup.js"></script>
			<script type='text/javascript'>
				/* <![CDATA[ */
				jQuery(document).ready(function($) {
					$('#scep-tinymce-form').submit(function() {
						if (window.tinyMCE) {
							window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, '[' + $('#scep-tinymce-shortcode').val() + ']');
							tinyMCEPopup.editor.execCommand('mceRepaint');
							tinyMCEPopup.close();
						}
						return false;
					});
				});
				/* ]]> */
			</script>
			</head>
			<body>
			<form method="post" action="#" id="scep-tinymce-form">
			<select id="scep-tinymce-shortcode">
<?php
			$names = self::Get_option(c_scep_option_names);
			foreach($names as $name)
				echo '<option value="' . $name . '">' . htmlspecialchars($name) . '</option>' . PHP_EOL;
?>
			</select>
			<br />
			<br />
			<input type="submit" value="<?php _e('Insert', c_scep_text_domain); ?>" />
			</form>
			</body>
			</html>
<?php
		}

		// Helpers global option management

		function Add_option($name, $value) {
			if (self::Is_multisite() && function_exists('add_site_option'))
				add_site_option($name, $value);
			add_option($name, $value);
		}

		function Update_option($name, $value) {
			if (self::Is_multisite() && function_exists('update_site_option'))
				update_site_option($name, $value);
			update_option($name, $value);
		}

		function Delete_option($name) {
			if (self::Is_multisite() && function_exists('delete_site_option'))
				delete_site_option($name);
			delete_option($name);
		}

		function Get_option($name) {
			if (self::Is_multisite() && function_exists('get_site_option') && get_site_option(c_scep_option_global))
				return get_site_option($name);
			return get_option($name);
		}

		// Helper check if network site
		function Is_multisite() {
			global $wpmu_version;
			return (function_exists('is_multisite') && is_multisite()) || !empty($wpmu_version);
		}

		// Helper check environment
		function Check_prerequisites() {
			// Check PHP version
			if (version_compare(PHP_VERSION, '4.3.0', '<'))
				die('Shortcode Exec PHP requires at least PHP 4.3.0');

			// Check WordPress version
			global $wp_version;
			if (version_compare($wp_version, '2.8') < 0)
				die('Shortcode Exec PHP requires at least WordPress 2.8');

			// Check basic prerequisities
			WPShortcodeExecPHP::Check_function('register_activation_hook');
			WPShortcodeExecPHP::Check_function('register_deactivation_hook');
			WPShortcodeExecPHP::Check_function('add_action');
			WPShortcodeExecPHP::Check_function('add_filter');
			WPShortcodeExecPHP::Check_function('wp_enqueue_script');
			WPShortcodeExecPHP::Check_function('wp_register_style');
			WPShortcodeExecPHP::Check_function('wp_enqueue_style');
		}

		function Check_function($name) {
			if (!function_exists($name))
				die('Required WordPress function "' . $name . '" does not exist');
		}

		// Helper change file extension
		function Change_extension($filename, $new_extension) {
			return preg_replace('/\..+$/', $new_extension, $filename);
		}
	}
}

?>
