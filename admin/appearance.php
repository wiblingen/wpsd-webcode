<?php
session_set_cookie_params(0, "/");
session_name("WPSD_Session");
session_id('wpsdsession');
session_start();

require_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/version.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/ircddblocal.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';

// --- CONFIGURATION LOADING ---
unset($_SESSION['WPSDdashConfig']);
unset($_SESSION['MMDVMHostConfigs']);
checkSessionValidity();

// Explicitly load config if not populated
if (!isset($_SESSION['MMDVMHostConfigs']) || empty($_SESSION['MMDVMHostConfigs'])) {
    $_SESSION['MMDVMHostConfigs'] = getMMDVMConfigContent();
}

$displayType = getConfigItem("General", "Display", $_SESSION['MMDVMHostConfigs']);

// --- THEME LOADING ---
$themes_filepath = $_SERVER['DOCUMENT_ROOT'].'/includes/wpsd-themes.json';
$themes = [];
if (file_exists($themes_filepath)) {
    $json_content = file_get_contents($themes_filepath);
    $decoded_themes = json_decode($json_content, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_themes)) {
        $themes = $decoded_themes;
    }
}

// --- ACTIVE INI LOADING ---
$default_theme_key = 'WPSD Light';
$filepath_ini = '/etc/wpsd-css.ini';
$parsed_ini = null;
$use_default_default_for_ini_file = false;

if (file_exists($filepath_ini)) {
    $parsed_ini_content = parse_ini_file($filepath_ini, true);
    if ($parsed_ini_content === false || empty($parsed_ini_content)) {
        $use_default_default_for_ini_file = true;
    } else {
        $parsed_ini = $parsed_ini_content;
    }
} else {
    $use_default_default_for_ini_file = true;
}

if ($use_default_default_for_ini_file) {
    if (isset($themes[$default_theme_key])) {
        $parsed_ini = $themes[$default_theme_key];
        $content = "";
        foreach ($themes[$default_theme_key] as $section => $values) {
            if (!is_array($values)) continue;
            $content .= "[" . $section . "]\n";
            foreach ($values as $key => $value) {
                $content .= $key . "=" . $value . "\n";
            }
            $content .= "\n";
        }
        $temp_ini_path = "/tmp/wpsd_default_default.ini";
        file_put_contents($temp_ini_path, $content);
        exec('sudo cp ' . escapeshellarg($temp_ini_path) . ' ' . escapeshellarg($filepath_ini));
        exec('sudo chmod 644 ' . escapeshellarg($filepath_ini));
        exec('sudo chown root:root ' . escapeshellarg($filepath_ini));
    } else {
        $parsed_ini = [];
    }
}

function getThemeVal($arr, $sec, $key, $default) {
    return isset($arr[$sec][$key]) && $arr[$sec][$key] != 'none' ? $arr[$sec][$key] : $default;
}

// INITIAL CSS VARIABLES
$c_Page         = getThemeVal($parsed_ini, 'Background', 'PageColor', '#ffffff');
$c_Content      = getThemeVal($parsed_ini, 'Background', 'ContentColor', '#f0f0f0');
$c_Banners      = getThemeVal($parsed_ini, 'Background', 'BannersColor', '#333333');
$c_Navbar       = getThemeVal($parsed_ini, 'Background', 'NavbarColor', '#444444');
$c_NavHover     = getThemeVal($parsed_ini, 'Background', 'NavbarHoverColor', '#555555');
$c_TableOdd     = getThemeVal($parsed_ini, 'Background', 'TableRowBgOddColor', '#ffffff');
$c_TableEven    = getThemeVal($parsed_ini, 'Background', 'TableRowBgEvenColor', '#eeeeee');

$t_Main         = getThemeVal($parsed_ini, 'Text', 'TextColor', '#000000');
$t_Banners      = getThemeVal($parsed_ini, 'Text', 'BannersColor', '#ffffff');
$t_Navbar       = getThemeVal($parsed_ini, 'Text', 'NavbarColor', '#ffffff');
$t_Section      = getThemeVal($parsed_ini, 'Text', 'TextSectionColor', '#000000');
$t_Links        = getThemeVal($parsed_ini, 'Text', 'TextLinkColor', '#0000ff');

// Detect active preset
$selected_theme_on_load = "custom";
foreach ($themes as $theme_key => $theme_data) {
    if (isset($theme_data['Background']['PageColor']) &&
        $theme_data['Background']['PageColor'] == $c_Page &&
        $theme_data['Background']['ContentColor'] == $c_Content) {
        $selected_theme_on_load = $theme_key;
        break;
    }
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
    <head>
        <meta name="language" content="English" />
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
        <meta http-equiv="Expires" content="0" />
        <title>WPSD Dashboard - Appearance Settings</title>
        <script type="text/javascript" src="/js/jquery.min.js?version=<?php echo $versionCmd; ?>"></script>
        <script type="text/javascript" src="/css/farbtastic/farbtastic.min.js?version=<?php echo $versionCmd; ?>"></script>
        <link rel="stylesheet" type="text/css" href="/css/farbtastic/farbtastic.css" />
        <link rel="stylesheet" type="text/css" href="/css/font-awesome-4.7.0/css/font-awesome.min.css" />

        <style id="wpsd-live-preview"></style>

        <?php include_once $_SERVER['DOCUMENT_ROOT'].'/config/browserdetect.php'; ?>

        <style type="text/css" media="screen">
            /* --- BASE LAYOUT --- */
            body {
                background-color: <?php echo $c_Page; ?>;
                color: <?php echo $t_Main; ?>;
            }

            .profile-wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 20px;
                padding: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }

            .profile-card {
                width: 100%;
                background-color: <?php echo $c_Content; ?>;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                overflow: hidden;
                color: <?php echo $t_Main; ?>;
            }

            .profile-header {
                padding: 12px;
                font-weight: 700;
                font-size: 1.1rem;
                text-transform: uppercase;
                text-align: center;
                background-color: <?php echo $c_Banners; ?>;
                color: <?php echo $t_Banners; ?>;
                border-bottom: 1px solid rgba(0,0,0,0.1);
                font-family: 'Source Sans Pro', sans-serif;
            }

            .profile-body { padding: 20px; }

            .profile-btn {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                height: 38px;
                line-height: 1; /* Forces alignment */
                padding: 0 20px;
                background-color: <?php echo $c_Navbar; ?>;
                color: <?php echo $t_Navbar; ?>;
                border: 1px solid rgba(0,0,0,0.1);
                border-radius: 4px;
                font-weight: bold;
                cursor: pointer;
                text-transform: uppercase;
                font-size: 0.9em;
                transition: all 0.2s;
                font-family: 'Source Sans Pro', sans-serif;
                box-sizing: border-box;
                text-decoration: none;
                margin: 5px;
            }

            .profile-btn i {
                margin-right: 8px;
            }

            .profile-btn:hover {
                background-color: <?php echo $c_NavHover; ?>;
                filter: brightness(110%);
            }

            .btn-full { width: 100%; margin: 5px 0; }

            /* INPUTS */
            select, input[type="text"] {
                width: 100%;
                padding: 10px;
                margin-bottom: 10px;
                background-color: rgba(128,128,128,0.1);
                border: 1px solid rgba(128,128,128,0.3);
                color: inherit;
                border-radius: 4px;
                box-sizing: border-box;
                font-family: 'Source Sans Pro', sans-serif;
            }

            option { background-color: #333; color: #fff; }

            /* Color Picker Input Override */
            input.colorwell {
                cursor: pointer;
                font-weight: bold;
                text-align: center;
                border: 1px solid rgba(0,0,0,0.3);
            }

            /* TOGGLE GROUP */
            .toggle-group {
                display: flex;
                width: 100%;
                gap: 2px;
                background: rgba(0,0,0,0.1);
                padding: 4px;
                border-radius: 6px;
                margin-bottom: 10px;
            }
            .toggle-group input[type="radio"] { display: none; }
            .toggle-group label {
                flex: 1;
                text-align: center;
                padding: 8px;
                cursor: pointer;
                background: transparent;
                opacity: 0.7;
                font-weight: bold;
                border-radius: 4px;
                transition: all 0.2s;
            }
            .toggle-group input[type="radio"]:checked + label {
                background-color: <?php echo $c_Navbar; ?>;
                color: <?php echo $t_Navbar; ?>;
                opacity: 1;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            }

            /* FILE UPLOAD */
            .file-upload-label {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 30px;
                border: 2px dashed rgba(128,128,128,0.4);
                border-radius: 6px;
                background-color: rgba(0,0,0,0.05);
                cursor: pointer;
                transition: all 0.2s;
                width: 100%;
                box-sizing: border-box;
                font-family: 'Source Sans Pro', sans-serif;
                margin-bottom: 15px;
            }
            .file-upload-label:hover {
                border-color: <?php echo $c_Navbar; ?>;
                background-color: rgba(0,0,0,0.1);
            }
            .inputfile { width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; position: absolute; z-index: -1; }

            /* MODAL */
            #customizer-overlay {
                display: none;
                position: fixed;
                top: 0; left: 0;
                width: 100%; height: 100%;
                background: rgba(0, 0, 0, 0.85);
                z-index: 9999;
                align-items: center;
                justify-content: center;
                backdrop-filter: blur(5px);
            }

            .customizer-box {
                background-color: <?php echo $c_Content; ?>;
                color: <?php echo $t_Main; ?>;
                border: 1px solid rgba(255,255,255,0.1);
                padding: 20px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
                width: 90%;
                max-height: 90vh;
                overflow-y: auto;
                display: flex;
                flex-direction: column;
                position: relative;
                justify-self: center;
                text-align: -webkit-center;
            }

            .customizer-box h3 {
                border-bottom: 1px solid rgba(128,128,128,0.2);
                padding-bottom: 10px;
                margin-top: 20px;
                color: <?php echo $t_Section; ?>;
            }

            .wpsd-grid-container {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
                gap: 15px;
            }

            .wpsd-setting-card {
                background: rgba(128,128,128,0.05);
                padding: 10px;
                border-radius: 4px;
                border: 1px solid rgba(128,128,128,0.1);
                text-align: center;
            }

            .wpsd-setting-card label {
                font-size: 0.9em; margin-bottom: 5px; display: block; font-weight: bold;
                color: <?php echo $t_Section; ?>;
            }

            .desc { font-size: 0.75em; opacity: 0.7; margin-top: 4px; min-height: 2em; }

            #colorpicker {
                position: fixed;
                z-index: 10001;
                background: #333;
                padding: 10px;
                border-radius: 4px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.5);
                border: 1px solid #555;
            }

            .modal-close-btn {
                position: absolute; top: 15px; right: 15px;
                background: none; border: none;
                color: <?php echo $t_Main; ?>;
                font-size: 1.5em; cursor: pointer; opacity: 0.6;
            }
            .modal-close-btn:hover { opacity: 1; color: #e74c3c; }
        </style>

        <script type="text/javascript">
            const WPSD_THEMES = <?php echo json_encode($themes); ?>;
            const WPSD_CURRENT_CONFIG_FROM_INI = <?php echo json_encode($parsed_ini); ?>;
            const INITIAL_SELECTED_THEME_KEY = <?php echo json_encode($selected_theme_on_load); ?>;

            function cssDownload() { window.location.href = "/admin/advanced/css_download.php"; }
            function cssUpload() { document.getElementById('cssUpload').submit(); }
            function toggleCustomizer() { $('#customizer-overlay').fadeToggle(); }

            function updateLivePreview() {
                function getVal(section, key) {
                    var name = section + "[" + key + "]";
                    var selector = 'input[name="' + name.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '"]';
                    return $(selector).val();
                }

                var bgPage      = getVal('Background', 'PageColor');
                var bgContent   = getVal('Background', 'ContentColor');
                var bgBanners   = getVal('Background', 'BannersColor');
                var bgNavbar    = getVal('Background', 'NavbarColor');
                var bgNavbarHover = getVal('Background', 'NavbarHoverColor');

                var tMain       = getVal('Text', 'TextColor');
                var tSection    = getVal('Text', 'TextSectionColor');
                var tBanners    = getVal('Text', 'BannersColor');
                var tNavbar     = getVal('Text', 'NavbarColor');

                var styles = `
                    body, html { background-color: ${bgPage} !important; color: ${tMain} !important; }

                    .profile-card, .customizer-box, .container, .content, .wpsd-section {
                        background-color: ${bgContent} !important;
                        color: ${tMain} !important;
                    }

                    .profile-header, .header, .footer {
                        background-color: ${bgBanners} !important;
                        color: ${tBanners} !important;
                    }

                    .profile-btn, .navbar, .navbar a {
                        background-color: ${bgNavbar} !important;
                        color: ${tNavbar} !important;
                    }

                    .profile-btn:hover, .navbar a:hover {
                        background-color: ${bgNavbarHover} !important;
                    }

                    .toggle-group input[type="radio"]:checked + label {
                         background-color: ${bgNavbar} !important;
                         color: ${tNavbar} !important;
                    }

                    .wpsd-setting-card label, .customizer-box h3, h2, h3 {
                        color: ${tSection} !important;
                    }
                `;
                $('#wpsd-live-preview').html(styles);
            }

            $(document).ready(function() {
                var f = $.farbtastic('#colorpicker');
                var p = $('#colorpicker').css('opacity', 1).hide();
                var selected;

                function getContrastColor(hex){
                    if (!hex || hex === 'none') return '#000000';
                    hex = hex.replace("#", "");
                    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
                    var r = parseInt(hex.substr(0,2),16), g = parseInt(hex.substr(2,2),16), b = parseInt(hex.substr(4,2),16);
                    return (((r*299)+(g*587)+(b*114))/1000 >= 128) ? '#000000' : '#FFFFFF';
                }

                // Initialize Color Wells
                $('.colorwell').each(function () {
                    var val = $(this).val();
                    $(this).css({ 'background-color': val, 'color': getContrastColor(val) });
                }).on('change input', function() {
                    var val = $(this).val();
                    $(this).css({ 'background-color': val, 'color': getContrastColor(val) });
                    updateLivePreview();
                }).focus(function() {
                    if (selected) $(selected).removeClass('colorwell-selected');
                    f.linkTo(this);
                    selected = this;

                    var rect = this.getBoundingClientRect();
                    p.css({
                        top: (rect.bottom + 5) + 'px',
                        left: (rect.left) + 'px'
                    }).show();
                });

                $(document).mousedown(function(event) {
                    if (!$(event.target).closest('#colorpicker').length && !$(event.target).is('.colorwell')) {
                        p.hide();
                    }
                });

                // Theme Selector
                $('#themeSelector').val(INITIAL_SELECTED_THEME_KEY).change(function() {
                    const theme = $(this).val();
                    let data = (theme === 'custom') ? WPSD_CURRENT_CONFIG_FROM_INI : WPSD_THEMES[theme];

                    if (!data) return;

                    for (const sec in data) {
                       for (const key in data[sec]) {
                           const name = sec + '[' + key + ']';
                           const el = $('input[name="' + name.replace(/\[/g, '\\[').replace(/\]/g, '\\]') + '"]');
                           if (el.length) {
                               var val = data[sec][key];
                               el.val(val);
                               if (el.hasClass('colorwell')) {
                                   el.css({ 'background-color': val, 'color': getContrastColor(val) });
                               }
                           }
                       }
                    }
                    updateLivePreview();
                });
            });
        </script>
    </head>
    <body>

    <div id="customizer-overlay">
        <div id="colorpicker"></div>
        <div class="customizer-box">
            <button class="modal-close-btn" onclick="toggleCustomizer()"><i class="fa fa-times"></i></button>
            <h2 style="text-align:center; margin-top:0; border-bottom:1px solid rgba(128,128,128,0.2); padding-bottom:15px;">Advanced Theme Customization</h2>

            <form action="" method="post" name="edit-css-custom">
                <?php
                foreach($parsed_ini as $section=>$values_in_section) {
                    if (!is_array($values_in_section)) continue;

                    echo "<h3>" . htmlspecialchars($section) . "</h3>";
                    echo "<div class='wpsd-grid-container'>";

                    foreach($values_in_section as $key=>$value) {
                        $key_display = htmlspecialchars($key);
                        $value_display = htmlspecialchars($value);
                        $input_name = htmlspecialchars($section) . "[" . htmlspecialchars($key) . "]";

                        echo "<div class='wpsd-setting-card'>";
                        echo "<label>$key_display</label>";

                        if (endsWith($key, 'Color')) {
                            echo "<input type='text' class='colorwell' name='$input_name' value='$value_display' />";
                            echo "<div class='desc'>Color Value (Hex)</div>";
                        }
                        elseif (strpos($key, 'FontSize') !== false || endsWith($key, 'HeardRows')) {
                            echo "<input type='text' name='$input_name' value='$value_display' style='text-align:center;' />";
                            echo "<div class='desc'>Value (px or count)</div>";
                        }
                        else {
                            echo "<input type='text' name='$input_name' value='$value_display' />";
                            echo "<div class='desc'></div>";
                        }

                        echo "</div>";
                    }
                    echo "</div>";
                }
                ?>
                <input type="hidden" name="Background[PageColor]" value="required_flag">

                <div style="margin-top:20px; text-align:center; border-top:1px solid rgba(128,128,128,0.2); padding-top:20px;">
                     <button type="submit" class="profile-btn" style="width:200px; height:45px;">Save & Apply</button>
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <?php include $_SERVER['DOCUMENT_ROOT'].'/admin/advanced/header-menu.inc'; ?>
        <div class="profile-wrapper">

            <?php
            // POST HANDLING
            $filepath = '/tmp/bW1kd4jg6b3N0DQo.tmp';

            if (!empty($_POST['CallLookupProvider'])) {
                exec('sudo sed -i "/CallLookupProvider = /c\\\CallLookupProvider = '.escapeshellcmd($_POST['CallProvider']).'" ' . $config_file . '');
                echo '<script>setTimeout(function() { window.location=window.location;},0);</script>';
                die();
            }
            if (isset($_POST['phoneticCallsigns'])) {
                $phoneticCallsigns = escapeshellcmd($_POST['phoneticCallsigns']);
                $output = shell_exec("grep -c '^PhoneticCallsigns =' $config_file");
                if (trim($output) == '0') {
                    exec("echo 'PhoneticCallsigns = $phoneticCallsigns' | sudo tee -a $config_file > /dev/null");
                } else {
                    exec("sudo sed -i '/PhoneticCallsigns = /c\\PhoneticCallsigns = $phoneticCallsigns' $config_file");
                }
                echo '<script>setTimeout(function() { window.location=window.location;},0);</script>';
                die();
            }
            if (file_exists($filepath_ini)) {
                exec('sudo cp '.$filepath_ini.' '.$filepath);
                exec('sudo chown www-data:www-data '.$filepath);
                exec('sudo chmod 664 '.$filepath);
            }

            if($_POST) {
                if (!empty($_POST['cssUpload'])) {
                    if (isset($_FILES['cssFile']) && $_FILES['cssFile']['error'] === UPLOAD_ERR_OK) {
                        $target_dir = "/tmp/css_restore/";
                        shell_exec("sudo rm -rf $target_dir 2>&1");
                        shell_exec("mkdir $target_dir 2>&1");
                        $filename = $_FILES["cssFile"]["name"];
                        $source = $_FILES["cssFile"]["tmp_name"];
                        $name = explode(".", $filename);

                        if (strtolower(end($name)) == 'zip') {
                            $target_path = $target_dir.$filename;
                            if(move_uploaded_file($source, $target_path)) {
                                $zip = new ZipArchive();
                                if ($zip->open($target_path) === true) {
                                    $zip->extractTo($target_dir);
                                    $zip->close();
                                    unlink($target_path);
                                    exec("sudo mv -v -f /tmp/css_restore/wpsd-css.ini ".$filepath_ini." 2>&1");
                                    echo '<script>window.location=window.location;</script>';
                                }
                            }
                        }
                    }
                }
                elseif (isset($_POST['Background'])) {
                    function update_ini_file($data, $filepath_to_update) {
                        $content = "";
                        foreach($data as $section=>$values) {
                            if (!is_array($values)) continue;
                            $section_for_ini = str_replace(" ", "_", $section);
                            $content .= "[".$section_for_ini."]\n";
                            foreach($values as $key=>$value) {
                                $content .= $key."=".($value ?: "none")."\n";
                            }
                            $content .= "\n";
                        }
                        if (!$handle = fopen($filepath_to_update, 'w')) return false;
                        fwrite($handle, $content);
                        fclose($handle);
                        return true;
                    }
                    if (update_ini_file($_POST, $filepath)) {
                        exec('sudo cp '.$filepath.' '.$filepath_ini);
                        exec('sudo chmod 644 '.$filepath_ini);
                        exec('sudo chown root:root '.$filepath_ini);
                        echo '<script>window.location=window.location.href.split("?")[0];</script>';
                        die();
                    }
                }
            }
            ?>

            <div class="profile-card">
                <div class="profile-header">Dashboard Theme</div>
                <div class="profile-body">
                    <form action="" method="post" name="edit-css" style="display:flex; flex-direction:column; gap:15px; align-items:center;">
                        <label style="width:100%; text-align:left; font-weight:bold;">Select Preset:</label>
                        <select id="themeSelector">
                            <option value="" disabled>-- Select a Theme --</option>
                            <?php
                            if ($selected_theme_on_load === 'custom') echo '<option value="custom" selected>Custom Configuration</option>';
                            foreach ($themes as $key => $theme_data_loop):
                                $selected_attr = ($selected_theme_on_load === $key) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($key) . '" ' . $selected_attr . '>' . htmlspecialchars($key) . '</option>';
                            endforeach;
                            ?>
                        </select>
                        <div style="display:flex; gap:10px; width:100%;">
                            <button type="submit" class="profile-btn" style="flex:1;">Apply Selected Theme</button>
                            <button type="button" class="profile-btn" style="flex:1; background-color: #2980b9;" onclick="toggleCustomizer()"><i class="fa fa-paint-brush"></i> Customize</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (strcasecmp(trim($displayType), 'OLED') == 0) { ?>
            <div class="profile-card">
                <div class="profile-header">Hardware Options</div>
                <div class="profile-body">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <strong>OLED Display Control</strong>
                        <button class="profile-btn" onclick="var xhr=new XMLHttpRequest();xhr.open('GET','/admin/OLED_ajax.php?action=toggle',true);xhr.send();">
                            <i class="fa fa-power-off"></i> Toggle On/Off
                        </button>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="profile-card">
                <div class="profile-header">General Settings</div>
                <div class="profile-body">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                        <div>
                            <form method="post" action="">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Callsign Lookup Provider</label>
                                <div class="toggle-group">
                                    <input type="radio" name="CallProvider" value="RadioID" id="rad-rid" <?php if (isset($_SESSION['WPSDdashConfig']['WPSD']['CallLookupProvider']) && $_SESSION['WPSDdashConfig']['WPSD']['CallLookupProvider'] == "RadioID") echo 'checked'; ?>>
                                    <label for="rad-rid">RadioID</label>

                                    <input type="radio" name="CallProvider" value="QRZ" id="rad-qrz" <?php if (isset($_SESSION['WPSDdashConfig']['WPSD']['CallLookupProvider']) && $_SESSION['WPSDdashConfig']['WPSD']['CallLookupProvider'] == "QRZ") echo 'checked'; ?>>
                                    <label for="rad-qrz">QRZ</label>
                                </div>
                                <button type="submit" name="CallLookupProvider" value="1" class="profile-btn btn-full">Apply</button>
                            </form>
                        </div>
                        <div>
                            <form method="post" action="">
                                <label style="display:block; margin-bottom:5px; font-weight:bold;">Phonetic Callsigns</label>
                                <div class="toggle-group">
                                    <input type="radio" name="phoneticCallsigns" value="0" id="pho-off" <?php if (!isset($_SESSION['WPSDdashConfig']['WPSD']['PhoneticCallsigns']) || $_SESSION['WPSDdashConfig']['WPSD']['PhoneticCallsigns'] == "0") echo 'checked'; ?>>
                                    <label for="pho-off">Disabled</label>

                                    <input type="radio" name="phoneticCallsigns" value="1" id="pho-on" <?php if (isset($_SESSION['WPSDdashConfig']['WPSD']['PhoneticCallsigns']) && $_SESSION['WPSDdashConfig']['WPSD']['PhoneticCallsigns'] == "1") echo 'checked'; ?>>
                                    <label for="pho-on">Enabled</label>
                                </div>
                                <button type="submit" name="phoneticCallsignsSubmit" value="1" class="profile-btn btn-full">Apply</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-header">Backup & Restore</div>
                <div class="profile-body">
                    <form id="cssUpload" action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="cssUpload" value="1" />

                        <div style="position:relative; margin-bottom:15px;">
                            <input id="fileid" name="cssFile" type="file" class="inputfile" onchange="cssUpload()" accept=".zip" />
                            <label for="fileid" class="file-upload-label">
                                <i class="fa fa-cloud-upload" style="font-size:1.5em; margin-right:10px;"></i>
                                <span>Click to Upload Appearance Backup (.zip)</span>
                            </label>
                        </div>

                        <button type="button" class="profile-btn btn-full" onclick="cssDownload()">
                            <i class="fa fa-download"></i> Download Current Settings
                        </button>
                    </form>
                </div>
            </div>

        </div>
        <?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
    </div>
    </body>
</html>
