<?php
if (isset($_POST['updateqanda'])){
	$question = mysql_real_escape_string(trim(stripslashes(htmlentities($_POST['question']))));
	$answer = mysql_real_escape_string(trim(stripslashes(htmlentities($_POST['answer']))));
	$ref = mysql_real_escape_string(stripslashes(htmlentities($_POST['ref'])));
	$ref = abs(intval($ref)); // it's a positive integer
	if ($question && $answer && $ref){
		$message = 'Question and/or answer updated.';
		$this->obr_update_qanda($ref, $question, $answer);
	} else {
		$message = 'Cannot update the question and/or answer.';
	}
}	
if (isset($_POST['deleteqanda'])){
	$ref = mysql_real_escape_string(stripslashes(htmlentities($_POST['ref'])));
	$ref = abs(intval($ref)); // it's a positive integer
	if ($ref){
		$message = 'Selected question and answer deleted.';
		$this->obr_delete_qanda($ref);
	} else {
		$message = 'Cannot delete the selected question and answer.';
	}
}	
if (isset($_POST['addqanda'])){
	$question = mysql_real_escape_string(trim(stripslashes(htmlentities($_POST['question']))));
	$answer = mysql_real_escape_string(trim(stripslashes(htmlentities($_POST['answer']))));
	if ($question && $answer){
		$message = 'New question and answer added.';
		$this->obr_add_qanda($question, $answer);
	} else {
		$message = 'Cannot add the new question and answer.';
	}
}
?>
<h1><img src="<?php echo plugins_url('humancaptcha/outerbridge-logo.png'); ?>" width="150" height="107" /><br />Outerbridge HumanCaptcha - Settings</h1>
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
