<?php
// Template from : https://stackoverflow.design/email/templates/short-transactional/

$s_h1		= 'font-weight:bold; font-size:27px; line-height:27px; color:#0C0D0E; margin:0 0 15px 0;';
$s_p		= 'margin:0 0 15px;';
$s_p_footer	= 'padding-bottom:5px; font-size:12px; line-height:15px; font-family:arial, sans-serif; color:#9199A1; text-align:left;';
$s_a 		= 'color:#0077CC; text-decoration:none;';
$s_a_footer	= 'color:#9199A1; text-decoration:underline;';
$s_ul		= 'padding:0; margin:0; list-style-type:disc;';
$s_li		= 'margin:0 0 10px 30px;';
$s_btnf		= 'background:#0095FF; border:1px solid #0077cc; box-shadow:inset 0 1px 0 0 rgba(102,191,255,.75); font-family:arial, sans-serif; font-size:17px; line-height:17px; color:#ffffff; text-align:center; text-decoration:none; padding:13px 17px; display:block; border-radius:4px;';

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="ltr" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
	<meta name="x-apple-disable-message-reformatting">
	<meta name="format-detection" content="telephone=no, date=no, address=no, email=no, url=no">
	<meta name="color-scheme" content="light dark">
	<meta name="supported-color-schemes" content="light dark">
	<title><?= $mail->Subject ?></title>

	<!-- CSS Reset : BEGIN -->
	<style>
		:root {
			color-scheme: light dark;
			supported-color-schemes: light dark;
		}

		html,
		body {
			margin: 0 auto !important;
			padding: 0 !important;
			height: 100% !important;
			width: 100% !important;
		}

		* {
			-ms-text-size-adjust: 100%;
			-webkit-text-size-adjust: 100%;
		}

		div[style*="margin: 16px 0"] {
			margin: 0 !important;
		}

		table,
		td {
			mso-table-lspace: 0pt !important;
			mso-table-rspace: 0pt !important;
		}

		table {
			border: 0;
			border-spacing: 0;
			border-collapse: collapse
		}

		#MessageViewBody,
		#MessageWebViewDiv {
			width: 100% !important;
		}

		img {
			-ms-interpolation-mode: bicubic;
		}

		a {
			text-decoration: none;
		}

		a[x-apple-data-detectors],
		.unstyle-auto-detected-links a,
		.aBn {
			border-bottom: 0 !important;
			cursor: default !important;
			color: inherit !important;
			text-decoration: none !important;
			font-size: inherit !important;
			font-family: inherit !important;
			font-weight: inherit !important;
			line-height: inherit !important;
		}

		u+#body a,
		#MessageViewBody a {
			color: inherit;
			text-decoration: none;
			font-size: inherit;
			font-family: inherit;
			font-weight: inherit;
			line-height: inherit;
		}

		.im {
			color: inherit !important;
		}

		.a6S {
			display: none !important;
			opacity: 0.01 !important;
		}

		/* If the above doesn't work, add a .g-img class to any image in question. */
		img.g-img+div {
			display: none !important;
		}

		@media only screen and (min-device-width: 320px) and (max-device-width: 374px) {
			u~div .email-container {
				min-width: 320px !important;
			}
		}

		@media only screen and (min-device-width: 375px) and (max-device-width: 413px) {
			u~div .email-container {
				min-width: 375px !important;
			}
		}

		@media only screen and (min-device-width: 414px) {
			u~div .email-container {
				min-width: 414px !important;
			}
		}
	</style>
	<!--[if gte mso 9]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
	<!-- CSS Reset : END -->

	<!-- Progressive Enhancements : BEGIN -->
	<style>
		.s-btn__filled:hover {
			background: #0077CC !important;
			border-color: #0077CC !important;
		}

		.s-btn__white:hover {
			background: #EFF0F1 !important;
			border-color: #EFF0F1 !important;
		}

		.s-btn__outlined:hover {
			background: rgba(0, 119, 204, .05) !important;
			color: #005999 !important;
		}

		.s-tag:hover,
		.post-tag:hover {
			border-color: #cee0ed !important;
			background: #cee0ed !important;
		}

		.has-markdown a,
		.has-markdown a:visited {
			color: #0077CC !important;
			text-decoration: none !important;
		}

		code {
			padding: 1px 5px;
			background-color: #EFF0F1;
			color: #242729;
			font-size: 13px;
			line-height: inherit;
			font-family: Consolas, Menlo, Monaco, Lucida Console, Liberation Mono, DejaVu Sans Mono, Bitstream Vera Sans Mono, Courier New, monospace, sans-serif;
		}

		pre {
			margin: 0 0 15px;
			line-height: 17px;
			background-color: #EFF0F1;
			padding: 4px 8px;
			border-radius: 3px;
			overflow-x: auto;
		}

		pre code {
			margin: 0 0 15px;
			padding: 0;
			line-height: 17px;
			background-color: none;
		}

		blockquote {
			margin: 0 0 15px;
			padding: 4px 10px;
			background-color: #FFF8DC;
			border-left: 2px solid #ffeb8e;
		}

		blockquote p {
			padding: 4px 0;
			margin: 0;
			overflow-wrap: break-word;
		}

		.bar {
			border-radius: 5px;
		}

		.btr {
			border-top-left-radius: 5px;
			border-top-right-radius: 5px;
		}

		.bbr {
			border-bottom-left-radius: 5px;
			border-bottom-right-radius: 5px;
		}

		@media screen and (max-width: 680px) {

			.stack-column,
			.stack-column-center {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				direction: ltr !important;
			}

			.stack-column-center {
				text-align: center !important;
			}

			.hide-on-mobile {
				display: none !important;
				max-height: 0 !important;
				overflow: hidden !important;
				visibility: hidden !important;
			}

			.sm-p-none {
				padding: 0 !important;
			}

			.sm-pt-none {
				padding-top: 0 !important;
			}

			.sm-pb-none {
				padding-bottom: 0 !important;
			}

			.sm-pr-none {
				padding-right: 0 !important;
			}

			.sm-pl-none {
				padding-left: 0 !important;
			}

			.sm-px-none {
				padding-left: 0 !important;
				padding-right: 0 !important;
			}

			.sm-py-none {
				padding-top: 0 !important;
				padding-bottom: 0 !important;
			}

			.sm-p {
				padding: 20px !important;
			}

			.sm-pt {
				padding-top: 20px !important;
			}

			.sm-pb {
				padding-bottom: 20px !important;
			}

			.sm-pr {
				padding-right: 20px !important;
			}

			.sm-pl {
				padding-left: 20px !important;
			}

			.sm-px {
				padding-left: 20px !important;
				padding-right: 20px !important;
			}

			.sm-py {
				padding-top: 20px !important;
				padding-bottom: 20px !important;
			}

			.sm-mb {
				margin-bottom: 20px !important;
			}

			.bar,
			.btr,
			.bbr {
				border-top-left-radius: 0;
				border-top-right-radius: 0;
				border-bottom-left-radius: 0;
				border-bottom-right-radius: 0;
			}
		}
	</style>
	<!-- Progressive Enhancements : END -->
</head>

<body width="100%" style="margin: 0; padding: 0 !important; background: #f3f3f5; mso-line-height-rule: exactly;">
	<center style="width: 100%; background: #f3f3f5;">
		<!--[if mso | IE]>
		<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f3f3f5;">
			<tr>
				<td>
		<![endif]-->

		<?php if (!empty($preview)) : ?>
		<div style="display: none; font-size: 1px; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all; font-family: sans-serif;">
			<?= $preview ?>
		</div>
		<?php endif; ?>

		<div class="email-container" style="max-width: 680px; margin: 0 auto;">
			<!--[if mso]>
			<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="680" align="center">
				<tr>
					<td>
			<![endif]-->
			<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 680px; width:100%">
				<!-- Logo : BEGIN -->
				<tr>
					<td style="padding: 20px 30px; text-align: left;" class="sm-px">
						<?= $appname ?>
					</td>
				</tr>
				<!-- Logo : END -->

				<!-----------------------------

					EMAIL BODY : BEGIN

				------------------------------>

				<tr>
					<td style="padding: 30px; background-color: #ffffff;" class="sm-p bar">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">