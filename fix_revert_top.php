<?php
$file = 'admin/settings/index.php';
$c = file_get_contents($file);

$old = '<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-cog mr-2 text-green-600"></i> Company Settings</h1>
        <p class="text-gray-600 mt-1">Configure your company information and email settings</p>
    </div>
    <button type="submit" form="settingsForm" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700"><i class="fas fa-save mr-2"></i> Save Settings</button>
</div>';
$new = '<div class="mb-6">
    <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-cog mr-2 text-green-600"></i> Company Settings</h1>
    <p class="text-gray-600 mt-1">Configure your company information and email settings</p>
</div>';
if(strpos($c, $old) !== false) {
    $c = str_replace($old, $new, $c);
    $c = str_replace(' id="settingsForm"', '', $c);
    file_put_contents($file, $c);
    echo "Reverted top save button\n";
} else {
    echo "Pattern not found\n";
}
?>
