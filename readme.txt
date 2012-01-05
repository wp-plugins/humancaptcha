=== Outerbridge HumanCaptcha ===
Contributors: Mike Jones (outerbridge)
Author URI: http://outerbridge.co.uk/author/mike/
Tags: captcha, text-based, human, logic, questions, answers
Requires at least: 3.2
Tested up to: 3.3
Stable tag: trunk

HumanCaptcha is plugin written by Outerbridge which uses questions that require human logic to answer them and which machines cannot easily answer.


== Description ==

HumanCaptcha is plugin written by Outerbridge which uses questions that require human logic to answer them and which machines cannot easily answer.  

Most captchas are based on the requirement to reproduce a number of randomly-generated characters (which are sometimes blurred, jiggled and/or on a fuzzy background).  HumanCaptcha generates a simple question which the user must answer using logical thought.  HumanCaptcha is much more accessible than standard captchas, which many people find difficult to read or understand.  Visually impaired people are more likely to be able to use HumanCaptcha than a character-based one.


CAPTCHAs are useful for improving security in a number of situations, for example:

1.	Reducing Comment Spam in Blogs
	Most bloggers will have come across programs that submit spam comments, often with the aim of improving the search engine ranking of a website.  By using a CAPTCHA, only humans can enter comments on your blog, and people do not need to sign up before they enter a comment.

2.	Protecting Email Addresses From Scrapers
	Spammers crawl the web looking for e-mail addresses rendered in text. CAPTCHAs can hide your e-mail address from web scrapers, by requiring users to solve a CAPTCHA before revealing your e-mail. 

3.	Deterring Viruses, Worms and Spam 
	CAPTCHAs may reduce the likelihood of e-mailed viruses, worms and spam, by only accepting an e-mail if it has been established that there is a human behind the sending computer.


== Installation ==

1. Install automatically through the 'Plugins', 'Add New' menu in WordPress, or upload the 'outerbridge-humancaptcha' folder to the '/wp-content/plugins/' directory. 

2. Activate the plugin through the 'Plugins' menu in WordPress. Look for the link under the Plugins menu to amend the questions and andswers. 

3. Test the plugin in by logging out and posting a comment.

4. Updates are automatic. Click on "Upgrade Automatically" if prompted from the admin menu. If you ever have to manually upgrade, simply deactivate, uninstall, and repeat the installation steps with the new version.


== Frequently Asked Questions ==

= Where do I get help with this plugin? =

Email us and we'll do our best to support.


== Screenshots ==

1. screenshot-1.png shows how the question slots seamlessly into the comments section of the WordPress site.

2. screenshot-2.png shows the administration section of the Outerbridge HumanCaptcha plugin.


== Changelog ==

= 1.2 =
* (05 Jan 2012) Updated obr_admin_menu function to check against 'manage_options' rather than 'edit_plugins'.

= 1.1 =
* (03 Jan 2012) Tested and stable up to WP3.3

= 1.0 =
* (30 Sep 2011) HumanCaptcha now added to registration and login forms as well as comments form.  Toggles added to admin menu to allow users to decide where HumanCaptcha is applied.

= 0.2 =
* (30 Aug 2011) Fixed session_start issue

= 0.1 =
* (25 Aug 2011) Initial Release


== Upgrade Notice ==

= 1.2 =
* Included safer admin menu.

= 1.1 =
* Tested and stable up to WP3.3

= 1.0 =
* New functionality added.

= 0.2 =
* Updated to improve plugin compatability.

= 0.1 =
* This is the initial release.
