<script src="https://unpkg.com/sweetalert2@7.18.0/dist/sweetalert2.all.js"></script>
<h3><?php _e( 'Khalti', 'khalti' ); ?></h3>
<?php 
if(@$success == true)
{
    echo "<script>Swal('Refunded','".$detail."','success')</script>";
}
else if(@$error == true)
{
    echo "<script>Swal('Error','".$detail."','error')</script>";
}
?>
<!-- <script>Swal('Title','Done','error')</script> -->
<div class="gateway-banner updated">
  <img src="<?php echo Khalti()->plugin_url() . '/assets/images/logo.png'; ?>" style="width:200px"/>
  <p class="main"><h3><?php _e( 'Transaction Detail', 'khalti' ); ?></h3></p>
  <p>Source: <?php _e($transaction_detail_array['source'],'khalti');?></p>
  <p>Mobile: <?php _e($transaction_detail_array['mobile'],'khalti');?></p>
  <p>Amount: <?php _e($transaction_detail_array['amount'],'khalti');?></p>
  <p>Fee Amount: <?php _e($transaction_detail_array['fee_amount'],'khalti');?></p>
  <p>Date/Time: <?php _e($transaction_detail_array['date'],'khalti');?></p>
  <p>State: <?php _e($transaction_detail_array['state'],'khalti');?></p>
  <?php if($transaction_detail_array['refunded'] != true) {?>
      <a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=khalti&transaction_id=' ).$transaction_detail_array['idx'].'&refund=true';?>" class="button-primary woocommerce-save-button" onclick="return confirm('Are you sure you want to refund the amount?');">Refund</a>
  <?php }
  ?>
  <a class="button-primary woocommerce-save-button" href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=khalti' );?>">Back</a>
  <p></p>
</div>

<table class="form-table">
  <?php $this->generate_settings_html(); ?>
  <script type="text/javascript">
  jQuery( '#khalti' ).change( function () {
    var sandbox = jQuery( '#khalti_sandbox_public_key, #khalti_sandbox_private_key' ).closest( 'tr' ),
    production  = jQuery( '#khalti_public_key, #khalti_private_key' ).closest( 'tr' );

    if ( jQuery( this ).is( ':checked' ) ) {
      sandbox.show();
      production.hide();
    } else {
      sandbox.hide();
      production.show();
    }
  }).change();
  </script>
</table>