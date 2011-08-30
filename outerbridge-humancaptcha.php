<?php  
/* 
Plugin Name: Outerbridge HumanCaptcha 
Plugin URI: http://outerbridge.co.uk/humancaptcha/ 
Description: HumanCaptcha is a plugin written by Outerbridge which uses questions that require human logic to answer them and which machines cannot easily answer.
Author: Mike Jones a.k.a. Outerbridge Mike
Version: 0.2
Author URI: http://outerbridge.co.uk/author/mike/
Tags: captcha, text-based, human, logic, questions, answers
License: GPL v2
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
 * 1.	Reducing Comment Spam in Blogs
 * 	Most bloggers will have come across programs that submit spam comments, often with the aim of improving the search
 * 	engine ranking of a website.  By using a CAPTCHA, only humans can enter comments on your blog, and people do not need
 * 	to sign up before they enter a comment.
 * 2.	Protecting Email Addresses From Scrapers
 * 	Spammers crawl the web looking for e-mail addresses rendered in text. CAPTCHAs can hide your e-mail address from web
 * 	scrapers, by requiring users to solve a CAPTCHA before revealing your e-mail. 
 * 3.	Deterring Viruses, Worms and Spam 
 * 	CAPTCHAs may reduce the likelihood of e-mailed viruses, worms and spam, by only accepting an e-mail if it has been
 *    established that there is a human behind the sending computer.
 * 
 */

new obr_humancaptcha;

class obr_humancaptcha{
	
	// version
	public $obr_humancaptcha_version = '0.2';
	
	// constructor
	function obr_humancaptcha() {
		$this->__construct();
	}
	
	function __construct(){
		register_activation_hook(__FILE__, array(&$this, 'obr_install'));
		add_filter('preprocess_comment', array(&$this, 'obr_validate_answer'));
		add_filter('comment_form_default_fields', array(&$this, 'obr_build_form'));
		add_action('wp_head', array(&$this, 'obr_header'));
		add_action('admin_menu', array(&$this, 'obr_admin_menu'));
		add_action('init', array(&$this, 'obr_init'));
	}

	// functions
	function obr_install(){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
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

		// now add in a version number
		add_option('obr_humancaptcha_version', $obr_humancaptcha_version);
		// check for updates
		$installed_ver = get_option("obr_humancaptcha_version");
		if($installed_ver != $obr_humancaptcha_version){
			echo 'Outerbridge humancaptcha updated to version ',$obr_humancaptcha_version;
			// update specifics go here
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			update_option("obr_humancaptcha_version", $obr_humancaptcha_version);
		}
	}
	
	function obr_insert_default_data(){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
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

	function obr_table_name(){
		global $wpdb;
		$obr_table_name = $wpdb->prefix."obr_humancaptcha_qanda";
		return $obr_table_name;
	}	
	
	function obr_build_form($fields){
		global $user_ID;
		if (!$user_ID){
			$selected = $this->obr_select_question();
			$question = $selected['question'];
			$answer = $selected['answer'];
			$_SESSION['obr_answer'] = md5(strtolower(trim(htmlentities($answer))));
			// use the comment-form-email class as it works better with 2011
			$fields['obr_hlc'] = '<p class="comment-form-email"><label for="obr_hlc">'.__(stripslashes($question)).'</label> <span class="required">*</span><input id="answer" name="answer" size="30" type="text" aria-required=\'true\' /></p>';
			return $fields;
		}
	}
	
	function obr_header(){
		echo "\n".'<!-- Using Outerbridge HumanCaptcha.  Find out more at http://outerbridge.co.uk/humancaptcha/ -->'."\n";
	}

	function obr_select_question(){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
		$mysql = "SELECT * FROM $obr_table_name ORDER BY RAND() LIMIT 1;";
		$row = $wpdb->get_row($mysql);
		$selected = array('question' => $row->fld_questions, 'answer' => $row->fld_answers);
		return $selected;
	}

	function obr_validate_answer($commentdata){
		global $user_ID;
		if (!$user_ID){
			session_start();
			if ((!isset($_POST['answer'])) || ($_POST['answer'] == '')){
				wp_die(__('Error: please fill the required field (humancaptcha).'));
			}
			$user_answer = md5(strtolower(trim(stripslashes(htmlentities($_POST['answer'])))));
			$obr_answer = strtolower(trim(stripslashes(htmlentities($_SESSION['obr_answer']))));
			if ($user_answer != $obr_answer){
				wp_die(__('Error: your answer to the humancaptcha question is incorrect.  Use your browser\'s back button to try again.'));
			}
		}
		return $commentdata;
	}

	function obr_admin_menu(){
		if (is_super_admin()) {
			add_submenu_page('plugins.php', 'HumanCaptcha Admin', 'HumanCaptcha Admin', 'edit_plugins', 'obr-hlc', array(&$this, 'obr_admin'));
		}
	}
	
	function obr_admin(){
		require_once('outerbridge-humancaptcha-admin.php');
	}
	
	function obr_qanda_settings($message = null, $question = null, $answer = null){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
		$mysql = "SELECT * FROM $obr_table_name ORDER BY fld_ref ASC;";
		$page = 'plugins.php?page=obr-hlc';
		$num_rows = $wpdb->get_row($mysql);
		if ($wpdb->num_rows > 0){
			echo '<table style="text-align: center;"><tr><td width="50">Number</td><td width="500">Question</td><td width="150">Answer</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
			$counter = 1;
			foreach($wpdb->get_results($mysql) as $key => $row){
				echo '<form method="post" action="',$page,'">';
				echo '<tr><td style="width: 75px;">',$counter,'</td><td><input type="text" name="question" value="',stripslashes($row->fld_questions),'" style="width: 490px; text-align: left;" /></td><td><input type="text" name="answer" value="',stripslashes($row->fld_answers),'" style="width: 140px; text-align: left;" /></td>';
				echo '<td><input type="hidden" name="ref" value=',$row->fld_ref,' /><input type="submit" name="updateqanda" value="Update Q & A" style="width: 125px;" /></td>';
				echo '</form>';
				echo '<form method="post" action="',$page,'">';
				echo '<td><input type="hidden" name="ref" value=',$row->fld_ref,' /><input type="submit" onclick="return confirm(\'Are you sure you want to delete this? Press OK to confirm\')" class="delRow" name="deleteqanda" value="Delete Q & A" style="width: 125px;" /></td></tr>';
				echo '</form>';
				$counter++;
			}
			echo '<form method="post" action="',$page,'">';
			echo '<tr><td>Add New</td><td><input type="text" name="question" value="';
			if (isset($question)){
				echo $question;
			}
			echo '" style="width: 490px; text-align: left;" /></td><td><input type="text" name="answer" value="';
			if (isset($answer)){
				echo $answer;
			}
			echo '" style="width: 140px; text-align: left;" /></td>';
			echo '<td><input type="submit" name="addqanda" value="Add New Q & A" style="width: 125px;" /></td></tr>';
			if (isset($message)){
				echo '<tr><td colspan="5"><strong>',$message,'</strong></td></tr>';
			}
			echo '</form>';
			echo '</table>';
		} else {
			echo 'Nothing to display.';
		}
	}
	
	function obr_update_qanda($ref, $question, $answer){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
		$wpdb->update($obr_table_name, array('fld_questions' => $question,'fld_answers' => $answer), array('fld_ref' => $ref));
	}

	function obr_delete_qanda($ref){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
		$wpdb->query("DELETE FROM $obr_table_name WHERE fld_ref = $ref;");
	}

	function obr_add_qanda($question, $answer){
		global $wpdb;
		$obr_table_name = $this->obr_table_name();
		$obr_add_qanda = $wpdb->insert($obr_table_name, array('fld_questions' => $question, 'fld_answers' => $answer));
	}
	
	function obr_init(){
		if (!session_id()){
			session_start();
		}
	}
}
?>