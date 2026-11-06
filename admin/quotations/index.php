<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();

$status = $_GET['status'] ?? null;
$quotations = $quotation->getAll($status);

$pageTitle = 'Quotations';
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
                        <span class="tracking-wide">Quotation Pipeline</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Quotations</h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Manage all your quotations</p>
            </div>
            <a href="<?= BASE_URL ?>admin/quotations/create.php" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                <span class="material-symbols-outlined text-base">add_circle</span><span>Create New Quotation</span>
            </a>
        </div>

        <!-- Stats Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Total Quotations</h2>
                    <p class="text-xs font-medium text-slate-500">All quotation records</p>
                </div>
                <div class="text-3xl font-extrabold text-emerald-700"><?= count($quotations) ?></div>
            </div>
        </div>

        <!-- Status Filters -->
        <div class="flex flex-wrap gap-2">
            <a href="<?= BASE_URL ?>admin/quotations/index.php" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= !$status ? 'bg-emerald-700 text-white shadow-sm shadow-emerald-700/25' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">All</a>
            <a href="<?= BASE_URL ?>admin/quotations/index.php?status=draft" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $status=='draft' ? 'bg-slate-100 text-slate-800 font-semibold border border-slate-200' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">Draft</a>
            <a href="<?= BASE_URL ?>admin/quotations/index.php?status=sent" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $status=='sent' ? 'bg-amber-50 text-amber-800 font-semibold border border-amber-200' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">Sent</a>
            <a href="<?= BASE_URL ?>admin/quotations/index.php?status=accepted" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $status=='accepted' ? 'bg-emerald-50 text-emerald-800 font-semibold border border-emerald-200' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">Accepted</a>
            <a href="<?= BASE_URL ?>admin/quotations/index.php?status=rejected" class="px-4 py-2.5 rounded-xl text-xs font-bold transition-all duration-200 <?= $status=='rejected' ? 'bg-red-50 text-red-800 font-semibold border border-red-200' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-50' ?>">Rejected</a>
        </div>

        <!-- Quotations Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-200 bg-white">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">table_chart</span> Quotation Register
                </h2>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6">Quotation #</th>
                            <th class="py-4 px-6">Client</th>
                            <th class="py-4 px-6">Subject</th>
                            <th class="py-4 px-6 text-right">Amount</th>
                            <th class="py-4 px-6 text-center">Status</th>
                            <th class="py-4 px-6">Date</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100 font-medium">
                        <?php if(empty($quotations)): ?>
                        <tr><td colspan="7" class="py-12 text-center text-slate-500">No quotations found.<br><a href="<?= BASE_URL ?>admin/quotations/create.php" class="text-emerald-700 font-bold hover:underline">Create your first quotation</a></td></tr>
                        <?php else: ?>
                        <?php foreach($quotations as $q): ?>
                        <?php
                        $statusColor = 'bg-slate-100 text-slate-700 border-slate-200';
                        $statusLabel = ucfirst($q['status']);
                        if($q['status']=='draft') { $statusColor = 'bg-slate-100 text-slate-700 border-slate-200'; $statusLabel = 'Draft'; }
                        elseif($q['status']=='sent') { $statusColor = 'bg-amber-100 text-amber-800 border-amber-200'; $statusLabel = 'Sent'; }
                        elseif($q['status']=='accepted') { $statusColor = 'bg-emerald-100 text-emerald-800 border-emerald-200'; $statusLabel = 'Accepted'; }
                        elseif($q['status']=='rejected') { $statusColor = 'bg-red-100 text-red-700 border-red-200'; $statusLabel = 'Rejected'; }
                        ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-6">
                                <a href="<?= BASE_URL ?>admin/quotations/view.php?id=<?= $q['id'] ?>" class="font-mono text-emerald-800 font-bold hover:underline bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"><?= e($q['quotation_number']) ?></a>
                            </td>
                            <td class="py-4 px-6">
                                <div class="flex flex-col">
                                    <span class="font-bold text-slate-900"><?= e($q['client_name']) ?></span>
                                    <span class="text-[11px] text-slate-500"><?= e($q['company']) ?></span>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-slate-700"><?= e($q['subject']) ?></td>
                            <td class="py-4 px-6 text-right font-mono font-extrabold text-slate-900"><?= formatCurrency($q['grand_total'], $comp['currency']) ?></td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold px-2.5 py-1 rounded-full <?= $statusColor ?>"><?= $statusLabel ?></span>
                            </td>
                            <td class="py-4 px-6 text-slate-500 font-medium"><?= date('d M Y', strtotime($q['quotation_date'])) ?></td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <a href="<?= BASE_URL ?>admin/quotations/view.php?id=<?= $q['id'] ?>" class="p-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors border border-emerald-200" title="View"><span class="material-symbols-outlined text-sm">visibility</span></a>
                                <a href="<?= BASE_URL ?>admin/quotations/preview.php?id=<?= $q['id'] ?>" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors border border-slate-200" title="Preview"><span class="material-symbols-outlined text-sm">desktop_windows</span></a>
                                <a href="<?= BASE_URL ?>admin/quotations/edit.php?id=<?= $q['id'] ?>" class="p-2 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors border border-emerald-200" title="Edit"><span class="material-symbols-outlined text-sm">edit</span></a>
                                <a href="<?= BASE_URL ?>admin/quotations/duplicate.php?id=<?= $q['id'] ?>" class="p-2 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 transition-colors border border-amber-200" title="Duplicate"><span class="material-symbols-outlined text-sm">content_copy</span></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<?php require_once '../../includes/footer.php'; ?>
