<?php
if (defined('CUSTOM_ALERT_LOADED')) {
    return;
}
define('CUSTOM_ALERT_LOADED', true);
?>

<div id="customAlertOverlay" class="custom-alert-overlay" style="display: none;">
    <div class="custom-alert-box">
        <div class="custom-alert-header">
            <span class="alert-title" id="customAlertTitle">Повідомлення</span>
            <span class="alert-close" onclick="closeAlert()">&times;</span>
        </div>
        
        <div class="custom-alert-body">
            <div class="alert-icon-area" id="customAlertIcon">
                </div>
            
            <div class="alert-content-area">
                <p id="customAlertText"></p>
            </div>
        </div>
        
        <div id="customAlertSubText" class="alert-sub-text" style="display: none;"></div>
        <div class="custom-alert-footer" id="customAlertButtons">
            <button class="btn-alert-ok" onclick="closeAlert()">OK</button>
        </div>
    </div>
</div>