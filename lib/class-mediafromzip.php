<?php
/**
 * Media from ZIP
 *
 * @package    Media from ZIP
 * @subpackage MediaFromZip Main function
/*  Copyright (c) 2020- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
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

$mediafromzip = new MediaFromZip();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class MediaFromZip {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		add_action( 'mediafromzip_unzip', array( $this, 'unzip' ), 10, 6 );
		add_action( 'mediafromzip_register_hook', array( $this, 'regist' ), 10, 7 );
		add_action( 'mediafromzip_check_images_regenerate', array( $this, 'check_regenerate' ), 10, 2 );
	}

	/** ==================================================
	 * Unzip
	 *
	 * @param string $zip_path  zip_path.
	 * @param string $upload_dir  upload_dir.
	 * @param string $upload_url  upload_url.
	 * @param string $name  name.
	 * @param int    $uid  uid.
	 * @param string $to  mail address.
	 * @return array or bool $files  files or false.
	 * @since 1.00
	 */
	public function unzip( $zip_path, $upload_dir, $upload_url, $name, $uid, $to ) {

		$upload_dir = untrailingslashit( $upload_dir );

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return false;
		}

		/* Check encoding */
		$count = 0;
		while ( $zip->statIndex( $count ) ) {
			$zipentry = $zip->statIndex( $count );
			if ( 'ASCII' <> mb_detect_encoding( $zipentry['name'] ) ) {
				$zip->close();
				if ( file_exists( $zip_path ) ) {
					wp_delete_file( $zip_path );
				}
				/* translators: Multi-byte characters file */
				echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html( sprintf( esc_html__( 'Multi-byte characters cannot be used in file and directory names.[%1$s] Please change.', 'media-from-zip' ), $zipentry['name'] ) ) . '</li></ul></div>';
				$back_url = admin_url( 'admin.php?page=mediafromzip' );
				?>
				<button type="button" style="margin: 5px; padding: 5px;" onclick="location.href='<?php echo esc_url( $back_url ); ?>'"><?php esc_html_e( 'Back' ); ?></button>
				<?php
				wp_die();
			}
			$count++;
		}

		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
		$wp_filesystem = new WP_Filesystem_Direct( false );

		/* Store in array */
		$files = array();
		$deny_files = array();
		$count = 0;
		while ( $zip->statIndex( $count ) ) {
			$zipentry = $zip->statIndex( $count );

			$filetype = wp_check_filetype( $zipentry['name'] );
			if ( ! $filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) && strstr( $zipentry['name'], '.' ) ) {
				$deny_files[] = $zipentry['name'];
			} else if ( strstr( $zipentry['name'], '__MACOSX' ) ||
						 'Thumbs.db' === $zipentry['name'] ||
						 '.DS_Store' === $zipentry['name'] ) {
				$deny_files[] = $zipentry['name'];
			} else {
				$title = wp_basename( $zipentry['name'], '.' . $filetype['ext'] );
				$org_file = $upload_dir . '/' . $zipentry['name'];
				$zip->extractTo( $upload_dir, $zipentry['name'] );
				if ( file_exists( $org_file ) ) {
					if ( is_file( $org_file ) ) {
						$org_file_name = wp_basename( $zipentry['name'] );
						$folder_name = wp_normalize_path( substr( $zipentry['name'], 0, strlen( $zipentry['name'] ) - strlen( $org_file_name ) ) );
						$file_name = sanitize_file_name( $org_file_name );
						$file = $upload_dir . '/' . $folder_name . $file_name;
						$wp_filesystem->move( $org_file, $file );
						$files[ $title ] = $file;
						$wp_filesystem->chmod( $file, 0644 );
					} else if ( is_dir( $org_file ) ) {
						$wp_filesystem->chmod( $org_file, 0755 );
					}
				}
			}

			$count++;
		}

		$zip->close();

		wp_delete_file( $zip_path );

		if ( ! wp_next_scheduled( 'mediafromzip_register_hook', array( $files, $uid, $to, $name, $upload_dir, $upload_url, $deny_files ) ) ) {
			wp_schedule_single_event( time(), 'mediafromzip_register_hook', array( $files, $uid, $to, $name, $upload_dir, $upload_url, $deny_files ) );
			/* translators: Registration Start Message */
			echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html( sprintf( __( 'Registration of media unzipped from %1$s in the background has started. You will be notified by email at the end.', 'media-from-zip' ), $name ) ) . '</li></ul></div>';
		}
	}

	/** ==================================================
	 * Check Images Regenerate
	 *
	 * @param int    $uid  uid.
	 * @param string $name  name.
	 * @since 1.08
	 */
	public function check_regenerate( $uid, $name ) {

		if ( ! get_user_option( 'mediafromzip_generate_mail_' . $name, $uid ) ) {
			update_user_option( $uid, 'mediafromzip_stop_' . $name, true );
		}
	}

	/** ==================================================
	 * Regist
	 *
	 * @param array  $files  files.
	 * @param int    $uid  uid.
	 * @param string $to  mail address.
	 * @param string $name  name.
	 * @param string $upload_dir  upload_dir.
	 * @param string $upload_url  upload_url.
	 * @param array  $deny_files  deny_files.
	 * @since 1.00
	 */
	public function regist( $files, $uid, $to, $name, $upload_dir, $upload_url, $deny_files ) {

		$mediafromzip_settings = get_user_option( 'mediafromzip', $uid );

		$max_exe_time = $mediafromzip_settings['max_execution_time'];
		$def_max_execution_time = ini_get( 'max_execution_time' );
		$limit_seconds_html = '<font color="red">' . $def_max_execution_time . __( 'seconds', 'media-from-zip' ) . '</font>';
		if ( ! @set_time_limit( $max_exe_time ) ) {
			/* translators: %1$s: limit max execution time */
			echo '<div class="notice notice-info is-dismissible"><ul><li>' . wp_kses_post( sprintf( __( 'Execution time for this server is fixed at %1$s. If this limit is exceeded, times out. No email is sent.', 'media-from-zip' ), $limit_seconds_html ) ) . '</li></ul></div>';
			$max_exe_time = $def_max_execution_time;
		}

		wp_schedule_single_event( time() + $max_exe_time + 30, 'mediafromzip_check_images_regenerate', array( $uid, $name ) );

		if ( function_exists( 'wp_date' ) ) {
			$now_date_time = wp_date( 'Y-m-d H:i:s' );
		} else {
			$now_date_time = date_i18n( 'Y-m-d H:i:s' );
		}

		/* translators: Date and Time */
		$message = sprintf( __( 'Media from ZIP : %s', 'media-from-zip' ), $now_date_time ) . "\r\n\r\n";

		$count = 0;
		if ( ! empty( $files ) ) {
			/* translators: zipname for message */
			$message .= sprintf( __( '[%s] is unziped and media file generation is completed.', 'media-from-zip' ), $name ) . "\r\n\r\n";
			foreach ( $files as $title => $file ) {
				$filetype = wp_check_filetype( $file );
				$ext = $filetype['ext'];
				$mime_type = $filetype['type'];
				$file_type = wp_ext2type( $ext );
				$filename = wp_basename( $file );
				$new_url_attach = $upload_url . str_replace( $upload_dir, '', $file );

				if ( ! is_null( $mime_type ) ) {
					/* File Regist */
					$newfile_post = array(
						'post_title' => $title,
						'post_content' => '',
						'post_author' => $uid,
						'guid' => $new_url_attach,
						'post_status' => 'inherit',
						'post_type' => 'attachment',
						'post_mime_type' => $mime_type,
					);
					$attach_id = wp_insert_attachment( $newfile_post, $file );

					/* for XAMPP [ get_attached_file( $attach_id ): Unable to get correct value ] */
					$metapath_name = str_replace( $upload_dir . '/', '', $file );
					update_post_meta( $attach_id, '_wp_attached_file', $metapath_name );

					/* Date Time Regist */
					if ( function_exists( 'wp_date' ) ) {
						$postdategmt = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
					} else {
						$postdategmt = date_i18n( 'Y-m-d H:i:s', false, true );
					}
					if ( 'server' === $mediafromzip_settings['dateset'] || 'exif' === $mediafromzip_settings['dateset'] ) {
						$datetime = $this->get_date_check( $file, $mediafromzip_settings['dateset'] );
						$postdategmt = get_gmt_from_date( $datetime );
					}
					if ( 'new' <> $mediafromzip_settings['dateset'] ) {
						if ( 'fixed' === $mediafromzip_settings['dateset'] ) {
							$postdategmt = get_gmt_from_date( $mediafromzip_settings['datefixed'] );
						}
						$postdate = get_date_from_gmt( $postdategmt );
						$up_post = array(
							'ID' => $attach_id,
							'post_date' => $postdate,
							'post_date_gmt' => $postdategmt,
							'post_modified' => $postdate,
							'post_modified_gmt' => $postdategmt,
						);
						wp_update_post( $up_post );
					}

					/* for wp_read_audio_metadata and wp_read_video_metadata */
					include_once ABSPATH . 'wp-admin/includes/media.php';
					/* for wp_generate_attachment_metadata */
					include_once ABSPATH . 'wp-admin/includes/image.php';

					/* Meta data Regist */
					$metadata = wp_generate_attachment_metadata( $attach_id, $file );
					/* for 'big_image_size_threshold' */
					if ( ! empty( $metadata ) && array_key_exists( 'original_image', $metadata ) && ! empty( $metadata['original_image'] ) ) {
						$metapath_scaled_file_name = str_replace( $upload_dir . '/', '', $metadata['file'] );
						update_post_meta( $attach_id, '_wp_attached_file', $metapath_scaled_file_name );
						$metadata['file'] = $metapath_scaled_file_name;
					}
					wp_update_attachment_metadata( $attach_id, $metadata );

					/* Thumbnail urls */
					list( $image_thumbnail, $imagethumburls ) = $this->thumbnail_urls( $attach_id, $metadata, $upload_url );
					/* Output datas*/
					list( $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length ) = $this->output_datas( $attach_id, $metadata, $file_type, $file );

					$count++;
					$message .= __( 'Count' ) . ': ' . $count . "\n";
					$message .= 'ID: ' . $attach_id . "\n";
					$message .= __( 'Title' ) . ': ' . $title . "\n";
					$message .= __( 'Permalink:' ) . ' ' . $attachment_link . "\n";
					$message .= 'URL: ' . $attachment_url . "\n";
					$message .= __( 'File name:' ) . ' ' . $filename . "\n";
					if ( ! empty( $original_image_url ) ) {
						$message .= __( 'Original URL:', 'media-from-zip' ) . ' ' . $original_image_url . "\n";
						$message .= __( 'Original File name:', 'media-from-zip' ) . ' ' . $original_filename . "\n";
					}
					$message .= __( 'Date/Time' ) . ': ' . $stamptime . "\n";
					$message .= __( 'File type:' ) . ' ' . $mime_type . "\n";
					$message .= __( 'File size:' ) . ' ' . $file_size . "\n";
					if ( ( 'image' === $file_type || 'pdf' === strtolower( $ext ) ) && ! empty( $imagethumburls ) ) {
						foreach ( $imagethumburls as $thumbsize => $imagethumburl ) {
							$message .= $thumbsize . ': ' . $imagethumburl . "\n";
						}
					} elseif ( 'video' === $file_type || 'audio' === $file_type ) {
							$message .= __( 'Length:' ) . ' ' . $length . "\n";
					}
					$message .= "\n";
				}
			}
		}

		$deny_count = 0;
		if ( ! empty( $deny_files ) ) {
			$message .= __( 'Sorry, this file type is not permitted for security reasons.' ) . "\r\n\r\n";
			foreach ( $deny_files as $deny_filename ) {
				$deny_count++;
				$message .= __( 'File name:' ) . ' ' . wp_basename( $deny_filename ) . "\n";
			}
		}

		$mediafromzip_mail_send = array();
		$mediafromzip_mail_send['datetime'] = $now_date_time;
		$mediafromzip_mail_send['count'] = $count;
		$mediafromzip_mail_send['deny_count'] = $deny_count;
		update_user_option( $uid, 'mediafromzip_generate_mail_' . $name, $mediafromzip_mail_send );

		/* translators: blogname for subject */
		$subject = sprintf( __( '[%1$s] Generated [%2$s] media file', 'media-from-zip' ), get_option( 'blogname' ), $name );
		$message .= "\r\n\r\n";

		wp_mail( $to, $subject, $message );
	}

	/** ==================================================
	 * Date check for exif
	 *
	 * @param string $file  file.
	 * @param string $dateset  dateset.
	 * @return string $date
	 * @since 1.00
	 */
	private function get_date_check( $file, $dateset ) {

		$date = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', filemtime( $file ) ) );

		if ( 'exif' === $dateset ) {
			$exifdata = @exif_read_data( $file, 'FILE', true );
			if ( isset( $exifdata['EXIF']['DateTimeOriginal'] ) && ! empty( $exifdata['EXIF']['DateTimeOriginal'] ) ) {
				$shooting_date_time = $exifdata['EXIF']['DateTimeOriginal'];
				$shooting_date = str_replace( ':', '-', substr( $shooting_date_time, 0, 10 ) );
				$shooting_time = substr( $shooting_date_time, 10 );
				$date = $shooting_date . $shooting_time;
			}
		}

		return $date;
	}

	/** ==================================================
	 * Thumbnail urls
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param string $upload_url  upload_url.
	 * @return array $image_thumbnail(string), $imagethumburls(array)
	 * @since 1.12
	 */
	private function thumbnail_urls( $attach_id, $metadata, $upload_url ) {

		$image_attr_thumbnail = wp_get_attachment_image_src( $attach_id, 'thumbnail', true );
		$image_thumbnail = $image_attr_thumbnail[0];

		$imagethumburls = array();
		if ( ! empty( $metadata ) && array_key_exists( 'sizes', $metadata ) ) {
			$thumbnails  = $metadata['sizes'];
			$path_file  = get_post_meta( $attach_id, '_wp_attached_file', true );
			$filename   = wp_basename( $path_file );
			$media_path = str_replace( $filename, '', $path_file );
			$media_url  = $upload_url . '/' . $media_path;
			foreach ( $thumbnails as $key => $key2 ) {
				$imagethumburls[ $key ] = $media_url . $key2['file'];
			}
		}

		return array( $image_thumbnail, $imagethumburls );
	}

	/** ==================================================
	 * Output datas
	 *
	 * @param int    $attach_id  attach_id.
	 * @param array  $metadata  metadata.
	 * @param string $file_type  file_type.
	 * @param string $file  fullpath_filename.
	 * @return array (string) $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length
	 * @since 1.12
	 */
	private function output_datas( $attach_id, $metadata, $file_type, $file ) {

		$attachment_link = get_attachment_link( $attach_id );

		$attachment_url = wp_get_attachment_url( $attach_id );

		if ( ! empty( $metadata ) && array_key_exists( 'original_image', $metadata ) && ! empty( $metadata['original_image'] ) ) {
			$original_image_url = wp_get_original_image_url( $attach_id );
			$original_filename = wp_basename( $original_image_url );
		} else {
			$original_image_url = null;
			$original_filename = null;
		}

		$stamptime = get_the_time( 'Y-n-j ', $attach_id ) . get_the_time( 'G:i:s', $attach_id );

		if ( ! empty( $metadata ) && array_key_exists( 'filesize', $metadata ) && ! empty( $metadata['filesize'] ) ) {
			$file_size = $metadata['filesize'];
		} else {
			$file_size = @filesize( $file );
		}
		if ( ! $file_size ) {
			$file_size = __( 'Could not retrieve.', 'media-from-zip' );
		} else {
			$file_size = size_format( $file_size );
		}

		$length = null;
		if ( 'video' === $file_type || 'audio' === $file_type ) {
			if ( ! empty( $metadata ) && array_key_exists( 'length_formatted', $metadata ) && ! empty( $metadata['length_formatted'] ) ) {
				$length = $metadata['length_formatted'];
			} else {
				$length = __( 'Could not retrieve.', 'media-from-zip' );
			}
		}

		return array( $attachment_link, $attachment_url, $original_image_url, $original_filename, $stamptime, $file_size, $length );
	}
}


