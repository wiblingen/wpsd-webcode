<?php

if (!isset($_SESSION) || !is_array($_SESSION)) {
    session_id('wpsdsession');
    session_start();

    unset($_SESSION['CSSConfigs']);
    include_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';
    checkSessionValidity();
}

require_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/version.php';

// CSS Defaults
$backgroundContent = "#1a1a1a";
$textContent = "inherit";
$tableBorderColor = "#333";
$backgroundServiceCellActiveColor = "#27ae60";
$backgroundServiceCellInactiveColor = "#8C0C26";
$tableRowOddBg = "#333";
$tableRowEvenBg = "#444";
$textLinks = "#2196F3";

if (isset($_SESSION['CSSConfigs'])) {
    if (isset($_SESSION['CSSConfigs']['Background']['ContentColor'])) $backgroundContent = $_SESSION['CSSConfigs']['Background']['ContentColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['ServiceCellActiveColor'])) $backgroundServiceCellActiveColor = $_SESSION['CSSConfigs']['Background']['ServiceCellActiveColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['ServiceCellInactiveColor'])) $backgroundServiceCellInactiveColor = $_SESSION['CSSConfigs']['Background']['ServiceCellInactiveColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['TableRowBgOddColor'])) $tableRowOddBg = $_SESSION['CSSConfigs']['Background']['TableRowBgOddColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['TableRowBgEvenColor'])) $tableRowEvenBg = $_SESSION['CSSConfigs']['Background']['TableRowBgEvenColor'];
    if (isset($_SESSION['CSSConfigs']['Text']['TextColor'])) $textContent = $_SESSION['CSSConfigs']['Text']['TextColor'];
    if (isset($_SESSION['CSSConfigs']['ExtraSettings']['TableBorderColor'])) $tableBorderColor = $_SESSION['CSSConfigs']['ExtraSettings']['TableBorderColor'];
    if (isset($_SESSION['CSSConfigs']['Text']['TextLinkColor'])) $textLinks = $_SESSION['CSSConfigs']['Text']['TextLinkColor'];
}

if ($_SERVER["PHP_SELF"] == "/admin/profile_manager.php") {
    header('Cache-Control: no-cache');

    $profile_dir = "/etc/WPSD_config_mgr";
    
    // Get Current Profile using the CLI tool
    $current_profile_friendly = shell_exec("sudo /usr/local/sbin/wpsd-profiles -cp");
    $current_profile_friendly = trim($current_profile_friendly); 
    $current_profile_friendly = trim($current_profile_friendly, '"'); 
    
    $current_profile_raw = str_replace(" ", "_", $current_profile_friendly);
    
    // Determine Status
    if (!empty($current_profile_friendly) && file_exists("$profile_dir/$current_profile_raw")) {
        $saved = date("M d Y @ h:i A", filemtime("$profile_dir/$current_profile_raw"));
        $activeColor = isset($textModeCellActiveColor) ? $textModeCellActiveColor : '#2ecc71';
        $current_profile_display = "<h2 style='margin:0; color:".$activeColor."; font-size: 1.5rem;'>".$current_profile_friendly."</h2><div class='profile-meta'>Saved: ".$saved."</div>";
        $no_raw_profile = false;
    } else {
        $no_raw_profile = true;
        $current_profile_display = "<div class='profile-meta'>No saved profiles yet or current profile deleted.</div>";
    }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
    <head>
        <meta name="language" content="English" />
        <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
        <meta http-equiv="pragma" content="no-cache" />
        <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
        <meta http-equiv="Expires" content="0" />
        <title>WPSD <?php echo __( 'Dashboard' )."";?> - Profile Manager</title>
        <link rel="stylesheet" type="text/css" href="/css/font-awesome-4.7.0/css/font-awesome.min.css" />
        <?php include_once $_SERVER['DOCUMENT_ROOT'].'/config/browserdetect.php'; ?>
        <script type="text/javascript" src="/js/jquery.min.js?version=<?php echo $versionCmd; ?>"></script>
        <script type="text/javascript" src="/js/functions.js?version=<?php echo $versionCmd; ?>"></script>
        
        <style>
            .profile-wrapper {
                display: flex;
                justify-content: center;
                flex-wrap: wrap;
                gap: 20px;
                padding-top: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            .profile-card {
                background-color: <?php echo $backgroundContent; ?>;
                border: 1px solid <?php echo $tableBorderColor; ?>;
                border-radius: 8px;
                padding: 0;
                flex: 1;
                min-width: 300px;
                max-width: 500px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.25);
                color: <?php echo $textContent; ?>;
                overflow: hidden;
                position: relative;
                display: flex;
                flex-direction: column;
            }

            .profile-header {
                padding: 15px 20px;
                font-weight: 700;
                font-size: 1.2rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                border-bottom: 1px solid <?php echo $tableBorderColor; ?>;
                text-align: center;
                background-color: <?php echo $backgroundBanners; ?>;
                color: <?php echo $textBanners; ?>;
                font-family: 'Source Sans Pro', sans-serif;
            }
            
            .profile-body {
                padding: 25px;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
                text-align: center;
            }

            .profile-select, .profile-input {
                width: 100%;
                padding: 12px;
                border: 1px solid <?php echo $tableBorderColor; ?>;
                border-radius: 4px;
                color: <?php echo $textContent; ?> !important;
                background-color: <?php echo $tableRowEvenBg; ?> !important;
                font-family: 'Inconsolata', monospace !important;
                font-size: 1.1rem;
                margin-bottom: 15px;
                box-sizing: border-box;
                appearance: none;
                -webkit-appearance: none;
            }

            .profile-btn {
                width: 100%;
                padding: 15px;
                background-color: <?php echo $backgroundNavbar; ?>;
                color: <?php echo $textNavbar; ?>;
                border: 1px solid <?php echo $tableBorderColor; ?>;
                border-radius: 6px;
                font-size: 1.1rem;
                font-weight: 700;
                cursor: pointer;
                text-transform: uppercase;
                transition: all 0.2s;
                -webkit-appearance: none;
                font-family: 'Source Sans Pro', sans-serif;
            }
            .profile-btn:hover {
                background-color: <?php echo $backgroundNavbarHover; ?>;
                color: <?php echo $textNavbarHover; ?>;
                transform: translateY(-2px);
            }
            
            .profile-btn-delete {
                background-color: #c0392b !important;
                color: #ffffff !important;
                border-color: #a93226 !important;
            }
            .profile-btn-delete:hover {
                background-color: #e74c3c !important;
            }

            .profile-meta {
                margin-top: 5px;
                font-size: 0.9em;
                opacity: 0.7;
                font-family: 'Source Sans Pro', sans-serif;
            }
            
            .profile-helper {
                margin-top: 15px;
                font-size: 0.85em;
                opacity: 0.6;
                border-top: 1px solid <?php echo $tableBorderColor; ?>;
                padding-top: 10px;
                line-height: 1.4;
                font-family: 'Source Sans Pro', sans-serif;
            }

            .profile-alert {
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
                text-align: center;
                border: 1px solid <?php echo $tableBorderColor; ?>;
                font-family: 'Source Sans Pro', sans-serif;
                font-weight: 600;
            }
            /* High Contrast Success Text: Dark Charcoal on Green */
            .profile-alert-success { 
                background-color: <?php echo $backgroundServiceCellActiveColor; ?>; 
                color: #1f1f1f; 
                text-shadow: 0 1px 0 rgba(255,255,255,0.3);
            }
            .profile-alert-error   { 
                background-color: <?php echo $backgroundServiceCellInactiveColor; ?>; 
                color: #ffffff; 
            }

            .mobile-back-btn { display: none; }

            @media screen and (max-width: 768px) {
                .desktop-only { display: none !important; }
                .noMob { display: none !important; }
                .profile-wrapper { flex-direction: column; align-items: center; padding: 10px; }
                .profile-card { width: 100%; max-width: 100%; }
                .mobile-back-btn { display: block; width: 100%; margin-bottom: 15px; }
                .mobile-back-btn a {
                    display: block; width: 100%; padding: 12px; text-align: center;
                    background-color: <?php echo $backgroundNavbar; ?>;
                    color: <?php echo $textNavbar; ?>;
                    border: 1px solid <?php echo $tableBorderColor; ?>;
                    border-radius: 4px; text-decoration: none; font-weight: bold; box-sizing: border-box;
                    font-family: 'Source Sans Pro', sans-serif;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="SmallHeader shLeft noMob">Hostname: <?php echo exec('cat /etc/hostname'); ?></div>
                <div class="SmallHeader shRight noMob">
                    <div id="CheckUpdate"><?php include $_SERVER['DOCUMENT_ROOT'].'/includes/checkupdates.php'; ?></div><br />
                </div>
                <h1>WPSD <?php echo __(( 'Dashboard' )); ?> - Profile Manager</h1>
                <div class="navbar">
                    <script type= "text/javascript">
                    window.time_format = '<?php echo constant("TIME_FORMAT"); ?>';
                    function reloadDateTime(){
                      $( '#timer' ).html( _getDatetime( window.time_format ) );
                        setTimeout(reloadDateTime,1000);
                      }
                      reloadDateTime();
                    </script>
                    <div class="headerClock"><span id="timer"></span></div>
                    <a class="menuconfig noMob" href="/admin/configure.php"><?php echo __( 'Configuration' );?></a>
                    <a class="menuadmin noMob" href="/admin/"><?php echo __( 'Admin' );?></a>
                    <a class="menudashboard" href="/"><?php echo __( 'Dashboard' );?></a>
                </div>
            </div>

            <div class="profile-wrapper">
                
                <div class="mobile-back-btn">
                    <a href="/"><i class="fa fa-chevron-left"></i> Back to Dashboard</a>
                </div>

                <?php if (!empty($_POST)) { ?>
                    <div style="width: 100%; max-width: 600px; margin-bottom: 20px;">
                    <?php
                    // --- SAVE LOGIC (Synchronous) ---
                    if ( isset($_POST["save_current_config"]) || isset($_POST['running_config']) ) {
                        $desc = isset($_POST["save_current_config"]) ? $_POST['config_desc'] : $_POST['current_profile'];
                        
                        if ($desc == "") {
                            echo '<div class="profile-alert profile-alert-error"><i class="fa fa-times-circle"></i> Description required.<br>Page reloading...</div>';
                            echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                        } else if (!preg_match('/^[a-zA-Z0-9\s]+$/', $desc)) {
                            echo '<div class="profile-alert profile-alert-error"><i class="fa fa-ban"></i> Invalid characters.<br>Page reloading...</div>';
                            echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                        } else {
                            $cmd = "sudo /usr/local/sbin/wpsd-profiles -s " . escapeshellarg($desc) . " 2>&1";
                            exec($cmd, $output, $retVal);
                            
                            $displayOutput = stripAnsi(implode("<br>", $output));
                            
                            if ($retVal === 0) {
                                echo '<div class="profile-alert profile-alert-success">'.$displayOutput.'<br>Page reloading...</div>';
                                echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                            } else {
                                echo '<div class="profile-alert profile-alert-error"><b>Error:</b><br>'.$displayOutput.'</div>';
                                echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 5000);</script>';
                            }
                        }
                    } 
                    // --- RESTORE LOGIC (Asynchronous with FIXED Countdown) ---
                    else if ( isset($_POST["restore_config"]) ) {
                        if (empty($_POST['configs'])) {
                             echo '<div class="profile-alert profile-alert-error">No profile selected!</div>';
                             echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                        } else {
                            $resto = $_POST['configs'];
                            
                            // Execute in background
                            $cmd = "sudo /usr/local/sbin/wpsd-profiles -sw " . escapeshellarg($resto) . " > /dev/null 2>&1 &";
                            exec($cmd);
                            
                            echo '<div class="profile-alert profile-alert-success">';
                            echo '<i class="fa fa-refresh fa-spin"></i> Switched to Profile: <strong>'.htmlspecialchars($resto).'</strong><br>';
                            echo 'Services are re-initializing...<br><br>';
                            echo 'Redirecting to Dashboard in <span id="redir_countdown">10</span> seconds...';
                            echo '</div>';
                            
                            echo '<script>
                                var seconds = 10;
                                var redirTimer = setInterval(function() {
                                    seconds--;
                                    if (seconds <= 0) {
                                        clearInterval(redirTimer);
                                        document.getElementById("redir_countdown").textContent = "0";
                                        window.location.href = "/";
                                    } else {
                                        document.getElementById("redir_countdown").textContent = seconds;
                                    }
                                }, 1000);
                            </script>';
                        }
                    }
                    // --- DELETE LOGIC (Synchronous) ---
                    else if ( isset($_POST["remove_config"]) ) {
                        if (empty($_POST['delete_configs'])) {
                            echo '<div class="profile-alert profile-alert-error">No profile selected!</div>';
                            echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                        } else {
                            $del = $_POST['delete_configs'];
                            $cmd = "echo 'y' | sudo /usr/local/sbin/wpsd-profiles -d " . escapeshellarg($del) . " 2>&1";
                            exec($cmd, $output, $retVal);
                            
                            $displayOutput = stripAnsi(implode("<br>", $output));
                            
                            if ($retVal === 0) {
                                echo '<div class="profile-alert profile-alert-success">'.$displayOutput.'<br>Page reloading...</div>';
                                echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 3000);</script>';
                            } else {
                                echo '<div class="profile-alert profile-alert-error"><b>Error:</b><br>'.$displayOutput.'</div>';
                                echo '<script>setTimeout("location.href = \''.$_SERVER["PHP_SELF"].'\'", 5000);</script>';
                            }
                        }
                    }
                    ?>
                    </div>
                <?php } else { 
                    // PAUSED CHECK
                    $is_paused = glob('/etc/*_paused');
                    if (!empty($is_paused)) {
                        echo '<div class="profile-alert profile-alert-error"><h3><i class="fa fa-pause-circle"></i> Modes Paused</h3>One or more modes are paused. You must Resume them in the <a href="/admin/?func=mode_man">Instant Mode Manager</a> before managing profiles.</div>';
                    } else {
                ?>
                
                <div class="profile-card">
                    <div class="profile-header">Switch Profile</div>
                    <div class="profile-body">
                        <?php if (count(glob("$profile_dir/*")) == 0) { ?>
                            <p>No saved profiles found.</p>
                        <?php } else { ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <select name="configs" class="profile-select">
                                    <option value="" disabled selected>Select Profile...</option>
                                    <?php
                                    foreach ( glob("$profile_dir/*") as $dir ) {
                                        $profile_file = str_replace("$profile_dir/", "", $dir);
                                        $profile_file_friendly = str_replace("_", " ", $profile_file);
                                        echo "<option value='$profile_file_friendly'>$profile_file_friendly</option>\n";
                                    }
                                    ?>
                                </select>
                                <input type="submit" name="restore_config" value="Switch to Profile" class="profile-btn">
                            </form>
                            <div class="profile-helper"><i class='fa fa-info-circle'></i> Instantly switch to a saved profile.</div>
                        <?php } ?>
                    </div>
                </div>

                <div class="profile-card desktop-only">
                    <div class="profile-header">Current Profile</div>
                    <div class="profile-body">
                        <?php echo $current_profile_display; ?>
                        
                        <?php if (!$no_raw_profile) { ?>
                            <hr style="width:100%; border:0; border-top:1px solid rgba(255,255,255,0.1); margin:15px 0;">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                <input type="hidden" name="current_profile" value="<?php echo $current_profile_friendly; ?>">
                                <button type="submit" name="running_config" class="profile-btn">Quick Save Over Current</button>
                            </form>
                            <div class="profile-helper"><i class="fa fa-info-circle"></i> Updates the current profile with current settings.</div>
                        <?php } ?>
                    </div>
                </div>

                <div class="profile-card desktop-only">
                    <div class="profile-header">Save New Profile</div>
                    <div class="profile-body">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <input type="text" placeholder="Enter Description..." name="config_desc" class="profile-input" maxlength="255">
                            <input type="submit" name="save_current_config" value="Save New Profile" class="profile-btn">
                        </form>
                        <div class="profile-helper"><i class='fa fa-save'></i> Save current settings as a new profile.<br>(Spaces permitted)</div>
                    </div>
                </div>

                <div class="profile-card desktop-only">
                    <div class="profile-header">Delete Profile</div>
                    <div class="profile-body">
                        <?php if (count(glob("$profile_dir/*")) == 0) { ?>
                            <p>No profiles to delete.</p>
                        <?php } else { ?>
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="del_configs">
                                <select name="delete_configs" class="profile-select">
                                    <option value="" disabled selected>Select Profile to Delete...</option>
                                    <?php
                                    foreach ( glob("$profile_dir/*") as $dir ) {
                                        $profile_file = str_replace("$profile_dir/", "", $dir);
                                        $profile_file_friendly = str_replace("_", " ", $profile_file);
                                        echo "<option value='$profile_file_friendly'>$profile_file_friendly</option>\n";
                                    }
                                    ?>
                                </select>
                                <input type="submit" name="remove_config" value="Delete Profile" class="profile-btn profile-btn-delete" onclick="return confirm('Are you sure you want to delete this profile?');">
                            </form>
                        <?php } ?>
                    </div>
                </div>
                
                <?php } // End paused check ?>
                <?php } // End !POST ?>
            </div>
            
            <?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
        </div>
    </body>
</html>
<?php
}
?>
