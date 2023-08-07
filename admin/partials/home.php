<?php
   $active_channel = isset($_GET['channel']) ? sanitize_text_field($_GET['channel']) : 'home';

?>
<div class='wrap woocommerce'><nav class="nav-tab-wrapper woo-nav-tab-wrapper">
	<?php $url = admin_url('admin.php?page=sales_channel'); ?>
	<a href="<?php echo esc_url($url); ?>" class="nav-tab <?php echo ( 'home' == $active_channel ? 'nav-tab-active' : '' ); ?>">Home</a>
		 <?php        
			/** 
			*
			* Filter to get array of active marketplaces
			 *
			* @since  1.0.0
			*/   
			$activeMarketplaces = apply_filters( 'ced_add_marketplace_menus_array', array() );
			foreach (  $activeMarketplaces as $navigation ) {
				if ($navigation['is_installed']) {
					
					echo '<a href="' . esc_url(ced_get_navigation_url($navigation['menu_link'])) . '" class="nav-tab ' . ( $navigation['menu_link'] == $active_channel ? 'nav-tab-active' : '' ) . '">' . esc_html__($navigation['tab'], 'ced-amazon') . '</a>';
				}
			}
			?>

		 
</nav>
