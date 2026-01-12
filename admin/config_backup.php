<?php
// 1. Session & Config Setup (Must be first)
if (isset($_COOKIE['PHPSESSID'])) { session_id($_COOKIE['PHPSESSID']); }
if (session_status() != PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION) || !is_array($_SESSION) || (count($_SESSION, COUNT_RECURSIVE) < 10)) {
    session_id('wpsdsession');
    session_start();
    include_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';
    include_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';
    checkSessionValidity();
}

require_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/version.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';
include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';

// 2. DOWNLOAD LOGIC (Moved to TOP - Before ANY HTML output)
if (!empty($_POST) && isset($_POST["action"]) && escapeshellcmd($_POST["action"]) == "download") {
    $tmpDir = "/tmp";
    // Call Shell Script to create backup
    // The script must only output the filename (handled by previous shell script fix)
    $cmd = "sudo /usr/local/sbin/wpsd-backup -c " . escapeshellarg($tmpDir);
    $backupFile = shell_exec($cmd);
    $backupFile = trim($backupFile);

    if (file_exists($backupFile)) {
        // Clear any previous output buffers to ensure clean binary download
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="'.basename($backupFile).'"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($backupFile));
        
        readfile($backupFile);
        
        // Cleanup and Exit immediately to prevent HTML from appending
        unlink($backupFile);
        exit;
    } else {
        // If it fails, we let the script continue so it renders the Error UI below
        $downloadError = true;
    }
}

// 3. Theme Settings
$backgroundContent = "#1a1a1a";
$textContent = "#ffffff";
$backgroundBanners = "#0b2041"; 
$textBanners = "#ffffff";
$backgroundNavbar = "#163b65";
$textNavbar = "#ffffff";
$backgroundNavbarHover = "#1c4b82";

if (isset($_SESSION['CSSConfigs'])) {
    if (isset($_SESSION['CSSConfigs']['Background']['ContentColor'])) $backgroundContent = $_SESSION['CSSConfigs']['Background']['ContentColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['BannersColor'])) $backgroundBanners = $_SESSION['CSSConfigs']['Background']['BannersColor'];
    if (isset($_SESSION['CSSConfigs']['Text']['TextColor'])) $textContent = $_SESSION['CSSConfigs']['Text']['TextColor'];
    if (isset($_SESSION['CSSConfigs']['Text']['BannersColor'])) $textBanners = $_SESSION['CSSConfigs']['Text']['BannersColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['NavbarColor'])) $backgroundNavbar = $_SESSION['CSSConfigs']['Background']['NavbarColor'];
    if (isset($_SESSION['CSSConfigs']['Text']['NavbarColor'])) $textNavbar = $_SESSION['CSSConfigs']['Text']['NavbarColor'];
    if (isset($_SESSION['CSSConfigs']['Background']['NavbarHoverColor'])) $backgroundNavbarHover = $_SESSION['CSSConfigs']['Background']['NavbarHoverColor'];
}

if ($_SERVER["PHP_SELF"] == "/admin/config_backup.php") {
    header('Cache-Control: no-cache');
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html lang="en">
	<head>
	    <meta name="language" content="English" />
	    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
	    <meta http-equiv="pragma" content="no-cache" />
	    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
	    <meta http-equiv="Expires" content="0" />
	    <title>WPSD <?php echo __( 'Dashboard' )." - ".__( 'Backup/Restore' );?></title>
	    <link rel="stylesheet" type="text/css" href="/css/font-awesome-4.7.0/css/font-awesome.min.css" />
        <?php include_once $_SERVER['DOCUMENT_ROOT'].'/config/browserdetect.php'; ?>
        <script type="text/javascript" src="/js/jquery.min.js"></script>
        <style>
            .profile-wrapper { display: flex; flex-direction: column; align-items: center; gap: 20px; padding: 20px; max-width: 1200px; margin: 0 auto; }
            .profile-card { width: 100%; background-color: <?php echo $backgroundContent; ?>; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); overflow: hidden; color: <?php echo $textContent; ?>; }
            .profile-header { padding: 12px; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; background-color: <?php echo $backgroundBanners; ?>; color: <?php echo $textBanners; ?>; font-family: 'Source Sans Pro', sans-serif; }
            .profile-body { padding: 20px; }
            
            .profile-btn { display: inline-flex; justify-content: center; align-items: center; height: 38px; line-height: 1; padding: 0 20px; background-color: <?php echo $backgroundNavbar; ?>; color: <?php echo $textNavbar; ?>; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 4px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 0.9em; transition: all 0.2s; font-family: 'Source Sans Pro', sans-serif; box-sizing: border-box; text-decoration: none; width: 100%; margin-top: 10px; }
            .profile-btn:hover { background-color: <?php echo $backgroundNavbarHover; ?>; color: white; }
            .profile-btn i { margin-right: 8px; }
            
            .backup-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
            @media(max-width: 768px) { .backup-grid { grid-template-columns: 1fr; } }

            .file-upload-label { display: flex; align-items: center; justify-content: center; padding: 30px; border: 2px dashed rgba(255,255,255,0.2); border-radius: 6px; background-color: rgba(255,255,255,0.05); cursor: pointer; transition: all 0.2s; width: 100%; box-sizing: border-box; font-family: 'Source Sans Pro', sans-serif; margin-bottom: 5px; }
            .file-upload-label:hover { border-color: <?php echo $backgroundNavbar; ?>; background-color: rgba(255,255,255,0.1); }
            .inputfile { width: 0.1px; height: 0.1px; opacity: 0; overflow: hidden; position: absolute; z-index: -1; }
            
            .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; text-align: center; font-weight: 600; }
            .alert-success { background-color: #27ae60; color: #1f1f1f; text-shadow: 0 1px 0 rgba(255,255,255,0.3); }
            .alert-error { background-color: #c0392b; color: #ffffff; }
            
            .info-list { display: flex; flex-direction: column; align-items: center; opacity: 0.8; font-size: 0.9em; line-height: 1.6; }
            .info-list ul { text-align: left; margin: 5px 0 0 0; padding-left: 20px; display: table; }
        </style>
	</head>
	<body>
	    <div class="container">
		<div class="header">
		    <div class="SmallHeader shLeft">Hostname: <?php echo exec('cat /etc/hostname'); ?></div>
			<?php if (isset($_SESSION['CURRENT_PROFILE'])) { ?><div class="SmallHeader shLeft noMob"> | <?php echo __( 'Current Profile' ).": ";?> <?php echo $_SESSION['CURRENT_PROFILE']; ?></div><?php } ?>
            <div class="SmallHeader shRight">
                <div id="CheckUpdate"><?php include $_SERVER['DOCUMENT_ROOT'].'/includes/checkupdates.php'; ?></div><br />
            </div>
		    <h1>WPSD <?php echo __(( 'Dashboard' )) . " - ".__( 'Backup/Restore' );?></h1>
		    <div class="navbar">
                <a class="menuconfig" href="/admin/configure.php"><?php echo __( 'Configuration' );?></a>
                <a class="menuupdate" href="/admin/update.php"><?php echo __( 'WPSD Update' );?></a>
                <a class="menupower" href="/admin/power.php"><?php echo __( 'Power' );?></a>
                <a class="menuadmin" href="/admin/"><?php echo __( 'Admin' );?></a>
                <a class="menudashboard" href="/"><?php echo __( 'Dashboard' );?></a>
            </div>
		</div>

		<div class="profile-wrapper">
		    <?php 
                // Display Download Error if logic above failed
                if (isset($downloadError) && $downloadError === true) {
                     echo "<div class='profile-card'><div class='profile-body'><div class='alert alert-error'>Backup creation failed. Please check system logs.</div><button class='profile-btn' onclick='window.history.back()'>Go Back</button></div></div>";
                }
            
                if (!empty($_POST)) {
                
                // --- RESTORE LOGIC (Shell Script + Upload) ---
                if ( isset($_POST["action"]) && escapeshellcmd($_POST["action"]) == "restore" ) {
                    echo "<div class='profile-card'><div class='profile-header'>Restore Status</div><div class='profile-body'>";
                    
                    $uploadOk = false;
                    $target_file = "";

                    if($_FILES["fileToUpload"]["name"]) {
                        $filename = $_FILES["fileToUpload"]["name"];
                        $source = $_FILES["fileToUpload"]["tmp_name"];
                        
                        // Basic validation
                        $nameParts = explode(".", $filename);
                        if (strtolower(end($nameParts)) == 'zip') {
                            $target_file = "/tmp/" . basename($filename);
                            if (move_uploaded_file($source, $target_file)) {
                                $uploadOk = true;
                            }
                        }
                    }

                    if ($uploadOk) {
                        // Execute Shell Script Asynchronously
                        $cmd = "sudo /usr/local/sbin/wpsd-backup -r " . escapeshellarg($target_file) . " > /dev/null 2>&1 &";
                        exec($cmd);

                        echo "<div class='alert alert-success'>";
                        echo "<i class='fa fa-refresh fa-spin'></i> Restoration in Progress...<br><br>";
                        echo "Services are re-initializing.<br>";
                        echo "Redirecting to Dashboard in <span id='redir_countdown'>20</span> seconds...";
                        echo "</div>";
                        
                        echo '<script>
                            var seconds = 20;
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
                    } else {
                        echo "<div class='alert alert-error'>Upload Failed. Please ensure you uploaded a valid .zip file.</div>";
                        echo "<button class='profile-btn' onclick='window.history.back()'>Go Back</button>";
                    }
                    echo "</div></div>";
                }

		    } else { ?>
            
            <div class="profile-card">
                <div class="profile-header">System Configuration Management</div>
                <div class="profile-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                        <div class="backup-grid">
                            <div style="text-align:center; padding: 10px;">
                                <h3 style="margin-top:0; color: <?php echo $textBanners; ?>">Backup Configuration</h3>
                                <p style="font-size:0.9em; opacity:0.8;">Download a complete backup of your current system settings.</p>
                                <button type="submit" name="action" value="download" class="profile-btn" style="height: 50px; font-size:1.1em;">
                                    <i class="fa fa-download fa-lg"></i> Download Backup
                                </button>
                            </div>

                            <div style="text-align:center; padding: 10px; border-left: 1px solid rgba(255,255,255,0.1);">
                                <h3 style="margin-top:0; color: <?php echo $textBanners; ?>">Restore Configuration</h3>
                                <p style="font-size:0.9em; opacity:0.8;">Upload a previously saved <code>.zip</code> backup file.</p>
                                
                                <div style="position:relative;">
                                    <input type="file" name="fileToUpload" id="fileToUpload" class="inputfile" onchange="$('#file-chosen').text(this.files[0].name)" accept=".zip" />
                                    <label for="fileToUpload" class="file-upload-label">
                                        <i class="fa fa-cloud-upload" style="font-size:2em; margin-right:15px;"></i>
                                        <span id="file-chosen" style="font-weight:bold;">Click to Select Backup File</span>
                                    </label>
                                </div>
                                
                                <button type="submit" name="action" value="restore" class="profile-btn" style="background-color: #27ae60;" onclick="return confirm('WARNING: This will overwrite your current configuration and reboot services.\n\nAre you sure?');">
                                    <i class="fa fa-refresh"></i> Restore Configuration
                                </button>
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid rgba(255,255,255,0.1); margin: 20px 0;">

                        <div class="info-list">
                            <strong><i class="fa fa-info-circle"></i> Important Notes:</strong>
                            <ul>
                                <li>System Passwords / Dashboard passwords are <strong>not</strong> included in backups.</li>
                                <li>Wireless Configuration (WiFi) <strong>is</strong> included.</li>
                                <li>All WPSD Profiles are included.</li>
                            </ul>
                        </div>
                    </form>
                </div>
            </div>
		    <?php } ?>
		</div>
<?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
	    </div>
	</body>
    </html>
<?php
}
?>
