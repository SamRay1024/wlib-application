<?php

$mail->Subject = __('Confirm your registration', W_L10N_DOMAIN);
$preview = __('Verify your email to finalize your account creation', W_L10N_DOMAIN);

require dirname(dirname(__FILE__)) . '/header.html.php';
?>
<!-- Rich Text : BEGIN -->
<tr>
	<td style="padding-bottom: 15px; font-family: arial, sans-serif; font-size: 15px; line-height: 21px; color: #3C3F44; text-align: left;">
	
		<h1 style="<?= $s_h1 ?>"><?= __('Confirm your registration', W_L10N_DOMAIN) ?></h1>
		<p style="<?= $s_p ?>"><?= __('To finalize your account creation, please click on the button below.', W_L10N_DOMAIN) ?></p>
		<p style="<?= $s_p ?>"><?= __('If you haven\'t ask for an account registration, you can simply ignore and delete this message.', W_L10N_DOMAIN) ?></p>

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
					<a class="s-btn s-btn__filled" href="<?= $confirmurl ?>"
						target="_parent"
						style="<?= $s_btnf ?>">
							<?= __('Create my account', W_L10N_DOMAIN) ?>
					</a>
				</td>
			</tr>
		</table>
		<!-- Button : END -->
	</td>
</tr>
<!-- Button Row : END -->
<?php

require dirname(dirname(__FILE__)) . '/footer.html.php';
