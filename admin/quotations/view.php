<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();

$id = intval($_GET['id']);
$q = $quotation->getById($id);
if(!$q) {
    header('Location: ' . BASE_URL . 'admin/quotations/index.php');
    exit;
}

$pageTitle = 'View Quotation ' . $q['quotation_number'];
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
                        <span class="tracking-wide">Quotation Detail</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Quotation <?= e($q['quotation_number']) ?></h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Status: <span class="font-bold"><?= ucfirst($q['status']) ?></span></p>
            </div>
            <div class="flex items-center gap-3">
                <a href="<?= BASE_URL ?>admin/quotations/index.php" class="px-4 py-2.5 rounded-lg bg-white border border-slate-300 text-slate-700 font-semibold hover:bg-slate-50 transition-colors flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">arrow_back</span> Back
                </a>
                <a href="<?= BASE_URL ?>admin/quotations/edit.php?id=<?= $q['id'] ?>" class="px-4 py-2.5 rounded-lg bg-emerald-50 text-emerald-700 font-semibold hover:bg-emerald-100 transition-colors border border-emerald-200 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">edit</span> Edit
                </a>
                <a href="<?= BASE_URL ?>admin/quotations/preview.php?id=<?= $q['id'] ?>" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">desktop_windows</span> Preview
                </a>
                <button onclick="openEmailModal()" class="px-4 py-2.5 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition-colors border border-blue-200 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">mail</span> Send Email
                </button>
                <a href="<?= BASE_URL ?>admin/quotations/duplicate.php?id=<?= $q['id'] ?>" class="px-4 py-2.5 rounded-lg bg-amber-50 text-amber-700 font-semibold hover:bg-amber-100 transition-colors border border-amber-200 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-base">content_copy</span> Duplicate
                </a>
            </div>
        </div>

        <?php if(isset($_GET['created'])): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-start gap-3">
            <span class="material-symbols-outlined text-emerald-600 mt-0.5">check_circle</span>
            <div>
                <h3 class="text-sm font-bold text-emerald-800">Quotation created successfully!</h3>
                <p class="text-[11px] text-emerald-700/80 mt-0.5">Your quotation has been saved.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <!-- Client Details -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[12px] text-emerald-600">person</span> Client Details
                </h3>
                <p class="text-lg font-bold text-slate-900 mb-1"><?= e($q['client_name']) ?></p>
                <?php if($q['company']): ?><p class="text-slate-700 text-sm mb-1"><?= e($q['company']) ?></p><?php endif; ?>
                <?php if($q['client_email']): ?><p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5"><span class="material-symbols-outlined text-[12px] text-slate-400">email</span><?= e($q['client_email']) ?></p><?php endif; ?>
                <?php if($q['client_phone']): ?><p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5"><span class="material-symbols-outlined text-[12px] text-slate-400">phone</span><?= e($q['client_phone']) ?></p><?php endif; ?>
                <?php if($q['client_address']): ?><p class="text-slate-600 text-sm mt-2 whitespace-pre-wrap"><?= nl2br(e($q['client_address'])) ?></p><?php endif; ?>
            </div>
            <!-- Quotation Info -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[12px] text-emerald-600">info</span> Quotation Info
                </h3>
                <p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5"><span class="material-symbols-outlined text-[12px] text-slate-400">today</span><strong>Date:</strong> <?= date('d M Y', strtotime($q['quotation_date'])) ?></p>
                <p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5"><span class="material-symbols-outlined text-[12px] text-slate-400">event</span><strong>Valid Until:</strong> <?= date('d M Y', strtotime($q['valid_until'])) ?></p>
                <?php if($q['signature_image']): ?>
                <div class="mt-3"><img src="<?= BASE_URL ?>assets/uploads/<?= e($q['signature_image']) ?>" alt="Signature" class="h-10 w-auto object-contain border border-slate-200 rounded"></div>
                <?php endif; ?>
                <p class="text-slate-600 text-sm mt-2 flex items-center gap-1.5"><span class="material-symbols-outlined text-[12px] text-slate-400">subject</span><strong>Subject:</strong><br class="hidden sm:block"><?= e($q['subject']) ?></p>
            </div>
            <!-- Amount -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-[12px] text-emerald-600">payments</span> Amount
                </h3>
                <p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5 justify-between"><span><strong>Subtotal:</strong></span><span class="font-mono font-bold text-slate-900"><?= formatCurrency($q['subtotal'], $comp['currency']) ?></span></p>
                <p class="text-slate-600 text-sm mb-1 flex items-center gap-1.5 justify-between"><span><strong>Tax (<?= $q['tax_percent'] ?>%):</strong></span><span class="font-mono font-bold text-slate-900"><?= formatCurrency($q['tax_amount'], $comp['currency']) ?></span></p>
                <p class="text-xl font-extrabold text-emerald-700 mt-3 flex items-center gap-1.5 justify-between"><span><strong>Grand Total:</strong></span><span class="font-mono"><?= formatCurrency($q['grand_total'], $comp['currency']) ?></span></p>
            </div>
        </div>

        <!-- Price Details Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-200 bg-white">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">table_chart</span> Price Details
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" style="table-layout: fixed; font-family: <?= e($comp['table_font_family'] ?? 'inherit') ?>; font-size: <?= e($comp['table_font_size'] ?? '14px') ?>; font-style: <?= e($comp['table_font_style'] ?? 'normal') ?>; font-weight: <?= e($comp['table_font_weight'] ?? 'normal') ?>; line-height: <?= e($comp['table_line_height'] ?? '1.4') ?>; letter-spacing: <?= e($comp['table_letter_spacing'] ?? 'normal') ?>; text-transform: <?= e($comp['table_text_transform'] ?? 'none') ?>; text-decoration: <?= e($comp['table_text_decoration'] ?? 'none') ?>; color: <?= e($comp['table_text_color'] ?? '#374151') ?>;">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 45%;">
                        <col style="width: 8%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                    </colgroup>
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500 text-center">SL</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">Description</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500 text-center">Qty</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500 text-right">Unit Price</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500 text-right">Amount</th>
                            <th class="px-4 py-3 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php foreach($q['items'] as $idx => $item): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm text-center text-slate-600 border-b border-slate-100"><?= $idx + 1 ?></td>
                            <td class="px-4 py-3 text-sm text-gray-900 whitespace-pre-wrap border-b border-slate-100"><?= e($item['description']) ?></td>
                            <td class="px-4 py-3 text-sm text-center text-slate-600 border-b border-slate-100"><?= $item['quantity'] ?></td>
                            <td class="px-4 py-3 text-sm text-right font-mono text-slate-900 border-b border-slate-100"><?= formatCurrency($item['unit_price'], $comp['currency']) ?></td>
                            <td class="px-4 py-3 text-sm text-right font-bold text-slate-900 border-b border-slate-100"><?= formatCurrency($item['amount'], $comp['currency']) ?></td>
                            <td class="px-4 py-3 text-sm text-slate-600 border-b border-slate-100"><?= e($item['remarks']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Email Modal -->
<div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
        <div class="p-5 border-b border-slate-200 bg-white flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center text-blue-700">
                    <span class="material-symbols-outlined text-base">mail</span>
                </div>
                <h2 class="text-base font-extrabold text-slate-900">Send Quotation via Email</h2>
            </div>
            <button onclick="closeEmailModal()" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form id="emailForm" class="p-6 space-y-5">
            <input type="hidden" name="quotation_id" value="<?= $q['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">To <span class="text-red-500">*</span></label>
                <input type="email" name="to_email" value="<?= e($q['client_email']) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">CC</label>
                    <input type="email" name="cc" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">BCC</label>
                    <input type="email" name="bcc" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Subject <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="Quotation <?= e($q['quotation_number']) ?> - <?= e($comp['company_name']) ?>" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Message <span class="text-red-500">*</span></label>
                <textarea name="message" rows="7" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-600/20 transition-all"><?= e($comp['email_template'] ?: "Dear " . $q['client_name'] . ",\n\nPlease find attached our quotation " . $q['quotation_number'] . " for your review.\n\nTotal Amount: " . formatCurrency($q['grand_total'], $comp['currency']) . "\n\nValid Until: " . date('d M Y', strtotime($q['valid_until'])) . "\n\nIf you have any questions, please do not hesitate to contact us.\n\nBest regards,\n" . $comp['company_name']) ?></textarea>
            </div>
            <div class="flex items-center gap-3 pt-2">
                <input type="checkbox" name="attach_pdf" id="attach_pdf" checked class="w-4 h-4 border-slate-300 rounded text-blue-600 focus:ring-blue-600">
                <label for="attach_pdf" class="text-sm text-slate-700">Attach PDF of quotation</label>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <button type="button" onclick="closeEmailModal()" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-semibold hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-blue-600 text-white font-semibold shadow-sm shadow-blue-600/25 hover:shadow-lg active:scale-[0.98] transition-all"><span class="material-symbols-outlined text-base">paper_plane</span> Send Email</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEmailModal() {
    document.getElementById('emailModal').classList.remove('hidden');
    document.getElementById('emailModal').classList.add('flex');
}
function closeEmailModal() {
    document.getElementById('emailModal').classList.add('hidden');
    document.getElementById('emailModal').classList.remove('flex');
}
document.getElementById('emailForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined">hourglass_empty</span> Sending...';
    const formData = new FormData(this);
    try {
        const res = await fetch('../../ajax/send_email.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(data.success) {
            alert('Email sent successfully!');
            closeEmailModal();
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    } catch(err) {
        alert('Error sending email');
    }
    btn.disabled = false;
    btn.innerHTML = '<span class="material-symbols-outlined">paper_plane</span> Send Email';
});
</script>
<?php require_once '../../includes/footer.php'; ?>
