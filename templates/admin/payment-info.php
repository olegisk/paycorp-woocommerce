<?php
/** @var string $card_type */
/** @var string $card_holder */
/** @var string $card_number */
/** @var string $card_expiry */
/** @var string $response_code */
/** @var string $response_text */
/** @var string $authcode */
/** @var string $settlement_date */
/** @var string $transaction_id */

defined( 'ABSPATH' ) || exit;

?>
<div>
	<?php if ( ! empty( $transaction_id ) ) : ?>
		<strong><?php _e( 'TXN Reference', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $transaction_id ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $response_code ) ) : ?>
		<strong><?php _e( 'Response code', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $response_code ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $response_text ) ) : ?>
		<strong><?php _e( 'Response text', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $response_text ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $authcode ) ) : ?>
		<strong><?php _e( 'Auth code', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $authcode ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $settlement_date ) ) : ?>
		<strong><?php _e( 'Settlement date', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $settlement_date ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $card_type ) ) : ?>
		<strong><?php _e( 'Card type', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $card_type ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $card_holder ) ) : ?>
		<strong><?php _e( 'Card holder', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $card_holder ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $card_number ) ) : ?>
		<strong><?php _e( 'Card number', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $card_number ); ?>
		<br/>
	<?php endif; ?>

	<?php if ( ! empty( $card_expiry ) ) : ?>
		<strong><?php _e( 'Card expiry', 'paycorp' ); ?>
			:</strong> <?php echo esc_html( $card_expiry ); ?>
		<br/>
	<?php endif; ?>
</div>
