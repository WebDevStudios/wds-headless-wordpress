/**
 * Additional Gutenberg block functionality.
 *
 * @author WebDevStudios
 * @since 1.0
 */
wp.domReady(() => {
	wp.blocks.unregisterBlockStyle("core/image", "default");
	wp.blocks.unregisterBlockStyle("core/separator", "dots");
	wp.blocks.unregisterBlockStyle("core/separator", "wide");
	wp.blocks.registerBlockStyle("core/separator", {
		name: "full-width",
		label: "Full Width",
	});
});
