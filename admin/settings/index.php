<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
$company = new Company();
$comp = $company->getSettings();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = $_POST;
    $uploadError = '';
    if(isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $logo = uploadFile($_FILES['logo']);
        if($logo) $data['logo'] = $logo;
        else {
            $data['logo'] = $comp['logo'];
            $uploadError = 'Logo upload failed. Please try again.';
        }
    } else {
        $data['logo'] = $comp['logo'];
    }
    if(isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
        $favicon = uploadFile($_FILES['favicon']);
        if($favicon) $data['favicon'] = $favicon;
        else {
            $data['favicon'] = $comp['favicon'];
            $uploadError = $uploadError ? $uploadError : 'Favicon upload failed. Please try again.';
        }
    } else {
        $data['favicon'] = $comp['favicon'];
    }
    $blockTypo = [];
    if(isset($_POST['block']) && is_array($_POST['block'])) {
        foreach($_POST['block'] as $key => $vals) {
            $clean = [];
            foreach($vals as $k => $v) {
                $v = trim($v);
                if($v !== '' && $v !== 'inherit' && $v !== 'Inherit') {
                    $clean[$k] = $v;
                }
            }
            if(!empty($clean)) {
                $blockTypo[$key] = $clean;
            }
        }
    }
    $data['block_typography'] = json_encode($blockTypo);
    $company->update($data);
    header('Location: ' . BASE_URL . 'admin/settings/index.php?' . ($uploadError ? 'error=1' : 'updated=1'));
    exit;
}

$pageTitle = 'Company Settings';
require_once '../../includes/header.php';
?>
<main class="w-full pt-16 bg-slate-50 min-h-screen">
    <div class="p-8 flex flex-col gap-6 max-w-[1680px] mx-auto w-full">
        <!-- Top Actionable Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                        <span class="tracking-wide">Company Configuration</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Company Settings</h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Configure your company information, branding, and email settings.</p>
            </div>
            
        </div>

        <?php if(isset($_GET['updated'])): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 mt-0.5">check_circle</span>
            <div>
                <h3 class="text-sm font-bold text-emerald-800">Settings updated successfully!</h3>
                <p class="text-[11px] text-emerald-700/80 mt-0.5">Your company settings have been saved.</p>
            </div>
        </div>
        <?php endif; ?>
        <?php if(isset($_GET['error'])): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-red-600 mt-0.5">error</span>
            <div>
                <h3 class="text-sm font-bold text-red-800">Upload failed</h3>
                <p class="text-[11px] text-red-700/80 mt-0.5">Please make sure the file is a JPG, PNG, GIF or WebP under 2 MB and try again.</p>
            </div>
        </div>
        <?php endif; ?>

        <form id="settingsForm" method="POST" enctype="multipart/form-data" class="space-y-8">
            <?php csrf_field(); ?>

            <!-- Company Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                            <span class="material-symbols-outlined text-base">building</span>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Company Information</h2>
                            <p class="text-[11px] text-slate-500 font-medium">Branding and contact details</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company Name <span class="text-red-500">*</span></label>
                        <input type="text" name="company_name" value="<?= e($comp['company_name']) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Default Authorized Signature Label</label>
                        <input type="text" name="default_authorized_signature" value="<?= e($comp['default_authorized_signature'] ?? 'Authorized Signature') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Address</label>
                        <textarea name="address" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($comp['address']) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone</label>
                        <input type="text" name="phone" value="<?= e($comp['phone']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Email</label>
                        <input type="email" name="email" value="<?= e($comp['email']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Website</label>
                        <input type="text" name="website" value="<?= e($comp['website']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Currency Symbol</label>
                        <input type="text" name="currency" value="<?= e($comp['currency']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Default Validity Days</label>
                        <input type="number" name="default_validity_days" value="<?= e($comp['default_validity_days']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>

                    <!-- Favicon -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Favicon (browser tab icon)</label>
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <?php if($comp['favicon']): ?>
                                <img src="<?= BASE_URL ?>assets/uploads/<?= e($comp['favicon']) ?>" alt="Favicon" class="h-8 w-auto">
                                <?php else: ?>
                                <div class="h-8 w-8 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-400 text-[10px]">No Favicon</div>
                                <?php endif; ?>
                                <input type="file" name="favicon" accept="image/*,.ico" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 transition-all">
                            </div>
                            <p class="text-[11px] text-slate-500">ICO, PNG, JPG — max 2MB. Recommended: 32x32 or 16x16.</p>
                        </div>
                    </div>

                    <!-- Company Logo -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Company Logo</label>
                        <div class="flex flex-col gap-3">
                            <div class="flex items-center gap-3">
                                <?php if($comp['logo']): ?>
                                <img src="<?= BASE_URL ?>assets/uploads/<?= e($comp['logo']) ?>" alt="Logo" class="h-12 w-auto object-contain border border-slate-200 rounded-lg">
                                <?php else: ?>
                                <div class="h-12 w-24 rounded-lg border-2 border-dashed border-slate-300 flex items-center justify-center text-slate-400 text-[10px]">No Logo Uploaded</div>
                                <?php endif; ?>
                                <input type="file" name="logo" accept="image/*" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 transition-all">
                            </div>
                            <p class="text-[11px] text-slate-500">JPG, PNG, GIF or WebP — max 2MB.</p>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Default Terms & Conditions</label>
                        <textarea name="default_terms" rows="4" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($comp['default_terms']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Email / SMTP Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-700">
                            <span class="material-symbols-outlined text-base">mail</span>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Email / SMTP Settings</h2>
                            <p class="text-[11px] text-slate-500 font-medium">Configure outgoing email for quotations</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?= e($comp['smtp_host']) ?>" placeholder="smtp.gmail.com" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">SMTP Port</label>
                        <input type="text" name="smtp_port" value="<?= e($comp['smtp_port']) ?>" placeholder="587" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">SMTP Username</label>
                        <input type="text" name="smtp_username" value="<?= e($comp['smtp_username']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">SMTP Password</label>
                        <input type="password" name="smtp_password" value="<?= e($comp['smtp_password']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Encryption</label>
                        <select name="smtp_encryption" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="tls" <?= $comp['smtp_encryption']=='tls'?'selected':'' ?>>TLS</option>
                            <option value="ssl" <?= $comp['smtp_encryption']=='ssl'?'selected':'' ?>>SSL</option>
                            <option value="" <?= $comp['smtp_encryption']==''?'selected':'' ?>>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">From Name</label>
                        <input type="text" name="from_name" value="<?= e($comp['from_name'] ?: $comp['company_name']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">From Email</label>
                        <input type="email" name="from_email" value="<?= e($comp['from_email'] ?: $comp['email']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Default Email Template</label>
                        <textarea name="email_template" rows="5" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($comp['email_template']) ?></textarea>
                        <p class="text-[11px] text-slate-500 mt-1">Available placeholders: {{client_name}}, {{quotation_number}}, {{grand_total}}, {{valid_until}}</p>
                    </div>
                </div>
            </div>

            <!-- Table Typography Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-700">
                            <span class="material-symbols-outlined text-base">format_size</span>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Table Typography Settings</h2>
                            <p class="text-[11px] text-slate-500 font-medium">Default typography for quotation tables</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Font Family</label>
                        <select name="table_font_family" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="inherit" <?= ($comp['table_font_family'] ?? 'inherit')=='inherit'?'selected':'' ?>>Default (Inherit)</option>
                            <option value="Arial, sans-serif" <?= ($comp['table_font_family'] ?? '')=='Arial, sans-serif'?'selected':'' ?>>Arial</option>
                            <option value="'Times New Roman', serif" <?= ($comp['table_font_family'] ?? "")=="'Times New Roman', serif"?'selected':'' ?>>Times New Roman</option>
                            <option value="'Courier New', monospace" <?= ($comp['table_font_family'] ?? "")=="'Courier New', monospace"?'selected':'' ?>>Courier New</option>
                            <option value="Georgia, serif" <?= ($comp['table_font_family'] ?? '')=='Georgia, serif'?'selected':'' ?>>Georgia</option>
                            <option value="Verdana, sans-serif" <?= ($comp['table_font_family'] ?? '')=='Verdana, sans-serif'?'selected':'' ?>>Verdana</option>
                            <option value="'Trebuchet MS', sans-serif" <?= ($comp['table_font_family'] ?? "")=="'Trebuchet MS', sans-serif"?'selected':'' ?>>Trebuchet MS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Font Size</label>
                        <select name="table_font_size" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="10px" <?= ($comp['table_font_size'] ?? '')=='10px'?'selected':'' ?>>10px</option>
                            <option value="11px" <?= ($comp['table_font_size'] ?? '')=='11px'?'selected':'' ?>>11px</option>
                            <option value="12px" <?= ($comp['table_font_size'] ?? '')=='12px'?'selected':'' ?>>12px</option>
                            <option value="13px" <?= ($comp['table_font_size'] ?? '')=='13px'?'selected':'' ?>>13px</option>
                            <option value="14px" <?= ($comp['table_font_size'] ?? '14px')=='14px'?'selected':'' ?>>14px (Default)</option>
                            <option value="15px" <?= ($comp['table_font_size'] ?? '')=='15px'?'selected':'' ?>>15px</option>
                            <option value="16px" <?= ($comp['table_font_size'] ?? '')=='16px'?'selected':'' ?>>16px</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Font Style</label>
                        <select name="table_font_style" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="normal" <?= ($comp['table_font_style'] ?? 'normal')=='normal'?'selected':'' ?>>Normal</option>
                            <option value="italic" <?= ($comp['table_font_style'] ?? '')=='italic'?'selected':'' ?>>Italic</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Font Weight</label>
                        <select name="table_font_weight" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="normal" <?= ($comp['table_font_weight'] ?? 'normal')=='normal'?'selected':'' ?>>Normal</option>
                            <option value="bold" <?= ($comp['table_font_weight'] ?? '')=='bold'?'selected':'' ?>>Bold</option>
                            <option value="300" <?= ($comp['table_font_weight'] ?? '')=='300'?'selected':'' ?>>Light (300)</option>
                            <option value="500" <?= ($comp['table_font_weight'] ?? '')=='500'?'selected':'' ?>>Medium (500)</option>
                            <option value="600" <?= ($comp['table_font_weight'] ?? '')=='600'?'selected':'' ?>>Semi-Bold (600)</option>
                            <option value="700" <?= ($comp['table_font_weight'] ?? '')=='700'?'selected':'' ?>>Bold (700)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Line Height</label>
                        <select name="table_line_height" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="1.0" <?= ($comp['table_line_height'] ?? '')=='1.0'?'selected':'' ?>>1.0 (Tight)</option>
                            <option value="1.2" <?= ($comp['table_line_height'] ?? '')=='1.2'?'selected':'' ?>>1.2</option>
                            <option value="1.4" <?= ($comp['table_line_height'] ?? '1.4')=='1.4'?'selected':'' ?>>1.4 (Default)</option>
                            <option value="1.6" <?= ($comp['table_line_height'] ?? '')=='1.6'?'selected':'' ?>>1.6</option>
                            <option value="1.8" <?= ($comp['table_line_height'] ?? '')=='1.8'?'selected':'' ?>>1.8 (Loose)</option>
                            <option value="2.0" <?= ($comp['table_line_height'] ?? '')=='2.0'?'selected':'' ?>>2.0</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Letter Spacing</label>
                        <select name="table_letter_spacing" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="normal" <?= ($comp['table_letter_spacing'] ?? 'normal')=='normal'?'selected':'' ?>>Normal</option>
                            <option value="0.5px" <?= ($comp['table_letter_spacing'] ?? '')=='0.5px'?'selected':'' ?>>0.5px</option>
                            <option value="1px" <?= ($comp['table_letter_spacing'] ?? '')=='1px'?'selected':'' ?>>1px</option>
                            <option value="1.5px" <?= ($comp['table_letter_spacing'] ?? '')=='1.5px'?'selected':'' ?>>1.5px</option>
                            <option value="2px" <?= ($comp['table_letter_spacing'] ?? '')=='2px'?'selected':'' ?>>2px</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Text Transform</label>
                        <select name="table_text_transform" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="none" <?= ($comp['table_text_transform'] ?? 'none')=='none'?'selected':'' ?>>None</option>
                            <option value="uppercase" <?= ($comp['table_text_transform'] ?? '')=='uppercase'?'selected':'' ?>>Uppercase</option>
                            <option value="lowercase" <?= ($comp['table_text_transform'] ?? '')=='lowercase'?'selected':'' ?>>Lowercase</option>
                            <option value="capitalize" <?= ($comp['table_text_transform'] ?? '')=='capitalize'?'selected':'' ?>>Capitalize</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Text Decoration</label>
                        <select name="table_text_decoration" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="none" <?= ($comp['table_text_decoration'] ?? 'none')=='none'?'selected':'' ?>>None</option>
                            <option value="underline" <?= ($comp['table_text_decoration'] ?? '')=='underline'?'selected':'' ?>>Underline</option>
                            <option value="line-through" <?= ($comp['table_text_decoration'] ?? '')=='line-through'?'selected':'' ?>>Line-through</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Text Color</label>
                        <input type="color" name="table_text_color" value="<?= e($comp['table_text_color'] ?? '#374151') ?>" class="w-full h-10 border rounded cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Per-Block Typography Settings -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-700">
                            <span class="material-symbols-outlined text-base">layers</span>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900">Per-Block Typography Settings</h2>
                            <p class="text-[11px] text-slate-500 font-medium">Customize typography for each quotation block independently</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-5">
                    <?php
                    $blocks = [
                        'header' => 'Header (Company Name & Contact)',
                        'to_label' => 'To: Label',
                        'client_info' => 'Client Info (To section)',
                        'subject' => 'Subject',
                        'introduction' => 'Introduction / Greeting',
                        'overview' => 'Overview / Features',
                        'price_table' => 'Price Details Table',
                        'support_services' => 'Support & Services',
                        'time_period' => 'Time Period / Delivery',
                        'validity_text' => 'Validity of Offer',
                        'terms_conditions' => 'Terms & Conditions',
                        'signature' => 'Signature Area',
                        'footer' => 'Footer',
                    ];
                    $blockTypo = $comp['block_typography'] ? json_decode($comp['block_typography'], true) : [];
                    if(!is_array($blockTypo)) $blockTypo = [];
                    $fonts = ['inherit'=>'Default','Arial, sans-serif'=>'Arial',"'Times New Roman', serif"=>'Times New Roman',"'Courier New', monospace"=>'Courier New','Georgia, serif'=>'Georgia','Verdana, sans-serif'=>'Verdana',"'Trebuchet MS', sans-serif"=>'Trebuchet MS'];
                    $sizes = [''=>'Inherit','10px'=>'10px','11px'=>'11px','12px'=>'12px','13px'=>'13px','14px'=>'14px','15px'=>'15px','16px'=>'16px','18px'=>'18px','20px'=>'20px'];
                    $weights = [''=>'Inherit','normal'=>'Normal','bold'=>'Bold','300'=>'Light','500'=>'Medium','600'=>'Semi-Bold','700'=>'Bold 700'];
                    $lineHeights = [''=>'Inherit','1.0'=>'1.0','1.2'=>'1.2','1.4'=>'1.4','1.6'=>'1.6','1.8'=>'1.8','2.0'=>'2.0'];
                    foreach($blocks as $key => $label):
                        $bt = $blockTypo[$key] ?? [];
                    ?>
                    <div class="border border-slate-200 rounded-xl p-5 bg-white">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 text-[10px]">
                                    <span class="material-symbols-outlined">cube</span>
                                </div>
                                <h3 class="font-bold text-slate-800 text-sm"><?= e($label) ?></h3>
                            </div>
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Block</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Font Family</label>
                                <select name="block[<?= $key ?>][font_family]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <?php foreach($fonts as $v=>$l): ?>
                                    <option value="<?= e($v) ?>" <?= ($bt['font_family'] ?? '')==$v?'selected':'' ?>><?= e($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Font Size</label>
                                <select name="block[<?= $key ?>][font_size]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <?php foreach($sizes as $v=>$l): ?>
                                    <option value="<?= e($v) ?>" <?= ($bt['font_size'] ?? '')==$v?'selected':'' ?>><?= e($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Style</label>
                                <select name="block[<?= $key ?>][font_style]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <option value="" <?= empty($bt['font_style'])?'selected':'' ?>>Inherit</option>
                                    <option value="normal" <?= ($bt['font_style'] ?? '')=='normal'?'selected':'' ?>>Normal</option>
                                    <option value="italic" <?= ($bt['font_style'] ?? '')=='italic'?'selected':'' ?>>Italic</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Weight</label>
                                <select name="block[<?= $key ?>][font_weight]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <?php foreach($weights as $v=>$l): ?>
                                    <option value="<?= e($v) ?>" <?= ($bt['font_weight'] ?? '')==$v?'selected':'' ?>><?= e($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Line Height</label>
                                <select name="block[<?= $key ?>][line_height]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <?php foreach($lineHeights as $v=>$l): ?>
                                    <option value="<?= e($v) ?>" <?= ($bt['line_height'] ?? '')==$v?'selected':'' ?>><?= e($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Letter Spacing</label>
                                <select name="block[<?= $key ?>][letter_spacing]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <option value="" <?= empty($bt['letter_spacing'])?'selected':'' ?>>Inherit</option>
                                    <option value="normal" <?= ($bt['letter_spacing'] ?? '')=='normal'?'selected':'' ?>>Normal</option>
                                    <option value="0.5px" <?= ($bt['letter_spacing'] ?? '')=='0.5px'?'selected':'' ?>>0.5px</option>
                                    <option value="1px" <?= ($bt['letter_spacing'] ?? '')=='1px'?'selected':'' ?>>1px</option>
                                    <option value="1.5px" <?= ($bt['letter_spacing'] ?? '')=='1.5px'?'selected':'' ?>>1.5px</option>
                                    <option value="2px" <?= ($bt['letter_spacing'] ?? '')=='2px'?'selected':'' ?>>2px</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Text Transform</label>
                                <select name="block[<?= $key ?>][text_transform]" class="w-full px-2 py-2 border border-slate-300 rounded-lg text-xs focus:outline-none focus:border-emerald-500 transition-all">
                                    <option value="" <?= empty($bt['text_transform'])?'selected':'' ?>>Inherit</option>
                                    <option value="none" <?= ($bt['text_transform'] ?? '')=='none'?'selected':'' ?>>None</option>
                                    <option value="uppercase" <?= ($bt['text_transform'] ?? '')=='uppercase'?'selected':'' ?>>Uppercase</option>
                                    <option value="lowercase" <?= ($bt['text_transform'] ?? '')=='lowercase'?'selected':'' ?>>Lowercase</option>
                                    <option value="capitalize" <?= ($bt['text_transform'] ?? '')=='capitalize'?'selected':'' ?>>Capitalize</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Text Color</label>
                                <input type="color" name="block[<?= $key ?>][text_color]" value="<?= e($bt['text_color'] ?? '#374151') ?>" class="w-full h-9 border rounded cursor-pointer">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                    <span class="material-symbols-outlined text-base">save</span><span>Save Settings</span>
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once '../../includes/footer.php'; ?>
