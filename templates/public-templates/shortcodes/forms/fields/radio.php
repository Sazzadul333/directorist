<?php
/**
 * @author  AazzTech
 * @since   6.7
 * @version 6.7
 */

?>

<div class="form-group" id="directorist-radio-field">
	<?php if ( ! empty( $label ) ) : ?>
		<label for="<?php echo esc_attr( $field_key ); ?>"><?php echo esc_html( $label ); ?>:<?php echo ! empty( $required ) ? Directorist_Listing_Forms::instance()->add_listing_required_html() : ''; ?></label>
	<?php endif; ?>

	<div class="form-control">
		<input type="radio" id="radio1" name="radio1" value="Bike">
		<label for="radio1"> I have a bike</label><br>
		<input type="radio" id="radio2" name="radio2" value="Car">
		<label for="radio2"> I have a car</label><br>
		<input type="radio" id="radio3" name="radio3" value="Boat">
		<label for="radio3"> I have a boat</label><br><br>
	</div>

	<p> <?php echo esc_attr( $description ); ?> </p>
</div>