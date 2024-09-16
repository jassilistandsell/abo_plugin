<?php

namespace Plugin\Abo_Mollie\adminmenu;

use JTL\Plugin\Helper as PluginHelper;
use JTL\Smarty\JTLSmarty;
use JTL\DB\DbInterface;
use JTL\Shop;

$rooturl = $_SERVER["DOCUMENT_ROOT"];
require_once  $rooturl.'/includes/globalinclude.php';

// Initialize plugin and database instances
$oPlugin = PluginHelper::getPluginById('Abo_Mollie');
$db = Shop::Container()->getDB();
$smarty = new JTLSmarty();

// Fetch existing frequencies and coupons from the database
$query = 'SELECT kFrequency, cFrequency, cFreq_coupon FROM tfrequency';
$existingFrequencies = $db->getObjects($query);

// Debug: Log fetched data to check if it's being retrieved correctly
error_log(print_r($existingFrequencies, true));

$frequenciesData = [];
if (!empty($existingFrequencies)) {
    foreach ($existingFrequencies as $entry) {
        $frequenciesData[] = [
            'kFrequency' => $entry->kFrequency, // Ensure we include the ID for updates
            'frequency'  => $entry->cFrequency,
            'coupon'     => $entry->cFreq_coupon
        ];
    }
} else {
    // Create three empty rows if there are no existing frequencies
    for ($i = 0; $i < 3; $i++) {
        $frequenciesData[] = [
            'kFrequency' => '',
            'frequency'  => '',
            'coupon'     => ''
        ];
    }
}

// Debug: Log processed data to check if it's being processed correctly
error_log(print_r($frequenciesData, true));

// Pass data to Smarty template
$smarty->assign('existingFrequencies', $frequenciesData);

// Construct the full path to the template file
$templatePath = $oPlugin->getPaths()->getFrontendPath() . 'template/mollie_frequency_form.tpl';

// Debug: Log template path to ensure it's correct
error_log('Template path: ' . $templatePath);

// Display the template
$smarty->display($templatePath);
?>
