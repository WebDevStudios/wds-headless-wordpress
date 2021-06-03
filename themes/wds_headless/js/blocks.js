const { validateThemeColors } = wp.blockEditor;
const { useEffect } = wp.element;
const { addFilter, applyFilters } = wp.hooks;

/**
 * Additional Gutenberg block functionality.
 *
 * @author WebDevStudios
 * @package wds-headless-theme
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

addFilter(
	"blocks.registerBlockType",
	"wds/filterBlockColorAttrs",
	wdsAddColorPaletteHexValues
);

/**
 * Filter block registration to add custom color attributes to specified blocks.
 *
 * TODO: Extend this to apply to other blocks that use the color palette.
 *
 * @param {object} settings Block settings config.
 * @param {string} name     Block name.
 * @return {object}         Block settings config.
 */
function wdsAddColorPaletteHexValues(settings, name) {
	/**
	 * Filter the array of blocks to receive hex color values.
	 *
	 * @author WebDevStudios
	 * @param {array} allowedBlocks Array of blocks.
	 */
	const allowedBlocks = applyFilters(
		"wds/colorPaletteHexValuesAllowedBlocks",
		["core/paragraph"]
	);

	if (!allowedBlocks.includes(name)) {
		return settings;
	}

	// Add hex color attributes.
	settings.attributes = {
		...settings.attributes,
		backgroundColorHex: {
			type: "string",
			default: "",
		},
		textColorHex: {
			type: "string",
			default: "",
		},
	};

	return {
		...settings,
		edit(props) {
			const {
				attributes: { backgroundColor, textColor },
			} = props;

			useEffect(() => {
				// Note: This may not work as expected if a custom theme palette has been set.
				// In that case, this filter may need to be customized.
				const defaultColors = validateThemeColors();

				// Check for presence of background color attr.
				if (backgroundColor) {
					// Get color object by slug.
					const backgroundColorObj = defaultColors.filter(
						(color) => color.slug === backgroundColor
					);

					// Retrieve color hex value.
					props.attributes.backgroundColorHex =
						backgroundColorObj?.[0]?.color || null;
				} else {
					props.attributes.backgroundColorHex = "";
				}

				// Check for presence of text color attr.
				if (textColor) {
					// Get color object by slug.
					const textColorObj = defaultColors.filter(
						(color) => color.slug === textColor
					);

					// Retrieve color hex value.
					props.attributes.textColorHex =
						textColorObj?.[0]?.color || null;
				} else {
					props.attributes.textColorHex = "";
				}
			}, [backgroundColor, textColor]);

			return settings.edit(props);
		},
	};
}
