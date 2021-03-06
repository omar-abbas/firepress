<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.bravedigital.com
 * @since      1.0.0
 *
 * @package    Brave_Firepress
 * @subpackage Brave_Firepress/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Brave_Firepress
 * @subpackage Brave_Firepress/admin
 * @author     Brave Digital <plugins@braveagency.com>
 */
class Brave_Firepress_Admin {

	private $plugin_name;
	private $version;

	/** @var Brave_Firepress $plugin */
	private $plugin;

	private $adminpage = 'options-general.php';
	private $slug = 'brave-firepress';
	private $hookname = 'settings_page_brave-firepress';
	private $settingspage = 'firepress-settings';
	private $settingsfields = array();

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param Brave_Firepress $plugin
	 */
	public function __construct($plugin)
	{

		$this->plugin = $plugin;
		$this->plugin_name = $plugin->get_plugin_name();
		$this->version = $plugin->get_version();

		$this->register_hooks();
	}

	public function getSlug() { return $this->slug; }
	public function getSettingsPage() { return $this->settingspage; }
	public function getAdminPage() { return $this->adminpage; }
	public function getAdminUrl() { return admin_url($this->adminpage . '?page=' . $this->slug); }
	public function getPlugin() { return $this->plugin; }

	private function register_hooks()
	{
		$loader = $this->plugin->get_loader();
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_styles'));
		add_action('admin_enqueue_scripts', array(&$this, 'enqueue_scripts'));
		$loader->add_action('admin_menu', $this, 'create_menu');
		$loader->add_action('admin_init', $this, 'init_settings');

		add_action("wp_ajax_bfp_resync", array(&$this, 'ajax_resync'));
		add_action("wp_ajax_bfp_export", array(&$this, 'ajax_export'));
		add_action("wp_ajax_bfp_import", array(&$this, 'ajax_import'));
	}


	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @param $hook
	 */
	public function enqueue_styles($hook)
	{
		if ($this->hookname != $hook)
		{
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/brave-firepress-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @param $hook
	 */
	public function enqueue_scripts($hook)
	{
		if ($this->hookname != $hook)
		{
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/brave-firepress-admin.js', array( 'jquery' ), $this->version, false );
	}


	public function create_menu()
	{
		add_submenu_page($this->adminpage, "FirePress", "FirePress", 'manage_options', $this->slug, array(&$this, 'render_admin_page'));
	}

	private function add_settings_field($optionid, $title, $args = array('type'=>'text'))
	{
		$args = array_merge(array('id'=>$optionid), $args);
		$this->settingsfields[] = $args;
		add_settings_field($optionid, $title, array(&$this, 'settings_field_render'), $this->settingspage, $this->slug, $args);

		if (isset($args['default']))
		{
			if (get_option($optionid) === false)
			{
				update_option($optionid, $args['default']);
			}
		}
	}

	public function init_settings()
	{

		$postfields = array(
		  "ID" => "ID",
		  "comment_count"  => "comment_count",
		  "comment_status"  => "comment_status",
		  "filter"  => "filter",
		  "guid"  => "guid",
		  "menu_order"  => "menu_order",
		  "ping_status"  => "ping_status",
		  "pinged"  => "pinged",
		  "post_author"  => "post_author",
		  "post_author_username"  => "post_author_username",
		  "post_content"  => "post_content",
		  "post_content_filtered"  => "post_content_filtered",
		  "post_date"  => "post_date",
		  "post_date_gmt"  => "post_date_gmt",
		  "post_excerpt"  => "post_excerpt",
		  "post_mime_type"  => "post_mime_type",
		  "post_modified"  => "post_modified",
		  "post_modified_gmt"  => "post_modified_gmt",
		  "post_name" => "post_name",
		  "post_parent"  => "post_parent",
		  "post_password"  => "post_password",
		  "post_status"  => "post_status",
		  "post_title"  => "post_title",
		  "post_type"  => "post_type",
		  "to_ping"  => "to_ping",
			"terms" => "terms",
			"fields" => "fields",
			"meta" => "meta",
			"image" => "image",
		);

		$termfields = array(
			"term_id" => "term_id",
			"count"  => "count",
			"description"  => "description",
			"name"  => "name",
			"parent"  => "parent",
			"slug"  => "slug",
			"term_group"  => "term_group",
		);

		$excludedfieldsdefault = "ping_status
post_type
post_mime_type
comment_count
filter
to_ping
pinged
post_modified_gmt
post_date_gmt
post_content_filtered";

		register_setting($this->slug, Brave_Firepress::SETTING_FIREBASE_URL);
		register_setting($this->slug, Brave_Firepress::SETTING_FIREBASE_KEY);
		register_setting($this->slug, Brave_Firepress::SETTING_DATABASE_BASEPATH);
		register_setting($this->slug, Brave_Firepress::SETTING_POST_TYPES_TO_SAVE);
		register_setting($this->slug, Brave_Firepress::SETTING_TAXONOMIES_TO_SAVE);
		//register_setting($this->slug, Brave_Firepress::SETTING_POST_KEY_FIELD);
		register_setting($this->slug, Brave_Firepress::SETTING_META_OPTION);
		register_setting($this->slug, Brave_Firepress::SETTING_TERMS_OPTION);
		register_setting($this->slug, Brave_Firepress::SETTING_ACF_OPTION);
		register_setting($this->slug, Brave_Firepress::SETTING_POST_FIELD_MAPPINGS);
		register_setting($this->slug, Brave_Firepress::SETTING_POST_FIELD_MAPPINGS);
		register_setting($this->slug, Brave_Firepress::SETTING_EXCLUDED_POST_META_FIELDS);
		register_setting($this->slug, Brave_Firepress::SETTING_EXCLUDE_TRASH);
		register_setting($this->slug, Brave_Firepress::SETTING_ARRAY_STYLE);

		add_settings_section($this->slug, __('FirePress Options', 'brave-firepress'), '__return_false', $this->settingspage);

		$this->add_settings_field(Brave_Firepress::SETTING_FIREBASE_URL, __('Firebase URL', 'brave-firepress'), array('type' =>'text', 'classes' =>'code', 'description'=>__('The URL of your Firebase install, usually https://<b>your-app</b>.firebaseio.com/', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_FIREBASE_KEY, __('Firebase Key', 'brave-firepress'), array('type' =>'filechoice', 'directory'=> $this->plugin->get_key_path(''), 'extensions'=>array('json'),'classes' =>'', 'description'=>__('The filename of the .json credentials file inside <code>wp-content/plugins/brave-firepress/accounts/</code>. You may have to refresh the page to see recent changes.', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_DATABASE_BASEPATH, __('Database Base Path', 'brave-firepress'), array('type' =>'text', 'description' =>__('The Firebase database path under which FirePress will store all data. Include a trailing slash ("/").', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE, __('Post Types To Save', 'brave-firepress'), array('type' =>'posttypes', 'label' =>__('Which post types to save to the Firebase database?', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_TAXONOMIES_TO_SAVE, __('Taxonomies To Save', 'brave-firepress'), array('type' =>'taxonomies', 'label' =>__('Which taxonomies to save to the Firebase database?', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_EXCLUDE_TRASH, __('Exclude Trashed Posts', 'brave-firepress'), array('type' =>'check', 'label' =>__('Exclude trashed posts from Firebase?', 'brave-firepress'), 'description'=>__('If checked, trashed posts are physically removed from Firebase and recreated if they are restored.', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_ARRAY_STYLE, __('Prefered Array Style', 'brave-firepress'), array('type' =>'select', 'choices'=>array('default'=>'Default', 'keyed'=> 'Keyed'), 'description'=>__('Choose how sequential arrays are sent to Firebase. Either as <i>Default</i>: <code>{0:apple, 1:pear, 2:orange}</code> or <i>Keyed</i>: <code>{apple:true, pear:true, orange:true}</code>.', 'brave-firepress')));
		//$this->add_settings_field(Brave_Firepress::SETTING_POST_KEY_FIELD, __('Post Key Field', 'brave-firepress'), array('type' =>'select', 'choices'=>array('post_name'=>'Post Slug','ID'=>'Post ID', 'guid'=>'Post GUID'), 'description' =>__('Which field should FirePress use to reference posts in your database?', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_META_OPTION, __('Post Meta Fields', 'brave-firepress'), array('type' =>'select', 'choices'=>array('key'=>'Save post meta fields into the \'meta\' key', 'merge'=>'Merge post meta fields into the main key', 'off'=>'Do not save post meta fields'), 'description' =>__('Choose what happens to post meta fields when they are saved to Firebase.', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_EXCLUDED_POST_META_FIELDS, __('Excluded Post Meta Fields', 'brave-firepress'), array('type' =>'textarea', 'default'=>$excludedfieldsdefault, 'description' =>__('A list of meta fields (post custom fields) to exclude from post data. Put each on a separate line.', 'brave-firepress')));
		$this->add_settings_field(Brave_Firepress::SETTING_TERMS_OPTION, __('Post Terms Fields', 'brave-firepress'), array('type' =>'select', 'choices'=>array('key'=>'Save post terms fields into the \'terms\' key', 'merge'=>'Merge post terms fields into the main key', 'off'=>'Do not save post terms fields'), 'description' =>__('Choose what happens to post meta fields when they are saved to Firebase.', 'brave-firepress')));

		if (is_plugin_active('advanced-custom-fields/acf.php'))
		{
			$this->add_settings_field(Brave_Firepress::SETTING_ACF_OPTION, __('<abbr title="Advanced Custom Fields">ACF</abbr> Fields', 'brave-firepress'), array('type' =>'select', 'choices'=>array('key'=>'Save ACF fields into the \'fields\' key', 'merge'=>'Merge ACF fields into the main key', 'off'=>'Do not save ACF fields'), 'description' =>__('Choose what happens to ACF fields when they are saved to Firebase.', 'brave-firepress')));
		}

		$this->add_settings_field(Brave_Firepress::SETTING_POST_FIELD_MAPPINGS, __('Post Field Mappings', 'brave-firepress'), array( 'type' =>'keyvalue', 'choices' =>$postfields, 'default' =>$postfields, 'label' =>__('Enter in what each field should be converted to in the Firebase database: (Leave an entry blank to exclude a specific field)', 'brave-firepress'), 'description' =>__('Leave an entry blank to exclude a specific field','brave-firepress')));

		$this->add_settings_field(Brave_Firepress::SETTING_TERM_FIELD_MAPPINGS, __('Term Field Mappings', 'brave-firepress'), array( 'type' =>'keyvalue', 'choices' =>$termfields, 'default' =>$termfields, 'label' =>__('Enter in what each field should be converted to in the Firebase database: (Leave an entry blank to exclude a specific field)', 'brave-firepress'), 'description' =>__('Leave an entry blank to exclude a specific field','brave-firepress')));


		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if (isset($_GET['settings-updated']) && $_GET['settings-updated'] && ($_GET['page'] == $this->slug))
		{
			//Clear the clean setup flag because we've changed settings and dont know yet if they work.
			$this->plugin->after_settings_changed();

		}

	}

	public function settings_field_render($args)
	{
		$type = (isset($args['type']) ? $args['type'] : 'text');

		$id = $args['id'];

		$html = '';
		$classes = (isset($args['classes']) ? $args['classes'] : '');
		$placeholder = (isset($args['placeholder']) ? $args['placeholder'] : '');
		$desc = (isset($args['description']) ? $args['description'] : '');
		$label = (isset($args['label']) ? $args['label'] : '');
		$default = (isset($args['default']) ? $args['default'] : '');

		$value = get_option($id, $default);

		switch ($type)
		{
			case 'select':
				$html .= '<select class="'.$classes.'" id="'.$id.'" name="'.$id.'">';
				foreach ($args['choices'] as $key=>$caption)
				{
					$html .= '<option '.(esc_attr($key) == $value ? 'selected="selected"' : '').' value="'.esc_attr($key).'">'.$caption.'</option>';
				}
				$html .= '</select>';

				break;

			case 'check':
				$checked = filter_var($value, FILTER_VALIDATE_BOOLEAN);
				$html .= '<label><input id="'.$id.'" value="1" name="'.$id.'" type="checkbox" '.($checked ? 'checked="checked"' : '').'/> '.$label.'</label>';

				break;

			case 'checklist':


				$choices = $args['choices'];

				$html .= '<fieldset class="'.$classes.'">';
				if (!empty($label)) $html .= '<p class="description">'.$label.'</p>';

				foreach ($choices as $key=>$caption)
				{
					$thisvalue = $key;
					$checked = is_array($value) && in_array($thisvalue, $value);
					$html .= '<label><input id="'.$id.'_'.$thisvalue.'" value="'.$thisvalue.'" name="'.$id.'[]" type="checkbox" '.($checked ? 'checked="checked"' : '').'/> <span style="min-width:10em; display:inline-block;">'.$caption.'</span> <code>'.$thisvalue.'</code></label><br/>';
				}

				$html .= '</fieldset>';


				break;

			case 'keyvalue':


				$choices = $args['choices'];

				$html .= '<fieldset class="'.$classes.'">';
				if (!empty($label)) $html .= '<p class="description">'.$label.'</p>';

				foreach ($choices as $key=>$caption)
				{

					//Value is the value stored for the setting (if exists) or falls back to the default value (if exists) or falls back to empty string.
					$val = is_array($value) && isset($value[$key]) ? $value[$key] : (is_array($default) && isset($default[$key]) ? $default[$key] : '');
					$html .= '<label><span style="min-width:10em; display:inline-block;">'.$key.'</span> = <input id="'.$id.'_'.$key.'" value="'.esc_attr($val).'" name="'.$id.'['.$key.']" type="text" /></label><br/>';
				}

				$html .= '</fieldset>';


				break;




			case 'posttypes':


				$posttypes = get_post_types(array('public'=>true), 'objects');

				$html .= '<fieldset class="'.$classes.'">';
				if (!empty($label)) $html .= '<p class="description">'.$label.'</p>';

				foreach ($posttypes as $posttype)
				{
					$thisvalue = $posttype->name;
					$checked = is_array($value) && in_array($thisvalue, $value);
					$caption = $posttype->label;
					$html .= '<label><input id="'.$id.'_'.$thisvalue.'" value="'.$thisvalue.'" name="'.$id.'[]" type="checkbox" '.($checked ? 'checked="checked"' : '').'/> <span style="min-width:10em; display:inline-block;">'.$caption.'</span> <code>'.$thisvalue.'</code></label><br/>';
				}

				$html .= '</fieldset>';


				break;

			case 'taxonomies':


				$taxes = get_taxonomies(array('public'=>true), 'objects');

				$html .= '<fieldset class="'.$classes.'">';
				if (!empty($label)) $html .= '<p class="description">'.$label.'</p>';

				foreach ($taxes as $tax)
				{
					$thisvalue = $tax->name;
					$checked = is_array($value) && in_array($thisvalue, $value);
					$caption = $tax->label;
					$meta = implode(', ', $tax->object_type);
					$html .= '<label><input id="'.$id.'_'.$thisvalue.'" value="'.$thisvalue.'" name="'.$id.'[]" type="checkbox" '.($checked ? 'checked="checked"' : '').'/> <span style="min-width:18em; display:inline-block;">'.$caption.' <small>('.$meta.')</small></span><code>'.$thisvalue.'</code></label><br/>';
				}

				$html .= '</fieldset>';


				break;


			case 'textarea':
				$html .= '<textarea class="regular-text '.$classes.'" id="'.$id.'" name="'.$id.'" placeholder="'.esc_attr($placeholder).'">'.esc_html($value).'</textarea>';
			break;


			case 'filechoice':

				$filelist = scandir($args['directory']);

				$choices = array();
				foreach ($filelist as $file)
				{
					if ($file == '.' || $file == '..') continue;

					$parts = explode('.', $file);

					if (count($parts) == 1)
					{
						if (in_array('', $args['extensions']))
						{
							$choices[] = $file;
						}
					}
					else
					{
					    if (in_array($parts[1], $args['extensions']))
					    {
					    	$choices[] = $file;
					    }
					}
				}

				if (empty($choices))
				{

					$html .= __('<p>There are no suitable files available.</p>', 'brave-firepress');
				}
				else
				{
					$html .= '<select class="'.$classes.'" id="'.$id.'" name="'.$id.'">';
					foreach ($choices as $file)
					{
						$html .= '<option '.(esc_attr($file) == $value ? 'selected="selected"' : '').' value="'.esc_attr($file).'">'.$file.'</option>';
					}
					$html .= '</select>';
				}

				break;

			case 'text': //This was done because when looking back at this function, sometimes you'll look for "case 'text':" and not realise that "default:" means the same thing.
			default:
				$html .= '<input type="text" class="regular-text '.$classes.'" id="'.$id.'" name="'.$id.'" placeholder="'.esc_attr($placeholder).'" value="'.esc_html($value).'"/>';
				break;
		}

		if (!empty($desc))
		{
			$html .= '<p class="description">'.$desc.'</p>';
		}

		echo $html;
	}

	public function render_admin_page()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		$GLOBALS['firepress_admin'] = $this;
		include plugin_dir_path(dirname( __FILE__ )) . 'admin/partials/mainpage.php';

	}


	public function ajax_resync()
	{
		header('Content-Type: application/json');
		$plugin = $this->getPlugin();
		$res = $plugin->resync();

		echo json_encode($res);
		wp_die();
	}

	public function ajax_export()
	{
		header('Content-Type: application/json');
		$plugin = $this->getPlugin();

		$res = array();
		foreach ($this->settingsfields as $field)
		{
			$value = get_option($field['id']);

			$res[$field['id']] = $value;
		}

		echo json_encode(json_encode($res));
		wp_die();
	}


	public function ajax_import()
	{
		//header('Content-Type: application/json');
		$plugin = $this->getPlugin();

		$res = array('success'=>false, 'msg'=>'Unknown error.');

		$import = array();

		if (isset($_REQUEST['data']))
		{

			try
			{

				$import = json_decode(stripslashes($_REQUEST['data']), true);
				if (!is_array($import)) throw new Exception("Invalid import data.");


			}
			catch(Exception $e)
			{
				$res['msg'] = 'Your imported data was not valid.';

			}
		}

		if (count($import) > 0)
		{
			foreach ($this->settingsfields as $field)
			{
				if (isset($import[$field['id']]))
				{
					update_option($field['id'], $import[$field['id']]);
				}
			}

			$res['success'] = true;
			$res['msg'] = 'Import successful';
		}

		echo json_encode($res);
		wp_die();
	}
}
