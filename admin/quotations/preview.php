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
    header('Location: index.php');
    exit;
}

$tableStyle = 'table-layout: fixed; font-family: ' . e($comp['table_font_family'] ?? 'inherit') . '; font-size: ' . e($comp['table_font_size'] ?? '14px') . '; font-style: ' . e($comp['table_font_style'] ?? 'normal') . '; font-weight: ' . e($comp['table_font_weight'] ?? 'normal') . '; line-height: ' . e($comp['table_line_height'] ?? '1.4') . '; letter-spacing: ' . e($comp['table_letter_spacing'] ?? 'normal') . '; text-transform: ' . e($comp['table_text_transform'] ?? 'none') . '; text-decoration: ' . e($comp['table_text_decoration'] ?? 'none') . '; color: ' . e($comp['table_text_color'] ?? '#374151') . ';';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation <?= e($q['quotation_number']) ?> - <?= e($comp['company_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <?php if(!empty($comp['favicon'])): ?>
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL ?>assets/uploads/<?= e($comp['favicon']) ?>">
    <?php endif; ?>
    <style>
        @page {
            size: A4;
            margin: 0.4in;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; margin: 0; padding: 0; }
            .print-page { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: auto; min-height: 0; }
            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: 1000;
                margin: 0 !important;
                padding: 4mm 0 0 !important;
                background: #fff;
            }
            thead { display: table-row-group; }
            tfoot { display: table-row-group; }
        }
        .quotation-table th { background-color: #f0fdf4; }
        .quotation-table td, .quotation-table th { border: 1px solid #d1d5db; }
        .section-title { border-bottom: 2px solid #16a34a; padding-bottom: 4px; margin-bottom: 8px; }
    </style>
</head>
<body class="bg-gray-100 p-2 sm:p-4">
    <div class="max-w-4xl mx-auto mb-4 no-print flex flex-wrap gap-2">
        <a href="<?= BASE_URL ?>admin/quotations/view.php?id=<?= $q['id'] ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <button onclick="window.print()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"><i class="fas fa-print mr-1"></i> Print / Save PDF</button>
        <button onclick="openEmailModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fas fa-envelope mr-1"></i> Send Email</button>
    </div>

    <div class="max-w-4xl mx-auto bg-white shadow-lg print-page" style="padding: 30px 40px; <?= $tableStyle ?>">
<?php
// Per-block typography styles
$introStyle = $company->getBlockStyle('introduction');
$overviewStyle = $company->getBlockStyle('overview');
$supportStyle = $company->getBlockStyle('support_services');
$timeStyle = $company->getBlockStyle('time_period');
$validityStyle = $company->getBlockStyle('validity_text');
$termsStyle = $company->getBlockStyle('terms_conditions');
$clientStyle = $company->getBlockStyle('client_info');
$toLabelStyle = $company->getBlockStyle('to_label');
$subjectStyle = $company->getBlockStyle('subject');
$footerStyle = $company->getBlockStyle('footer');
$headerStyle = $company->getBlockStyle('header');
$signatureStyle = $company->getBlockStyle('signature');
?>
        <!-- Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start mb-4 pb-4 border-b-2 border-green-600" style="<?= $headerStyle ?>">
            <div class="flex-1 mb-4 sm:mb-0">
                <?php if($comp['logo']): ?>
                <img src="<?= BASE_URL ?>assets/uploads/<?= e($comp['logo']) ?>" alt="Logo" class="h-14 sm:h-16 mb-2 object-contain">
                <?php else: ?>
                <h1 class="text-2xl font-bold text-green-700 mb-1"><?= e($comp['company_name']) ?></h1>
                <?php endif; ?>
                <p class="text-gray-600 text-sm">Phone: <?= e($comp['phone']) ?> | Email: <?= e($comp['email']) ?><?php if($comp['website']): ?> | Web: <?= e($comp['website']) ?><?php endif; ?></p>
            </div>
            <div class="text-left sm:text-right">
                <h2 class="text-3xl font-bold text-green-700 mb-2 tracking-wide">QUOTATION</h2>
                <div class="text-sm text-gray-700 space-y-1">
                    <p><span class="font-semibold">Quotation No:</span> <?= e($q['quotation_number']) ?></p>
                    <p><span class="font-semibold">Date:</span> <?= date('d M Y', strtotime($q['quotation_date'])) ?></p>
                    <p><span class="font-semibold">Valid Until:</span> <?= date('d M Y', strtotime($q['valid_until'])) ?></p>
                </div>
            </div>
        </div>

        <!-- To -->
        <div class="mb-5" style="<?= $clientStyle ?>">
            <p class="font-semibold uppercase tracking-wide mb-1" style="<?= $toLabelStyle ?>">To:</p>
            <p class="font-bold text-lg"><?= e($q['client_name']) ?></p>
            <?php if($q['company']): ?><p><?= e($q['company']) ?></p><?php endif; ?>
            <?php if($q['client_address']): ?><p class="text-sm mt-1 whitespace-pre-wrap"><?= e($q['client_address']) ?></p><?php endif; ?>
            <?php if($q['client_email']): ?><p class="text-sm"><?= e($q['client_email']) ?></p><?php endif; ?>
            <?php if($q['client_phone']): ?><p class="text-sm"><?= e($q['client_phone']) ?></p><?php endif; ?>
        </div>

        <div class="mb-5">
            <p style="<?= $subjectStyle ?>"><span class="font-semibold">Subject:</span> <?= e($q['subject']) ?></p>
        </div>

        <?php if($q['introduction']): ?>
        <div class="mb-5 leading-relaxed whitespace-pre-wrap" style="<?= $introStyle ?>"><?= e($q['introduction']) ?></div>
        <?php endif; ?>

        <?php if($q['overview']): ?>
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Overview</h3>
            <div class="leading-relaxed whitespace-pre-wrap" style="<?= $overviewStyle ?>"><?= e($q['overview']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Price Table -->
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Price Details</h3>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse quotation-table text-sm" style="<?= $tableStyle ?> table-layout: fixed;">
                    <colgroup>
                        <col style="width: 5%;">
                        <col style="width: 45%;">
                        <col style="width: 8%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="px-3 py-1 text-center font-semibold text-gray-700">SL</th>
                            <th class="px-3 py-1 text-left font-semibold text-gray-700">Description</th>
                            <th class="px-3 py-1 text-center font-semibold text-gray-700">Qty</th>
                            <th class="px-3 py-1 text-right font-semibold text-gray-700">Unit Price</th>
                            <th class="px-3 py-1 text-right font-semibold text-gray-700">Amount</th>
                            <th class="px-3 py-1 text-left font-semibold text-gray-700">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($q['items'] as $idx => $item): ?>
                        <tr class="<?= $idx % 2 === 1 ? 'bg-gray-50' : 'bg-white' ?>">
                            <td class="px-3 py-1 text-center"><?= $idx + 1 ?></td>
                            <td class="px-3 py-1"><?= e($item['description']) ?></td>
                            <td class="px-3 py-1 text-center"><?= $item['quantity'] ?></td>
                            <td class="px-3 py-1 text-right"><?= number_format($item['unit_price'], 2) ?></td>
                            <td class="px-3 py-1 text-right font-semibold"><?= number_format($item['amount'], 2) ?></td>
                            <td class="px-3 py-1"><?= e($item['remarks']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="px-3 py-1 text-right font-semibold">Subtotal:</td>
                            <td class="px-3 py-1 text-right font-semibold"><?= formatCurrency($q['subtotal'], $comp['currency']) ?></td>
                            <td class="px-3 py-1"></td>
                        </tr>
                        <?php if($q['tax_percent'] > 0): ?>
                        <tr>
                            <td colspan="4" class="px-3 py-1 text-right">Tax (<?= $q['tax_percent'] ?>%):</td>
                            <td class="px-3 py-1 text-right"><?= formatCurrency($q['tax_amount'], $comp['currency']) ?></td>
                            <td class="px-3 py-1"></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="bg-green-50">
                            <td colspan="4" class="px-3 py-1 text-right font-bold">Grand Total:</td>
                            <td class="px-3 py-1 text-right font-bold text-green-700"><?= formatCurrency($q['grand_total'], $comp['currency']) ?></td>
                            <td class="px-3 py-1"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <?php if($q['support_services']): ?>
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Support & Services</h3>
            <div class="leading-relaxed whitespace-pre-wrap" style="<?= $supportStyle ?>"><?= e($q['support_services']) ?></div>
        </div>
        <?php endif; ?>

        <?php if($q['time_period']): ?>
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Time Period / Delivery</h3>
            <div class="leading-relaxed whitespace-pre-wrap" style="<?= $timeStyle ?>"><?= e($q['time_period']) ?></div>
        </div>
        <?php endif; ?>

        <?php if($q['validity_text']): ?>
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Validity of Offer</h3>
            <div class="leading-relaxed whitespace-pre-wrap" style="<?= $validityStyle ?>"><?= e($q['validity_text']) ?></div>
        </div>
        <?php endif; ?>

        <?php if($q['terms_conditions']): ?>
        <div class="mb-5">
            <h3 class="font-bold text-green-700 section-title">Terms & Conditions</h3>
            <div class="leading-relaxed whitespace-pre-wrap text-sm" style="<?= $termsStyle ?>"><?= e($q['terms_conditions']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Signature -->
        <div class="mt-10 pt-6" style="<?= $signatureStyle ?>">
            <div class="flex justify-end">
                <div class="w-1/2 text-center">
                    <div class="pt-4">
                        <?php if($q['signature_image']): ?>
                        <img src="<?= BASE_URL ?>assets/uploads/<?= e($q['signature_image']) ?>" alt="Signature" class="mx-auto mb-2">
                        <?php endif; ?>
                        <div class="w-1/2 mx-auto border-t-2 border-gray-400 pt-2 mt-4">
                            <p class="font-semibold text-gray-800"><?= e($q['authorized_signature'] ?: 'Authorized Signature') ?></p>
                            <p class="text-gray-500 text-sm"><?= e($comp['company_name']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="print-footer mt-6 pt-4 border-t text-center text-xs" style="<?= $footerStyle ?>">
            <p><?= e($comp['company_name']) ?> | <?= e($comp['address']) ?> | Phone: <?= e($comp['phone']) ?> | Email: <?= e($comp['email']) ?></p>
        </div>
    </div>

    <!-- Email Modal -->
    <div id="emailModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 no-print">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="px-6 py-4 border-b bg-blue-50 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800"><i class="fas fa-envelope mr-2"></i> Send Quotation via Email</h2>
                <button onclick="closeEmailModal()" class="text-gray-600 hover:text-gray-900"><i class="fas fa-times text-xl"></i></button>
            </div>
            <form id="emailForm" class="p-6 space-y-4">
                <input type="hidden" name="quotation_id" value="<?= $q['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">To *</label>
                    <input type="email" name="to_email" value="<?= e($q['client_email']) ?>" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">CC</label>
                        <input type="email" name="cc" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2">BCC</label>
                        <input type="email" name="bcc" class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Subject *</label>
                    <input type="text" name="subject" value="Quotation <?= e($q['quotation_number']) ?> - <?= e($comp['company_name']) ?>" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-bold mb-2">Message *</label>
                    <textarea name="message" rows="8" required class="w-full px-3 py-2 border rounded focus:outline-none focus:border-blue-500"><?= e($comp['email_template'] ?: "Dear " . $q['client_name'] . ",\n\nPlease find attached our quotation " . $q['quotation_number'] . " for your review.\n\nTotal Amount: " . formatCurrency($q['grand_total'], $comp['currency']) . "\n\nValid Until: " . date('d M Y', strtotime($q['valid_until'])) . "\n\nBest regards,\n" . $comp['company_name']) ?></textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" name="attach_pdf" id="attach_pdf" checked class="mr-2">
                    <label for="attach_pdf" class="text-gray-700">Attach PDF of quotation</label>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEmailModal()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</button>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"><i class="fas fa-paper-plane mr-1"></i> Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
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
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';
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
            } else {
                alert('Error: ' + data.message);
            }
        } catch(err) {
            alert('Error sending email');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane mr-1"></i> Send Email';
    });
    </script>
</body>
</html>
