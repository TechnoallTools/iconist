<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Iconist_Converter {

    public function __construct() {
        add_filter( 'wp_handle_upload',                    [ $this, 'handle_upload' ], 10, 2 );
        add_action( 'wp_ajax_iconist_get_svg_count',       [ $this, 'ajax_get_svg_count' ] );
        add_action( 'wp_ajax_iconist_bulk_convert_single', [ $this, 'ajax_bulk_convert_single' ] );
        add_action( 'wp_ajax_iconist_add_exception',       [ $this, 'ajax_add_exception' ] );
        add_action( 'wp_ajax_iconist_remove_exception',    [ $this, 'ajax_remove_exception' ] );
        add_action( 'wp_ajax_iconist_restore_backup',      [ $this, 'ajax_restore_backup' ] );
        add_filter( 'upload_mimes',                        [ $this, 'allow_svg_mime' ] );
        add_filter( 'wp_check_filetype_and_ext',           [ $this, 'fix_svg_filetype' ], 10, 4 );
    }

    public function allow_svg_mime( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';
        $mimes['svgz'] = 'image/svg+xml';
        return $mimes;
    }

    public function fix_svg_filetype( $data, $file, $filename, $mimes ) {
        if ( ! $data['type'] ) {
            $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
            if ( $ext === 'svg' || $ext === 'svgz' ) {
                $data['type'] = 'image/svg+xml';
                $data['ext']  = $ext;
            }
        }
        return $data;
    }

    public function handle_upload( $upload, $context = 'upload' ) {
        if ( ! get_option( 'iconist_auto_convert', 1 ) ) {
            return $upload;
        }

        if ( isset( $_COOKIE['iconist_exception_upload'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_COOKIE['iconist_exception_upload'] ) ), 'iconist_exception_upload' ) ) {
            return $upload;
        }

        if ( isset( $upload['type'] ) && $upload['type'] === 'image/svg+xml' ) {
            $attachment_id = attachment_url_to_postid( $upload['url'] );
            if ( $attachment_id && in_array( $attachment_id, self::get_exceptions(), true ) ) {
                return $upload;
            }

            $result = $this->convert_file( $upload['file'] );
            if ( is_wp_error( $result ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'Iconist: ' . $result->get_error_message() );
            }
        }

        return $upload;
    }

    public function convert_file( $filepath ) {
        if ( ! file_exists( $filepath ) ) {
            return new WP_Error( 'not_found', __( 'فایل پیدا نشد.', 'iconist' ) );
        }

        if ( ! is_writable( $filepath ) ) {
            return new WP_Error( 'not_writable', __( 'فایل قابل نوشتن نیست.', 'iconist' ) );
        }

        $content = @file_get_contents( $filepath );
        if ( $content === false ) {
            return new WP_Error( 'read_error', __( 'خواندن فایل ممکن نیست.', 'iconist' ) );
        }

        $sanitized = Iconist_Sanitizer::sanitize( $content );
        if ( $sanitized === false ) {
            return new WP_Error( 'invalid_svg', __( 'فایل SVG معتبر نیست.', 'iconist' ) );
        }

        $converted = $this->process_svg_content( $sanitized );

        if ( $converted === $content ) {
            return [ 'status' => 'skipped', 'message' => __( 'تغییری نیاز نبود.', 'iconist' ) ];
        }

        if ( get_option( 'iconist_backup_enabled', 1 ) ) {
            $ext         = pathinfo( $filepath, PATHINFO_EXTENSION );
            $backup_path = $ext
                ? substr( $filepath, 0, -( strlen( $ext ) + 1 ) ) . '-iconist-backup.' . $ext
                : $filepath . '-iconist-backup';
            @copy( $filepath, $backup_path );
        }

        $written = @file_put_contents( $filepath, $converted );
        if ( $written === false ) {
            return new WP_Error( 'write_error', __( 'نوشتن فایل ممکن نیست.', 'iconist' ) );
        }

        return [ 'status' => 'success', 'message' => __( 'با موفقیت تبدیل شد.', 'iconist' ) ];
    }

    public function process_svg_content( $content ) {
        $dom = new DOMDocument();
        libxml_use_internal_errors( true );
        $loaded = $dom->loadXML( $content, LIBXML_NOWARNING | LIBXML_NOERROR );
        libxml_clear_errors();

        if ( ! $loaded ) {
            return $this->process_svg_regex( $content );
        }

        $xpath    = new DOMXPath( $dom );
        $elements = $xpath->query( '//*' );
        $changed  = false;

        foreach ( $elements as $el ) {
            if ( $el->hasAttribute( 'fill' ) ) {
                $fill = trim( $el->getAttribute( 'fill' ) );
                if ( $fill !== 'none' && $fill !== 'currentColor' && strpos( $fill, 'url(' ) !== 0 ) {
                    $el->setAttribute( 'fill', 'currentColor' );
                    $changed = true;
                }
            }

            if ( $el->hasAttribute( 'stroke' ) ) {
                $stroke = trim( $el->getAttribute( 'stroke' ) );
                if ( $stroke !== 'none' && $stroke !== 'currentColor' && strpos( $stroke, 'url(' ) !== 0 ) {
                    $el->setAttribute( 'stroke', 'currentColor' );
                    $changed = true;
                }
            }

            if ( $el->hasAttribute( 'style' ) ) {
                $style     = $el->getAttribute( 'style' );
                $new_style = $this->process_inline_style( $style );
                if ( $new_style !== $style ) {
                    $el->setAttribute( 'style', $new_style );
                    $changed = true;
                }
            }
        }

        $style_nodes = $xpath->query( '//style' );
        foreach ( $style_nodes as $style_node ) {
            $css     = $style_node->nodeValue;
            $new_css = $this->process_css_block( $css );
            if ( $new_css !== $css ) {
                $style_node->nodeValue = $new_css;
                $changed = true;
            }
        }

        if ( ! $changed ) {
            return $content;
        }

        $result = $dom->saveXML( $dom->documentElement );
        if ( strpos( $content, '<?xml' ) !== false && strpos( $result, '<?xml' ) === false ) {
            $result = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $result;
        }

        return $result;
    }

    private function process_svg_regex( $content ) {
        $content = preg_replace_callback(
            '/\bfill=["\']([^"\']*)["\']/',
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'fill="currentColor"';
            },
            $content
        );

        $content = preg_replace_callback(
            '/\bstroke=["\']([^"\']*)["\']/',
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'stroke="currentColor"';
            },
            $content
        );

        return $content;
    }

    private function process_inline_style( $style ) {
        $fill_pattern   = '/\bfill\s*:\s*([^;\x7D"\']+)/';
        $stroke_pattern = '/\bstroke\s*:\s*([^;\x7D"\']+)/';

        $style = preg_replace_callback(
            $fill_pattern,
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'fill: currentColor';
            },
            $style
        );

        $style = preg_replace_callback(
            $stroke_pattern,
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'stroke: currentColor';
            },
            $style
        );

        return $style;
    }

    private function process_css_block( $css ) {
        $fill_pattern   = '/\bfill\s*:\s*([^;\x7D"\']+)/';
        $stroke_pattern = '/\bstroke\s*:\s*([^;\x7D"\']+)/';

        $css = preg_replace_callback(
            $fill_pattern,
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'fill: currentColor';
            },
            $css
        );

        $css = preg_replace_callback(
            $stroke_pattern,
            function ( $m ) {
                $val = trim( $m[1] );
                if ( $val === 'none' || $val === 'currentColor' || strpos( $val, 'url(' ) === 0 ) {
                    return $m[0];
                }
                return 'stroke: currentColor';
            },
            $css
        );

        return $css;
    }

    public static function get_server_support_status() {
        return [
            'DOM XML' => [
                'status'  => class_exists( 'DOMDocument' ),
                'message' => class_exists( 'DOMDocument' )
                    ? __( 'فعال — تبدیل دقیق DOM', 'iconist' )
                    : __( 'غیرفعال — از روش Regex استفاده می‌شود', 'iconist' ),
            ],
            'libxml' => [
                'status'  => function_exists( 'libxml_use_internal_errors' ),
                'message' => function_exists( 'libxml_use_internal_errors' )
                    ? __( 'فعال', 'iconist' )
                    : __( 'غیرفعال', 'iconist' ),
            ],
            'SVG Upload' => [
                'status'  => true,
                'message' => __( 'فعال — آپلود SVG مجاز است', 'iconist' ),
            ],
        ];
    }

    public static function get_exceptions() {
        return (array) get_option( 'iconist_exceptions', [] );
    }

    public function ajax_add_exception() {
        check_ajax_referer( 'iconist_bulk_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'دسترسی غیرمجاز', 'iconist' ) );
        }
        $id = absint( $_POST['attachment_id'] );
        if ( ! $id ) {
            wp_send_json_error( __( 'شناسه نامعتبر', 'iconist' ) );
        }
        $list = self::get_exceptions();
        if ( ! in_array( $id, $list, true ) ) {
            $list[] = $id;
            update_option( 'iconist_exceptions', $list );
        }
        wp_send_json_success();
    }

    public function ajax_remove_exception() {
        check_ajax_referer( 'iconist_bulk_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'دسترسی غیرمجاز', 'iconist' ) );
        }
        $id   = absint( $_POST['attachment_id'] );
        $list = array_values( array_filter( self::get_exceptions(), function( $i ) use ( $id ) { return $i !== $id; } ) );
        update_option( 'iconist_exceptions', $list );
        wp_send_json_success();
    }

    public function ajax_get_svg_count() {
        check_ajax_referer( 'iconist_bulk_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'دسترسی غیرمجاز', 'iconist' ) );
        }

        $last_run = get_option( 'iconist_bulk_last_run', 0 );

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image/svg+xml',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ];

        if ( $last_run > 0 ) {
            $args['date_query'] = [
                [
                    'after'     => gmdate( 'Y-m-d H:i:s', $last_run ),
                    'inclusive' => false,
                ],
            ];
        }

        $query = new WP_Query( $args );

        wp_send_json_success( [
            'count'    => $query->found_posts,
            'last_run' => $last_run,
        ] );
    }

    public function ajax_bulk_convert_single() {
        check_ajax_referer( 'iconist_bulk_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'دسترسی غیرمجاز', 'iconist' ) );
        }

        $offset   = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;
        $last_run = get_option( 'iconist_bulk_last_run', 0 );

        $args = [
            'post_type'      => 'attachment',
            'post_mime_type' => 'image/svg+xml',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'offset'         => $offset,
            'fields'         => 'ids',
        ];

        if ( $last_run > 0 ) {
            $args['date_query'] = [
                [
                    'after'     => gmdate( 'Y-m-d H:i:s', $last_run ),
                    'inclusive' => false,
                ],
            ];
        }

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            update_option( 'iconist_bulk_last_run', time() );
            wp_send_json_success( [ 'more_images' => false, 'log' => [] ] );
        }

        $attachment_id = $query->posts[0];
        $filepath      = get_attached_file( $attachment_id );
        $filename      = basename( $filepath );
        $log           = [];

        if ( ! $filepath || ! file_exists( $filepath ) ) {
            $log[] = [ 'type' => 'error', 'text' => esc_html( sprintf( __( 'فایل پیدا نشد: %s', 'iconist' ), $filename ) ) ];
            wp_send_json_success( [ 'more_images' => ( $query->found_posts > 1 ), 'log' => $log ] );
        }

        if ( in_array( $attachment_id, self::get_exceptions(), true ) ) {
            $log[] = [ 'type' => 'info', 'text' => esc_html( $filename ) . ': ' . esc_html__( 'در لیست استثناهاست — رد شد.', 'iconist' ) ];
            wp_send_json_success( [ 'more_images' => ( $query->found_posts > 1 ), 'log' => $log ] );
        }

        $result = $this->convert_file( $filepath );

        if ( is_wp_error( $result ) ) {
            $log[] = [ 'type' => 'error', 'text' => esc_html( $filename ) . ': ' . esc_html( $result->get_error_message() ) ];
        } elseif ( $result['status'] === 'skipped' ) {
            $log[] = [ 'type' => 'info', 'text' => esc_html( $filename ) . ': ' . esc_html__( 'قبلاً تبدیل شده بود.', 'iconist' ) ];
        } else {
            $log[] = [ 'type' => 'success', 'text' => esc_html( $filename ) . ': ' . esc_html__( 'با موفقیت تبدیل شد!', 'iconist' ) ];
        }

        wp_send_json_success( [
            'more_images' => ( $query->found_posts > 1 ),
            'log'         => $log,
        ] );
    }

    public function ajax_restore_backup() {
        check_ajax_referer( 'iconist_bulk_nonce', 'security' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'دسترسی غیرمجاز', 'iconist' ) );
        }

        $attachment_id = absint( $_POST['attachment_id'] );
        if ( ! $attachment_id ) {
            wp_send_json_error( __( 'شناسه نامعتبر', 'iconist' ) );
        }

        $filepath = get_attached_file( $attachment_id );
        if ( ! $filepath || ! file_exists( $filepath ) ) {
            wp_send_json_error( __( 'فایل پیدا نشد.', 'iconist' ) );
        }

        $ext    = pathinfo( $filepath, PATHINFO_EXTENSION );
        $backup = $ext
            ? substr( $filepath, 0, -( strlen( $ext ) + 1 ) ) . '-iconist-backup.' . $ext
            : $filepath . '-iconist-backup';
        if ( ! file_exists( $backup ) ) {
            wp_send_json_error( __( 'فایل پشتیبان وجود ندارد.', 'iconist' ) );
        }

        $restored = @copy( $backup, $filepath );
        if ( ! $restored ) {
            wp_send_json_error( __( 'بازیابی فایل ممکن نیست.', 'iconist' ) );
        }

        wp_send_json_success( __( 'فایل با موفقیت بازیابی شد.', 'iconist' ) );
    }
}
