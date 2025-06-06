<?php

namespace WPGraphQL\Data;

use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQLRelay\Relay;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\CommentConnectionResolver;
use WPGraphQL\Data\Connection\PluginConnectionResolver;
use WPGraphQL\Data\Connection\PostObjectConnectionResolver;
use WPGraphQL\Data\Connection\TermObjectConnectionResolver;
use WPGraphQL\Data\Connection\ThemeConnectionResolver;
use WPGraphQL\Data\Connection\UserConnectionResolver;
use WPGraphQL\Data\Connection\UserRoleConnectionResolver;
use WPGraphQL\Model\Avatar;
use WPGraphQL\Model\Comment;
use WPGraphQL\Model\CommentAuthor;
use WPGraphQL\Model\Menu;
use WPGraphQL\Model\Plugin;
use WPGraphQL\Model\Post;
use WPGraphQL\Model\PostType;
use WPGraphQL\Model\Taxonomy;
use WPGraphQL\Model\Term;
use WPGraphQL\Model\Theme;
use WPGraphQL\Model\User;
use WPGraphQL\Model\UserRole;
use WPGraphQL\Registry\TypeRegistry;

/**
 * Class DataSource
 *
 * This class serves as a factory for all the resolvers for queries and mutations. This layer of
 * abstraction over the actual resolve functions allows easier, granular control over versioning as
 * we can change big things behind the scenes if/when needed, and we just need to ensure the
 * call to the DataSource method returns the expected data later on. This should make it easy
 * down the road to version resolvers if/when changes to the WordPress API are rolled out.
 *
 * @package WPGraphQL\Data
 * @since   0.0.4
 */
class DataSource {

	/**
	 * Stores an array of node definitions
	 *
	 * @var mixed[] $node_definition
	 * @since  0.0.4
	 */
	protected static $node_definition;

	/**
	 * Retrieves a WP_Comment object for the id that gets passed
	 *
	 * @param int                   $id      ID of the comment we want to get the object for.
	 * @param \WPGraphQL\AppContext $context The context of the request.
	 *
	 * @return \GraphQL\Deferred object
	 * @throws \GraphQL\Error\UserError Throws UserError.
	 * @throws \Exception Throws UserError.
	 *
	 * @since 0.0.5
	 *
	 * @deprecated Use the Loader passed in $context instead
	 */
	public static function resolve_comment( $id, $context ) {
		_deprecated_function( __METHOD__, '0.8.4', 'Use $context->get_loader( \'comment\' )->load_deferred( $id ) instead.' );

		return $context->get_loader( 'comment' )->load_deferred( $id );
	}

	/**
	 * Retrieves a WP_Comment object for the ID that gets passed
	 *
	 * @param int $comment_id The ID of the comment the comment author is associated with.
	 *
	 * @return \WPGraphQL\Model\CommentAuthor|null
	 * @throws \Exception Throws Exception.
	 */
	public static function resolve_comment_author( int $comment_id ) {
		$comment_author = get_comment( $comment_id );

		return ! empty( $comment_author ) ? new CommentAuthor( $comment_author ) : null;
	}

	/**
	 * Wrapper for the CommentsConnectionResolver class
	 *
	 * @param mixed                                $source  The object the connection is coming from
	 * @param array<string,mixed>                  $args    Query args to pass to the connection resolver
	 * @param \WPGraphQL\AppContext                $context The context of the query to pass along
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since 0.0.5
	 */
	public static function resolve_comments_connection( $source, array $args, AppContext $context, ResolveInfo $info ) {
		$resolver = new CommentConnectionResolver( $source, $args, $context, $info );

		return $resolver->get_connection();
	}

	/**
	 * Wrapper for PluginsConnectionResolver::resolve
	 *
	 * @param mixed                                $source  The object the connection is coming from
	 * @param array<string,mixed>                  $args    Array of arguments to pass to resolve method
	 * @param \WPGraphQL\AppContext                $context AppContext object passed down
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since  0.0.5
	 */
	public static function resolve_plugins_connection( $source, array $args, AppContext $context, ResolveInfo $info ) {
		$resolver = new PluginConnectionResolver( $source, $args, $context, $info );
		return $resolver->get_connection();
	}

	/**
	 * Returns the post object for the ID and post type passed
	 *
	 * @param int                   $id      ID of the post you are trying to retrieve
	 * @param \WPGraphQL\AppContext $context The context of the GraphQL Request
	 *
	 * @return \GraphQL\Deferred
	 *
	 * @throws \GraphQL\Error\UserError
	 * @throws \Exception
	 *
	 * @since      0.0.5
	 * @deprecated Use the Loader passed in $context instead
	 */
	public static function resolve_post_object( int $id, AppContext $context ) {
		_deprecated_function( __METHOD__, '0.8.4', 'Use $context->get_loader( \'post\' )->load_deferred( $id ) instead.' );
		return $context->get_loader( 'post' )->load_deferred( $id );
	}

	/**
	 * @param int                   $id      The ID of the menu item to load
	 * @param \WPGraphQL\AppContext $context The context of the GraphQL request
	 *
	 * @return \GraphQL\Deferred|null
	 * @throws \Exception
	 *
	 * @deprecated Use the Loader passed in $context instead
	 */
	public static function resolve_menu_item( int $id, AppContext $context ) {
		_deprecated_function( __METHOD__, '0.8.4', 'Use $context->get_loader( \'post\' )->load_deferred( $id ) instead.' );
		return $context->get_loader( 'post' )->load_deferred( $id );
	}

	/**
	 * Wrapper for PostObjectsConnectionResolver
	 *
	 * @param mixed                                $source    The object the connection is coming from
	 * @param array<string,mixed>                  $args      Arguments to pass to the resolve method
	 * @param \WPGraphQL\AppContext                $context AppContext object to pass down
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 * @param mixed|string|string[]                $post_type Post type of the post we are trying to resolve
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since  0.0.5
	 */
	public static function resolve_post_objects_connection( $source, array $args, AppContext $context, ResolveInfo $info, $post_type ) {
		$resolver = new PostObjectConnectionResolver( $source, $args, $context, $info, $post_type );

		return $resolver->get_connection();
	}

	/**
	 * Retrieves the taxonomy object for the name of the taxonomy passed
	 *
	 * @param string $taxonomy Name of the taxonomy you want to retrieve the taxonomy object for
	 *
	 * @return \WPGraphQL\Model\Taxonomy object
	 * @throws \GraphQL\Error\UserError If no taxonomy is found with the name passed.
	 * @since  0.0.5
	 */
	public static function resolve_taxonomy( $taxonomy ) {

		/**
		 * Get the allowed_taxonomies.
		 */
		$allowed_taxonomies = \WPGraphQL::get_allowed_taxonomies();

		if ( ! in_array( $taxonomy, $allowed_taxonomies, true ) ) {
			// translators: %s is the name of the taxonomy.
			throw new UserError( esc_html( sprintf( __( 'No taxonomy was found with the name %s', 'wp-graphql' ), $taxonomy ) ) );
		}

		$tax_object = get_taxonomy( $taxonomy );

		if ( ! $tax_object instanceof \WP_Taxonomy ) {
			// translators: %s is the name of the taxonomy.
			throw new UserError( esc_html( sprintf( __( 'No taxonomy was found with the name %s', 'wp-graphql' ), $taxonomy ) ) );
		}

		return new Taxonomy( $tax_object );
	}

	/**
	 * Get the term object for a term
	 *
	 * @param int                   $id      ID of the term you are trying to retrieve the object for
	 * @param \WPGraphQL\AppContext $context The context of the GraphQL Request
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since      0.0.5
	 *
	 * @deprecated Use the Loader passed in $context instead
	 */
	public static function resolve_term_object( $id, AppContext $context ) {
		_deprecated_function( __METHOD__, '0.8.4', 'Use $context->get_loader( \'term\' )->load_deferred( $id ) instead.' );
		return $context->get_loader( 'term' )->load_deferred( $id );
	}

	/**
	 * Wrapper for TermObjectConnectionResolver::resolve
	 *
	 * @param mixed                                $source   The object the connection is coming from
	 * @param array<string,mixed>                  $args     Array of args to be passed to the resolve method
	 * @param \WPGraphQL\AppContext                $context The AppContext object to be passed down
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 * @param string                               $taxonomy The name of the taxonomy the term belongs to
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since  0.0.5
	 */
	public static function resolve_term_objects_connection( $source, array $args, AppContext $context, ResolveInfo $info, string $taxonomy ) {
		$resolver = new TermObjectConnectionResolver( $source, $args, $context, $info, $taxonomy );

		return $resolver->get_connection();
	}

	/**
	 * Retrieves the theme object for the theme you are looking for
	 *
	 * @param string $stylesheet Directory name for the theme.
	 *
	 * @return \WPGraphQL\Model\Theme object
	 * @throws \GraphQL\Error\UserError
	 * @since  0.0.5
	 */
	public static function resolve_theme( $stylesheet ) {
		$theme = wp_get_theme( $stylesheet );
		if ( $theme->exists() ) {
			return new Theme( $theme );
		} else {
			// translators: %s is the name of the theme stylesheet.
			throw new UserError( esc_html( sprintf( __( 'No theme was found with the stylesheet: %s', 'wp-graphql' ), $stylesheet ) ) );
		}
	}

	/**
	 * Wrapper for the ThemesConnectionResolver::resolve method
	 *
	 * @param mixed                                $source  The object the connection is coming from
	 * @param array<string,mixed>                  $args    Passes an array of arguments to the resolve method
	 * @param \WPGraphQL\AppContext                $context The AppContext object to be passed down
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since  0.0.5
	 */
	public static function resolve_themes_connection( $source, array $args, AppContext $context, ResolveInfo $info ) {
		$resolver = new ThemeConnectionResolver( $source, $args, $context, $info );
		return $resolver->get_connection();
	}

	/**
	 * Gets the user object for the user ID specified
	 *
	 * @param int                   $id      ID of the user you want the object for
	 * @param \WPGraphQL\AppContext $context The AppContext
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 *
	 * @since      0.0.5
	 * @deprecated Use the Loader passed in $context instead
	 */
	public static function resolve_user( $id, AppContext $context ) {
		_deprecated_function( __METHOD__, '0.8.4', 'Use $context->get_loader( \'user\' )->load_deferred( $id ) instead.' );
		return $context->get_loader( 'user' )->load_deferred( $id );
	}

	/**
	 * Wrapper for the UsersConnectionResolver::resolve method
	 *
	 * @param mixed                                $source  The object the connection is coming from
	 * @param array<string,mixed>                  $args    Array of args to be passed down to the resolve method
	 * @param \WPGraphQL\AppContext                $context The AppContext object to be passed down
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 * @since  0.0.5
	 */
	public static function resolve_users_connection( $source, array $args, AppContext $context, ResolveInfo $info ) {
		$resolver = new UserConnectionResolver( $source, $args, $context, $info );

		return $resolver->get_connection();
	}

	/**
	 * Returns an array of data about the user role you are requesting
	 *
	 * @param string $name Name of the user role you want info for
	 *
	 * @return \WPGraphQL\Model\UserRole
	 * @throws \GraphQL\Error\UserError If no user role is found with the name passed.
	 * @since  0.0.30
	 */
	public static function resolve_user_role( $name ) {
		$role = isset( wp_roles()->roles[ $name ] ) ? wp_roles()->roles[ $name ] : null;

		if ( null === $role ) {
			// translators: %s is the name of the user role.
			throw new UserError( esc_html( sprintf( __( 'No user role was found with the name %s', 'wp-graphql' ), $name ) ) );
		} else {
			$role                = (array) $role;
			$role['id']          = $name;
			$role['displayName'] = $role['name'];
			$role['name']        = $name;

			return new UserRole( $role );
		}
	}

	/**
	 * Resolve the avatar for a user
	 *
	 * @param int                 $user_id ID of the user to get the avatar data for
	 * @param array<string,mixed> $args    The args to pass to the get_avatar_data function
	 *
	 * @return \WPGraphQL\Model\Avatar|null
	 * @throws \Exception
	 */
	public static function resolve_avatar( int $user_id, array $args ) {
		$avatar = get_avatar_data( absint( $user_id ), $args );

		// if there's no url returned, return null
		if ( empty( $avatar['url'] ) ) {
			return null;
		}

		$avatar = new Avatar( $avatar );

		if ( 'private' === $avatar->get_visibility() ) {
			return null;
		}

		return $avatar;
	}

	/**
	 * Resolve the connection data for user roles
	 *
	 * @param mixed[]                              $source  The Query results
	 * @param array<string,mixed>                  $args    The query arguments
	 * @param \WPGraphQL\AppContext                $context The AppContext passed down to the query
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo object
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 */
	public static function resolve_user_role_connection( $source, array $args, AppContext $context, ResolveInfo $info ) {
		$resolver = new UserRoleConnectionResolver( $source, $args, $context, $info );

		return $resolver->get_connection();
	}

	/**
	 * Format the setting group name to our standard.
	 *
	 * @param string $group
	 *
	 * @return string $group
	 */
	public static function format_group_name( string $group ) {
		$replaced_group = graphql_format_name( $group, ' ', '/[^a-zA-Z0-9 -]/' );

		if ( ! empty( $replaced_group ) ) {
			$group = $replaced_group;
		}

		$group = lcfirst( str_replace( '_', ' ', ucwords( $group, '_' ) ) );
		$group = lcfirst( str_replace( '-', ' ', ucwords( $group, '_' ) ) );
		$group = lcfirst( str_replace( ' ', '', ucwords( $group, ' ' ) ) );

		return $group;
	}

	/**
	 * Get all of the allowed settings by group and return the
	 * settings group that matches the group param
	 *
	 * @param string                           $group
	 * @param \WPGraphQL\Registry\TypeRegistry $type_registry The WPGraphQL TypeRegistry
	 *
	 * @return array<string,mixed>
	 */
	public static function get_setting_group_fields( string $group, TypeRegistry $type_registry ) {

		/**
		 * Get all of the settings, sorted by group
		 */
		$settings_groups = self::get_allowed_settings_by_group( $type_registry );

		return ! empty( $settings_groups[ $group ] ) ? $settings_groups[ $group ] : [];
	}

	/**
	 * Get all of the allowed settings by group
	 *
	 * @param \WPGraphQL\Registry\TypeRegistry $type_registry The WPGraphQL TypeRegistry
	 *
	 * @return array<string,array<string,mixed>> $allowed_settings_by_group
	 */
	public static function get_allowed_settings_by_group( TypeRegistry $type_registry ) {

		/**
		 * Get all registered settings
		 */
		$registered_settings = get_registered_settings();

		/**
		 * Loop through the $registered_settings array and build an array of
		 * settings for each group ( general, reading, discussion, writing, reading, etc. )
		 * if the setting is allowed in REST or GraphQL
		 */
		$allowed_settings_by_group = [];
		foreach ( $registered_settings as $key => $setting ) {
			// Bail if the setting doesn't have a group.
			if ( empty( $setting['group'] ) ) {
				continue;
			}

			$group = self::format_group_name( $setting['group'] );

			if ( ! isset( $setting['type'] ) || ! $type_registry->get_type( $setting['type'] ) ) {
				continue;
			}

			if ( ! isset( $setting['show_in_graphql'] ) ) {
				if ( isset( $setting['show_in_rest'] ) && false !== $setting['show_in_rest'] ) {
					$setting['key']                              = $key;
					$allowed_settings_by_group[ $group ][ $key ] = $setting;
				}
			} elseif ( true === $setting['show_in_graphql'] ) {
				$setting['key']                              = $key;
				$allowed_settings_by_group[ $group ][ $key ] = $setting;
			}
		}

		/**
		 * Set the setting groups that are allowed
		 */
		$allowed_settings_by_group = ! empty( $allowed_settings_by_group ) ? $allowed_settings_by_group : [];

		/**
		 * Filter the $allowed_settings_by_group to allow enabling or disabling groups in the GraphQL Schema.
		 *
		 * @param array<string,array<string,mixed>> $allowed_settings_by_group
		 */
		return apply_filters( 'graphql_allowed_settings_by_group', $allowed_settings_by_group );
	}

	/**
	 * Get all of the $allowed_settings
	 *
	 * @param \WPGraphQL\Registry\TypeRegistry $type_registry The WPGraphQL TypeRegistry
	 *
	 * @return array<string,array<string,mixed>> $allowed_settings
	 */
	public static function get_allowed_settings( TypeRegistry $type_registry ) {

		/**
		 * Get all registered settings
		 */
		$registered_settings = get_registered_settings();

		/**
		 * Set allowed settings variable.
		 */
		$allowed_settings = [];

		if ( ! empty( $registered_settings ) ) {

			/**
			 * Loop through the $registered_settings and if the setting is allowed in REST or GraphQL
			 * add it to the $allowed_settings array
			 */
			foreach ( $registered_settings as $key => $setting ) {
				if ( ! isset( $setting['type'] ) || ! $type_registry->get_type( $setting['type'] ) ) {
					continue;
				}

				if ( ! isset( $setting['show_in_graphql'] ) ) {
					if ( isset( $setting['show_in_rest'] ) && false !== $setting['show_in_rest'] ) {
						$setting['key']           = $key;
						$allowed_settings[ $key ] = $setting;
					}
				} elseif ( true === $setting['show_in_graphql'] ) {
					$setting['key']           = $key;
					$allowed_settings[ $key ] = $setting;
				}
			}
		}

		/**
		 * Verify that we have the allowed settings
		 */
		$allowed_settings = ! empty( $allowed_settings ) ? $allowed_settings : [];

		/**
		 * Filter the $allowed_settings to allow some to be enabled or disabled from showing in
		 * the GraphQL Schema.
		 *
		 * @param array<string,array<string,mixed>> $allowed_settings
		 */
		return apply_filters( 'graphql_allowed_setting_groups', $allowed_settings );
	}

	/**
	 * We get the node interface and field from the relay library.
	 *
	 * The first method is the way we resolve an ID to its object. The second is the way we resolve
	 * an object that implements node to its type.
	 *
	 * @return mixed[]
	 * @throws \GraphQL\Error\UserError
	 */
	public static function get_node_definition() {
		if ( null === self::$node_definition ) {
			$node_definition = Relay::nodeDefinitions(
			// The ID fetcher definition
				static function ( $global_id, AppContext $context, ResolveInfo $info ) {
					self::resolve_node( $global_id, $context, $info );
				},
				// Type resolver
				static function ( $node ) {
					self::resolve_node_type( $node );
				}
			);

			self::$node_definition = $node_definition;
		}

		return self::$node_definition;
	}

	/**
	 * Given a node, returns the GraphQL Type
	 *
	 * @param mixed $node The node to resolve the type of
	 *
	 * @return string
	 * @throws \GraphQL\Error\UserError If no type is found for the node.
	 */
	public static function resolve_node_type( $node ) {
		$type = null;

		if ( true === is_object( $node ) ) {
			switch ( true ) {
				case $node instanceof Post:
					if ( $node->isRevision ) {
						/** @var ?\WP_Post */
						$parent_post = get_post( $node->parentDatabaseId );

						if ( ! empty( $parent_post ) ) {
							/** @var \WP_Post_Type $post_type_object */
							$post_type_object = get_post_type_object( $parent_post->post_type );
							$type             = $post_type_object->graphql_single_name ?? null;

							break;
						}
					}

					/** @var \WP_Post_Type $post_type_object */
					$post_type_object = isset( $node->post_type ) ? get_post_type_object( $node->post_type ) : null;
					$type             = $post_type_object->graphql_single_name ?? null;
					break;
				case $node instanceof Term:
					/** @var \WP_Taxonomy $tax_object */
					$tax_object = isset( $node->taxonomyName ) ? get_taxonomy( $node->taxonomyName ) : null;
					$type       = $tax_object->graphql_single_name;
					break;
				case $node instanceof Comment:
					$type = 'Comment';
					break;
				case $node instanceof PostType:
					$type = 'ContentType';
					break;
				case $node instanceof Taxonomy:
					$type = 'Taxonomy';
					break;
				case $node instanceof Theme:
					$type = 'Theme';
					break;
				case $node instanceof User:
					$type = 'User';
					break;
				case $node instanceof Plugin:
					$type = 'Plugin';
					break;
				case $node instanceof CommentAuthor:
					$type = 'CommentAuthor';
					break;
				case $node instanceof Menu:
					$type = 'Menu';
					break;
				case $node instanceof \_WP_Dependency:
					$type = isset( $node->type ) ? $node->type : null;
					break;
				default:
					$type = null;
			}
		}

		/**
		 * Add a filter to allow externally registered node types to return the proper type
		 * based on the node_object that's returned
		 *
		 * @param mixed|object|array $type The type definition the node should resolve to.
		 * @param mixed|object|array $node The $node that is being resolved
		 *
		 * @since 0.0.6
		 */
		$type = apply_filters( 'graphql_resolve_node_type', $type, $node );

		/**
		 * If the $type is not properly resolved, throw an exception
		 *
		 * @since 0.0.6
		 */
		if ( empty( $type ) ) {
			throw new UserError( esc_html__( 'No type was found matching the node', 'wp-graphql' ) );
		}

		/**
		 * Return the resolved $type for the $node
		 *
		 * @since 0.0.5
		 */
		return ucfirst( $type );
	}

	/**
	 * Given the ID of a node, this resolves the data
	 *
	 * @param string                               $global_id The Global ID of the node
	 * @param \WPGraphQL\AppContext                $context The Context of the GraphQL Request
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo for the GraphQL Request
	 *
	 * @return string|null
	 * @throws \GraphQL\Error\UserError If no ID is passed.
	 */
	public static function resolve_node( $global_id, AppContext $context, ResolveInfo $info ) {
		if ( empty( $global_id ) ) {
			throw new UserError( esc_html__( 'An ID needs to be provided to resolve a node.', 'wp-graphql' ) );
		}

		/**
		 * Convert the encoded ID into an array we can work with
		 *
		 * @since 0.0.4
		 */
		$id_components = Relay::fromGlobalId( $global_id );

		/**
		 * If the $id_components is a proper array with a type and id
		 *
		 * @since 0.0.5
		 */
		if ( is_array( $id_components ) && ! empty( $id_components['id'] ) && ! empty( $id_components['type'] ) ) {

			/**
			 * Get the allowed_post_types and allowed_taxonomies
			 *
			 * @since 0.0.5
			 */

			$loader = $context->get_loader( $id_components['type'] );

			if ( $loader ) {
				return $loader->load_deferred( $id_components['id'] );
			}

			return null;
		} else {
			// translators: %s is the global ID.
			throw new UserError( esc_html( sprintf( __( 'The global ID isn\'t recognized ID: %s', 'wp-graphql' ), $global_id ) ) );
		}
	}

	/**
	 * Returns array of nav menu location names
	 *
	 * @return string[]
	 */
	public static function get_registered_nav_menu_locations() {
		global $_wp_registered_nav_menus;

		return ! empty( $_wp_registered_nav_menus ) && is_array( $_wp_registered_nav_menus ) ? array_keys( $_wp_registered_nav_menus ) : [];
	}

	/**
	 * This resolves a resource, given a URI (the path / permalink to a resource)
	 *
	 * Based largely on the core parse_request function in wp-includes/class-wp.php
	 *
	 * @param string                               $uri     The URI to fetch a resource from
	 * @param \WPGraphQL\AppContext                $context The AppContext passed through the GraphQL Resolve Tree
	 * @param \GraphQL\Type\Definition\ResolveInfo $info The ResolveInfo passed through the GraphQL Resolve tree
	 *
	 * @return \GraphQL\Deferred
	 * @throws \Exception
	 */
	public static function resolve_resource_by_uri( $uri, $context, $info ) {
		$node_resolver = new NodeResolver( $context );

		return $node_resolver->resolve_uri( $uri );
	}
}
