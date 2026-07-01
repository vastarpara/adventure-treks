<?php
/**
 * Bookings List View.
 *
 * @package AdventureTreks
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Trek Bookings', 'adventure-treks' ); ?></h1>
	<hr class="wp-header-end">

	<div id="poststuff">
		<div id="post-body" class="metabox-holder">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="get">
						<input type="hidden" name="post_type" value="adventure_trek" />
						<input type="hidden" name="page" value="at-bookings" />
						<?php
						$table->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>
