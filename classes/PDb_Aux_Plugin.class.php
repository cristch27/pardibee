<?php
/**
 * parent class for auxiliary plugins to the Participants Database Plugin
 *
 * the main function here is to establish a connection to the parent plugin and
 * provide some common functionality
 * 
 * Requires PHP Version 5.3 or greater
 * 
 * @category   
 * @package    WordPress
 * @author     Roland Barker <webdesign@xnau.com>
 * @copyright  2014 xnau webdesign
 * @license    GPL2
 * @version    Release: 3.2
 * @link       http://wordpress.org/extend/plugins/participants-database/
 */
if ( ! defined( 'ABSPATH' ) ) die;
if (!class_exists('PDb_Aux_Plugin')) :
class PDb_Aux_Plugin {

  /**
   * boolean true if the Participants Database plugin is found and active
   * @var bool
   */
  var $connected = true;
  /**
   * the directory and path of the main plugin file
   * @var string
   */
  var $plugin_path;
  /**
   * holds the path to the parent plugin
   * @var string
   */
  var $parent_path;
  /**
   * name of the instantiating subclass
   * @var string
   */
  var $subclass;
  /**
   * slug of the aux plugin
   * @var string
   */
  var $aux_plugin_name;
  /**
   * title of the aux plugin
   * @var string
   */
  var $aux_plugin_title;
  /**
   * slug of the aux plugin settings page
   * @var string
   */
  var $settings_page;
  /**
   * name of the WP option used for the plugin settings
   * @var string
   */
  var $aux_plugin_settings;
  /**
   * holds the plugin's options
   * @var array
   */
  var $plugin_options;
  /**
   * holds the plugin info fields as parsed from the main plugin file header
   * @var array
   */
  var $plugin_data;
  /**
   * the updater class instance for this plugin
   * @var object
   */
  var $Updater;
  /**
   * status of the settings API
   * 
   * @var bool true if the settings API is in use
   */
  var $settings_API_status = true;
  /**
   * 
   * this is typically instantiated in the child class with: 
   * parent::__construct(__CLASS__, __FILE__);
   * 
   * @param string $subclass name of the instantiating subclass
   * @param string $plugin_file absolute path
   */

  function __construct($subclass, $plugin_file)
  {
    $this->plugin_path = plugin_basename($plugin_file);
      $this->plugin_data = get_plugin_data($plugin_file);
      $this->aux_plugin_settings = $this->aux_plugin_name;
      $this->subclass = $subclass;
      $this->set_settings_containers();
      $this->plugin_options = get_option($this->aux_plugin_settings);
    
      add_action('admin_menu', array($this, 'add_settings_page'));
      add_action('admin_init', array($this, 'settings_api_init'));
      add_action('init', array(&$this, 'initialize_updater'));
    add_action('plugins_loaded', array(&$this, 'load_textdomain'));
  }
  
  /**
   * checks for a valid connection to the parent plugin
   * 
   * @return bool
   */
  function check_connection() {
    // find the path to the parent plugin
    $active_plugins = get_option('active_plugins');
    foreach ($active_plugins as $plugin_file) {
      if (false !== stripos($plugin_file, 'participants-database.php')) {
        return true;
      }
    }
    return false;
  }

  /**
   * initializes the update class
   * 
   */
  function initialize_updater() {
    $this->Updater = new PDb_Update($this->plugin_path, $this->plugin_data['Version']);
  }

  /**
   * sets the slug for the aux plugin settings page
   */
  function set_settings_containers()
  {
    $this->settings_page = Participants_Db::$plugin_page . '-' . $this->aux_plugin_name . '_settings';
    $this->aux_plugin_settings = $this->aux_plugin_shortname . '_settings';
  }

  /**
   * loads the plugin text domain
   * 
   * defaults to the main plugin translation file
   * 
   */
  public function load_textdomain() {
    $translation_file_path = dirname( $this->plugin_path ) . '/languages/';
    if (is_file($translation_file_path . $this->aux_plugin_name . '.mo')) {
      $plugin_name = $this->aux_plugin_name;
    } else {
      $plugin_name = Participants_Db::PLUGIN_NAME;
      $translation_file_path = Participants_Db::translation_file_path();
    }
    load_plugin_textdomain($plugin_name, false, $translation_file_path);
  }

  /*********************************
   * plugin options section
   */
  /**
   * initializes the settings API
   */
  function settings_api_init() {
  }
  
  /**
   * sets up the plugin settings page
   */
  function add_settings_page() {
    if ($this->settings_API_status) {
			// create the submenu page
			add_submenu_page(
							Participants_Db::$plugin_page, // Participants_Db::PLUGIN_NAME, 
							$this->aux_plugin_title . ' Settings', 
							$this->aux_plugin_title, 
							'manage_options', 
							$this->settings_page, 
							array($this,'render_settings_page')
							);
		}
  }
  
  function _add_settings_sections($sections) {
    
    foreach($sections as $section) {
      // Add the section to reading settings so we can add our
      // fields to it
      add_settings_section(
              $section['slug'],
              $section['title'],
              array($this, 'setting_section_callback_function'),
              $this->aux_plugin_name
              );
    }
  }  
  /**
   * renders the plugin settings page
   * 
   * this generic rendering is expected to be overridden in the subclass
   */
  function render_settings_page() {
    ?>
    <div class="wrap" >
  
        <?php Participants_Db::admin_page_heading() ?>  
        <h2><?php echo $this->aux_plugin_title ?></h2>
  
        <?php settings_errors(); ?>  
  
        <form method="post" action="options.php">  
            <?php 
            settings_fields($this->aux_plugin_name . '_settings');
            do_settings_sections($this->aux_plugin_name);
            submit_button(); 
            ?>  
        </form>  
  
    </div><!-- /.wrap -->  
    <?php
    
    }
  
  /**
   * renders a section heading
   * 
   * this is expected to be overridden in the subclass
   * 
   * @param array $section information about the section
   */
  function setting_section_callback_function($section) {}

  /**
   * shows a setting input field
   * 
   * @param array $atts associative array of attributes (* required)
   *                      name    - name of the setting*
   *                      type    - the element type to use for the setting, defaults to 'text'
   *                      value   - preset value of the setting
   *                      title   - title of the setting
   *                      class   - classname for the settting
   *                      style   - CSS style for the setting element
   *                      help    - help text
   *                      options - an array of options for multiple-option input types (name => title)
   */
  public function setting_callback_function($atts)
  {
    $options = get_option($this->aux_plugin_settings);
    $defaults = array(
        'name'    => '',                      // 0
        'type'    => 'text',                  // 1
        'value'   => $options[$atts['name']], // 2
        'title'   => '',                      // 3
        'class'   => '',                      // 4
        'style'   => '',                      // 5
        'help'    => '',                      // 6
        'options' => '',                      // 7
        'select'  => '',                      // 8
    );
    $setting = shortcode_atts($defaults, $atts);
    $setting['value'] = isset($options[$atts['name']]) ? $options[$atts['name']] : $atts['value'];
    // create an array of numeric keys
    for($i = 0;$i<count($defaults);$i++) $keys[] = $i;
    // replace the string keys with numeric keys in the order defined in $defaults
    $values = array_combine($keys,$setting);
    
    $values[3] = htmlspecialchars($values[3]);
    $values[8] = $this->set_selectstring($setting['type']);
    $build_function = '_build_' . $setting['type'];
    if (is_callable(array($this, $build_function))) {
      echo call_user_func(array($this, $build_function), $values);
    }
  }
  
  /**
   * builds a text setting element
   * 
   * @param array $values array of setting values
   *                       0 - setting name
   *                       1 - element type
   *                       2 - setting value
   *                       3 - title
   *                       4 - CSS class
   *                       5 - CSS style
   *                       6 - help text
   *                       7 - setting options array
   *                       8 - select string
   * @return string HTML
   */
  protected function _build_text($values) {
    $pattern = "\n" . '<input name="' . $this->aux_plugin_settings . '[%1$s]" type="%2$s" value="%3$s" title="%4$s" class="%5$s" style="%6$s"  />';
    if (!empty($values[6])) $pattern .= "\n" . '<p class="description">%7$s</p>';
    return vsprintf($pattern, $values);
  }
  
  /**
   * builds a text area setting element
   * 
   * @param array $values array of setting values
   * @return string HTML
   */
  protected function _build_textarea($values) {
        $pattern = '<textarea name="' . $this->aux_plugin_settings . '[%1$s]" title="%4$s" class="%5$s" style="%6$s"  />%3$s</textarea>';
    if (!empty($values[6]))
      $pattern .= '<p class="description">%7$s</p>';
    return vsprintf($pattern, $values);
  }
  
  /**
   * builds a checkbox setting element
   * 
   * @param array $values array of setting values
   * @return string HTML
   */
  protected function _build_checkbox($values) {
    $selectstring = $this->set_selectstring($values[1]);
    $values[8] = $values[2] == 1 ? $selectstring : '';
    $pattern = '
<input name="' . $this->aux_plugin_settings . '[%1$s]" type="hidden" value="0" />
<input name="' . $this->aux_plugin_settings . '[%1$s]" type="%2$s" value="1" title="%4$s" class="%5$s" style="%6$s" %9$s />
';
    if (!empty($values[6])) $pattern .= '<p class="description">%7$s</p>';
    return vsprintf($pattern, $values);
  }
  
  /**
   * builds a radio button setting element
   * 
   * @param array $values array of setting values
   * @return string HTML
   */
  protected function _build_radio($values) {
    $selectstring = $this->set_selectstring($values[1]);
    $html = '';
    $pattern = "\n" . '<label title="%4$s"><input type="%2$s" %9$s value="%3$s" name="' . $this->aux_plugin_settings . '[%1$s]"> <span>%4$s</span></label><br />';
    $html .= "\n" . '<div class="' . $values[1] . ' ' . $values[4] . '" >';
    foreach ($values[7] as $name => $title) {
      $values[8] = $values[2] == $name ? $selectstring : '';
          $values[2] = $name;
          $values[3] = $title;
      $html .= vsprintf($pattern, $values);
    }
$html .= "\n" . '</div>';
    if (!empty($setting['help'])) $html .= "\n" . '<p class="description">' . $setting['help'] . '</p>';
    return $html;
  }
  
  /**
   * builds a multi-checkbox setting element
   * 
   * @param array $values array of setting values
   * @return string HTML
   */
  protected function _build_multicheckbox($values) {
    $selectstring = $this->set_selectstring($values[1]);
    $html = '';
    $pattern = "\n" . '<label title="%4$s"><input type="checkbox" %9$s value="%4$s" name="' . $this->aux_plugin_settings . '[%1$s][]"> <span>%4$s</span></label><br />';
    $html .= "\n" . '<div class="checkbox-group ' . $values[1] . ' ' . $values[4] . '" >';
    for ($i= 0;$i < count($values[7]);$i++) {
      $value = $values[7][$i];
      $values[8] = in_array($value, $values[2]) ? $selectstring : '';
      $values[3] = $value;
      $html .= vsprintf($pattern, $values);
    }
$html .= "\n" . '</div>';
    if (!empty($setting['help'])) $html .= "\n" . '<p class="description">' . $setting['help'] . '</p>';
    return $html;
  }
  /**
   * sets the select string
   * 
   * define a select indicator string fro form elements that offer multiple slections
   * 
   * @param string $type the form element type
   */
  protected function set_selectstring($type) {
    switch ($type) {
      case 'radio':
      case 'checkbox':
      case 'multicheckbox':
        return 'checked="checked"';
      case 'dropdown':
        return 'selected="selected"';
      default:
        return '';
    }
  }
  /**
   * builds a text setting control
   * 
   * @param array $setting the parameters of the setting
   * @param array $values  an array of setting values for use as a replacement array
   * @return string the setting control HTMLn
   */
  protected function _text_setting($setting,$values) {
    $pattern = '<input name="' . $this->aux_plugin_settings . '[%1$s]" type="%2$s" value="%3$s" title="%4$s" class="%5$s" style="%6$s"  />';
    if (!empty($setting['help'])) $pattern .= '<p class="description">%7$s</p>';
    return vsprintf($pattern, $values);
  }
  
  /**
   * adds a setting to the Settings API
   * 
   * @param array $atts an array of settings parameters
   * @return null
   * 
   */
  protected function add_setting($atts) {
    
    $default = array(
        'type' => 'text',
        'name' => '',
        'title' => '',
        'default' => '',
        'help' => '',
        'options' => '',
        'style' => '',
        'class' => '',
    );
    $params = shortcode_atts($default, $atts);

    add_settings_field(
            $params['name'], 
            $params['title'],
            array($this, 'setting_callback_function'),
            $this->aux_plugin_name,
            $this->aux_plugin_shortname . '_setting_section',
            array(
                'type'  => $params['type'],
                'name'  => $params['name'],
                'value' => isset($this->plugin_options[$params['name']]) ? $this->plugin_options[$params['name']] : $params['default'],
                'title' => $params['title'],
                'help'  => $params['help'],
                'options' => $params['options'],
                'style' => $params['style'],
                'class' => $params['class'],
                )
            );
  }

  /**
   * shows an error message in the admin
   */
  function _trigger_error($message, $errno = E_USER_ERROR)
  {
    if(isset($_GET['action']) and false !== stripos($_GET['action'], 'error_scrape')) {
      
      error_log('Plugin Activation Failed: ' . $_GET['plugin']);

      echo($message);

      exit;
    } else {

      trigger_error($message, $errno);
    }
  }

}
endif;
?>