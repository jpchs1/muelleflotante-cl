<?php
/**
 * Plugin Name: Mi plugin personalizado
 * Description:  Plugin en donde iré añadiendo mi código personalizado.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
	
function add_tag_manager() {
?>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-3P80R06BS9"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-3P80R06BS9');
</script>

<?php
}
add_action( 'wp_head', 'add_tag_manager' );