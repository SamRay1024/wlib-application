<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FeedStack</title>

	<?php foreach ($stylesheets as $link) : ?>
	<link rel="stylesheet"
		media="<?= arrayValue($link, 'media', 'screen') ?>"
		href="<?= arrayValue($link, 'href', '') ?>">
	<?php endforeach; ?>

	<style>
		body {
			background: #f5f5f5;
		}

		form {
			background: #fff;
			border-radius: var(--border-radius);
			box-shadow: 0px 0px 3px rgba(0, 0, 0, 0.1);
		}
	</style>
</head>

<body>
	<header class="center mtl tce">
		<h1><?= $appname ?></h1>
	</header>

	<section class="center w400p mtl">

		<?php if (isset($error) && $error) : ?>
			<p class="wcallout wdanger mbl"><?= $error ?></p>
		<?php endif; ?>

		<?php if (isset($success) && $success) : ?>
			<p class="wcallout wsuccess mbl"><?= $success ?></p>
		<?php endif; ?>

		<form method="post" class="pal">

			<?php // ==== LOGIN SCREEN ====================================== ?>
			<?php if ($screen == 'login') : ?>

				<div class="wfield">
					<label for="user"><?= __('User:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="text" name="username" value="<?= $username ?>" required>
					</div>
				</div>

				<div class="wfield">
					<label><?= __('Password:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="password" name="password" required>
					</div>
				</div>

				<div class="wfield">
					<button type="submit" class="wbtn-primary w100 mtm big"><?= __('Connect') ?></button>
				</div>
				
				<?php if ($can_update_users) : ?>
				<div class="wfield small tce">
					<?php if ($can_register) : ?>
					<a href="register"><?= __('Register') ?></a> • 
					<?php endif; ?>
					<a href="forgot"><?= __('Forgot password ?') ?></a>
				</div>
				<?php endif; ?>

			<?php endif; /* login */ ?>

			<?php // ==== REGISTER START SCREEN ============================= ?>
			<?php if ($screen == 'register') : ?>

				<h3><?= __('Register your email address') ?></h3>

				<div class="wfield">
					<label for="user"><?= __('Email address:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="text" name="email" value="<?= $email ?>" required>
					</div>
				</div>

				<p><?= __('A validation email with a link will be sent to the given address.') ?></p>
				<p><?= __('Please click on the link to continue the registering process.') ?></p>

				<div class="wfield">
					<button type="submit" class="wbtn-primary w100 mtm big"><?= __('Send') ?></button>
				</div>

				<div class="wfield small tce">
					<a href="login"><?= __('« Go back to login') ?></a>
				</div>

			<?php endif; /* register */ ?>

			<?php // ==== REGISTER WAITING SCREEN =========================== ?>
			<?php if ($screen == 'register-waiting') : ?>

				<h3><?= __('Thanks for your interest !') ?></h3>
				<p><?= __('A validation email has been sent. Check your mail box for our confirmation email.') ?></p>
				<p>✅ <?= __('You can now close this page. See you very soon !') ?></p>

			<?php endif; /* register-waiting */ ?>

			<?php // ==== VERIFY SCREEN ===================================== ?>
			<?php if ($screen == 'verify') : ?>
				<h3><?= __('You\'re almost done !'); ?></h3>

				<input type="hidden" name="vkey" value="<?= $vk ?>">

				<div class="wfield">
					<label for="name"><?= __('Your name:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="text" name="name" value="<?= $name ?>" required>
					</div>
				</div>

				<div class="wfield">
					<label for="user"><?= __('Password:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="password" name="password" value="" required>
					</div>
				</div>

				<div class="wfield">
					<label for="user"><?= __('Password verification:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="password" name="password_check" value="" required>
					</div>
				</div>

				<div class="wfield">
					<button type="submit" class="wbtn-primary w100 mtm big"><?= __('Create my account') ?></button>
				</div>
			<?php endif; /* verify */ ?>

			<?php // ==== FORGOT PWD SCREEN ================================= ?>
			<?php if ($screen == 'forgot') : ?>

				<h3><?= __('Don\'t remember your password ?') ?></h3>

				<div class="wfield">
					<label for="user"><?= __('Email address:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="text" name="email" value="<?= $email ?>" required>
					</div>
				</div>

				<p><?= __('Please fill in with your account email address.') ?></p>
				<p><?= __('You will receive an email with a link to renew your password.') ?></p>

				<div class="wfield">
					<button type="submit" class="wbtn-primary w100 mtm big"><?= __('Send') ?></button>
				</div>

				<div class="wfield small tce">
					<a href="login"><?= __('« Go back to login') ?></a>
				</div>

			<?php endif; /* forgot */ ?>

			<?php // ==== FORGOT WAITING SCREEN ============================= ?>
			<?php if ($screen == 'forgot-waiting') : ?>

				<h3><?= __('Password renewal in progress') ?></h3>
				<p><?= __('An email has been sent with a link to access password renewal form. Check your mail box.') ?></p>
				<p>✅ <?= __('You can now close this page.') ?></p>

			<?php endif; /* forgot-waiting */ ?>

			<?php // ==== RENEW PASSWORD SCREEN ============================= ?>
			<?php if ($screen == 'renew') : ?>
				<h3><?= __('Renew your password'); ?></h3>

				<div class="wfield">
					<label for="user"><?= __('Password:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="password" name="password" value="" required>
					</div>
				</div>

				<div class="wfield">
					<label for="user"><?= __('Password verification:') ?></label>
					<div class="winput">
						<input class="w100 bigger" type="password" name="password_check" value="" required>
					</div>
				</div>

				<div class="wfield">
					<button type="submit" class="wbtn-primary w100 mtm big"><?= __('Update password') ?></button>
				</div>

				<div class="wfield small tce">
					<a href="login"><?= __('« Go back to login') ?></a>
				</div>
			<?php endif; /* renew */ ?>

			<input type="hidden" name="_token" value="<?= $token ?>">
		</form>
	</section>
</body>

</html>