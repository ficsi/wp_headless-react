<?php
namespace WPGraphQL\Type\Connection;

use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\AppContext;
use WPGraphQL\Data\Connection\UserConnectionResolver;
use WPGraphQL\Data\DataSource;
use WPGraphQL\Model\Post;
use WPGraphQL\Utils\Utils;

/**
 * Class Users
 *
 * This class organizes the registration of connections to Users
 *
 * @package WPGraphQL\Type\Connection
 */
class Users {

	/**
	 * Register connections to Users
	 *
	 * @return void
	 */
	public static function register_connections() {

		/**
		 * Connection from RootQuery to Users
		 */
		register_graphql_connection(
			[
				'fromType'       => 'RootQuery',
				'toType'         => 'User',
				'fromFieldName'  => 'users',
				'resolve'        => static function ( $source, $args, $context, $info ) {
					return DataSource::resolve_users_connection( $source, $args, $context, $info );
				},
				'connectionArgs' => self::get_connection_args(),
			]
		);

		register_graphql_connection(
			[
				'fromType'           => 'ContentNode',
				'toType'             => 'User',
				'connectionTypeName' => 'ContentNodeToEditLockConnection',
				'edgeFields'         => [
					'lockTimestamp' => [
						'type'        => 'String',
						'description' => static function () {
							return __( 'The timestamp for when the node was last edited', 'wp-graphql' );
						},
						'resolve'     => static function ( $edge ) {
							if ( isset( $edge['source'] ) && ( $edge['source'] instanceof Post ) ) {
								$edit_lock = $edge['source']->editLock;
								$time      = ( is_array( $edit_lock ) && ! empty( $edit_lock[0] ) ) ? $edit_lock[0] : null;
								return ! empty( $time ) ? Utils::prepare_date_response( $time, gmdate( 'Y-m-d H:i:s', (int) $time ) ) : null;
							}
							return null;
						},
					],
				],
				'fromFieldName'      => 'editingLockedBy',
				'description'        => static function () {
					return __( 'If a user has edited the node within the past 15 seconds, this will return the user that last edited. Null if the edit lock doesn\'t exist or is greater than 15 seconds', 'wp-graphql' );
				},
				'oneToOne'           => true,
				'resolve'            => static function ( Post $source, $args, $context, $info ) {
					if ( ! isset( $source->editLock[1] ) || ! absint( $source->editLock[1] ) ) {
						return null;
					}

					$resolver = new UserConnectionResolver( $source, $args, $context, $info );
					$resolver->one_to_one()->set_query_arg( 'include', [ absint( $source->editLock[1] ) ] );

					return $resolver->get_connection();
				},
			]
		);

		register_graphql_connection(
			[
				'fromType'           => 'ContentNode',
				'toType'             => 'User',
				'fromFieldName'      => 'lastEditedBy',
				'connectionTypeName' => 'ContentNodeToEditLastConnection',
				'description'        => static function () {
					return __( 'The user that most recently edited the node', 'wp-graphql' );
				},
				'oneToOne'           => true,
				'resolve'            => static function ( Post $source, $args, $context, $info ) {
					if ( empty( $source->editLastId ) ) {
						return null;
					}

					$resolver = new UserConnectionResolver( $source, $args, $context, $info );
					$resolver->set_query_arg( 'include', [ absint( $source->editLastId ) ] );
					return $resolver->one_to_one()->get_connection();
				},
			]
		);

		register_graphql_connection(
			[
				'fromType'      => 'NodeWithAuthor',
				'toType'        => 'User',
				'fromFieldName' => 'author',
				'oneToOne'      => true,
				'resolve'       => static function ( Post $post, $args, AppContext $context, ResolveInfo $info ) {
					if ( empty( $post->authorDatabaseId ) ) {
						return null;
					}

					$resolver = new UserConnectionResolver( $post, $args, $context, $info );
					$resolver->set_query_arg( 'include', [ absint( $post->authorDatabaseId ) ] );
					return $resolver->one_to_one()->get_connection();
				},
			]
		);
	}

	/**
	 * Returns the connection args for use in the connection
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_connection_args() {
		return [
			'role'              => [
				'type'        => 'UserRoleEnum',
				'description' => static function () {
					return __( 'An array of role names that users must match to be included in results. Note that this is an inclusive list: users must match *each* role.', 'wp-graphql' );
				},
			],
			'roleIn'            => [
				'type'        => [
					'list_of' => 'UserRoleEnum',
				],
				'description' => static function () {
					return __( 'An array of role names. Matched users must have at least one of these roles.', 'wp-graphql' );
				},
			],
			'roleNotIn'         => [
				'type'        => [
					'list_of' => 'UserRoleEnum',
				],
				'description' => static function () {
					return __( 'An array of role names to exclude. Users matching one or more of these roles will not be included in results.', 'wp-graphql' );
				},
			],
			'include'           => [
				'type'        => [
					'list_of' => 'Int',
				],
				'description' => static function () {
					return __( 'Array of userIds to include.', 'wp-graphql' );
				},
			],
			'exclude'           => [ // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'type'        => [
					'list_of' => 'Int',
				],
				'description' => static function () {
					return __( 'Array of userIds to exclude.', 'wp-graphql' );
				},
			],
			'search'            => [
				'type'        => 'String',
				'description' => static function () {
					return __( 'Search keyword. Searches for possible string matches on columns. When "searchColumns" is left empty, it tries to determine which column to search in based on search string.', 'wp-graphql' );
				},
			],
			'searchColumns'     => [
				'type'        => [
					'list_of' => 'UsersConnectionSearchColumnEnum',
				],
				'description' => static function () {
					return __( 'Array of column names to be searched. Accepts \'ID\', \'login\', \'nicename\', \'email\', \'url\'.', 'wp-graphql' );
				},
			],
			'hasPublishedPosts' => [
				'type'        => [
					'list_of' => 'ContentTypeEnum',
				],
				'description' => static function () {
					return __( 'Pass an array of post types to filter results to users who have published posts in those post types.', 'wp-graphql' );
				},
			],
			'nicename'          => [
				'type'        => 'String',
				'description' => static function () {
					return __( 'The user nicename.', 'wp-graphql' );
				},
			],
			'nicenameIn'        => [
				'type'        => [
					'list_of' => 'String',
				],
				'description' => static function () {
					return __( 'An array of nicenames to include. Users matching one of these nicenames will be included in results.', 'wp-graphql' );
				},
			],
			'nicenameNotIn'     => [
				'type'        => [
					'list_of' => 'String',
				],
				'description' => static function () {
					return __( 'An array of nicenames to exclude. Users matching one of these nicenames will not be included in results.', 'wp-graphql' );
				},
			],
			'login'             => [
				'type'        => 'String',
				'description' => static function () {
					return __( 'The user login.', 'wp-graphql' );
				},
			],
			'loginIn'           => [
				'type'        => [
					'list_of' => 'String',
				],
				'description' => static function () {
					return __( 'An array of logins to include. Users matching one of these logins will be included in results.', 'wp-graphql' );
				},
			],
			'loginNotIn'        => [
				'type'        => [
					'list_of' => 'String',
				],
				'description' => static function () {
					return __( 'An array of logins to exclude. Users matching one of these logins will not be included in results.', 'wp-graphql' );
				},
			],
			'orderby'           => [
				'type'        => [
					'list_of' => 'UsersConnectionOrderbyInput',
				],
				'description' => static function () {
					return __( 'What parameter to use to order the objects by.', 'wp-graphql' );
				},
			],
		];
	}
}
