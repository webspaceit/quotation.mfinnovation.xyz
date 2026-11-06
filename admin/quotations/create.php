<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
verify_csrf();

$client = new Client();
$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();
$clients = $client->getAll();

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $signatureImage = null;
        if(isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
            $signatureImage = uploadFile($_FILES['signature_image']);
        }
        $data = [
            'client_id' => $_POST['client_id'],
            'subject' => $_POST['subject'],
            'introduction' => $_POST['introduction'],
            'overview' => $_POST['overview'],
            'support_services' => $_POST['support_services'],
            'time_period' => $_POST['time_period'],
            'validity_text' => $_POST['validity_text'],
            'terms_conditions' => $_POST['terms_conditions'],
            'prepared_by' => $_POST['prepared_by'],
            'signature_image' => $signatureImage,
            'authorized_signature' => $_POST['authorized_signature'] ?? $comp['default_authorized_signature'],
            'quotation_date' => $_POST['quotation_date'],
            'validity_days' => $_POST['validity_days'] ?? $comp['default_validity_days']
        ];
        $qId = $quotation->create($data);
        
        if(isset($_POST['items']['description'])) {
            $db = Database::getInstance();
            $subtotal = 0;
            foreach($_POST['items']['description'] as $idx => $desc) {
                if(empty(trim($desc))) continue;
                $qty = floatval($_POST['items']['quantity'][$idx] ?? 0);
                $price = floatval($_POST['items']['unit_price'][$idx] ?? 0);
                $amount = $qty * $price;
                $subtotal += $amount;
                $remarks = $_POST['items']['remarks'][$idx] ?? '';
                $db->query(
                    "INSERT INTO quotation_items (quotation_id, sort_order, description, quantity, unit_price, amount, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$qId, $idx, $desc, $qty, $price, $amount, $remarks]
                );
            }
            $taxPercent = floatval($_POST['tax_percent'] ?? 0);
            $taxAmount = $subtotal * $taxPercent / 100;
            $grandTotal = $subtotal + $taxAmount;
            $quotation->updateTotals($qId, $subtotal, $taxPercent, $taxAmount, $grandTotal);
        }
        header('Location: ' . BASE_URL . 'admin/quotations/view.php?id=' . $qId . '&created=1');
        exit;
    } catch(Exception $e) {
        $error = 'Error creating quotation: ' . $e->getMessage();
    }
}

$pageTitle = 'Create Quotation';
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
                        <span class="tracking-wide">Create Quotation</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Create New Quotation</h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Fill in the details to create a professional quotation</p>
            </div>
            <a href="<?= BASE_URL ?>admin/quotations/index.php" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-white border border-slate-300 text-slate-700 font-bold text-xs shadow-xs hover:bg-slate-50 hover:text-slate-900 transition-colors">
                <span class="material-symbols-outlined text-base">arrow_back</span><span>Back to List</span>
            </a>
        </div>

        <?php if($error): ?>
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-red-600 mt-0.5">error</span>
            <div>
                <h3 class="text-sm font-bold text-red-800"><?= e($error) ?></h3>
            </div>
        </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" class="space-y-8">
            <?php csrf_field(); ?>

            <!-- Header Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                            <span class="material-symbols-outlined text-base">info</span>
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900">Quotation Header</h2>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Client <span class="text-red-500">*</span></label>
                        <select name="client_id" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                            <option value="">Select Client</option>
                            <?php foreach($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['company'] ?: $c['name']) ?> - <?= e($c['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Quotation Date <span class="text-red-500">*</span></label>
                        <input type="date" name="quotation_date" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subject <span class="text-red-500">*</span></label>
                        <input type="text" name="subject" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all" placeholder="e.g., Quotation for Web Hosting Services">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Prepared By</label>
                        <input type="text" name="prepared_by" value="<?= e($currentUser['name']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Signature Image (optional)</label>
                        <div class="flex flex-col gap-2">
                            <input type="file" name="signature_image" accept="image/*" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 transition-all">
                            <p class="text-[11px] text-slate-500">JPG, PNG, GIF, WebP — max 2MB</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Authorized Signature Label</label>
                        <input type="text" name="authorized_signature" value="<?= e($comp['default_authorized_signature'] ?? 'Authorized Signature') ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Introduction / Greeting</label>
                        <textarea name="introduction" rows="4" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">Dear [Client Name],

Thank you for your inquiry. We are pleased to submit our quotation as follows:</textarea>
                    </div>
                </div>
            </div>

            <!-- Price Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                            <span class="material-symbols-outlined text-base">table</span>
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900">Price Details</h2>
                    </div>
                    <button type="button" onclick="addItemRow()" class="px-3 py-2 rounded-lg bg-emerald-50 text-emerald-700 font-semibold hover:bg-emerald-100 transition-colors border border-emerald-200 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-base">add</span> Add Row
                    </button>
                </div>
                <div class="p-6 overflow-x-auto">
                    <table class="w-full border border-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-3 py-2 border-b border-slate-200 text-center w-12 text-[11px] font-bold uppercase text-slate-500">SL</th>
                                <th class="px-3 py-2 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">Description</th>
                                <th class="px-3 py-2 border-b border-slate-200 w-24 text-[11px] font-bold uppercase text-slate-500">Quantity</th>
                                <th class="px-3 py-2 border-b border-slate-200 w-32 text-[11px] font-bold uppercase text-slate-500">Unit Price (<?= e($comp['currency']) ?>)</th>
                                <th class="px-3 py-2 border-b border-slate-200 w-32 text-[11px] font-bold uppercase text-slate-500">Amount (<?= e($comp['currency']) ?>)</th>
                                <th class="px-3 py-2 border-b border-slate-200 w-40 text-[11px] font-bold uppercase text-slate-500">Remarks</th>
                                <th class="px-3 py-2 border-b border-slate-200 w-16 text-[11px] font-bold uppercase text-slate-500">Action</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody">
                            <tr class="item-row">
                                <td class="px-3 py-3 border-b border-slate-100 text-center">1</td>
                                <td class="px-3 py-3 border-b border-slate-100"><textarea name="items[description][]" rows="3" class="w-full px-2 py-1.5 border border-slate-300 rounded focus:outline-none focus:border-emerald-500" placeholder="Item description..."></textarea></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[quantity][]" class="w-full px-2 py-1.5 border border-slate-300 rounded qty" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="1"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[unit_price][]" class="w-full px-2 py-1.5 border border-slate-300 rounded price" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="0"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[amount][]" class="w-full px-2 py-1.5 border border-slate-300 rounded amount" readonly value="0"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="text" name="items[remarks][]" class="w-full px-2 py-1.5 border border-slate-300 rounded" placeholder="Remarks"></td>
                                <td class="px-3 py-3 border-b border-slate-100 text-center">
                        <button type="button" onclick="duplicateItemRow(this)" class="text-blue-600 hover:text-blue-800 mr-1" title="Duplicate row"><span class="material-symbols-outlined">content_copy</span></button>
                        <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800" title="Remove row"><span class="material-symbols-outlined">delete</span></button>
                    </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-end">
                        <div class="w-full md:w-1/3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Subtotal:</span>
                                <input type="text" id="subtotal" value="0.00" readonly class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right bg-slate-50">
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Tax (%):</span>
                                <input type="number" step="0.01" id="tax_percent" name="tax_percent" value="0" class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right" onchange="calculateTotal()" onkeyup="calculateTotal()">
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Tax Amount:</span>
                                <input type="text" id="tax_amount" value="0.00" readonly class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right bg-slate-50">
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                                <span class="font-bold text-lg text-slate-900">Grand Total:</span>
                                <input type="text" id="grand_total" value="0.00" readonly class="w-40 px-2 py-1.5 border-2 border-emerald-500 rounded text-right bg-emerald-50 font-bold">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                            <span class="material-symbols-outlined text-base">list_alt</span>
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900">Additional Information</h2>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Overview / Features</label>
                        <textarea name="overview" rows="5" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all" placeholder="Enter features as bullet points or paragraphs..."></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Support & Services</label>
                        <textarea name="support_services" rows="4" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Time Period / Delivery Timeline</label>
                        <textarea name="time_period" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Validity of Offer</label>
                            <textarea name="validity_text" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">This offer is valid for <?= e($comp['default_validity_days']) ?> days from the date of quotation.</textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Validity Days</label>
                            <input type="number" name="validity_days" value="<?= e($comp['default_validity_days']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Terms & Conditions</label>
                        <textarea name="terms_conditions" rows="7" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($comp['default_terms']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="<?= BASE_URL ?>admin/quotations/index.php" class="px-6 py-3 rounded-lg bg-white border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-colors"><span class="material-symbols-outlined text-base">cancel</span> Cancel</a>
                <button type="submit" class="px-6 py-3 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-semibold shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all"><span class="material-symbols-outlined text-base">save</span> Save as Draft</button>
            </div>
        </form>
    </div>
</main>

<script src="../../assets/js/quotation.js"></script>
<?php require_once '../../includes/footer.php'; ?>
