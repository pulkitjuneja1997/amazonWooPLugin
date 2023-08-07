var bootstrapCss = 'bootstrapCss';

if ( ! document.getElementById( bootstrapCss )) {
	var plugin_url         = ced_amazon_plugin_properties.plugin_url;
	var bootstrapWrapper   = document.createElement( 'link' );
	bootstrapWrapper.id    = bootstrapCss;
	bootstrapWrapper.rel   = 'stylesheet/less';
	bootstrapWrapper.type  = 'text/css';
	bootstrapWrapper.href  = plugin_url + '/amazon-integration-for-woocommerce/admin/css/ced-amazon-bootstrap-wrapper.less';
	bootstrapWrapper.media = 'all';
	document.head.appendChild( bootstrapWrapper );
	console.log( bootstrapWrapper );
	var lessjs  = document.createElement( 'script' );
	lessjs.type = 'text/javascript';
	lessjs.src  = plugin_url + '/amazon-integration-for-woocommerce/admin/js/less.min.js';
	document.head.appendChild( lessjs );

	
}
