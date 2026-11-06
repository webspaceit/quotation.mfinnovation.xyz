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

$id = intval($_GET['id']);
$q = $quotation->getById($id);
if(!$q) {
    header('Location: ' . BASE_URL . 'admin/quotations/index.php');
    exit;
}

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $signatureImage = $q['signature_image'];
        if(isset($_FILES['signature_image']) && $_FILES['signature_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = uploadFile($_FILES['signature_image']);
            if($uploaded) $signatureImage = $uploaded;
        }
        if(isset($_POST['remove_signature'])) {
            $signatureImage = null;
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
            'authorized_signature' => $_POST['authorized_signature'] ?? $q['authorized_signature'],
            'quotation_date' => $_POST['quotation_date'],
            'validity_days' => $_POST['validity_days'] ?? $comp['default_validity_days']
        ];
        $quotation->update($id, $data);
        
        $db = Database::getInstance();
        $db->query("DELETE FROM quotation_items WHERE quotation_id = ?", [$id]);
        
        if(isset($_POST['items']['description'])) {
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
                    [$id, $idx, $desc, $qty, $price, $amount, $remarks]
                );
            }
            $taxPercent = floatval($_POST['tax_percent'] ?? 0);
            $taxAmount = $subtotal * $taxPercent / 100;
            $grandTotal = $subtotal + $taxAmount;
            $quotation->updateTotals($id, $subtotal, $taxPercent, $taxAmount, $grandTotal);
        }
        header('Location: ' . BASE_URL . 'admin/quotations/view.php?id=' . $id . '&updated=1');
        exit;
    } catch(Exception $e) {
        $error = 'Error updating quotation: ' . $e->getMessage();
    }
}

$pageTitle = 'Edit Quotation';
require_once '../../includes/header.php';
?>
<main class="w-full pt-16 bg-slate-50 min-h-screen">
    <div class="p-8 flex flex-col gap-6 max-w-[1680px] mx-auto w-full">
        <!-- Top Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1.5">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs shadow-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                        <span class="tracking-wide">Edit Quotation</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Edit Quotation <?= e($q['quotation_number']) ?></h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Update quotation details</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>admin/quotations/view.php?id=<?= $q['id'] ?>" class="px-4 py-2.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">arrow_back</span> Back to View
                </a>
                <a href="<?= BASE_URL ?>admin/quotations/preview.php?id=<?= $q['id'] ?>" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">desktop_windows</span> Preview
                </a>
            </div>
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
                            <option value="<?= $c['id'] ?>" <?= $q['client_id'] == $c['id'] ? 'selected' : '' ?>><?= e($c['company'] ?: $c['name']) ?> - <?= e($c['email']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Quotation Date <span class="text-red-500">*</span></label>
                        <input type="date" name="quotation_date" value="<?= e($q['quotation_date']) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subject <span class="text-red-500">*</span></label>
                        <input type="text" name="subject" value="<?= e($q['subject']) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Prepared By</label>
                        <input type="text" name="prepared_by" value="<?= e($q['prepared_by']) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Introduction / Greeting</label>
                        <textarea name="introduction" rows="4" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['introduction']) ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Overview / Features</label>
                        <textarea name="overview" rows="5" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['overview']) ?></textarea>
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
                            <?php if(empty($q['items'])): ?>
                            <tr class="item-row">
                                <td class="px-3 py-3 border-b border-slate-100 text-center">1</td>
                                <td class="px-3 py-3 border-b border-slate-100"><textarea name="items[description][]" rows="3" class="w-full px-2 py-1.5 border border-slate-300 rounded focus:outline-none focus:border-emerald-500"></textarea></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[quantity][]" class="w-full px-2 py-1.5 border border-slate-300 rounded qty" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="1"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[unit_price][]" class="w-full px-2 py-1.5 border border-slate-300 rounded price" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="0"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[amount][]" class="w-full px-2 py-1.5 border border-slate-300 rounded amount" readonly value="0"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="text" name="items[remarks][]" class="w-full px-2 py-1.5 border border-slate-300 rounded"></td>
                                <td class="px-3 py-3 border-b border-slate-100 text-center">
                        <button type="button" onclick="duplicateItemRow(this)" class="text-blue-600 hover:text-blue-800 mr-1" title="Duplicate row"><span class="material-symbols-outlined">content_copy</span></button>
                        <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800" title="Remove row"><span class="material-symbols-outlined">delete</span></button>
                    </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach($q['items'] as $idx => $item): ?>
                            <tr class="item-row">
                                <td class="px-3 py-3 border-b border-slate-100 text-center"><?= $idx + 1 ?></td>
                                <td class="px-3 py-3 border-b border-slate-100"><textarea name="items[description][]" rows="3" class="w-full px-2 py-1.5 border border-slate-300 rounded focus:outline-none focus:border-emerald-500"><?= e($item['description']) ?></textarea></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[quantity][]" class="w-full px-2 py-1.5 border border-slate-300 rounded qty" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="<?= e($item['quantity']) ?>"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[unit_price][]" class="w-full px-2 py-1.5 border border-slate-300 rounded price" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="<?= e($item['unit_price']) ?>"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[amount][]" class="w-full px-2 py-1.5 border border-slate-300 rounded amount" readonly value="<?= e($item['amount']) ?>"></td>
                                <td class="px-3 py-3 border-b border-slate-100"><input type="text" name="items[remarks][]" class="w-full px-2 py-1.5 border border-slate-300 rounded" value="<?= e($item['remarks']) ?>"></td>
                                <td class="px-3 py-3 border-b border-slate-100 text-center">
                        <button type="button" onclick="duplicateItemRow(this)" class="text-blue-600 hover:text-blue-800 mr-1" title="Duplicate row"><span class="material-symbols-outlined">content_copy</span></button>
                        <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800" title="Remove row"><span class="material-symbols-outlined">delete</span></button>
                    </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="mt-4 flex justify-end">
                        <div class="w-full md:w-1/3 space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Subtotal:</span>
                                <input type="text" id="subtotal" value="<?= number_format($q['subtotal'], 2) ?>" readonly class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right bg-slate-50">
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Tax (%):</span>
                                <input type="number" step="0.01" id="tax_percent" name="tax_percent" value="<?= $q['tax_percent'] ?>" class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right" onchange="calculateTotal()" onkeyup="calculateTotal()">
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-slate-700">Tax Amount:</span>
                                <input type="text" id="tax_amount" value="<?= number_format($q['tax_amount'], 2) ?>" readonly class="w-40 px-2 py-1.5 border border-slate-300 rounded text-right bg-slate-50">
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-slate-200">
                                <span class="font-bold text-lg text-slate-900">Grand Total:</span>
                                <input type="text" id="grand_total" value="<?= number_format($q['grand_total'], 2) ?>" readonly class="w-40 px-2 py-1.5 border-2 border-emerald-500 rounded text-right bg-emerald-50 font-bold">
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
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Support & Services</label>
                        <textarea name="support_services" rows="4" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['support_services']) ?></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Time Period / Delivery Timeline</label>
                        <textarea name="time_period" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['time_period']) ?></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Validity of Offer</label>
                            <textarea name="validity_text" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['validity_text']) ?></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Validity Days</label>
                            <input type="number" name="validity_days" value="<?= ceil((strtotime($q['valid_until']) - strtotime($q['quotation_date']))/86400) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Terms & Conditions</label>
                        <textarea name="terms_conditions" rows="7" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"><?= e($q['terms_conditions']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Signature -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-200 bg-white">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center text-green-700">
                            <span class="material-symbols-outlined text-base">signature</span>
                        </div>
                        <h2 class="text-base font-extrabold text-slate-900">Signature</h2>
                    </div>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Signature Image (optional)</label>
                        <div class="flex flex-col gap-2">
                            <?php if($q['signature_image']): ?>
                            <img src="<?= BASE_URL ?>assets/uploads/<?= e($q['signature_image']) ?>" alt="Signature" class="h-10 w-auto object-contain border border-slate-200 rounded mb-2">
                            <label class="flex items-center text-sm text-gray-600 mb-2"><input type="checkbox" name="remove_signature" value="1" class="w-3 h-3 text-emerald-600 border-slate-300 rounded focus:ring-emerald-600"> Remove current signature</label>
                            <?php endif; ?>
                            <input type="file" name="signature_image" accept="image/*" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 transition-all">
                            <p class="text-[11px] text-slate-500">JPG, PNG, GIF, WebP — max 2MB</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Authorized Signature Label</label>
                        <input type="text" name="authorized_signature" value="<?= e($q['authorized_signature'] ?: ($comp['default_authorized_signature'] ?? 'Authorized Signature')) ?>" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <a href="<?= BASE_URL ?>admin/quotations/view.php?id=<?= $q['id'] ?>" class="px-6 py-3 rounded-lg bg-white border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-colors"><span class="material-symbols-outlined text-base">cancel</span> Cancel</a>
                <button type="submit" class="px-6 py-3 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-semibold shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all"><span class="material-symbols-outlined text-base">save</span> Update Quotation</button>
            </div>
        </form>
    </div>
</main>

<script src="../../assets/js/quotation.js"></script>
<?php require_once '../../includes/footer.php'; ?>
