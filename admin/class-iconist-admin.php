<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Iconist_Admin {
    public function __construct() {
        add_action( 'admin_menu',             [ $this, 'add_admin_menu' ] );
        add_action( 'admin_enqueue_scripts',  [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_init',             [ $this, 'settings_init' ] );
        add_action( 'admin_notices',          [ $this, 'show_notices' ] );
    }

    public function add_admin_menu() {
        add_options_page(
            __( 'تنظیمات آیکونیست', 'iconist' ),
            __( 'آیکونیست', 'iconist' ),
            'manage_options',
            'iconist',
            [ $this, 'settings_page' ]
        );
    }

    public function enqueue_scripts( $hook ) {
        if ( $hook !== 'settings_page_iconist' ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_style(
            'iconist-admin-style',
            ICONIST_DIR_URL . 'css/style.css',
            [],
            filemtime( ICONIST_DIR_PATH . 'css/style.css' )
        );

        wp_enqueue_script(
            'iconist-panel-script',
            ICONIST_DIR_URL . 'js/panel.js',
            [ 'jquery' ],
            filemtime( ICONIST_DIR_PATH . 'js/panel.js' ),
            true
        );

        wp_localize_script( 'iconist-panel-script', 'iconistAjax', [
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'security'         => wp_create_nonce( 'iconist_bulk_nonce' ),
            'exception_nonce'  => wp_create_nonce( 'iconist_exception_upload' ),
            'i18n'     => [
                'processing'    => __( 'در حال تبدیل...', 'iconist' ),
                'convert_all'   => __( 'تبدیل همه آیکون‌ها', 'iconist' ),
                'done'          => __( 'همه تبدیل‌ها انجام شد!', 'iconist' ),
                'error_stopped' => __( 'تبدیل به‌دلیل خطا متوقف شد.', 'iconist' ),
                'confirm_msg'   => __( 'آیا مطمئن هستید؟ تمام SVGهای موجود در رسانه وردپرس تبدیل می‌شوند.', 'iconist' ),
                'calculating'   => __( 'در حال محاسبه تعداد فایل‌ها...', 'iconist' ),
                'no_files'      => __( 'هیچ فایل SVG برای تبدیل پیدا نشد.', 'iconist' ),
                'found'         => __( 'فایل SVG برای تبدیل پیدا شد.', 'iconist' ),
                'converting'    => __( 'در حال تبدیل:', 'iconist' ),
                'retry'         => __( 'تلاش مجدد...', 'iconist' ),
                'net_error'     => __( 'خطای شبکه — تلاش مجدد', 'iconist' ),
                'confirm_title' => __( 'تأیید فرایند', 'iconist' ),
                'cancel'        => __( 'انصراف', 'iconist' ),
                'proceed'       => __( 'ادامه', 'iconist' ),
            ],
        ] );
    }

    public function settings_init() {
        register_setting( 'iconist_settings_group', 'iconist_auto_convert',   'absint' );
        register_setting( 'iconist_settings_group', 'iconist_backup_enabled', 'absint' );

        if ( get_option( 'iconist_auto_convert' )  === false ) update_option( 'iconist_auto_convert',  1 );
        if ( get_option( 'iconist_backup_enabled' ) === false ) update_option( 'iconist_backup_enabled', 1 );
    }

    public function show_notices() {
        if ( isset( $_GET['settings-updated'] ) && isset( $_GET['page'] ) && $_GET['page'] === 'iconist' ) {
            echo '<div class="notice notice-success is-dismissible iconist-notice"><p><strong>' .
                 esc_html__( 'تنظیمات آیکونیست با موفقیت ذخیره شد.', 'iconist' ) .
                 '</strong></p></div>';
        }
    }

    public function settings_page() {
        $is_rtl = is_rtl();
        ?>
        <div class="iconist_admin_wrapper <?php echo esc_attr( $is_rtl ? 'iconist-rtl' : 'iconist-ltr' ); ?>">

            <div class="iconist_sidebar_wrapper">
                <div class="sidebar">
                    <div class="ic_logo_section">
                        <div class="ic_logo">
                            <?php echo $this->get_logo_svg(); ?>
                        </div>
                        <div class="ic_hello">
                            <span class="ic_plugin_name"><?php esc_html_e( 'آیکونیست', 'iconist' ); ?></span>
                            <span class="ic_plugin_sub">Iconist <?php echo esc_html( ICONIST_VERSION ); ?></span>
                        </div>
                    </div>

                    <ul class="ic_nav">
                        <li class="active" data-tab="general">
                            <a href="#">
                                <span class="ic_nav_icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 6.5H16" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 6.5H2" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 10C11.933 10 13.5 8.433 13.5 6.5C13.5 4.567 11.933 3 10 3C8.067 3 6.5 4.567 6.5 6.5C6.5 8.433 8.067 10 10 10Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 17.5H18" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 17.5H2" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 21C15.933 21 17.5 19.433 17.5 17.5C17.5 15.567 15.933 14 14 14C12.067 14 10.5 15.567 10.5 17.5C10.5 19.433 12.067 21 14 21Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span><?php esc_html_e( 'تنظیمات عمومی', 'iconist' ); ?></span>
                            </a>
                        </li>
                        <li data-tab="bulk">
                            <a href="#">
                                <span class="ic_nav_icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.57996 5.15991H17.42C19.08 5.15991 20.42 6.49991 20.42 8.15991V11.4799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.73996 2L3.57996 5.15997L6.73996 8.32001" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M20.42 18.84H6.57996C4.91996 18.84 3.57996 17.5 3.57996 15.84V12.52" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span><?php esc_html_e( 'تبدیل دسته‌جمعی', 'iconist' ); ?></span>
                            </a>
                        </li>
                        <li data-tab="exceptions">
                            <a href="#">
                                <span class="ic_nav_icon">
                                    
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.37 20.1C2.43 18.97 2 17.31 2 15V9C2 4 4 2 9 2H15C17.19 2 18.8 2.38 19.92 3.23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21.97 7.98999C21.99 8.30999 22 8.64999 22 8.99999V15C22 20 20 22 15 22H8.99996C8.25996 22 7.57996 21.96 6.95996 21.86" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 2L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M11.4301 16.45C11.7301 16.75 12.2201 16.75 12.5201 16.45L17.5501 11.41C18.3301 10.63 19.5901 10.63 20.3701 11.41L22.0001 13.05" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>

                                </span>
                                <span><?php esc_html_e( 'استثناها', 'iconist' ); ?></span>
                            </a>
                        </li>
                        <li data-tab="status">
                            <a href="#">
                                <span class="ic_nav_icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.32 10H4.69002C3.21002 10 2.01001 8.79002 2.01001 7.32002V4.69002C2.01001 3.21002 3.22002 2.01001 4.69002 2.01001H19.32C20.8 2.01001 22 3.22002 22 4.69002V7.32002C22 8.79002 20.79 10 19.32 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M19.32 22H4.69002C3.21002 22 2.01001 20.79 2.01001 19.32V16.69C2.01001 15.21 3.22002 14.01 4.69002 14.01H19.32C20.8 14.01 22 15.22 22 16.69V19.32C22 20.79 20.79 22 19.32 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 17V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 17V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 6H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 18H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <span><?php esc_html_e( 'وضعیت سرور', 'iconist' ); ?></span>
                            </a>
                        </li>
                    </ul>

                    <div class="ic_sidebar_footer">
                        <div class="ic_badge">
                            <span class="ic_badge_dot"></span>
                            <?php esc_html_e( 'افزونه فعال است', 'iconist' ); ?>
                        </div>
                        <p class="ic_free_label">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="ic_heart" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19.86 8.08997C19.86 8.50997 19.83 8.91997 19.78 9.30997C19.32 9.10997 18.82 8.99997 18.29 8.99997C17.07 8.99997 15.99 9.58996 15.32 10.49C14.64 9.58996 13.56 8.99997 12.34 8.99997C10.29 8.99997 8.63 10.67 8.63 12.74C8.63 15.42 10.05 17.47 11.63 18.86C11.58 18.89 11.53 18.9 11.48 18.92C11.18 19.03 10.68 19.03 10.38 18.92C7.79 18.03 2 14.35 2 8.08997C2 5.32997 4.21999 3.09998 6.95999 3.09998C8.58999 3.09998 10.03 3.87997 10.93 5.08997C11.84 3.87997 13.28 3.09998 14.9 3.09998C17.64 3.09998 19.86 5.32997 19.86 8.08997Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M22 12.74C22 17.42 17.67 20.18 15.73 20.84C15.5 20.92 15.13 20.92 14.9 20.84C14.07 20.56 12.8 19.89 11.63 18.86C10.05 17.47 8.63 15.42 8.63 12.74C8.63 10.67 10.29 9 12.34 9C13.56 9 14.64 9.58999 15.32 10.49C15.99 9.58999 17.07 9 18.29 9C18.82 9 19.32 9.11 19.78 9.31C21.09 9.89 22 11.2 22 12.74Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php esc_html_e( 'رایگان برای همیشه', 'iconist' ); ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="iconist_content_wrapper">
                <div class="iconist_content">

                    <div class="ic_tab_content active" id="tab-general">
                        <form method="post" action="options.php" id="iconist-options-form">
                            <?php settings_fields( 'iconist_settings_group' ); ?>

                            <div class="ic_section">
                                <h3 class="ic_section_title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 8V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M11.9945 16H12.0035" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e( 'درباره افزونه', 'iconist' ); ?>
                                </h3>
                                <div class="ic_about_box">
                                    <p><?php esc_html_e( 'آیکونیست به‌صورت خودکار تمام فایل‌های SVG آپلود‌شده در رسانه وردپرس را دریافت می‌کند و با ایجاد تغییراتی، آیکون ها را برای استفاده و تغییر رنگ در المنتور آماده می‌کند.', 'iconist' ); ?></p>
                                </div>
                            </div>

                            <div class="ic_section">
                                <h3 class="ic_section_title">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M22 5.15V8.85C22 11.1 21.1 12 18.85 12H16.15C13.9 12 13 11.1 13 8.85V5.15C13 2.9 13.9 2 16.15 2H18.85C21.1 2 22 2.9 22 5.15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M11 15.15V18.85C11 21.1 10.1 22 7.85 22H5.15C2.9 22 2 21.1 2 18.85V15.15C2 12.9 2.9 12 5.15 12H7.85C10.1 12 11 12.9 11 15.15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M22 15C22 18.87 18.87 22 15 22L16.05 20.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M2 9C2 5.13 5.13 2 9 2L7.95 3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e( 'تنظیمات تبدیل', 'iconist' ); ?>
                                </h3>

                                <div class="ic_toggle_list">
                                    <div class="ic_toggle_row">
                                        <div class="ic_toggle_label">
                                            <span class="ic_toggle_title"><?php esc_html_e( 'تبدیل خودکار هنگام آپلود', 'iconist' ); ?></span>
                                            <span class="ic_toggle_desc"><?php esc_html_e( 'هر SVG جدیدی که آپلود می‌شود به‌صورت خودکار تبدیل می‌گردد', 'iconist' ); ?></span>
                                        </div>
                                        <div class="ic_toggle_switch">
                                            <input type="checkbox" id="iconist_auto_convert" name="iconist_auto_convert" value="1" <?php checked( get_option( 'iconist_auto_convert', 1 ) ); ?>>
                                            <label for="iconist_auto_convert"></label>
                                        </div>
                                    </div>

                                    <div class="ic_toggle_row">
                                        <div class="ic_toggle_label">
                                            <span class="ic_toggle_title"><?php esc_html_e( 'ذخیره نسخه پشتیبان قبل از تبدیل', 'iconist' ); ?></span>
                                            <span class="ic_toggle_desc"><?php esc_html_e( 'قبل از هر تبدیل، یک نسخه پشتیبان از فایل اصلی با پسوند -iconist-backup ذخیره می‌شود', 'iconist' ); ?></span>
                                        </div>
                                        <div class="ic_toggle_switch">
                                            <input type="checkbox" id="iconist_backup_enabled" name="iconist_backup_enabled" value="1" <?php checked( get_option( 'iconist_backup_enabled', 1 ) ); ?>>
                                            <label for="iconist_backup_enabled"></label>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="ic_section">
                                <div class="ic_field">
                                    <input type="submit" class="ic_btn ic_btn_primary ic_btn_full" value="<?php esc_attr_e( 'ذخیره تنظیمات', 'iconist' ); ?>">
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="ic_tab_content" id="tab-bulk">
                        <div class="ic_section">
                            <h3 class="ic_section_title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.57996 5.15991H17.42C19.08 5.15991 20.42 6.49991 20.42 8.15991V11.4799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6.73996 2L3.57996 5.15997L6.73996 8.32001" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M20.42 18.84H6.57996C4.91996 18.84 3.57996 17.5 3.57996 15.84V12.52" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php esc_html_e( 'تبدیل دسته‌جمعی', 'iconist' ); ?>
                            </h3>
                            <div class="ic_about_box">
                                <p><?php esc_html_e( 'این ابزار تمام فایل‌های SVG موجود در رسانه وردپرس را پیدا کرده و آن‌ها را برای استفاده در المنتور آماده می‌کند.', 'iconist' ); ?></p>
                            </div>

                            <div class="ic_field">
                                <button id="iconist_bulk_btn" class="ic_btn ic_btn_primary ic_btn_full">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3.57996 5.15991H17.42C19.08 5.15991 20.42 6.49991 20.42 8.15991V11.4799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6.73996 2L3.57996 5.15997L6.73996 8.32001" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M20.42 18.84H6.57996C4.91996 18.84 3.57996 17.5 3.57996 15.84V12.52" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M17.26 21.9999L20.42 18.84L17.26 15.6799" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e( 'تبدیل همه آیکون‌ها', 'iconist' ); ?>
                                </button>
                            </div>

                            <div id="iconist_progress_wrap" style="display:none; margin-top:20px;">
                                <div class="ic_progress_info">
                                    <span id="iconist_progress_text"></span>
                                    <span id="iconist_progress_pct"></span>
                                </div>
                                <div class="ic_progress_bar_bg">
                                    <div class="ic_progress_bar_fill" id="iconist_progress_fill"></div>
                                </div>
                            </div>

                            <div id="iconist_log" style="display:none; margin-top:15px;">
                                <div class="ic_log_header">
                                    <span><?php esc_html_e( 'گزارش تبدیل', 'iconist' ); ?></span>
                                    <button id="iconist_clear_log" class="ic_log_clear_btn"><?php esc_html_e( 'پاک کردن', 'iconist' ); ?></button>
                                </div>
                                <div class="ic_log_body" id="iconist_log_body"></div>
                            </div>

                            <div id="iconist_done_msg" style="display:none;" class="ic_done_banner">
                                
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M9 22H15C20 22 22 20 22 15V9C22 4 20 2 15 2H9C4 2 2 4 2 9V15C2 20 4 22 9 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7.75 12L10.58 14.83L16.25 9.17004" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                                <?php esc_html_e( 'همه فایل‌ها با موفقیت تبدیل شدند!', 'iconist' ); ?>
                            </div>
                        </div>
                    </div>

                    <div class="ic_tab_content" id="tab-exceptions">
                        <div class="ic_section">
                            <h3 class="ic_section_title">
                                
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3.37 20.1C2.43 18.97 2 17.31 2 15V9C2 4 4 2 9 2H15C17.19 2 18.8 2.38 19.92 3.23" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M21.97 7.98999C21.99 8.30999 22 8.64999 22 8.99999V15C22 20 20 22 15 22H8.99996C8.25996 22 7.57996 21.96 6.95996 21.86" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M8 10C9.10457 10 10 9.10457 10 8C10 6.89543 9.10457 6 8 6C6.89543 6 6 6.89543 6 8C6 9.10457 6.89543 10 8 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 2L2 22" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M11.4301 16.45C11.7301 16.75 12.2201 16.75 12.5201 16.45L17.5501 11.41C18.3301 10.63 19.5901 10.63 20.3701 11.41L22.0001 13.05" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>

                                <?php esc_html_e( 'فایل‌های استثنا', 'iconist' ); ?>
                            </h3>
                            <div class="ic_about_box">
                                <p><?php esc_html_e( 'فایل‌های SVG زیر هنگام آپلود و تبدیل دسته‌جمعی دست نخورده باقی می‌مانند.', 'iconist' ); ?></p>
                            </div>

                            <div class="ic_field">
                                <button id="iconist_add_exception_btn" class="ic_btn ic_btn_primary">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M12 22C17.5 22 22 17.5 22 12C22 6.5 17.5 2 12 2C6.5 2 2 6.5 2 12C2 17.5 6.5 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M8 12H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 16V8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e( 'افزودن از رسانه وردپرس', 'iconist' ); ?>
                                </button>
                            </div>

                            <div id="iconist_exceptions_list" style="margin-top:20px;">
                                <?php
                                $exceptions = Iconist_Converter::get_exceptions();
                                if ( empty( $exceptions ) ) {
                                    echo '<p class="ic_empty_msg">' . esc_html__( 'هیچ استثنایی تعریف نشده.', 'iconist' ) . '</p>';
                                } else {
                                    foreach ( $exceptions as $id ) {
                                        $url   = wp_get_attachment_url( $id );
                                        $title = get_the_title( $id );
                                        if ( ! $url ) continue;
                                        echo '<div class="ic_exception_row" data-id="' . esc_attr( $id ) . '">';
                                        echo '<div class="ic_exception_info">';
                                        echo '<span class="ic_exception_icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.67 14.3L21.27 19.3C21.12 20.83 21 22 18.29 22H5.71001C3.00001 22 2.88001 20.83 2.73001 19.3L2.33001 14.3C2.25001 13.47 2.51001 12.7 2.98001 12.11C2.99001 12.1 2.99001 12.1 3.00001 12.09C3.55001 11.42 4.38001 11 5.31001 11H18.69C19.62 11 20.44 11.42 20.98 12.07C20.99 12.08 21 12.09 21 12.1C21.49 12.69 21.76 13.46 21.67 14.3Z" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10"/><path d="M3.5 11.43V6.28003C3.5 2.88003 4.35 2.03003 7.75 2.03003H9.02C10.29 2.03003 10.58 2.41003 11.06 3.05003L12.33 4.75003C12.65 5.17003 12.84 5.43003 13.69 5.43003H16.24C19.64 5.43003 20.49 6.28003 20.49 9.68003V11.47" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.42993 17H14.5699" stroke="currentColor" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
                                        echo '<span class="ic_exception_name">' . esc_html( $title ?: basename( $url ) ) . '</span>';
                                        echo '</div>';
                                        echo '<button class="ic_exception_remove_btn ic_btn ic_btn_ghost" data-id="' . esc_attr( $id ) . '">';
                                        echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M21 5.97998C17.67 5.64998 14.32 5.47998 10.98 5.47998C9 5.47998 7.02 5.57998 5.04 5.77998L3 5.97998" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M8.5 4.97L8.72 3.66C8.88 2.71 9 2 10.69 2H13.31C15 2 15.13 2.75 15.28 3.67L15.5 4.97" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.85 9.14001L18.2 19.21C18.09 20.78 18 22 15.21 22H8.79002C6.00002 22 5.91002 20.78 5.80002 19.21L5.15002 9.14001" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M10.33 16.5H13.66" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M9.5 12.5H14.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>';
                                        echo esc_html__( 'حذف', 'iconist' );
                                        echo '</button>';
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <div class="ic_tab_content" id="tab-status">
                        <div class="ic_section">
                            <h3 class="ic_section_title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.32 10H4.69002C3.21002 10 2.01001 8.79002 2.01001 7.32002V4.69002C2.01001 3.21002 3.22002 2.01001 4.69002 2.01001H19.32C20.8 2.01001 22 3.22002 22 4.69002V7.32002C22 8.79002 20.79 10 19.32 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19.32 22H4.69002C3.21002 22 2.01001 20.79 2.01001 19.32V16.69C2.01001 15.21 3.22002 14.01 4.69002 14.01H19.32C20.8 14.01 22 15.22 22 16.69V19.32C22 20.79 20.79 22 19.32 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6 5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 5V7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M6 17V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M10 17V19" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 6H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M14 18H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php esc_html_e( 'وضعیت سرور', 'iconist' ); ?>
                            </h3>
                            <ul class="ic_status_list">
                                <?php foreach ( Iconist_Converter::get_server_support_status() as $name => $info ) : ?>
                                    <li class="ic_status_item <?php echo esc_attr( $info['status'] ? 'ic_status_ok' : 'ic_status_err' ); ?>">
                                        <span class="ic_status_icon">
                                            <?php if ( $info['status'] ) : ?>
                                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                            <?php else : ?>
                                                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                            <?php endif; ?>
                                        </span>
                                        <span class="ic_status_name"><?php echo esc_html( $name ); ?></span>
                                        <span class="ic_status_msg"><?php echo esc_html( $info['message'] ); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="ic_section">
                            <h3 class="ic_section_title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 22H21" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.59998 8.37988H4C3.45 8.37988 3 8.82988 3 9.37988V17.9999C3 18.5499 3.45 18.9999 4 18.9999H5.59998C6.14998 18.9999 6.59998 18.5499 6.59998 17.9999V9.37988C6.59998 8.82988 6.14998 8.37988 5.59998 8.37988Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12.7999 5.18994H11.2C10.65 5.18994 10.2 5.63994 10.2 6.18994V17.9999C10.2 18.5499 10.65 18.9999 11.2 18.9999H12.7999C13.3499 18.9999 13.7999 18.5499 13.7999 17.9999V6.18994C13.7999 5.63994 13.3499 5.18994 12.7999 5.18994Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M19.9999 2H18.3999C17.8499 2 17.3999 2.45 17.3999 3V18C17.3999 18.55 17.8499 19 18.3999 19H19.9999C20.5499 19 20.9999 18.55 20.9999 18V3C20.9999 2.45 20.5499 2 19.9999 2Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <?php esc_html_e( 'اطلاعات سایت', 'iconist' ); ?>
                            </h3>
                            <ul class="ic_status_list">
                                <li class="ic_status_item ic_status_info">
                                    <span class="ic_status_name">PHP</span>
                                    <span class="ic_status_msg"><?php echo esc_html( PHP_VERSION ); ?></span>
                                </li>
                                <li class="ic_status_item ic_status_info">
                                    <span class="ic_status_name">WordPress</span>
                                    <span class="ic_status_msg"><?php global $wp_version; echo esc_html( $wp_version ); ?></span>
                                </li>
                                <li class="ic_status_item ic_status_info">
                                    <span class="ic_status_name">Iconist</span>
                                    <span class="ic_status_msg"><?php echo esc_html( ICONIST_VERSION ); ?></span>
                                </li>
                                <li class="ic_status_item ic_status_info">
                                    <span class="ic_status_name"><?php esc_html_e( 'زبان سایت', 'iconist' ); ?></span>
                                    <span class="ic_status_msg"><?php echo esc_html( get_locale() ); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <div id="iconist_modal_overlay" style="display:none;">
            <div id="iconist_modal">
                <div id="iconist_modal_icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 9V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12.0001 21.41H5.94005C2.47005 21.41 1.02005 18.93 2.70005 15.9L5.82006 10.28L8.76006 5.00003C10.5401 1.79003 13.4601 1.79003 15.2401 5.00003L18.1801 10.29L21.3001 15.91C22.9801 18.94 21.5201 21.42 18.0601 21.42H12.0001V21.41Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M11.9945 17H12.0035" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 id="iconist_modal_title"><?php esc_html_e( 'تأیید فرایند', 'iconist' ); ?></h3>
                <p id="iconist_modal_msg"></p>
                <div id="iconist_modal_actions">
                    <button id="iconist_modal_cancel" class="ic_btn ic_btn_ghost"><?php esc_html_e( 'انصراف', 'iconist' ); ?></button>
                    <button id="iconist_modal_confirm" class="ic_btn ic_btn_primary"><?php esc_html_e( 'ادامه', 'iconist' ); ?></button>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_logo_svg() {
        return '
<svg width="742" height="743" viewBox="0 0 742 743" fill="none" xmlns="http://www.w3.org/2000/svg">
<g clip-path="url(#clip0_170_101)">
<path d="M527.054 0.431641H215.946C80.8112 0.431641 0.25 81.3641 0.25 216.499V527.235C0.25 662.37 80.8112 742.931 215.946 742.931H526.683C661.818 742.931 742.379 662.37 742.379 527.235V216.499C742.75 81.3641 662.189 0.431641 527.054 0.431641ZM355.536 549.51C355.536 560.647 349.967 570.671 340.315 576.611C335.117 579.952 329.549 581.437 323.609 581.437C318.783 581.437 313.956 580.323 309.13 578.096L179.193 513.127C160.63 503.475 148.75 484.541 148.75 463.38V340.496C148.75 329.358 154.319 319.335 163.971 313.395C173.624 307.455 185.132 307.083 195.156 311.91L325.094 376.878C344.027 386.531 355.907 405.465 355.907 426.626V549.51H355.536ZM347.74 350.891L208.15 275.528C198.126 269.959 191.815 259.193 191.815 246.942C191.815 235.062 198.126 223.924 208.15 218.355L347.74 142.992C362.59 135.196 380.039 135.196 394.889 142.992L534.479 218.355C544.502 223.924 550.814 234.691 550.814 246.942C550.814 259.193 544.502 269.959 534.479 275.528L394.889 350.891C387.464 354.975 379.296 356.831 371.129 356.831C362.961 356.831 355.165 354.975 347.74 350.891ZM594.25 463.38C594.25 484.541 582.37 503.846 563.436 513.127L433.499 578.096C429.044 580.323 424.217 581.437 419.02 581.437C413.08 581.437 407.511 579.952 402.314 576.611C392.661 570.671 387.093 560.647 387.093 549.51V426.626C387.093 405.465 398.973 386.16 417.906 376.878L547.844 311.91C557.868 307.083 569.376 307.455 579.029 313.395C588.681 319.335 594.25 329.358 594.25 340.496V463.38Z" fill="#354AC4"/>
<g clip-path="url(#clip1_170_101)">
<path d="M306.02 181.842C329.584 187.314 361.693 203.144 383.609 219.571C366.526 225.052 354.97 241.146 355.245 259.169C332.905 243.155 307.844 217.137 295.455 196.254C293.355 192.548 293.903 188.433 295.97 185.613C298.152 182.637 301.832 180.819 306.02 181.842Z" fill="#354AC4"/>
<path d="M390.115 224.689C391.478 225.868 392.841 227.048 394.136 228.238L405.713 238.893C405.51 238.924 405.249 239.034 404.977 239.076C397.861 241.145 391.664 245.573 387.013 251.918C382.305 258.341 379.978 265.787 380.199 273.535C380.23 273.739 380.204 274.021 380.235 274.224L366.298 266.296C364.93 265.534 363.619 264.694 362.23 263.796L362.13 259.086C361.919 244.18 371.581 230.671 385.75 226.125L390.115 224.689Z" fill="#354AC4"/>
<path d="M410.788 244.777C418.254 243.627 425.82 245.38 432.019 249.984C436.865 253.476 440.573 258.602 442.433 265.264L446.827 281.611C449.246 290.55 442.643 299.558 433.323 299.951L416.344 300.69C401.229 301.351 389.813 290.394 387.513 277.268C387.246 275.989 387.095 274.553 387.021 273.175C386.879 267.291 388.669 261.317 392.563 255.923C396.41 250.675 401.375 247.269 406.893 245.655C408.23 245.31 409.499 244.975 410.788 244.777Z" fill="#354AC4"/>
</g>
<path d="M533 433.626H511.78V384.179C511.78 372.642 505.53 370.307 497.907 378.96L492.413 385.21L445.92 438.09C439.534 445.3 442.212 451.206 451.827 451.206H473.047V500.652C473.047 512.19 479.297 514.525 486.919 505.872L492.413 499.622L538.906 446.743C545.293 439.532 542.615 433.626 533 433.626Z" fill="#354AC4"/>
<path d="M286.32 397.702V402.784L262.009 388.706C252.806 383.418 239.964 383.418 230.83 388.706L206.52 402.853V397.702C206.52 382.251 214.967 373.735 230.418 373.735H262.421C277.873 373.735 286.32 382.251 286.32 397.702Z" fill="#354AC4"/>
<path d="M286.516 414.722L285.555 414.241L276.215 408.885L256.849 397.691C250.943 394.257 241.877 394.257 235.971 397.691L216.605 408.816L207.265 414.31L206.029 414.928C194.011 423.032 193.187 424.542 193.187 437.522V468.563C193.187 481.543 194.011 483.054 206.304 491.363L235.971 508.463C238.924 510.249 242.633 511.004 246.41 511.004C250.118 511.004 253.896 510.18 256.849 508.463L286.791 491.157C298.878 483.054 299.633 481.612 299.633 468.563V437.522C299.633 424.542 298.809 423.032 286.516 414.722ZM265.57 452.699L261.381 457.85C260.694 458.605 260.214 460.048 260.282 461.078L260.694 467.671C260.969 471.722 258.085 473.783 254.308 472.34L248.196 469.868C247.234 469.525 245.655 469.525 244.693 469.868L238.581 472.272C234.804 473.783 231.92 471.654 232.194 467.602L232.606 461.009C232.675 459.979 232.194 458.537 231.508 457.781L227.25 452.699C224.64 449.609 225.808 446.175 229.722 445.145L236.109 443.497C237.139 443.222 238.306 442.261 238.856 441.437L242.427 435.943C244.624 432.509 248.127 432.509 250.393 435.943L253.964 441.437C254.514 442.329 255.75 443.222 256.711 443.497L263.098 445.145C267.013 446.175 268.18 449.609 265.57 452.699Z" fill="#354AC4"/>
</g>
<defs>
<clipPath id="clip0_170_101">
<rect width="742" height="743" fill="white"/>
</clipPath>
<clipPath id="clip1_170_101">
<rect width="164.82" height="164.82" fill="white" transform="translate(302.093 333.992) rotate(-98.7572)"/>
</clipPath>
</defs>
</svg>
';
    }
}