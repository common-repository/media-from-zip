<?php
/**
 * Media from ZIP
 *
 * @package    Media from ZIP
 * @subpackage MediaFromZipAdmin Management screen
	Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$mediafromzipadmin = new MediaFromZipAdmin();

/** ==================================================
 * Management screen
 */
class MediaFromZipAdmin {

	/** ==================================================
	 * Path
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;

	/** ==================================================
	 * Path
	 *
	 * @var $upload_url  upload_url.
	 */
	private $upload_url;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$wp_uploads         = wp_upload_dir();
		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$basepath   = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url = $this->realurl( $basepath, $relationalpath );
			$upload_dir = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url = $wp_uploads['baseurl'];
			$upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}
		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}
		$this->upload_dir = untrailingslashit( $upload_dir );
		$this->upload_url = untrailingslashit( $upload_url );

		add_action( 'init', array( $this, 'register_settings' ) );

		add_action( 'admin_menu', array( $this, 'add_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_custom_wp_admin_style' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'generate_notice' ) );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'media-from-zip/mediafromzip.php';
		}
		if ( $file == $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromzip' ) . '">Media from ZIP</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromzip-upload-register' ) . '">' . __( 'Register by upload', 'media-from-zip' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromzip-server-register' ) . '">' . __( 'Register on server', 'media-from-zip' ) . '</a>';
			$links[] = '<a href="' . admin_url( 'admin.php?page=mediafromzip-settings' ) . '">' . __( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/** ==================================================
	 * Add page
	 *
	 * @since 1.0
	 */
	public function add_pages() {
		add_menu_page(
			'Media from ZIP',
			'Media from ZIP',
			'upload_files',
			'mediafromzip',
			array( $this, 'manage_page' ),
			'dashicons-upload'
		);
		add_submenu_page(
			'mediafromzip',
			__( 'Register by upload', 'media-from-zip' ),
			__( 'Register by upload', 'media-from-zip' ),
			'upload_files',
			'mediafromzip-upload-register',
			array( $this, 'register_upload_page' )
		);
		add_submenu_page(
			'mediafromzip',
			__( 'Register on server', 'media-from-zip' ),
			__( 'Register on server', 'media-from-zip' ),
			'upload_files',
			'mediafromzip-server-register',
			array( $this, 'register_server_page' )
		);
		add_submenu_page(
			'mediafromzip',
			__( 'Settings' ),
			__( 'Settings' ),
			'upload_files',
			'mediafromzip-settings',
			array( $this, 'settings_page' )
		);
	}

	/** ==================================================
	 * Add Css and Script
	 *
	 * @since 1.00
	 */
	public function load_custom_wp_admin_style() {
		if ( $this->is_my_plugin_screen() ) {
			wp_enqueue_style( 'mediafromzip', plugin_dir_url( __DIR__ ) . '/css/mediafromzip.css', array(), '1.00' );
			wp_enqueue_style( 'jquery-datetimepicker', plugin_dir_url( __DIR__ ) . '/css/jquery.datetimepicker.css', array(), '2.3.4' );
			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-datetimepicker', plugin_dir_url( __DIR__ ) . '/js/jquery.datetimepicker.js', null, '2.3.4' );
			wp_enqueue_script( 'mediafromzip-admin-js', plugin_dir_url( __DIR__ ) . 'js/jquery.mediafromzip.admin.js', array( 'jquery' ), array(), '1.00', false );
		}
	}

	/** ==================================================
	 * For only admin style
	 *
	 * @since 1.00
	 */
	private function is_my_plugin_screen() {
		$screen = get_current_screen();
		if ( is_object( $screen ) && 'media-from-zip_page_mediafromzip-server-register' === $screen->id ) {
			return true;
		} else if ( is_object( $screen ) && 'media-from-zip_page_mediafromzip-settings' === $screen->id ) {
			return true;
		} else {
			return false;
		}
	}

	/** ==================================================
	 * Register by upload
	 *
	 * @since 1.00
	 */
	public function register_upload_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$scriptname = admin_url( 'admin.php?page=mediafromzip-upload-register' );

		$max_upload_size = wp_max_upload_size();
		if ( ! $max_upload_size ) {
			$max_upload_size = 0;
		}
		if ( isset( $_SERVER['CONTENT_LENGTH'] ) && ! empty( $_SERVER['CONTENT_LENGTH'] ) ) {
			if ( 0 < $max_upload_size && $max_upload_size < intval( $_SERVER['CONTENT_LENGTH'] ) ) {
				echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'This is larger than the maximum size. Please try another.' ) . '</li></ul></div>';
			}
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$wp_filesystem = new WP_Filesystem_Direct( false );

		$import_html = null;
		if ( isset( $_POST['Import'] ) && ! empty( $_POST['Import'] ) ) {
			if ( check_admin_referer( 'mz_file_load', 'mediafromzip_import_file_load' ) ) {
				if ( isset( $_FILES['filename']['tmp_name'] ) && ! empty( $_FILES['filename']['tmp_name'] ) &&
						isset( $_FILES['filename']['name'] ) && ! empty( $_FILES['filename']['name'] ) &&
						isset( $_FILES['filename']['type'] ) && ! empty( $_FILES['filename']['type'] ) &&
						isset( $_FILES['filename']['error'] ) ) {
					if ( 0 === intval( wp_unslash( $_FILES['filename']['error'] ) ) ) {
						$tmp_file_path_name = wp_strip_all_tags( wp_unslash( wp_normalize_path( $_FILES['filename']['tmp_name'] ) ) );
						$tmp_file_name = sanitize_file_name( wp_basename( $tmp_file_path_name ) );
						$tmp_path_name = str_replace( $tmp_file_name, '', $tmp_file_path_name );
						$tmp_file_path_name = $tmp_path_name . $tmp_file_name;
						$filename = sanitize_file_name( wp_unslash( $_FILES['filename']['name'] ) );
						$mimetype = sanitize_text_field( wp_unslash( $_FILES['filename']['type'] ) );
						$filetype = wp_check_filetype( $filename );
						if ( ! $filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) ) {
							echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Sorry, this file type is not permitted for security reasons.' ) . '</li></ul></div>';
						} else {
							$filetype2 = wp_check_filetype( $filename, array( $filetype['ext'] => $mimetype ) );
							if ( ! empty( $filetype2['type'] ) ) {
								$post_zipfiles = array();
								$post_zipfiles[] = $tmp_file_path_name;
								update_user_option( get_current_user_id(), 'mediafromzip_submit_file', $post_zipfiles );
								$zip_file = $this->upload_dir . '/' . $filename;
								$move = $wp_filesystem->move( $tmp_file_path_name, $zip_file );
								if ( $move ) {
									$user = wp_get_current_user();
									do_action( 'mediafromzip_unzip', $zip_file, $this->upload_dir, $this->upload_url, $filename, $user->ID, $user->user_email );
								} else {
									echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Could not copy file.' ) . '</li></ul></div>';
								}
							} else {
								echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Sorry, this file type is not permitted for security reasons.' ) . '</li></ul></div>';
							}
						}
					} else {
						echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'No such file exists! Double check the name and try again.' ) . '</li></ul></div>';
					}
				}
			}
		}

		?>
		<div class="wrap">

		<h2>Media from ZIP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-upload-register' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Register by upload', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-server-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register on server', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( class_exists( 'ZipFromMedia' ) ) {
				$zipfrommedia_url = admin_url( 'admin.php?page=zipfrommedia' );
			} elseif ( is_multisite() ) {
					$zipfrommedia_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			} else {
				$zipfrommedia_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			}
			?>
			<a href="<?php echo esc_url( $zipfrommedia_url ); ?>" class="page-title-action">ZIP from Media</a>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<h3><?php esc_html_e( 'Register by upload', 'media-from-zip' ); ?></h3>
			<div style="margin: 5px; padding: 5px;">
				<p class="description">
				<?php esc_html_e( 'Upload the file directly. There are restrictions depending on the file size.', 'media-from-zip' ); ?>
				</p>
				<?php
				if ( 0 == $max_upload_size ) {
					$limit_str = __( 'No limit', 'media-from-zip' );
				} else {
					$limit_str = size_format( $max_upload_size, 0 );
				}
				?>
				<div>
				<?php
				/* translators: Maximum upload file size */
				echo esc_html( sprintf( __( 'Maximum upload file size: %s.' ), $limit_str ) );
				?>
				</div>
				<form method="post" action="<?php echo esc_url( $scriptname ); ?>" enctype="multipart/form-data">
				<?php wp_nonce_field( 'mz_file_load', 'mediafromzip_import_file_load' ); ?>
				<input name="filename" type="file" accept="application/zip" size="80" />
				<?php submit_button( __( 'Import' ), 'large', 'Import', false ); ?>
				</form>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Register on server
	 *
	 * @since 1.00
	 */
	public function register_server_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$scriptname = admin_url( 'admin.php?page=mediafromzip-server-register' );

		if ( isset( $_POST['Unzip'] ) && ! empty( $_POST['Unzip'] ) ) {
			if ( check_admin_referer( 'mz_file_unzip', 'mediafromzip_file_unzip' ) ) {
				if ( isset( $_POST['zipfiles'] ) && ! empty( $_POST['zipfiles'] ) ) {
					$post_zipfiles = array();
					if ( isset( $_POST['zipfiles'] ) && ! empty( $_POST['zipfiles'] ) ) {
						$tmps = filter_var(
							wp_unslash( $_POST['zipfiles'] ),
							FILTER_CALLBACK,
							array(
								'options' => function ( $value ) {
									return sanitize_text_field( $value );
								},
							)
						);
						foreach ( $tmps as $value ) {
							$post_zipfiles[] = $value;
						}
					}
					if ( ! empty( $post_zipfiles ) ) {
						update_user_option( get_current_user_id(), 'mediafromzip_submit_file', $post_zipfiles );
						$user = wp_get_current_user();
						foreach ( $post_zipfiles as $zip_name ) {
							$zip_file = $this->upload_dir . '/' . $zip_name;
							do_action( 'mediafromzip_unzip', $zip_file, $this->upload_dir, $this->upload_url, $zip_name, $user->ID, $user->user_email );
						}
					}
				}
			}
		}

		$zipfiles = array();
		$files = scandir( $this->upload_dir );
		foreach ( $files as $file ) {
			$filetype = wp_check_filetype( $file );
			if ( 'zip' == $filetype['ext'] ) {
				$zipfiles[] = $file;
			}
		}

		?>
		<div class="wrap">

		<h2>Media from ZIP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-server-register' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Register on server', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-upload-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register by upload', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( class_exists( 'ZipFromMedia' ) ) {
				$zipfrommedia_url = admin_url( 'admin.php?page=zipfrommedia' );
			} elseif ( is_multisite() ) {
					$zipfrommedia_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			} else {
				$zipfrommedia_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			}
			?>
			<a href="<?php echo esc_url( $zipfrommedia_url ); ?>" class="page-title-action">ZIP from Media</a>
		</h2>
		<div style="clear: both;"></div>

		<div class="wrap">
			<h3><?php esc_html_e( 'Unzip', 'media-from-zip' ); ?></h3>
			<div style="margin: 5px; padding: 5px;">
				<p class="description">
				<?php
				$upload_path = '<strong>' . str_replace( ABSPATH, '', $this->upload_dir ) . '</strong>';
				/* translators: Upload path */
				echo wp_kses_post( sprintf( __( 'Select a file that has been uploaded to the server path[ %1$s ] via FTP.', 'media-from-zip' ), $upload_path ) );
				?>
				</p>
				<?php
				if ( ! empty( $zipfiles ) ) {
					?>
					<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
					<?php
					wp_nonce_field( 'mz_file_unzip', 'mediafromzip_file_unzip' );
					?>
					<table class="table_ziplist">
					<colgroup>
						<col style="width: 20px;">
						<col style="width: 200px;">
					</colgroup>
					<thead>
					<tr>
					<th><input type="checkbox" id="allcheck"></th>
					<th><?php esc_html_e( 'File' ); ?></th>
					</tr>
					</thead>
					<tbody>
					<?php
					foreach ( $zipfiles as $value ) {
						?>
						<tr>
						<td>
						<input type="checkbox" class="zips" name="zipfiles[]" value="<?php echo esc_attr( $value ); ?>">
						</td>
						<td>
						<?php echo esc_html( $value ); ?>
						</td>
						</tr>
						<?php
					}
					?>
					</tbody>
					</table>
					<?php submit_button( __( 'Unzip', 'media-from-zip' ), 'large', 'Unzip', true ); ?>
					</form>
					<?php
				} else {
					?>
					<div><?php esc_html_e( 'File doesn&#8217;t exist?', 'media-from-zip' ); ?></div>
					<?php
				}
				?>
			</div>
		</div>

		<?php
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function settings_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		$this->options_updated();

		$mediafromzip_settings = get_user_option( 'mediafromzip', get_current_user_id() );

		$def_max_execution_time = ini_get( 'max_execution_time' );
		$scriptname = admin_url( 'admin.php?page=mediafromzip-settings' );

		?>
		<div class="wrap">

		<h2>Media from ZIP <a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-settings' ) ); ?>" style="text-decoration: none;"><?php esc_html_e( 'Settings' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-server-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register on server', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-upload-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register by upload', 'media-from-zip' ); ?></a>
			<?php
			if ( class_exists( 'ZipFromMedia' ) ) {
				$zipfrommedia_url = admin_url( 'admin.php?page=zipfrommedia' );
			} elseif ( is_multisite() ) {
					$zipfrommedia_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			} else {
				$zipfrommedia_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			}
			?>
			<a href="<?php echo esc_url( $zipfrommedia_url ); ?>" class="page-title-action">ZIP from Media</a>
		</h2>
		<div style="clear: both;"></div>

			<div class="wrap">
				<form method="post" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'mfg_settings', 'media_from_zip_settings' ); ?>
					<h3><?php esc_html_e( 'Date' ); ?></h3>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="mediafromzip_dateset" value="new" 
					<?php
					if ( 'new' === $mediafromzip_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of the current date/time.', 'media-from-zip' ); ?>
					</div>
					<div style="display: block;padding:5px 5px">
					<input type="radio" name="mediafromzip_dateset" value="server" 
					<?php
					if ( 'server' === $mediafromzip_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Get the date/time of the file, and updated based on it. Change it if necessary.', 'media-from-zip' ); ?>
					</div>
					<div style="display: block; padding:5px 5px">
					<input type="radio" name="mediafromzip_dateset" value="exif" 
					<?php
					if ( 'exif' === $mediafromzip_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php
					esc_html_e( 'Get the date/time of the file, and updated based on it. Change it if necessary.', 'media-from-zip' );
					esc_html_e( 'Get by priority if there is date and time of the Exif information.', 'media-from-zip' );
					?>
					</div>
					<div style="display: block; padding:5px 5px">
					<input type="radio" name="mediafromzip_dateset" value="fixed" 
					<?php
					if ( 'fixed' === $mediafromzip_settings['dateset'] ) {
						echo 'checked';
					}
					?>
					>
					<?php esc_html_e( 'Update to use of fixed the date/time.', 'media-from-zip' ); ?>
					</div>
					<div style="display: block; padding:5px 40px">
					<input type="text" id="datetimepicker-mediafromzip" name="mediafromzip_datefixed" value="<?php echo esc_attr( $mediafromzip_settings['datefixed'] ); ?>">
					</div>
					<h3><?php esc_html_e( 'Execution time', 'media-from-zip' ); ?></h3>
					<div style="display:block; padding:5px 5px">
						<?php
						$max_execution_time = $mediafromzip_settings['max_execution_time'];
						if ( ! @set_time_limit( $max_execution_time ) ) {
							$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'media-from-zip' ) . '</font>';
							?>
							<p class="description">
							<?php
							/* translators: %1$s: limit max execution time */
							echo wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out. No email is sent.', 'media-from-zip' ), $limit_seconds_html ) );
							?>
							</p>
							<input type="hidden" name="mediafromzip_max_execution_time" value="<?php echo esc_attr( $def_max_execution_time ); ?>" />
							<?php
						} else {
							$max_execution_time_text = __( 'The number of seconds a script is allowed to run.', 'media-from-zip' ) . '(' . __( 'The max_execution_time value defined in the php.ini.', 'media-from-zip' ) . '[<font color="red">' . $def_max_execution_time . '</font>])';
							?>
							<p class="description">
							<?php esc_html_e( 'This is to suppress timeouts when there are too many images. If you do not receive the processing completion notification email, increase the number of seconds.', 'media-from-zip' ); ?>
							</p>
							<p class="description">
							<?php echo wp_kses_post( $max_execution_time_text ); ?>:<input type="number" step="1" min="1" max="9999" style="width: 80px;" name="mediafromzip_max_execution_time" value="<?php echo esc_attr( $max_execution_time ); ?>" />
							</p>
							<?php
						}
						?>
					</div>
				<?php submit_button( __( 'Save Changes' ), 'large', 'media-from-zip-settings-options-apply', true ); ?>
				</form>
			</div>

		</div>
		<?php
	}

	/** ==================================================
	 * Main
	 *
	 * @since 1.00
	 */
	public function manage_page() {

		if ( ! current_user_can( 'upload_files' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		?>

		<div class="wrap">

		<h2>Media from ZIP
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-upload-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register by upload', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-server-register' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Register on server', 'media-from-zip' ); ?></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mediafromzip-settings' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Settings' ); ?></a>
			<?php
			if ( class_exists( 'ZipFromMedia' ) ) {
				$zipfrommedia_url = admin_url( 'admin.php?page=zipfrommedia' );
			} elseif ( is_multisite() ) {
					$zipfrommedia_url = network_admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			} else {
				$zipfrommedia_url = admin_url( 'plugin-install.php?tab=plugin-information&plugin=zip-from-media' );
			}
			?>
			<a href="<?php echo esc_url( $zipfrommedia_url ); ?>" class="page-title-action">ZIP from Media</a>
		</h2>
		<div style="clear: both;"></div>

		<h3><?php esc_html_e( 'Extract from ZIP archive to Media Library.', 'media-from-zip' ); ?></h3>

		<?php $this->credit(); ?>

		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( esc_html__( 'https://wordpress.org/plugins/%s/faq', 'media-from-zip' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = sprintf( esc_html__( 'https://shop.riverforest-wp.info/donate/', 'media-from-zip' ), $slug );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php sprintf( esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'media-from-zip' ) ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		$mediafromzip_settings = get_user_option( 'mediafromzip', get_current_user_id() );

		if ( isset( $_POST['media-from-zip-settings-options-apply'] ) && ! empty( $_POST['media-from-zip-settings-options-apply'] ) ) {
			if ( check_admin_referer( 'mfg_settings', 'media_from_zip_settings' ) ) {
				if ( ! empty( $_POST['mediafromzip_dateset'] ) ) {
					$mediafromzip_settings['dateset'] = sanitize_text_field( wp_unslash( $_POST['mediafromzip_dateset'] ) );
				}
				if ( ! empty( $_POST['mediafromzip_datefixed'] ) ) {
					$mediafromzip_settings['datefixed'] = sanitize_text_field( wp_unslash( $_POST['mediafromzip_datefixed'] ) );
				}
				if ( ! empty( $_POST['mediafromzip_max_execution_time'] ) ) {
					$mediafromzip_settings['max_execution_time'] = intval( $_POST['mediafromzip_max_execution_time'] );
				}
				update_user_option( get_current_user_id(), 'mediafromzip', $mediafromzip_settings );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( __( 'Settings' ) . ' --> ' . __( 'Changes saved.' ) ) . '</li></ul></div>';
			}
		}
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		/* Ver 1.07 later */
		if ( get_option( 'mediafromzip' ) ) {
			delete_option( 'mediafromzip' );
		}

		if ( ! get_user_option( 'mediafromzip', get_current_user_id() ) ) {
			if ( function_exists( 'wp_date' ) ) {
				$datefixed = wp_date( 'Y-m-d H:i:s' );
			} else {
				$datefixed = date_i18n( 'Y-m-d H:i:s' );
			}
			$mediafromzip_tbl = array(
				'dateset' => 'new',
				'datefixed' => $datefixed,
				'max_execution_time' => 600,
			);
			update_user_option( get_current_user_id(), 'mediafromzip', $mediafromzip_tbl );
		} else {
			$mediafromzip_settings = get_user_option( 'mediafromzip', get_current_user_id() );
			/* Ver 1.05 later */
			if ( ! array_key_exists( 'max_execution_time', $mediafromzip_settings ) ) {
				$mediafromzip_settings['max_execution_time'] = 600;
				update_user_option( get_current_user_id(), 'mediafromzip', $mediafromzip_settings );
			}
		}
	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  1.00
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}
	}

	/** ==================================================
	 * Generate notice
	 *
	 * @since 1.03
	 */
	public function generate_notice() {

		if ( get_user_option( 'mediafromzip_submit_file', get_current_user_id() ) ) {
			$post_zipfiles = get_user_option( 'mediafromzip_submit_file', get_current_user_id() );
			if ( ! empty( $post_zipfiles ) ) {
				foreach ( $post_zipfiles as $zip_name ) {
					if ( get_user_option( 'mediafromzip_generate_mail_' . $zip_name, get_current_user_id() ) ) {
						$mediafromzip_mail_send = get_user_option( 'mediafromzip_generate_mail_' . $zip_name, get_current_user_id() );
						if ( 0 < $mediafromzip_mail_send['count'] ) {
							?>
							<div class="notice notice-success is-dismissible"><ul><li><strong>Media from ZIP</strong>
							<?php
							/* translators: %1$s Date Time, %2$s Zip Name %3$d File Count */
							echo wp_kses_post( sprintf( __( ' : %1$s : %2$s : %3$d files have been added to the Media Library. Details have been sent by e-mail.', 'media-from-zip' ), $mediafromzip_mail_send['datetime'], $zip_name, $mediafromzip_mail_send['count'] ) );
							?>
							</li></ul></div>
							<?php
						}
						if ( 0 < $mediafromzip_mail_send['deny_count'] ) {
							?>
							<div class="notice notice-error is-dismissible"><ul><li><strong>Media from ZIP</strong>
							<?php
							/* translators: %1$s Date Time %2$s Zip Name %3$d Deny Count */
							echo wp_kses_post( sprintf( __( ' : %1$s : %2$s : %3$d files could not be added to the Media Library. Details have been sent by e-mail.', 'media-from-zip' ), $mediafromzip_mail_send['datetime'], $zip_name, $mediafromzip_mail_send['deny_count'] ) );
							?>
							</li></ul></div>
							<?php
						}
						wp_clear_scheduled_hook( 'mediafromzip_check_images_regenerate', array( get_current_user_id(), $zip_name ) );
						delete_user_option( get_current_user_id(), 'mediafromzip_generate_mail_' . $zip_name );
					}
					if ( get_user_option( 'mediafromzip_stop_' . $zip_name, get_current_user_id() ) ) {
						?>
						<div class="notice notice-error is-dismissible"><ul><li><strong>Media from ZIP</strong>
						<?php
						/* translators: %1$s Zip Name */
						echo wp_kses_post( sprintf( __( ' : %1$s : Processing may have been interrupted. Please increase "Execution time" and register again.', 'media-from-zip' ), $zip_name ) );
						?>
						</div>
						<?php
						delete_user_option( get_current_user_id(), 'mediafromzip_stop_' . $zip_name );
					}
				}
			}
		}
	}
}


