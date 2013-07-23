<?php
$message = '';
$message2 = '';
if (isset($_POST['updateqanda'])){
	$question = mysql_real_escape_string(trim($_POST['question']));
	$answer = mysql_real_escape_string(trim($_POST['answer']));
	$ref = abs(intval($_POST['ref'])); // it's a positive integer
	if ($question && $answer && $ref){
		$message = 'Question and/or answer updated.';
		$this->obr_update_qanda($ref, $question, $answer);
	} else {
		$message = 'Cannot update the question and/or answer.';
	}
}	
if (isset($_POST['deleteqanda'])){
	$ref = abs(intval($_POST['ref'])); // it's a positive integer
	if ($ref){
		$message = 'Selected question and answer deleted.';
		$this->obr_delete_qanda($ref);
	} else {
		$message = 'Cannot delete the selected question and answer.';
	}
}	
if (isset($_POST['addqanda'])){
	$question = mysql_real_escape_string(trim($_POST['question']));
	$answer = mysql_real_escape_string(trim($_POST['answer']));
	if ($question && $answer){
		$message = 'New question and answer added.';
		$this->obr_add_qanda($question, $answer);
	} else {
		$message = 'Cannot add the new question and answer.';
	}
}
if (isset($_POST['togglesetting'])){
	$setting = mysql_real_escape_string(trim($_POST['setting']));
	$value = intval($_POST['value']);
	if ($setting && (($value == 0) || ($value == 1))){
		$message2 = 'Setting updated.';
		//toggle $value
		$value = 1 - $value;
		$this->obr_update_admin_settings($setting, $value);
	} else {
		$message2 = 'Setting could not be updated.';
	}
}
?>
<h1><img src="<?php echo plugins_url('humancaptcha/outerbridge-logo.png'); ?>" width="150" height="107" /><br />Outerbridge HumanCaptcha - Settings</h1>
<br />
<h3>HumanCaptcha Usage</h3>
<p>You can decide whether or not HumanCaptcha is applied to comment, registration and/or login forms.  Just toggle the settings below.</p>
<?php
echo $this->obr_admin_settings();
?>
<br />
<h3>HumanCaptcha Questions and Answers</h3>
<p>You can update your questions and answers here.</p>
<?php
if (isset($_POST['addqanda']) && (!$question) && (!$answer)){
	echo $this->obr_qanda_settings($message, $question, $answer);
} else {
	echo $this->obr_qanda_settings($message);
}
?>
<br />
<br />