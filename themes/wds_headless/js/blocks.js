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
 * @author WebDevStudios
 * @param {object} settings Block settings config.
 * @return {object}         Block settings config.
 */
function wdsAddColorPaletteHexValues(settings) {
	// Add background color hex attribute.
	if (settings.attributes.hasOwnProperty("backgroundColor")) {
		settings.attributes.backgroundColorHex = {
			type: "string",
			default: "",
		};
	}

	// Add main color hex attribute.
	if (settings.attributes.hasOwnProperty("mainColor")) {
		settings.attributes.mainColorHex = {
			type: "string",
			default: "",
		};
	}

	// Add text color hex attribute.
	if (settings.attributes.hasOwnProperty("textColor")) {
		settings.attributes.textColorHex = {
			type: "string",
			default: "",
		};
	}

	return {
		...settings,
		edit(props) {
			const {
				attributes: { backgroundColor, mainColor, textColor },
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
				}

				// Check for presence of main color attr.
				if (mainColor) {
					// Get color object by slug.
					const mainColorObj = defaultColors.filter(
						(color) => color.slug === mainColor
					);

					// Retrieve color hex value.
					props.attributes.mainColorHex =
						mainColorObj?.[0]?.color || null;
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
				}
			}, [backgroundColor, mainColor, textColor]);

			return settings.edit(props);
		},
	};
}
