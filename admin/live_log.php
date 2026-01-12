<?php

require_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/config/version.php';

// Sanity Check
if ($_SERVER["PHP_SELF"] != "/admin/live_log.php") die();

// --- THEME SETTINGS ---
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

$curDateStr = gmdate('Y-m-d');
$logFiles = [
    "MMDVMHost"     => "/var/log/pi-star/MMDVM-{$curDateStr}.log",
    "DMRGateway"    => "/var/log/pi-star/DMRGateway-{$curDateStr}.log",
    "YSFGateway"    => "/var/log/pi-star/YSFGateway-{$curDateStr}.log",
    "DGIdGateway"   => "/var/log/pi-star/DGIdGateway-{$curDateStr}.log",
    "ircDDBGateway" => "/var/log/pi-star/ircDDBGateway-{$curDateStr}.log",
    "P25Gateway"    => "/var/log/pi-star/P25Gateway-{$curDateStr}.log",
    "NXDNGateway"   => "/var/log/pi-star/NXDNGateway-{$curDateStr}.log",
    "DAPNETGateway" => "/var/log/pi-star/DAPNETGateway-{$curDateStr}.log",
    "DMR2NXDN"      => "/var/log/pi-star/DMR2NXDN-{$curDateStr}.log",
    "DMR2YSF"       => "/var/log/pi-star/DMR2YSF-{$curDateStr}.log",
    "YSF2DMR"       => "/var/log/pi-star/YSF2DMR-{$curDateStr}.log",
    "YSF2NXDN"      => "/var/log/pi-star/YSF2NXDN-{$curDateStr}.log",
    "YSF2P25"       => "/var/log/pi-star/YSF2P25-{$curDateStr}.log",
    "APRSGateway"   => "/var/log/pi-star/APRSGateway-{$curDateStr}.log",
];

header('Cache-Control: no-cache');

$log = $_GET['log'] ?? '';
$logfile = $logFiles[$log] ?? null;

if (isset($_GET['download'])) {
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="WPSD_' . basename($logfile) . '";');
    header('Content-Length: ' . filesize($logfile));
    header('Accept-Ranges: bytes');
    set_time_limit(0);
    $file = @fopen($logfile, "rb");
    fpassthru($file);
    exit();
}

if (!isset($_GET['ajax'])) {
    unset($_SESSION['logviewer'][$log]);
} else {
    if (empty($logfile) || !file_exists($logfile)) exit();
    $handle = fopen($logfile, 'rb');
    if (!$handle) exit();

    fseek($handle, 0, SEEK_END);
    $logLen = ftell($handle);
    $output = "";
    $curOffset = 0;

    if (!isset($_SESSION['logviewer'][$log])) {  
        $maxInitialLogSize = 32 * 1024;
        if ($logLen - $curOffset > $maxInitialLogSize) {
            $output = "<i>&gt;&gt;&gt; FILE TOO LARGE, TRUNCATED &lt;&lt;&lt;</i><br /> ... ";
            $curOffset = $logLen - $maxInitialLogSize;
        }
    } else {
        $curOffset = $_SESSION['logviewer'][$log]['offset'];
        if ($curOffset > $logLen) $curOffset = 0;
    }

    $_SESSION['logviewer'][$log]['offset'] = $logLen;
    $data = stream_get_contents($handle, null, $curOffset);
    $data = wordwrap($data, 200, "\n");
    echo $output . htmlentities($data); 
    fclose($handle);
    exit();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="en">
  <head>
    <meta name="language" content="English" />
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="pragma" content="no-cache" />
    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />
    <meta http-equiv="Expires" content="0" />
    <title>WPSD <?php echo __( 'Dashboard' )." - ".__( 'Log Viewer' );?></title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'].'/config/browserdetect.php'; ?>
    <link rel="stylesheet" type="text/css" href="/css/font-awesome-4.7.0/css/font-awesome.min.css" />
    <script type="text/javascript" src="/js/jquery.min.js?version=<?php echo $versionCmd; ?>"></script>
    <script type="text/javascript" src="/js/jquery-timing.min.js?version=<?php echo $versionCmd; ?>"></script>
    <script type="text/javascript" src="/js/functions.js?version=<?php echo $versionCmd; ?>"></script>
    <style>
        .profile-wrapper { display: flex; flex-direction: column; align-items: center; gap: 20px; padding: 20px; max-width: 1200px; margin: 0 auto; }
        .profile-card { width: 100%; background-color: <?php echo $backgroundContent; ?>; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2); overflow: hidden; color: <?php echo $textContent; ?>; }
        .profile-header { padding: 12px; font-weight: 700; font-size: 1.1rem; text-transform: uppercase; border-bottom: 1px solid rgba(255, 255, 255, 0.1); text-align: center; background-color: <?php echo $backgroundBanners; ?>; color: <?php echo $textBanners; ?>; font-family: 'Source Sans Pro', sans-serif; }
        .profile-body { padding: 20px; }
        
        .profile-btn { display: inline-flex; justify-content: center; align-items: center; height: 38px; line-height: 1; padding: 0 20px; background-color: <?php echo $backgroundNavbar; ?>; color: <?php echo $textNavbar; ?>; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 4px; font-weight: bold; cursor: pointer; text-transform: uppercase; font-size: 0.9em; transition: all 0.2s; font-family: 'Source Sans Pro', sans-serif; box-sizing: border-box; text-decoration: none; margin: 0 5px; }
        .profile-btn:hover { background-color: <?php echo $backgroundNavbarHover; ?>; color: white; }
        .profile-btn i { margin-right: 8px; }
        
        select { height: 38px; padding: 5px 10px; background-color: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2); color: <?php echo $textContent; ?>; border-radius: 4px; margin-right: 10px; font-family: 'Source Sans Pro', sans-serif; }
        option { background-color: #333; color: white; }
        
        #tail {
            background-color: #000;
            color: #00ffff; /* Fixed: Cyan Text */
            font-family: 'Inconsolata', 'Courier New', monospace;
            padding: 15px;
            height: 500px;
            overflow-y: scroll;
            border: 1px solid #444;
            border-radius: 4px;
            font-size: 0.9rem;
            line-height: 1.4;
            white-space: pre-wrap; 
        }
        #tail::-webkit-scrollbar { width: 10px; }
        #tail::-webkit-scrollbar-track { background: #222; }
        #tail::-webkit-scrollbar-thumb { background: #555; border-radius: 5px; }
        
        .control-bar { display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 10px; }
    </style>
<?php if (!empty($log)) {?>
    <script type="text/javascript">
    $(function() {
        var placeholderVisible = true;
        $.repeat(1000, function() {
            $.get('/admin/live_log.php?log=<?php echo "$log";?>&ajax', function(data) {
                if (data.length < 1) return;
                var objDiv = document.getElementById("tail");
                var isScrolledToBottom = objDiv.scrollHeight - objDiv.clientHeight <= objDiv.scrollTop + 5;
                if (placeholderVisible) { $('#tail').empty(); placeholderVisible = false; }
                $('#tail').append(data);
                if (isScrolledToBottom) objDiv.scrollTop = objDiv.scrollHeight;
            });
        });
    });
    </script>
<?php }?>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <div class="SmallHeader shLeft">Hostname: <?php echo exec('cat /etc/hostname'); ?></div>
        <?php if ($_SESSION['CURRENT_PROFILE']) { ?><div class="SmallHeader shLeft noMob"> | <?php echo __( 'Current Profile' ).": ";?> <?php echo $_SESSION['CURRENT_PROFILE']; ?></div><?php } ?>
        <div class="SmallHeader shRight">
          <div id="CheckUpdate"><?php include $_SERVER['DOCUMENT_ROOT'].'/includes/checkupdates.php'; ?></div><br />
        </div>
        <h1>WPSD <?php echo __(( 'Dashboard' )) . " - ".__( 'Log Viewer' );?></h1>
        <p>
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
            <a class="menuconfig" href="/admin/configure.php"><?php echo __( 'Configuration' );?></a>
            <a class="menubackup" href="/admin/config_backup.php"><?php echo __( 'Backup/Restore' );?></a>
            <a class="menupower" href="/admin/power.php"><?php echo __( 'Power' );?></a>
            <a class="menuadmin" href="/admin/"><?php echo __( 'Admin' );?></a>
            <?php if (file_exists("/etc/dstar-radio.mmdvmhost")) { ?>
            <a class="menulive" href="/live/">Live Caller</a>
            <?php } ?>
            <a class="menudashboard" href="/"><?php echo __( 'Dashboard' );?></a>
          </div>
        </p>
      </div>

      <div class="profile-wrapper">
        <div class="profile-card">
            <div class="profile-header">
                <?php echo __( 'Log Selection' ); if (!empty($log)) echo " : $log"; ?>
            </div>
            <div class="profile-body">
                <form method="get" class="control-bar">
                    <label style="font-weight:bold;">Select Log:</label>
                    <select name="log">
                        <?php foreach ($logFiles as $logName => $logFilename) { ?>
                        <option value="<?=$logName?>" <?php if ($log == $logName) { echo "selected='selected'"; } ?>><?=$logName?></option>
                        <?php } ?>
                    </select>
                    <button class="profile-btn" type="submit"><i class="fa fa-eye"></i> Live View</button>
                    <button class="profile-btn" type="submit" name="download" value="1"><i class="fa fa-download"></i> Download This Log</button>
                    <button class="profile-btn" onclick="location.href='/admin/download_all_logs.php'; return false;" style="background-color: #2980b9;"><i class="fa fa-file-archive-o"></i> Download All Logs</button>
                </form>
            </div>
        </div>

        <?php if (!empty($log)) { ?>
        <div class="profile-card">
            <div class="profile-header">Live Output</div>
            <div class="profile-body" style="padding:0;">
                <div id="tail">
                    <?php
                        if (!file_exists($logfile)) {
                            echo "<b>File `$logfile` not found!</b>";
                        } else {
                            echo "<i>Initializing Log Stream...</i>";
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php } ?>
      </div>

      <?php include $_SERVER['DOCUMENT_ROOT'].'/includes/footer.php'; ?>
    </div>
  </body>
</html>
