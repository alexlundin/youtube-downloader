<?php
/**
 * Plugin Name: Youtube Downloader
 * Description: Плагин позволяет скачать видео с видеохостинга YouTube
 * Version:     1.1.2
 * Author:      Alex Lundin
 * Author URI:  https://alexlundin.com
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: youtube-downloader
 * Domain Path: /languages
 */


require 'plugin-update-checker/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/alexlundin/youtube-downloader/',
	__FILE__,
	'youtube-downloader'
);

add_shortcode('youtube-downloader', function () {
    return '<div class="download-youtube"> <div class="downloader-wrap">
        <form action=""  class="dow-form" id="dow-form">
            <div class="dow-input-wrap l-box">
                <input type="text" name="dow-url" value="" class="dow-input" autofocus="" placeholder="https://www.youtube.com/watch?v=FU8csnZxdPA" id="dow_url">
                <span class="clear-btn"></span>
            </div>
            <div class="r-box">
                <button type="submit" class="submit" name="dow-submit" id="dow-submit"></button>
            </div>
        </form>
            <small>Ссылка должна быть вида: <span>https://youtu.be/FU8csnZxdPA</span> или <span>https://www.youtube.com/watch?v=FU8csnZxdPA</span></small>
    </div>
    <div id="result"></div></div>';
});

add_action('wp', 'add_js_css');

function add_js_css()
{
    /* Получаем глобальную переменную $post */
    global $post;

    /* Регистрируем таблицу стилей */
    wp_register_style('youtube-downloader-style', plugins_url('assets/css/youtube.css', __FILE__));

    wp_register_script('youtube-downloader-script', plugins_url('assets/js/youtube.js', __FILE__), 'jquery', false, true);

    /* Проверяем нет присутствует ли в записи шорткод, если да то выводит css */
    if ( has_shortcode($post->post_content, 'youtube-downloader') ) {
        wp_enqueue_style('youtube-downloader-style');
        wp_enqueue_script('youtube-downloader-script');
    }
}



add_action('wp_enqueue_scripts', 'myajax_data', 99);
function myajax_data()
{
    wp_localize_script('youtube-downloader-script', 'myajax',
        array(
            'url' => admin_url('admin-ajax.php')
        )
    );
}

function formatSize($bytes)
{

    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes = $bytes . ' байты';
    } elseif ($bytes == 1) {
        $bytes = $bytes . ' байт';
    } else {
        $bytes = '0 байтов';
    }

    return $bytes;
}

add_action('wp_ajax_youtube', 'youtube_down');
add_action('wp_ajax_nopriv_youtube', 'youtube_down');
function youtube_down()
{
    $link = $_POST['link'];
    if (strpos($link, 'youtube.com') !== false) {
        $linkArr = explode('&', $link);
        $resLink = explode('=', $linkArr[0]);
        $id = $resLink[1];
    } elseif (strpos($link, 'youtu.be') !== false) {
        $linkArr = explode('be/', $link);
        $id = $linkArr[1];
    }

    $data = file_get_contents("https://www.youtube.com/get_video_info?video_id=" . $id);
    $query_params = array();
    parse_str($data, $query_params);
    $player_response = json_decode($query_params['player_response'], true);
    $main_format = $player_response['streamingData']['formats'];
    $adaptive_format = $player_response['streamingData']['adaptiveFormats'];
    $title = $player_response['videoDetails']['title'];
    $view = $player_response['videoDetails']['viewCount'];
    $arr = explode(",", $query_params['url_encoded_fmt_stream_map']);
    $img = $player_response['videoDetails']['thumbnail']['thumbnails'][4]['url'];
    $time = $player_response['videoDetails']['lengthSeconds'];
    $sec = $time % 60;
    if (strlen($sec) == 1) {
        $sec = '0' . $sec;
    }
    $time = floor($time / 60);
    $min = $time % 60;
    if (strlen($min) == 1) {
        $min = '0' . $min;
    }
    $time = floor($time / 60);
    if (strlen($time) == 1) {
        $time = '0' . $time;
    }
    $links = array_merge($main_format, $adaptive_format);
    $res = array();
    foreach ($links as $link) {
        $type = explode('/', strstr($link['mimeType'], ';', true));
        array_push($res, array(
            'link' => $link['url'],
            'quality' => $link['qualityLabel'],
            'width' => $link['width'],
            'height' => $link['height'],
            'audio' => $link['audioQuality'],
            'type' => $type[0],
            'format' => $type[1],
            'size' => $link['contentLength']
        ));
        //rsort($res, $link['height']);
    }
    if ($id === null) {
        $out = '<div class="result">Не найдена ссылка для скачивания.</div>';
    } else {
        $out = '<div class="res-video">
            <div class="video-right">
                <div class="video-thumb">
                <a href="https://youtube.com/watch?v=' . $id . '" target="_blank">
                    <img src="' . $img . '" alt="' . $title . '">
                    </a>
                </div>
                
            </div>
            <div class="video-left">
                <span class="video-title">' . $title . '</span>
                <div class="video-info">
                    <div class="time">
                    <img class="video-icon" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDYwIDYwIiBzdHlsZT0iZW5hYmxlLWJhY2tncm91bmQ6bmV3IDAgMCA2MCA2MDsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTJweCIgaGVpZ2h0PSI1MTJweCI+CjxnPgoJPHBhdGggZD0iTTMwLDBDMTMuNDU4LDAsMCwxMy40NTgsMCwzMHMxMy40NTgsMzAsMzAsMzBzMzAtMTMuNDU4LDMwLTMwUzQ2LjU0MiwwLDMwLDB6IE0zMCw1OEMxNC41NjEsNTgsMiw0NS40MzksMiwzMCAgIFMxNC41NjEsMiwzMCwyczI4LDEyLjU2MSwyOCwyOFM0NS40MzksNTgsMzAsNTh6IiBmaWxsPSIjMDAwMDAwIi8+Cgk8cGF0aCBkPSJNMzAsNmMtMC41NTIsMC0xLDAuNDQ3LTEsMXYyM0gxNGMtMC41NTIsMC0xLDAuNDQ3LTEsMXMwLjQ0OCwxLDEsMWgxNmMwLjU1MiwwLDEtMC40NDcsMS0xVjdDMzEsNi40NDcsMzAuNTUyLDYsMzAsNnoiIGZpbGw9IiMwMDAwMDAiLz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />
                    ' . $time . ':' . $min . ':' . $sec . ' </div>
                    <div class="counts">
                     <img class="video-icon" src="data:image/svg+xml;utf8;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IS0tIEdlbmVyYXRvcjogQWRvYmUgSWxsdXN0cmF0b3IgMTkuMC4wLCBTVkcgRXhwb3J0IFBsdWctSW4gLiBTVkcgVmVyc2lvbjogNi4wMCBCdWlsZCAwKSAgLS0+CjxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgdmVyc2lvbj0iMS4xIiBpZD0iQ2FwYV8xIiB4PSIwcHgiIHk9IjBweCIgdmlld0JveD0iMCAwIDU5LjIgNTkuMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTkuMiA1OS4yOyIgeG1sOnNwYWNlPSJwcmVzZXJ2ZSIgd2lkdGg9IjUxMnB4IiBoZWlnaHQ9IjUxMnB4Ij4KPGc+Cgk8cGF0aCBkPSJNNTEuMDYyLDIxLjU2MWMtMTEuODg5LTExLjg4OS0zMS4yMzItMTEuODg5LTQzLjEyMSwwTDAsMjkuNTAxbDguMTM4LDguMTM4YzUuOTQ0LDUuOTQ0LDEzLjc1Miw4LjkxNywyMS41NjEsOC45MTcgICBzMTUuNjE2LTIuOTcyLDIxLjU2MS04LjkxN2w3Ljk0MS03Ljk0MUw1MS4wNjIsMjEuNTYxeiBNNDkuODQ1LDM2LjIyNWMtMTEuMTA5LDExLjEwOC0yOS4xODQsMTEuMTA4LTQwLjI5MywwbC02LjcyNC02LjcyNCAgIGw2LjUyNy02LjUyN2MxMS4xMDktMTEuMTA4LDI5LjE4NC0xMS4xMDgsNDAuMjkzLDBsNi43MjQsNi43MjRMNDkuODQ1LDM2LjIyNXoiIGZpbGw9IiMwMDAwMDAiLz4KCTxwYXRoIGQ9Ik0yOC41NzIsMjEuNTdjLTMuODYsMC03LDMuMTQtNyw3YzAsMC41NTIsMC40NDgsMSwxLDFzMS0wLjQ0OCwxLTFjMC0yLjc1NywyLjI0My01LDUtNWMwLjU1MiwwLDEtMC40NDgsMS0xICAgUzI5LjEyNSwyMS41NywyOC41NzIsMjEuNTd6IiBmaWxsPSIjMDAwMDAwIi8+Cgk8cGF0aCBkPSJNMjkuNTcyLDE2LjU3Yy03LjE2OCwwLTEzLDUuODMyLTEzLDEzczUuODMyLDEzLDEzLDEzczEzLTUuODMyLDEzLTEzUzM2Ljc0MSwxNi41NywyOS41NzIsMTYuNTd6IE0yOS41NzIsNDAuNTcgICBjLTYuMDY1LDAtMTEtNC45MzUtMTEtMTFzNC45MzUtMTEsMTEtMTFzMTEsNC45MzUsMTEsMTFTMzUuNjM4LDQwLjU3LDI5LjU3Miw0MC41N3oiIGZpbGw9IiMwMDAwMDAiLz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8Zz4KPC9nPgo8L3N2Zz4K" />
                     ' . $view . '</div>
                </div>
                <table class="table-download" style="color: #444; font-size: 10pt; margin-bottom: 3px; margin-left:auto; margin-right:auto;">
                    <thead>
                        <tr>
                            <th>Девайс</th>
                            <th>Скачать</th>
                            <th>Разрешение</th>
                        </tr>
                    </thead>
                    <tbody>';
        foreach ($res as $item) {
            if ($item['audio'] != null && $item['height'] != null && $item['link'] !== null && $item['format'] !== 'webm') {
                $out .= '<tr>';
                $out .= '<td>';
                if((int)$item["quality"] <= 480) {
                    $out .= '<span>Для телефона</span>';
                } else if ((int)$item["quality"] >= 720) {
                     $out .= '<span>Для компьютера и планшета</span>';
                }
                $out .= '</td>';
                
                $out .= '<td><a download="' . $title . '.' . $item['format'] . '" href="' . $item['link'] . '&video_id=' . $id . '&title=' . $title . '" class="download-link">
                <img src="data:image/svg+xml;base64,
PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiB2ZXJzaW9uPSIxLjEiIGlkPSJDYXBhXzEiIHg9IjBweCIgeT0iMHB4IiB2aWV3Qm94PSIwIDAgNTEyIDUxMiIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgNTEyIDUxMjsiIHhtbDpzcGFjZT0icHJlc2VydmUiIHdpZHRoPSI1MTIiIGhlaWdodD0iNTEyIiBjbGFzcz0iIj48Zz48Zz4KCTxnPgoJCTxwYXRoIGQ9Ik0zODIuNTYsMjMzLjM3NkMzNzkuOTY4LDIyNy42NDgsMzc0LjI3MiwyMjQsMzY4LDIyNGgtNjRWMTZjMC04LjgzMi03LjE2OC0xNi0xNi0xNmgtNjRjLTguODMyLDAtMTYsNy4xNjgtMTYsMTZ2MjA4aC02NCAgICBjLTYuMjcyLDAtMTEuOTY4LDMuNjgtMTQuNTYsOS4zNzZjLTIuNjI0LDUuNzI4LTEuNiwxMi40MTYsMi41MjgsMTcuMTUybDExMiwxMjhjMy4wNCwzLjQ4OCw3LjQyNCw1LjQ3MiwxMi4wMzIsNS40NzIgICAgYzQuNjA4LDAsOC45OTItMi4wMTYsMTIuMDMyLTUuNDcybDExMi0xMjhDMzg0LjE5MiwyNDUuODI0LDM4NS4xNTIsMjM5LjEwNCwzODIuNTYsMjMzLjM3NnoiIGRhdGEtb3JpZ2luYWw9IiMwMDAwMDAiIGNsYXNzPSJhY3RpdmUtcGF0aCIgc3R5bGU9ImZpbGw6I0ZGRkZGRiIgZGF0YS1vbGRfY29sb3I9IiMwMDAwMDAiPjwvcGF0aD4KCTwvZz4KPC9nPjxnPgoJPGc+CgkJPHBhdGggZD0iTTQzMiwzNTJ2OTZIODB2LTk2SDE2djEyOGMwLDE3LjY5NiwxNC4zMzYsMzIsMzIsMzJoNDE2YzE3LjY5NiwwLDMyLTE0LjMwNCwzMi0zMlYzNTJINDMyeiIgZGF0YS1vcmlnaW5hbD0iIzAwMDAwMCIgY2xhc3M9ImFjdGl2ZS1wYXRoIiBzdHlsZT0iZmlsbDojRkZGRkZGIiBkYXRhLW9sZF9jb2xvcj0iIzAwMDAwMCI+PC9wYXRoPgoJPC9nPgo8L2c+PC9nPiA8L3N2Zz4=" />
                Скачать <span>видео</span></a></td>';
                 $out .= '<td>' . $item["quality"] . ' <span class="label label-default">' . $item["format"] . '</span></td>';
                $out .= '</tr>';
            }

        }

        $out .= '</tbody>
                </table>
            </div>
        </div>';
    }
    echo $out;
    wp_die();
}

function download_button()
{
    if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
        add_filter('mce_external_plugins', 'download_plugin');
        add_filter('mce_buttons_3', 'download_register_button');
    }
}

add_action('init', 'download_button');

function download_register_button($buttons)
{
    array_push($buttons, "download");
    return $buttons;
}

function download_plugin($plugin_array)
{
    $plugin_array['download'] = plugin_dir_url(__FILE__) . 'assets/js/youtube-button.js';
    return $plugin_array;
}

