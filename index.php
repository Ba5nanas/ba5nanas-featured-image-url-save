<?php

/*
  Plugin Name: Ba5nanas Featured Image URL
  Plugin URI:
  Description:
  Version: 1.1
  Author: Ba5nanas
  Author URI: http://themeforest.net/user/Ba5nanas
  License: GPLv3 none commerial
 */

function ba5nanas_featured_image_add_meta_box() {

    $screens = array('post', 'page');

    foreach ($screens as $screen) {

        add_meta_box(
                'ba5nanas_featured_image', 'Ba5nanas Featured Image', 'ba5nanas_featured_image_meta_box_callback', $screen
        );
    }
}

add_action('add_meta_boxes', 'ba5nanas_featured_image_add_meta_box');

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function ba5nanas_featured_image_meta_box_callback($post) {

    // Add an nonce field so we can check for it later.
    wp_nonce_field('ba5nanas_featured_image_meta_box', 'ba5nanas_featured_image_meta_box_nonce');

    /*
     * Use get_post_meta() to retrieve an existing value
     * from the database and use the value for the form.
     */
    $value = get_post_meta($post->ID, '_ba5nanas_featured_image_meta', true);

    echo '<label for="">';
    _e('URL Image', 'ba5nanas_featured_image_textdomain');
    echo '</label> ';
    echo '<input type="text" id="ba5nanas_featured_image_new_field" style="width:100%;" name="ba5nanas_featured_image_new_field" value="' . esc_attr($value) . '" size="25" />';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function ba5nanas_featured_image_save_meta_box_data($post_id) {
    global $post;
    
    session_start();
    ob_start();
    /*
     * We need to verify this came from our screen and with proper authorization,
     * because the save_post action can be triggered at other times.
     */
    if (isset($_POST['ba5nanas_featured_image_new_field'])) {
        $time = current_time('mysql');
        if ($post = get_post($post_id)) {
            if (substr($post->post_date, 0, 4) > 0)
                $time = $post->post_date;
        }

        $uploads = wp_upload_dir($time);
        $file_url = $_POST['ba5nanas_featured_image_new_field'];


        $img = @get_headers($file_url);
        if ($img['0'] != "HTTP/1.1 200 OK") {
            return;
        }
        
        $file = pathinfo($file_url);
        $newfile = $uploads['path'] . "/" . $file['basename'];
        echo $newfile;
        if (is_file($newfile)) {
            $item_new = pathinfo($newfile);
            $rename = $uploads['path'] . "/" . $item_new['filename'] . "_" . rand(1, 10000) . "." . $item_new['extension'];
            $newfile = $rename;
            
            copy($file_url, $newfile);
        } else {
            
            copy($file_url, $newfile);
        }

        $file = pathinfo($newfile);
        $item = $file['filename'];
        $url = $file['dirname'] . "/" . $file['basename'];
        $type = $file['extension'];
        $file = $file['dirname'] . "/" . $file['basename'];
        $title = $item;
        $post_data = array();
        $attachment = array_merge(array(
            'post_mime_type' => ba5nanas_feature_type_getMimeType($type),
            'guid' => $url,
            'post_parent' => $post_id,
            'post_title' => $title,
                ), $post_data);

        // This should never be set as it would then overwrite an existing attachment.
        if (isset($attachment['ID']))
            unset($attachment['ID']);

        // Save the data
        $thumbnail_id = wp_insert_attachment($attachment, $file, $post_id);

        if (!is_wp_error($id)) {
            wp_update_attachment_metadata($thumbnail_id, wp_generate_attachment_metadata($thumbnail_id, $file));
        }
        delete_post_thumbnail( $post_id );
        set_post_thumbnail($post_id, $thumbnail_id);
    } // end
    //print_r(pathinfo($file));
    //exit;
    // Check if our nonce is set.
    if (!isset($_POST['ba5nanas_featured_image_meta_box_nonce'])) {
        return;
    }

    // Verify that the nonce is valid.
    if (!wp_verify_nonce($_POST['ba5nanas_featured_image_meta_box_nonce'], 'ba5nanas_featured_image_meta_box')) {
        return;
    }

    // If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check the user's permissions.
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {

        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    /* OK, it's safe for us to save the data now. */

    // Make sure that it is set.
    if (!isset($_POST['ba5nanas_featured_image_new_field'])) {
        return;
    }

    // Sanitize user input.
    $my_data = sanitize_text_field($_POST['ba5nanas_featured_image_new_field']);

    // Update the meta field in the database.
    update_post_meta($post_id, '_ba5nanas_featured_image_meta', "");
}

add_action('save_post', 'ba5nanas_featured_image_save_meta_box_data');

function ba5nanas_featured_image_action_callback($html = "", $post_id = "", $post_thumbnail_id = "", $size = array(), $attr = array()) {
    global $post;
    $value = get_post_meta($post->ID, '_ba5nanas_featured_image_meta', true);
    return "<img src='{$value}' class='ba5nanas-features-image'>";
}

//add_filter('post_thumbnail_html', 'ba5nanas_featured_image_action_callback');

function ba5nanas_feature_type_getMimeType($file) {
    // MIME types array
    $mimeTypes = array(
        "323" => "text/h323",
        "acx" => "application/internet-property-stream",
        "ai" => "application/postscript",
        "aif" => "audio/x-aiff",
        "aifc" => "audio/x-aiff",
        "aiff" => "audio/x-aiff",
        "asf" => "video/x-ms-asf",
        "asr" => "video/x-ms-asf",
        "asx" => "video/x-ms-asf",
        "au" => "audio/basic",
        "avi" => "video/x-msvideo",
        "axs" => "application/olescript",
        "bas" => "text/plain",
        "bcpio" => "application/x-bcpio",
        "bin" => "application/octet-stream",
        "bmp" => "image/bmp",
        "c" => "text/plain",
        "cat" => "application/vnd.ms-pkiseccat",
        "cdf" => "application/x-cdf",
        "cer" => "application/x-x509-ca-cert",
        "class" => "application/octet-stream",
        "clp" => "application/x-msclip",
        "cmx" => "image/x-cmx",
        "cod" => "image/cis-cod",
        "cpio" => "application/x-cpio",
        "crd" => "application/x-mscardfile",
        "crl" => "application/pkix-crl",
        "crt" => "application/x-x509-ca-cert",
        "csh" => "application/x-csh",
        "css" => "text/css",
        "dcr" => "application/x-director",
        "der" => "application/x-x509-ca-cert",
        "dir" => "application/x-director",
        "dll" => "application/x-msdownload",
        "dms" => "application/octet-stream",
        "doc" => "application/msword",
        "dot" => "application/msword",
        "dvi" => "application/x-dvi",
        "dxr" => "application/x-director",
        "eps" => "application/postscript",
        "etx" => "text/x-setext",
        "evy" => "application/envoy",
        "exe" => "application/octet-stream",
        "fif" => "application/fractals",
        "flr" => "x-world/x-vrml",
        "gif" => "image/gif",
        "gtar" => "application/x-gtar",
        "gz" => "application/x-gzip",
        "h" => "text/plain",
        "hdf" => "application/x-hdf",
        "hlp" => "application/winhlp",
        "hqx" => "application/mac-binhex40",
        "hta" => "application/hta",
        "htc" => "text/x-component",
        "htm" => "text/html",
        "html" => "text/html",
        "htt" => "text/webviewhtml",
        "ico" => "image/x-icon",
        "ief" => "image/ief",
        "iii" => "application/x-iphone",
        "ins" => "application/x-internet-signup",
        "isp" => "application/x-internet-signup",
        "jfif" => "image/pipeg",
        "jpe" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "jpg" => "image/jpeg",
        "js" => "application/x-javascript",
        "latex" => "application/x-latex",
        "lha" => "application/octet-stream",
        "lsf" => "video/x-la-asf",
        "lsx" => "video/x-la-asf",
        "lzh" => "application/octet-stream",
        "m13" => "application/x-msmediaview",
        "m14" => "application/x-msmediaview",
        "m3u" => "audio/x-mpegurl",
        "man" => "application/x-troff-man",
        "mdb" => "application/x-msaccess",
        "me" => "application/x-troff-me",
        "mht" => "message/rfc822",
        "mhtml" => "message/rfc822",
        "mid" => "audio/mid",
        "mny" => "application/x-msmoney",
        "mov" => "video/quicktime",
        "movie" => "video/x-sgi-movie",
        "mp2" => "video/mpeg",
        "mp3" => "audio/mpeg",
        "mpa" => "video/mpeg",
        "mpe" => "video/mpeg",
        "mpeg" => "video/mpeg",
        "mpg" => "video/mpeg",
        "mpp" => "application/vnd.ms-project",
        "mpv2" => "video/mpeg",
        "ms" => "application/x-troff-ms",
        "mvb" => "application/x-msmediaview",
        "nws" => "message/rfc822",
        "oda" => "application/oda",
        "p10" => "application/pkcs10",
        "p12" => "application/x-pkcs12",
        "p7b" => "application/x-pkcs7-certificates",
        "p7c" => "application/x-pkcs7-mime",
        "p7m" => "application/x-pkcs7-mime",
        "p7r" => "application/x-pkcs7-certreqresp",
        "p7s" => "application/x-pkcs7-signature",
        "pbm" => "image/x-portable-bitmap",
        "pdf" => "application/pdf",
        "pfx" => "application/x-pkcs12",
        "pgm" => "image/x-portable-graymap",
        "pko" => "application/ynd.ms-pkipko",
        "pma" => "application/x-perfmon",
        "pmc" => "application/x-perfmon",
        "pml" => "application/x-perfmon",
        "pmr" => "application/x-perfmon",
        "pmw" => "application/x-perfmon",
        "pnm" => "image/x-portable-anymap",
        "pot" => "application/vnd.ms-powerpoint",
        "ppm" => "image/x-portable-pixmap",
        "pps" => "application/vnd.ms-powerpoint",
        "ppt" => "application/vnd.ms-powerpoint",
        "prf" => "application/pics-rules",
        "ps" => "application/postscript",
        "pub" => "application/x-mspublisher",
        "qt" => "video/quicktime",
        "ra" => "audio/x-pn-realaudio",
        "ram" => "audio/x-pn-realaudio",
        "ras" => "image/x-cmu-raster",
        "rgb" => "image/x-rgb",
        "rmi" => "audio/mid",
        "roff" => "application/x-troff",
        "rtf" => "application/rtf",
        "rtx" => "text/richtext",
        "scd" => "application/x-msschedule",
        "sct" => "text/scriptlet",
        "setpay" => "application/set-payment-initiation",
        "setreg" => "application/set-registration-initiation",
        "sh" => "application/x-sh",
        "shar" => "application/x-shar",
        "sit" => "application/x-stuffit",
        "snd" => "audio/basic",
        "spc" => "application/x-pkcs7-certificates",
        "spl" => "application/futuresplash",
        "src" => "application/x-wais-source",
        "sst" => "application/vnd.ms-pkicertstore",
        "stl" => "application/vnd.ms-pkistl",
        "stm" => "text/html",
        "svg" => "image/svg+xml",
        "sv4cpio" => "application/x-sv4cpio",
        "sv4crc" => "application/x-sv4crc",
        "t" => "application/x-troff",
        "tar" => "application/x-tar",
        "tcl" => "application/x-tcl",
        "tex" => "application/x-tex",
        "texi" => "application/x-texinfo",
        "texinfo" => "application/x-texinfo",
        "tgz" => "application/x-compressed",
        "tif" => "image/tiff",
        "tiff" => "image/tiff",
        "tr" => "application/x-troff",
        "trm" => "application/x-msterminal",
        "tsv" => "text/tab-separated-values",
        "txt" => "text/plain",
        "uls" => "text/iuls",
        "ustar" => "application/x-ustar",
        "vcf" => "text/x-vcard",
        "vrml" => "x-world/x-vrml",
        "wav" => "audio/x-wav",
        "wcm" => "application/vnd.ms-works",
        "wdb" => "application/vnd.ms-works",
        "wks" => "application/vnd.ms-works",
        "wmf" => "application/x-msmetafile",
        "wps" => "application/vnd.ms-works",
        "wri" => "application/x-mswrite",
        "wrl" => "x-world/x-vrml",
        "wrz" => "x-world/x-vrml",
        "xaf" => "x-world/x-vrml",
        "xbm" => "image/x-xbitmap",
        "xla" => "application/vnd.ms-excel",
        "xlc" => "application/vnd.ms-excel",
        "xlm" => "application/vnd.ms-excel",
        "xls" => "application/vnd.ms-excel",
        "xlsx" => "vnd.ms-excel",
        "xlt" => "application/vnd.ms-excel",
        "xlw" => "application/vnd.ms-excel",
        "xof" => "x-world/x-vrml",
        "xpm" => "image/x-xpixmap",
        "xwd" => "image/x-xwindowdump",
        "z" => "application/x-compress",
        "zip" => "application/zip"
    );

    $extension = end(explode('.', $file));
    return $mimeTypes[$extension]; // return the array value
}

function ba5nanas_feature_image_getHTTPStatus($url) {
    $ch = curl_init();
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_VERBOSE => false
    ));
    curl_exec($ch);
    $http_status = curl_getinfo($ch);
    return $http_status;
}

