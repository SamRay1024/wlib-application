<?php

$mail->Subject = __('Your password has been updated', W_L10N_DOMAIN);
$preview = __('This mail confirms your password update.', W_L10N_DOMAIN);

require dirname(dirname(__FILE__)) . '/header.html.php';

?>
<!-- Rich Text : BEGIN -->
<tr>
	<td style="padding-bottom: 15px; font-family: arial, sans-serif; font-size: 15px; line-height: 21px; color: #3C3F44; text-align: left;">

		<h1 style="<?= $s_h1 ?>"><?= __('Your password has been updated', W_L10N_DOMAIN) ?></h1>
		<p style="<?= $s_p ?>">✅ <?= __('The password of your FeedStack account has been updated.', W_L10N_DOMAIN) ?></p>
		<p style="<?= $s_p ?>"><?= __('If you did not initiate this request to renew your password, make sure :', W_L10N_DOMAIN) ?></p>
		<ul style="<? $s_ul ?>">
			<li style="<?= $s_li ?>"><?= __('To <strong>secure your boxmail</strong> by setting a new password,', W_L10N_DOMAIN) ?></li>
			<li style="<?= $s_li ?>"><?= __('To <strong>restart the password renewal procedure</strong> to set a new password.', W_L10N_DOMAIN) ?></li>
		</ul>

	</td>
</tr>
<!-- Rich Text : END -->
<!-- Button Row : BEGIN -->
<tr>
	<td>
		<!-- Button : BEGIN -->
		<table align="left" border="0" cellpadding="0" cellspacing="0" role="presentation">
			<tr>
				<td class="s-btn s-btn__filled" style="border-radius: 4px; background: #0095ff;">
					<a class="s-btn s-btn__filled" href="<?= $loginurl ?>"
						target="_parent"
						style="<?= $s_btnf ?>">
							<?= __('Connect', W_L10N_DOMAIN) ?>
					</a>
				</td>
			</tr>
		</table>
		<!-- Button : END -->
	</td>
</tr>
<!-- Button Row : END -->
<?php require dirname(dirname(__FILE__)) . '/footer.html.php';
