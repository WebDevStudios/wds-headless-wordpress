<?php
/**
 * ACF settings.
 *
 * @author WebDevStudios
 * @package wds
 * @since 1.0
 */

if ( ! function_exists( 'wds_expand_graphql_acf_relational_field_value' ) ) {

	/**
	 * Expand data saved to relationship fields in ACF blocks to include more than just post ID.
	 *
	 * @author WebDevStudios
	 * @since 1.0
	 * @param  array $attrs Block attributes.
	 * @return array        Block attributes.
	 */
	function wds_expand_acf_block_relational_data( array $attrs ) {
		switch ( $attrs['name'] ?? null ) {
			case 'acf/netflix':
				$attrs['data']['cards_expanded'] = wds_get_relational_posts( is_array( $attrs['data']['cards'] ) ? $attrs['data']['cards'] : [] );
				break;

			case 'acf/header-featured-content':
				$attrs['data']['featured_content_expanded'] = wds_get_relational_posts( is_array( $attrs['data']['featured_content'] ) ? $attrs['data']['featured_content'] : [] );
		}

		return $attrs;
	}
}
add_filter( 'acf/pre_save_block', 'wds_expand_acf_block_relational_data' );

/**
 * Filter the ACF relationship query.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param array $args The query args. See WP_Query for available args.
 * @param array $field The field array containing all settings.
 * @param int   $post_id The current post ID being edited.
 * @return array $args
 */
function wds_relationship_query( $args, $field, $post_id ) {
	$args['post_status'] = 'publish';
	return $args;
}
add_filter( 'acf/fields/relationship/query', 'wds_relationship_query', 10, 3 );
add_filter( 'acf/fields/post_object/query', 'wds_relationship_query', 10, 3 );

/**
 * Create field options for the Post Type select dropdown in the Content Grid block.
 *
 * @author WebDevStudios
 * @since 1.0
 * @param array $field  the field values.
 * @return array $field the updated field values.
 */
function wds_acf_load_content_grid_choices( $field ) {

	// reset choices.
	$field['choices'] = [];

	$args       = [
		'public'   => true,
		'_builtin' => false,
	];
	$post_types = get_post_types( $args, 'objects' );

	foreach ( $post_types as $post_type ) {
		$field['choices'][ $post_type->graphql_single_name ] = $post_type->labels->singular_name;
	}

	natcasesort( $field['choices'] );

	// return the field.
	return $field;

}
add_filter( 'acf/load_field/name=content_grid_post_type', 'wds_acf_load_content_grid_choices' );

