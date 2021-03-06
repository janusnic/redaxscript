<?php

/**
 * registration form
 *
 * @since 1.2.1
 * @deprecated 2.0.0
 *
 * @package Redaxscript
 * @category Registration
 * @author Henry Ruhs
 */

function registration_form()
{
	$output = Redaxscript\Hook::trigger(__FUNCTION__ . '_start');

	/* disable fields if attack blocked */

	if (ATTACK_BLOCKED > 9)
	{
		$code_disabled = ' disabled="disabled"';
	}

	/* captcha object */

	if (s('captcha') > 0)
	{
		$captcha = new Redaxscript\Captcha(Redaxscript\Language::getInstance());
		$captcha->init();
	}

	/* collect output */

	$output .= '<h2 class="title_content">' . l('account_create') . '</h2>';
	$output .= form_element('form', 'form_registration', 'js_validate_form form_default form_registration', '', '', '', 'action="' . REWRITE_ROUTE . 'registration" method="post"');
	$output .= form_element('fieldset', '', 'set_registration', '', '', l('fields_required') . l('point')) . '<ul>';
	$output .= '<li>' . form_element('text', 'name', 'field_text field_note', 'name', '', '* ' . l('name'), 'maxlength="50" required="required" autofocus="autofocus"' . $code_disabled) . '</li>';
	$output .= '<li>' . form_element('text', 'user', 'field_text field_note', 'user', '', '* ' . l('user'), 'maxlength="50" required="required"' . $code_disabled) . '</li>';
	$output .= '<li>' . form_element('email', 'email', 'field_text field_note', 'email', '', '* ' . l('email'), 'maxlength="50" required="required"' . $code_disabled) . '</li>';

	/* collect captcha task output */

	if (LOGGED_IN != TOKEN && s('captcha') > 0)
	{
		$output .= '<li>' . form_element('number', 'task', 'field_text field_note', 'task', '', $captcha->getTask(), 'min="1" max="20" required="required"' . $code_disabled) . '</li>';
	}
	$output .= '</ul></fieldset>';

	/* collect captcha solution output */

	if (s('captcha') > 0)
	{
		if (LOGGED_IN == TOKEN)
		{
			$output .= form_element('hidden', '', '', 'task', $captcha->getSolution('raw'));
		}
		$output .= form_element('hidden', '', '', 'solution', $captcha->getSolution());
	}

	/* collect hidden and button output */

	$output .= form_element('hidden', '', '', 'token', TOKEN);
	$output .= form_element('button', '', 'js_submit button_default', 'registration_post', l('create'), '', $code_disabled);
	$output .= '</form>';
	$output .= Redaxscript\Hook::trigger(__FUNCTION__ . '_end');
	$_SESSION[ROOT . '/registration'] = 'visited';
	echo $output;
}

/**
 * registration post
 *
 * @since 1.2.1
 * @deprecated 2.0.0
 *
 * @package Redaxscript
 * @category Registration
 * @author Henry Ruhs
 */

function registration_post()
{
	/* clean post */

	if (ATTACK_BLOCKED < 10 && $_SESSION[ROOT . '/registration'] == 'visited')
	{
		$name = $r['name'] = clean($_POST['name'], 0);
		$user = $r['user'] = clean($_POST['user'], 0);
		$email = $r['email'] = clean($_POST['email'], 3);
		$password = hash_generator(10);
		$r['password'] = sha1($password) . SALT;
		$r['description'] = '';
		$r['language'] = Redaxscript\Registry::get('language');
		$r['first'] = $r['last'] = NOW;
		$r['groups'] = Redaxscript\Db::forTablePrefix('groups')->where('alias', 'members')->findOne()->id;
		if ($r['groups'] == '')
		{
			$r['groups'] = 0;
		}
		$task = $_POST['task'];
		$solution = $_POST['solution'];
	}

	/* validate post */

	$loginValidator = new Redaxscript\Validator\Login();
	$emailValidator = new Redaxscript\Validator\Email();
	$captchaValidator = new Redaxscript\Validator\Captcha();

	if ($name == '')
	{
		$error = l('name_empty');
	}
	else if ($user == '')
	{
		$error = l('user_empty');
	}
	else if ($email == '')
	{
		$error = l('email_empty');
	}
	else if ($loginValidator->validate($user) == Redaxscript\Validator\ValidatorInterface::FAILED)
	{
		$error = l('user_incorrect');
	}
	else if ($emailValidator->validate($email) == Redaxscript\Validator\ValidatorInterface::FAILED)
	{
		$error = l('email_incorrect');
	}
	else if ($captchaValidator->validate($task, $solution) == Redaxscript\Validator\ValidatorInterface::FAILED)
	{
		$error = l('captcha_incorrect');
	}
	else if (Redaxscript\Db::forTablePrefix('users')->where('user', $user)->findOne()->id)
	{
		$error = l('user_exists');
	}
	else
	{
		if (USERS_NEW == 0 && s('verification') == 1)
		{
			$r['status'] = 0;
			$success = l('registration_verification');
		}
		else
		{
			$r['status'] = 1;
			$success = l('registration_sent');
		}

		/* send login information */

		$loginRoute = ROOT . '/' . REWRITE_ROUTE . 'login';
		$loginLink = anchor_element('external', '', '', $loginRoute, $loginRoute);
		$toArray = array(
			$name => $email
		);
		if (s('notification') == 1)
		{
			$toArray[s('author')] = s('email');
		}
		$fromArray = array(
			$author => $email
		);
		$subject = l('registration');
		$bodyArray = array(
			'<strong>' . l('name') . l('colon') . '</strong> ' . $name,
			'<br />',
			'<strong>' . l('user') . l('colon') . '</strong> ' . $user,
			'<br />',
			'<strong>' . l('password') . l('colon') . '</strong> ' . $password,
			'<br />',
			'<strong>' . l('login') . l('colon') . '<strong> ' . $loginLink
		);

		/* mailer object */

		$mailer = new Redaxscript\Mailer();
		$mailer->init($toArray, $fromArray, $subject, $bodyArray);
		$mailer->send();

		/* create user */

		Redaxscript\Db::forTablePrefix('users')
			->create()
			->set($r)
			->save();
	}

	/* handle error */

	if ($error)
	{
		if (s('blocker') == 1)
		{
			$_SESSION[ROOT . '/attack_blocked']++;
		}
		notification(l('error_occurred'), $error, l('back'), 'registration');
	}

	/* handle success */

	else
	{
		notification(l('operation_completed'), $success, l('login'), 'login');
	}
	$_SESSION[ROOT . '/registration'] = '';
}
