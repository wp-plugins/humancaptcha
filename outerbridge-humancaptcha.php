<?php  
/* 
Plugin Name: HumanCaptcha by Outerbridge 
Plugin URI: http://outerbridge.co.uk/
Description: HumanCaptcha uses questions that require human logic to answer them and which machines cannot easily answer.  This plugin is written by Outerbridge.
Author: Outerbridge
Version: 1.5.4
Author URI: http://outerbridge.co.uk/
Text Domain: humancaptcha
Tags: captcha, text-based, human, logic, questions, answers
License: GPL v2
*/

/**
 *
 *	v1.5.4	131212	Tested and stable up to WP3.8 and updated author name
 *
 *	v1.5.3	131007	Added cross-reference to Human Contact and Captcha.
 *
 *	v1.5.2	130816	Corrected one missed translation point
 *
 *	v1.5.1	130816	Added TH90 of MPW D&D's Persian translation file
 *
 *	v1.5	130816	Made the plugin translation ready and tidied the code a bit
 *
 *	v1.4	130724	Fixed the "add new" option which disappeared if the user deleted all questions
 *
 *	v1.3	130723	Fixed UTF8 issue
 *
 *	v1.2.1	120105	No changes. v1.2 didn't commit properly.
 *
 *	v1.2	120105	Updated obr_admin_menu function to check against 'manage_options' rather than 'edit_plugins'.  This allows for "define('DISALLOW_FILE_EDIT', true);" being enabled in wp-config.php
 *
 *	v1.1	120103	Tested and stable up to WP3.3
 *
 *	v1.0	110930	HumanCaptcha now added to registration and login forms as well as comments form.  Toggles added to admin menu to allow users to decide where HumanCaptcha is applied.
 *
 *	v0.2	110830	Fixed session_start issue
 *
 *	v0.1	110825	Initial Release
 *
 */

/**
 * HumanCaptcha is plugin written by Outerbridge which uses questions that require human logic to answer them and which
 * machines cannot easily answer.  Most captchas are based on the requirement to reproduce a number of randomly-generated
 * characters (which are sometimes blurred, jiggled and/or on a fuzzy background).  HumanCaptcha generates a simple
 * question which the user must answer using logical thought.  HumanCaptcha is much more accessible than standard captchas,
 * which many people find difficult to read or understand.  Visually impaired people are more likely to be able to use
 * HumanCaptcha than a character-based one.
 * 
 * CAPTCHAs are useful for improving security in a number of situations, for example:
 * 1. Reducing Comment Spam in Blogs
 * 	  Most bloggers will have come across programs that submit spam comments, often with the aim of improving the search
 * 	  engine ranking of a website.  By using a CAPTCHA, only humans can enter comments on your blog, and people do not need
 * 	  to sign up before they enter a comment.
 * 2. Protecting Email Addresses From Scrapers
 * 	  Spammers crawl the web looking for e-mail addresses rendered in text. CAPTCHAs can hide your e-mail address from web
 * 	  scrapers, by requiring users to solve a CAPTCHA before revealing your e-mail. 
 * 3. Deterring Viruses, Worms and Spam 
 * 	  CAPTCHAs may reduce the likelihood of e-mailed viruses, worms and spam, by only accepting an e-mail if it has been
 *    established that there is a human behind the sending computer.
 * 
 */

$obr_humancaptcha = new obr_humancaptcha;

global $wpdb;
	
// define the table name to be used
global $obr_table_name;
$obr_table_name = $wpdb->prefix."obr_humancaptcha_qanda";
global $obr_admin_table_name;
$obr_admin_table_name = $wpdb->prefix."obr_humancaptcha_admin";

class obr_humancaptcha{
	
	// version
	public $obr_humancaptcha_version = '1.5.4';
	
	// constructor
	function obr_humancaptcha() {
		$this->__construct();
	}
	
	function __construct(){
		register_activation_hook(__FILE__, array(&$this, 'obr_install'));
		add_action('plugins_loaded', array(&$this, 'obr_update_check'));
		add_action('plugins_loaded', array(&$this, 'obr_internationalisation'));
		add_filter('comment_form_default_fields', array(&$this, 'obr_comment_build_form'));
		add_filter('preprocess_comment', array(&$this, 'obr_comment_validate_answer'), 10, 2);
		
		add_action('register_form', array(&$this, 'obr_register_build_form'));
		add_filter('register_post', array(&$this, 'obr_register_validate_answer'), 10, 2);

		add_action('login_form', array(&$this, 'obr_login_build_form'));
		add_filter('wp_authenticate', array(&$this, 'obr_login_validate_answer'), 10, 2);

		add_action('wp_head', array(&$this, 'obr_header'));
		add_action('admin_menu', array(&$this, 'obr_admin_menu'));
		add_action('init', array(&$this, 'obr_init'));
	}

	// functions
	function obr_install(){
		global $wpdb;
		global $obr_table_name;
		global $obr_admin_table_name;
		$mysql = '';

		if($wpdb->get_var("SHOW TABLES LIKE '$obr_table_name';") != $obr_table_name){
			$mysql = "CREATE TABLE $obr_table_name (
				fld_ref int(11) NOT NULL AUTO_INCREMENT,
				fld_questions varchar(100) NOT NULL,
				fld_answers varchar(20) NOT NULL,
				UNIQUE KEY fld_ref (fld_ref)
			);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($mysql);
			$new_rows = $this->obr_insert_default_data();
		}

		if($wpdb->get_var("SHOW TABLES LIKE '$obr_admin_table_name';") != $obr_admin_table_name){
			$mysql = "CREATE TABLE $obr_admin_table_name (
				fld_ref int(11) NOT NULL AUTO_INCREMENT,
				fld_setting int(11) NOT NULL,
				fld_value boolean NOT NULL DEFAULT 0,
				UNIQUE KEY fld_setting (fld_setting),
				UNIQUE KEY fld_ref (fld_ref)
			);";
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($mysql);
			$new_rows = $this->obr_insert_default_admin_data();
		}

		// now add in a version number
		add_option('obr_humancaptcha_version', $this->obr_humancaptcha_version);
		// check for updates
		$installed_ver = get_option("obr_humancaptcha_version");
		$our_version = $this->obr_humancaptcha_version;
		if($installed_ver != $our_version){
			echo '<div id="message" class="updated fade"><p>';
			printf(__('Outerbridge HumanCaptcha updated to version %s', 'humancaptcha'), $our_version);
			echo '</p></div>';
			// update specifics go here
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($mysql);
			update_option("obr_humancaptcha_version", $our_version);
		}
	}
	
	function obr_update_check(){
		// check if there's an update
		if (get_site_option('obr_humancaptcha_version') != $this->obr_humancaptcha_version){
			$this->obr_install();
		}
	}

	function obr_insert_default_data(){
		global $wpdb;
		global $obr_table_name;
		$default_data = array(
			array('Which of steel, bread, umbrella, robot or cupboard is edible?', 'bread'),
			array('Which of 49, four, 7 and sixty is the smallest? Type as a number', '4'),
			array('What is the usual colour of the sky on a sunny day?', 'blue'),
			array('How many legs do 2 spiders have? Type a number', '16'),
			array('Monday, Tuesday, Wednesday, Thursday: what comes next?', 'friday'),
			array('Which word is in bold: <strong>first</strong>, second, third, fourth or fifth?', 'first'),
			array('How many hearts does a heartless human have? Type a number', '1'),
			array('If July was last month, what is this month?', 'august'),
			array('Which is lightest: truck, feather, dog, mountain or elephant?', 'feather'),
			array('Which is rounder: square, triangle, rectangle, circle, hexagon or pentagon?', 'circle')
		);
		foreach ($default_data as $row){
			$new_row = $wpdb->insert($obr_table_name, array('fld_questions' => $row[0], 'fld_answers' => $row[1]));
		}
	}

	function obr_insert_default_admin_data(){
		global $wpdb;
		global $obr_admin_table_name;
		$default_admin_data = array(
			// 1 is for comments - default true
			array(1, 1),
			// 2 is for registration - default false
			array(2, 0),
			// 3 is for login - default false
			array(3, 0)
		);
		foreach ($default_admin_data as $row){
			$new_row = $wpdb->insert($obr_admin_table_name, array('fld_setting' => $row[0], 'fld_value' => $row[1]));
		}
	}

	function obr_internationalisation(){
		// Willkommen tout le monde...
		load_plugin_textdomain('humancaptcha', false, dirname(plugin_basename(__FILE__)).'/languages/');
	}
	
	function obr_comment_build_form($fields){
		global $comments_on;
		if (!$comments_on){
			return $fields;
		}
		global $user_ID;
		if (!$user_ID){
			$selected = $this->obr_select_question();
			$question = $selected['question'];
			$answer = $selected['answer'];
			$_SESSION['obr_answer'] = md5(strtolower(trim($answer)));
			// use the comment-form-email class as it works better with 2011
			$fields['obr_hlc'] = '<p class="comment-form-email"><label for="obr_hlc">'.stripslashes($question).'</label> <span class="required">*</span><input id="answer" name="answer" size="30" type="text" aria-required=\'true\' /></p>';
			return $fields;
		}
	}
	
	function obr_register_build_form($fields){
		global $register_on;
		if (!$register_on){
			return $fields;
		}
		$fields = $this->obr_build_form($fields);
		return $fields;
	}

	function obr_login_build_form($fields){
		global $login_on;
		if (!$login_on){
			return $fields;
		}
		$fields = $this->obr_build_form($fields);
		return $fields;
		
	}

	function obr_build_form($fields){
		$selected = $this->obr_select_question();
		$question = $selected['question'];
		$answer = $selected['answer'];
		$_SESSION['obr_answer'] = md5(strtolower(trim($answer)));
		$fields['obr_hlc'] = '<p><label for="obr_hlc">'.stripslashes($question).'<br /><input type="text" name="answer" id="answer" class="input" value="" size="25" tabindex="20" /></label></p>';
		echo $fields['obr_hlc'];
		return $fields;
	}
	
	function obr_header(){
		echo "\n".'<!-- Using Outerbridge HumanCaptcha.  Find out more at http://outerbridge.co.uk/ -->'."\n";
	}

	function obr_select_question(){
		global $wpdb;
		global $obr_table_name;
		$mysql = "SELECT * FROM $obr_table_name ORDER BY RAND() LIMIT 1;";
		$row = $wpdb->get_row($mysql);
		$selected = array('question' => $row->fld_questions, 'answer' => $row->fld_answers);
		return $selected;
	}

	function obr_comment_validate_answer($commentdata){
		global $comments_on;
		global $user_ID;
		if (!$user_ID && $comments_on){
			$this->obr_validate_answer();
		}
		return $commentdata;
	}

	function obr_register_validate_answer($user_login, $user_email){
		global $register_on;
		if (($user_login != '') && ($user_email != '') && $register_on){
			$this->obr_validate_answer();
		}
	}
	
	function obr_login_validate_answer($user_login, $user_password){
		global $login_on;
		if (($user_login != '') && ($user_password != '') && $login_on){
			$this->obr_validate_answer();
		}
	}
	
	function obr_validate_answer(){
		if (!session_id()){
			session_start();
		}
		if ((!isset($_POST['answer'])) || ($_POST['answer'] == '')){
			wp_die(__('Error: please fill the required field (humancaptcha).', 'humancaptcha'));
		}
		$user_answer = md5(strtolower(trim($_POST['answer'])));
		$obr_answer = strtolower(trim($_SESSION['obr_answer']));
		if ($user_answer != $obr_answer){
			wp_die(__('Error: your answer to the humancaptcha question is incorrect.  Use your browser\'s back button to try again.', 'humancaptcha'));
		}
		return true;
	}	

	function obr_admin_menu(){
		if (is_super_admin()) {
			add_submenu_page('plugins.php', __('HumanCaptcha', 'humancaptcha'), __('HumanCaptcha', 'humancaptcha'), 'manage_options', 'obr-hlc', array(&$this, 'obr_admin'));
		}
	}
	
	function obr_admin(){
		require_once('outerbridge-humancaptcha-admin.php');
	}
	
	function obr_qanda_settings($message = null, $question = null, $answer = null){
		global $wpdb;
		global $obr_table_name;
		$mysql = "SELECT * FROM $obr_table_name ORDER BY fld_ref ASC;";
		$page = 'plugins.php?page=obr-hlc';
		$num_rows = $wpdb->get_row($mysql);
		echo '<table style="text-align: center;"><tr><td width="50"><em>'.__('Number', 'humancaptcha').'</em></td><td width="500"><em>'.__('Question', 'humancaptcha').'</em></td><td width="150"><em>'.__('Answer', 'humancaptcha').'</em></td><td>&nbsp;</td><td>&nbsp;</td></tr>';
		if ($wpdb->num_rows > 0){
			$counter = 1;
			foreach($wpdb->get_results($mysql) as $key => $row){
				echo '<form method="post" action="',$page,'">';
				echo '<tr><td style="width: 75px;">',$counter,'</td><td><input type="text" name="question" value="',stripslashes($row->fld_questions),'" style="width: 490px; text-align: left;" /></td><td><input type="text" name="answer" value="',stripslashes($row->fld_answers),'" style="width: 140px; text-align: left;" /></td>';
				echo '<td><input type="hidden" name="ref" value=',$row->fld_ref,' /><input type="submit" name="updateqanda" value="'.__('Update Q & A', 'humancaptcha').'" style="width: 125px;" /></td>';
				echo '</form>';
				echo '<form method="post" action="',$page,'">';
				echo '<td><input type="hidden" name="ref" value=',$row->fld_ref,' /><input type="submit" onclick="return confirm(\''.__('Are you sure you want to delete this? Press OK to confirm', 'humancaptcha').'\')" class="delRow" name="deleteqanda" value="'.__('Delete Q & A', 'humancaptcha').'" style="width: 125px;" /></td></tr>';
				echo '</form>';
				$counter++;
			}
		}
		echo '<form method="post" action="',$page,'">';
		echo '<tr><td style="width: 75px;">'.__('Add New', 'humancaptcha').'</td><td><input type="text" name="question" value="';
		if (isset($question)){
			echo $question;
		}
		echo '" style="width: 490px; text-align: left;" /></td><td><input type="text" name="answer" value="';
		if (isset($answer)){
			echo $answer;
		}
		echo '" style="width: 140px; text-align: left;" /></td>';
		echo '<td><input type="submit" name="addqanda" value="'.__('Add New Q & A', 'humancaptcha').'" style="width: 125px;" /></td></tr>';
		if (isset($message)){
			echo '<tr><td colspan="5"><strong>',$message,'</strong></td></tr>';
		}
		echo '</form>';
		echo '</table>';
	}
	
	function obr_update_qanda($ref, $question, $answer){
		global $wpdb;
		global $obr_table_name;
		$wpdb->update($obr_table_name, array('fld_questions' => $question,'fld_answers' => $answer), array('fld_ref' => $ref));
	}

	function obr_delete_qanda($ref){
		global $wpdb;
		global $obr_table_name;
		$wpdb->query("DELETE FROM $obr_table_name WHERE fld_ref = $ref;");
	}

	function obr_add_qanda($question, $answer){
		global $wpdb;
		global $obr_table_name;
		$obr_add_qanda = $wpdb->insert($obr_table_name, array('fld_questions' => $question, 'fld_answers' => $answer));
	}
	
	function obr_admin_settings($message2 = null){
		global $wpdb;
		global $obr_admin_table_name;
		$mysql = "SELECT * FROM $obr_admin_table_name ORDER BY fld_setting ASC;";
		$page = 'plugins.php?page=obr-hlc';
		$num_rows = $wpdb->get_row($mysql);
		if ($wpdb->num_rows == 3){
			echo '<table style="text-align: center;"><tr><td width="50"><em>'.__('Number', 'humancaptcha').'</em></td><td width="300"><em>'.__('Setting', 'humancaptcha').'</em></td><td width="150"><em>'.__('Status', 'humancaptcha').'</em></td><td>&nbsp;</td></tr>';
			$counter = 1;
			foreach($wpdb->get_results($mysql) as $key => $row){
				echo '<form method="post" action="',$page,'">';
				echo '<tr><td style="width: 75px;">',$counter,'</td><td style="text-align: left;">';
				if (stripslashes($row->fld_setting) == 1){
					_e('Use on comments form? <em>Default: On</em>', 'humancaptcha');
				} elseif (stripslashes($row->fld_setting) == 2){
					_e('Use on registration form? <em>Default: Off</em>', 'humancaptcha');
				} elseif (stripslashes($row->fld_setting) == 3){
					_e('Use on login form? <em>Default: Off</em>', 'humancaptcha');
				} else {
					// there shouldn't be any other cases!
				}
				echo '</td><td>';
				if (stripslashes($row->fld_value)){
					echo '<strong>'.__('On', 'humancaptcha').'</strong><input type="hidden" name="value" value=1 />';
				} else {
					echo '<strong>'.__('Off', 'humancaptcha').'</strong><input type="hidden" name="value" value=0 />';
				}
				echo '</td>';
				echo '<td><input type="hidden" name="setting" value=',$row->fld_setting,' /><input type="submit" name="togglesetting" value="'.__('Toggle Setting', 'humancaptcha').'" style="width: 125px;" /></td>';
				echo '</form>';
				$counter++;
			}
			if (isset($message2)){
				echo '<tr><td colspan="4"><strong>',$message2,'</strong></td></tr>';
			}
			echo '</table>';
		} else {
			_e('Nothing to display.', 'humancaptcha');
		}
	}
	
	function obr_update_admin_settings($setting, $value){
		global $wpdb;
		global $obr_admin_table_name;
		$wpdb->update($obr_admin_table_name, array('fld_value' => $value), array('fld_setting' => $setting));
	}

	function obr_get_setting_value($setting){
		global $wpdb;
		global $obr_admin_table_name;
		$mysql = $wpdb->get_row("SELECT * FROM $obr_admin_table_name WHERE fld_setting = $setting LIMIT 1;");
		$value = $mysql->fld_value;
		if ($value){
			return $value;
		} else {
			return 0;
		}
	}

	function obr_init(){
		if (!session_id()){
			session_start();
		}
		global $comments_on, $register_on, $login_on; 
		// see obr_insert_default_admin_data for which setting is which...
		$comments_on = $this->obr_get_setting_value(1);
		$register_on = $this->obr_get_setting_value(2);
		$login_on = $this->obr_get_setting_value(3);
	}
}
?>
