<?php

//--------------------------------------------------
// Resources

	$response->css_add('/a/css/global/core.css');

	config::array_set('output.links', 'apple-touch-icon', '/a/img/global/favicon.png');

?>
<!DOCTYPE html>
<html lang="<?= html($response->lang_get()) ?>" xml:lang="<?= html($response->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<meta name="viewport" content="initial-scale=1" />

	<?= $response->head_get_html(); ?>

</head>
<body id="<?= html($response->page_id_get()) ?>">

	<div id="page_wrapper">

		<header id="page_header" role="banner">
			<h1><a href="/">Reader</a></h1>
		</header>

		<main id="page_content" role="main">









<!-- END OF PAGE TOP -->

	<?php if (isset($title_html)) { ?>

		<div id="page_title">
			<?= $title_html . "\n" ?>
		</div>

	<?php } ?>

	<?= $response->message_get_html(); ?>

	<?= $response->view_get_html(); ?>

<!-- START OF PAGE BOTTOM -->









		</main>

		<?php if (isset($footer_urls)) { ?>

			<footer id="page_footer" role="contentinfo">
				<?php
					foreach ($footer_urls as $footer_url) {
						if ($footer_url['href']) {
							echo '
								<a href="' . html($footer_url['href']) . '" class="' . html($footer_url['class']) . '"' . (isset($footer_url['target']) ? ' target="' . html($footer_url['target']) . '"' : '') . '><span>' . html($footer_url['text']) . '</span></a>';
						} else {
							echo '
								<span class="' . html($footer_url['class']) . '"><span>' . html($footer_url['text']) . '</span></span>';
						}
					}
				?>
			</footer>

		<?php } ?>

	</div>

	<?= $response->foot_get_html(); ?>

</body>
</html>