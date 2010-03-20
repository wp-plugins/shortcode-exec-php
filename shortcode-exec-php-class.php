<?php

/*
	Support class Shortcode Exec PHP Plugin
	Copyright (c) 2010 by Marcel Bokhorst
*/

// Define constants
define('c_scep_option_widget', 'scep_widget');
define('c_scep_option_cleanup', 'scep_cleanup');
define('c_scep_option_donated', 'scep_donated');
define('c_scep_option_names', 'scep_names');
define('c_scep_option_enabled', 'scep_enabled_');
define('c_scep_option_phpcode', 'scep_phpcode_');

define('c_scep_form_delete', 'scep_delete');
define('c_scep_form_enabled', 'scep_enabled');
define('c_scep_form_shortcode', 'scep_shortcode');
define('c_scep_form_phpcode', 'scep_phpcode');

define('c_scep_nonce_form', 'scep-nonce-form');
define('c_scep_text_domain', 'shortcode-exec-php');

// Define class
if (!class_exists('WPShortcodeExecPHP')) {
	class WPShortcodeExecPHP {
		// Class variables
		private $main_file = null;

		// Constructor
		function WPShortcodeExecPHP() {
			$bt = debug_backtrace();
			$this->main_file = $bt[0]['file'];

			// Register (de)activation hook
			register_activation_hook($this->main_file, array(&$this, 'Activate'));
			register_deactivation_hook($this->main_file, array(&$this, 'Deactivate'));

			// Register actions
			add_action('init', array(&$this, 'Init'), 0);
			if (is_admin())
				add_action('admin_menu', array(&$this, 'Admin_menu'));

			// Enable shortcodes for widgets
			if (get_option(c_scep_option_widget))
				add_filter('widget_text', 'do_shortcode');

			// Wire shortcode handlers
			$name = get_option(c_scep_option_names);
			for ($i = 0; $i < count($name); $i++)
				if (get_option(c_scep_option_enabled . $name[$i]))
					add_shortcode($name[$i], array(&$this, 'Shortcode_handler'));
		}

		function Init() {
			if (is_admin()) {
				// I18n
				load_plugin_textdomain(c_scep_text_domain, false, basename(dirname(__FILE__)));

				// Enqueue style sheet
				$css_name = $this->Change_extension(basename($this->main_file), '.css');
				if (file_exists(TEMPLATEPATH . '/' . $css_name))
					$css_url = get_bloginfo('template_directory') . '/' . $css_name;
				else
					$css_url = WP_PLUGIN_URL . '/' . basename(dirname($this->main_file)) . '/' . $css_name;
				wp_register_style('scep_style', $css_url);
				wp_enqueue_style('scep_style');
			}
		}

		// Handle plugin activation
		function Activate() {
			if (!get_option(c_scep_option_names)) {
				// Define example shortcode
				$name = array();
				$name[] = 'hello_world';
				update_option(c_scep_option_names, $name);
				update_option(c_scep_option_enabled . $name[0], true);
				update_option(c_scep_option_phpcode . $name[0], "return 'Hello world!';");
			}
		}

		// Handle plugin deactivation
		function Deactivate() {
			// Cleanup if requested
			if (get_option(c_scep_option_cleanup)) {
				delete_option(c_scep_option_widget);
				delete_option(c_scep_option_cleanup);
				delete_option(c_scep_option_donated);

				$name = get_option(c_scep_option_names);
				for ($i = 0; $i < count($name); $i++) {
					remove_shortcode($name[$i]);
					delete_option(c_scep_option_enabled . $name[$i]);
					delete_option(c_scep_option_phpcode . $name[$i]);
				}

				delete_option(c_scep_option_names);
			}
		}

		// Register options page
		function Admin_menu() {
			if (function_exists('add_options_page'))
				add_options_page(
					__('Shortcode Exec PHP Administration', c_scep_text_domain),
					__('Shortcode Exec PHP', c_scep_text_domain),
					0,
					$this->main_file,
					array(&$this, 'Administration'));
		}

		// Handle option page
		function Administration() {
			// Check post back
			if (strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
				// Check security
				check_admin_referer(c_scep_nonce_form);

				if (current_user_can('manage_options')) {
					// Update settings
					update_option(c_scep_option_widget,  $_POST[c_scep_option_widget]);
					update_option(c_scep_option_cleanup, $_POST[c_scep_option_cleanup]);
					update_option(c_scep_option_donated, $_POST[c_scep_option_donated]);

					echo '<div id="message" class="updated fade"><p><strong>' . __('Settings updated', c_scep_text_domain) . '</strong></p></div>';

					// Update shortcodes
					$name = get_option(c_scep_option_names);

					// Remove previous shortnames and handlers
					for ($i = 0; $i < count($name); $i++) {
						remove_shortcode($name[$i]);
						delete_option(c_scep_option_enabled . $name[$i]);
						delete_option(c_scep_option_phpcode . $name[$i]);
					}

					// Add current shortnames
					$cur_name = array();
					for ($i = 0; $i < count($name); $i++) {
						if (!$_POST[c_scep_form_delete . $i]) {
							$shortcode = $_POST[c_scep_form_shortcode . $i];
							$enabled = $_POST[c_scep_form_enabled . $i];
							$code = stripslashes(html_entity_decode($_POST[c_scep_form_phpcode . $i], ENT_NOQUOTES));
							$cur_name[] = $shortcode;
							add_option(c_scep_option_enabled . $shortcode, $enabled);
							add_option(c_scep_option_phpcode . $shortcode, $code);
						}
					}

					// Add new shortname
					$shortcode = $_POST[c_scep_form_shortcode . '_new'];
					$enabled = $_POST[c_scep_form_enabled . '_new'];
					$code = stripslashes(html_entity_decode($_POST[c_scep_form_phpcode . '_new'], ENT_NOQUOTES));
					if ($shortcode) {
						$cur_name[] = $shortcode;
						add_option(c_scep_option_enabled . $shortcode, $enabled);
						add_option(c_scep_option_phpcode . $shortcode, $code);
					}

					// Persist names
					update_option(c_scep_option_names, $cur_name);

					// Wire new shortcodes
					for ($i = 0; $i < count($cur_name); $i++)
						if (get_option(c_scep_option_enabled . $cur_name[$i]))
							add_shortcode($cur_name[$i], array(&$this, 'Shortcode_handler'));
				}
			}

			// Sustainable Plugins Sponsorship Network
			$this->Render_pluginsponsor();

			echo '<div class="wrap">';

			// Render info panel
			$this->Render_info_panel();

			// Render title
			echo '<div id="scep_admin_panel">';
			echo '<h2>' . __('Shortcode Exec PHP Administration', c_scep_text_domain) . '</h2>';
			echo '<form method="post" action="">';

			// Security
			wp_nonce_field(c_scep_nonce_form);

			// Get current settings
			$scep_widget  = get_option(c_scep_option_widget)  ? 'checked="checked"' : '';
			$scep_cleanup = get_option(c_scep_option_cleanup) ? 'checked="checked"' : '';
			$scep_donated = get_option(c_scep_option_donated) ? 'checked="checked"' : '';
?>
			<h3><?php _e('Options', c_scep_text_domain); ?></h3>
			<table id="option_table" class="form-table">

			<tr valign="top"><th scope="row">
				<label for="scep_option_widget"><?php _e('Execute shortcodes in (sidebar) widgets', c_scep_text_domain); ?></label>
			</th><td>
				<input id="scep_option_widget" name="<?php echo c_scep_option_widget; ?>" type="checkbox"<?php echo $scep_widget; ?> />
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

			<h3><?php _e('Shortcodes', c_scep_text_domain); ?></h3>
			<?php if (function_exists('parsekit_compile_string')) echo 'parsekit_compile_string'; ?>
			<table id="shortcode_table" class="form-table">
			<tr>
				<th class="scep_table_center"><?php _e('Delete', c_scep_text_domain); ?></th>
				<th class="scep_table_center"><?php _e('Enabled', c_scep_text_domain); ?></th>
				<th><?php _e('Shortcode', c_scep_text_domain); ?></th>
				<th><?php _e('PHP code snippet', c_scep_text_domain); ?></th>
			</tr>
<?php
			// Render shortcode definitions
			$name = get_option(c_scep_option_names);
			for ($i = 0; $i < count($name); $i++) {
				$enabled = get_option(c_scep_option_enabled . $name[$i]);
				$code = get_option(c_scep_option_phpcode . $name[$i]);
				echo '<tr valign="top">';
				echo '<td class="scep_table_center"><input name="' . c_scep_form_delete . $i . '" type="checkbox"></td>';
				echo '<td class="scep_table_center"><input name="' . c_scep_form_enabled . $i . '" type="checkbox" ' . ($enabled ? 'checked="checked"' : '') . '></td>';
				echo '<td>[<input name="' . c_scep_form_shortcode . $i . '" type="text" value="' . $name[$i] . '">]</td>';
				echo '<td><textarea name="' . c_scep_form_phpcode . $i . '">' . htmlentities($code, ENT_NOQUOTES) . '</textarea></td>';
				echo '</tr>';
			}
?>
			<tr valign="top">
			<td />
			<td class="scep_table_center"><input name="<?php echo c_scep_form_enabled . '_new'; ?>" type="checkbox" checked="checked"></td>
			<td><input name="<?php echo c_scep_form_shortcode . '_new'; ?>" type="text" value=""></td>
			<td><textarea class="scep_table_code" name="<?php echo c_scep_form_phpcode . '_new'; ?>"></textarea></td>
			</tr>
			</table>

			<table id="example_table" class="form-table">
				<tr><td class="example_title">[shortcode arg="value"]</td>
				<td class="example_explanation">extract(shortcode_atts(array('arg' =&gt; 'default'), $atts));</td></tr>
				<tr><td class="example_title">[shortcode]content[/shortcode]</td>
				<td class="example_explanation">$content</td></tr>
			</table>

			<p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes', c_scep_text_domain) ?>" />
			</p>

			</form>
			</div>
			</div>
<?php
		}

		function Render_pluginsponsor() {
			if (!get_option(c_scep_option_donated)) {
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
			<li><a href="http://www.php.net/manual/" target="_blank">PHP manual</a></li>
			<li><a href="http://blog.bokhorst.biz/" target="_blank"><?php _e('Support page', c_scep_text_domain); ?></a></li>
			<li><a href="http://blog.bokhorst.biz/about/" target="_blank"><?php _e('About the author', c_scep_text_domain); ?></a></li>
			</ul>

			<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
			</form>
			</div>
<?php
		}

		// Shortcode execution
		function Shortcode_handler($atts, $content, $code) {
			return eval(get_option(c_scep_option_phpcode . $code));
		}

		// Helper check environment
		function Check_prerequisites() {
			// Check PHP version
			if (version_compare(PHP_VERSION, '4.3.0', '<'))
				die('Shortcode Exec PHP requires at least PHP 4.3.0');

			// Check WordPress version
			global $wp_version;
			if (version_compare($wp_version, '2.5') < 0)
				die('Shortcode Exec PHP requires at least WordPress 2.5');

			// Check basic prerequisities
			WPShortcodeExecPHP::Check_function('register_activation_hook');
			WPShortcodeExecPHP::Check_function('register_deactivation_hook');
			WPShortcodeExecPHP::Check_function('add_action');
			WPShortcodeExecPHP::Check_function('add_filter');
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
