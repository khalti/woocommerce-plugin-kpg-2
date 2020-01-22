<script src="https://unpkg.com/sweetalert2@7.18.0/dist/sweetalert2.all.js"></script>
<link rel="stylesheet" href="//cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
<script src="//cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js"></script>
<style>
.tab {
    overflow: hidden;
    border: 1px solid #ccc;
    background-color: #f1f1f1;
}

/* Style the buttons inside the tab */
.tab button {
    background-color: inherit;
    float: left;
    border: none;
    outline: none;
    cursor: pointer;
    padding: 14px 16px;
    transition: 0.3s;
    font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
    background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
    background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
    display: none;
    padding: 47px 12px;
    border: 1px solid #ccc;
    border-top: none;
}

.khalti ul {
    list-style-type: none;
    margin: 0;
    padding: 0;
    overflow: hidden;
    border: 1px solid #e7e7e7;
    background-color: #f3f3f3;
}

.khalti li {
    float: left;
}

.khalti li a {
    display: block;
    color: #666;
    text-align: center;
    padding: 14px 16px;
    text-decoration: none;
}

.khalti li a:hover:not(.active) {
    background-color: #ddd;
}

.khalti li a.active {
    color: white;
    background-color: #4CAF50;
}
</style>
<h3><?php _e( 'Khalti', 'khalti' ); ?></h3>
<div class="gateway-banner updated inline">
  <img src="<?php echo Khalti()->plugin_url() . '/assets/images/logo.png'; ?>" style="width:200px"/>
  <p class="main"><strong><?php _e( 'Getting started', 'khalti' ); ?></strong></p>
  <p><?php _e( 'Khalti is the new generation Payment Gateway, Digital Wallet and API provider for various services. We provide you with true Payment Gateway, where you can accepts payments from<br><b>For Queries, feel free to call us at 9801165568 or email merchantapi@khalti.com</b>', 'khalti' ); ?></p>
 <!--new tabs-->
<ul class="khalti">
  <li><a class="tablinks" onclick="openTabs(event, 'Status')" href="#" id="defaultTab">Status</a></li>
  <li><a class="tablinks" onclick="openTabs(event, 'Transactions')" href="#">Transactions</a></li>
  <li><a class="tablinks" onclick="openTabs(event, 'FAQ')" href="#">FAQ</a></li>
</ul>

<div id="Status" class="tabcontent">
<div class="content">
  <p class="main"><strong><?php _e( 'Gateway Status', 'khalti' ); ?></strong></p>
            <ul>
              <li><?php echo __( 'Gateway Enabled?', 'khalti' ) . ' <strong>' . $this->enabled . '</strong>'; ?></li>
              <li><?php echo __( 'Sandbox Enabled?', 'khalti' ) . ' <strong>' . $this->sandbox . '</strong>'; ?></li>
            </ul>
            <?php if( empty( $this->public_key ) ) { ?>
            <p><a href="https://khalti.com/join/merchant/" target="_blank" class="button button-primary"><?php _e( 'Create Merchant Account', 'khalti' ); ?></a> <a href="https://khalti.com/join/" target="_blank" class="button"><?php _e( 'Create Consumer Account', 'khalti' ); ?></a></p>
            <?php } ?>
            </p>
  </p>
  </div>
</div>

<div id="Transactions" class="tabcontent">
<div class="content">
            <table class="table table-bordered table-hover" id="transaction_tbl" style="margin-top:20px">
            <thead>
            <tr>
              <th>Source</th>
              <th>Amount(Rs)</th>
              <th>Fee(Rs)</th>
              <th>Date</th>
              <th>Type</th>
              <th>State</th>
              <th>#</th>
          </tr>
          </thead>
          <tbody>
          <?php 
          foreach($transaction as $t)
          {
            ?>
            <tr>
            <td><?php echo $t['source'];?></td>
            <td><?php echo $t['amount'];?></td>
            <td><?php echo $t['fee'];?></td>
            <td><?php echo $t['date'];?></td>
            <td><?php echo $t['state'];?></td>
            <td><?php echo $t['state'];?></td>
            <td><a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=checkout&section=khalti' );?>&transaction_id=<?php echo $t['idx'];?>" class="btn btn-primary">View</a></td>
          <?php 
          }
          ?>
          </tbody>
          </table>
        </div> 
</div>

<div id="FAQ" class="tabcontent">
  <h3>FAQ</h3>
  <ul type="circle">
    <li><p><strong>I forgot my password. How do I reset it ?</strong><br>Please visit khalti.com/recover to reset your password</p>
    <li><p><strong>How do I change my password?</strong><br>You can change your password from your account setting page. Click on "Change Password"</p>
    <li><p><strong>Why do you need my Citizenship, Drivers License or Passport?</strong><br>As per the policy for financial transactions, we need to verify your identity.</p>
  </ul>
</div>
 <!--new tabs end-->
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

<script>
    jQuery(document).ready( function () {
    jQuery('#transaction_tbl').DataTable();
} );
</script>

<script>
document.getElementById("defaultTab").click();
function openTabs(evt, cityName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(cityName).style.display = "block";
    evt.currentTarget.className += " active";
}
</script>