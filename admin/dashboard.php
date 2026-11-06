<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
$quotation = new Quotation();
$company = new Company();
$comp = $company->getSettings();
$stats = $quotation->getStats();
$recent = $quotation->getAll();
$recent = array_slice($recent, 0, 8);

// Calculate total value
$totalValue = 0;
foreach($quotation->getAll() as $q) {
    $totalValue += floatval($q['grand_total']);
}
$winRate = $stats['total'] > 0 ? round($stats['accepted'] / $stats['total'] * 100, 1) : 0;
$avgValue = $stats['total'] > 0 ? $totalValue / $stats['total'] : 0;
$pageTitle = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        @layer base{html,body{margin:0;padding:0;font-family:'Inter',sans-serif;}body{overscroll-behavior:none;}main>:first-child{margin-top:0!important;}main>:last-child{margin-bottom:0!important;}}::-webkit-scrollbar{width:6px;height:6px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px;}
    </style>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <script id="tailwind-config">tailwind.config={darkMode:"class",theme:{extend:{colors:{"primary":"#00652c","primary-container":"#15803d","secondary":"#bb0112","secondary-container":"#e02928","surface":"#faf8ff","surface-container":"#eaedff","surface-container-high":"#e2e7ff","on-surface":"#131b2e","outline":"#6f7a6e","outline-variant":"#becabc"}}}}</script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen">
<aside class="fixed left-0 top-0 h-full w-64 bg-white/95 backdrop-blur-xl border-r border-slate-200 z-50 flex flex-col justify-between shadow-[4px_0_24px_rgba(0,0,0,0.04)]">
    <div class="flex flex-col flex-1 overflow-y-auto">
        <div class="h-20 px-4 flex items-center gap-3 border-b border-slate-200/80 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
            <div class="flex items-center gap-2.5 min-w-0">
                <?php if($comp['logo']): ?>
                <img alt="<?= e($comp['company_name']) ?>" class="h-10 w-auto object-contain shrink-0 drop-shadow-sm" src="<?= BASE_URL ?>assets/uploads/<?= e($comp['logo']) ?>"/>
                <?php else: ?>
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-700 to-green-600 flex items-center justify-center text-white font-bold text-sm shrink-0"><?= e(substr($comp['company_name'],0,2)) ?></div>
                <?php endif; ?>
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center gap-1">
                        <span class="font-black text-sm tracking-tight text-slate-900 truncate"><?= e($comp['company_name']) ?></span>
                        <span class="px-1 py-0.2 text-[9px] font-extrabold rounded bg-emerald-700 text-white shadow-xs">QMS</span>
                    </div>
                    <span class="text-[10px] font-semibold text-slate-500 tracking-wider uppercase truncate">Quotation Mgmt</span>
                </div>
            </div>
        </div>
        <div class="px-4 py-5">
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Main Menu</p>
            <nav class="flex flex-col gap-1.5">
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25" href="dashboard.php">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px]">dashboard</span><span>Dashboard</span></div>
                    <span class="w-2 h-2 rounded-full bg-emerald-200 animate-pulse"></span>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-all duration-200 group" href="quotations/index.php">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">request_quote</span><span>Quotations</span></div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-200 group-hover:bg-emerald-700 group-hover:text-white transition-colors"><?= $stats['total'] ?></span>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-all duration-200 group" href="clients/index.php">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">corporate_fare</span><span>Clients</span></div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-all duration-200 group" href="users/index.php">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">group</span><span>Users</span></div>
                </a>
                <a class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 transition-all duration-200 group" href="settings/index.php">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-slate-800 transition-colors">settings</span><span>Settings</span></div>
                    <span class="material-symbols-outlined text-sm text-slate-400">chevron_right</span>
                </a>
            </nav>
        </div>
    </div>
    <div class="p-4 border-t border-slate-200 bg-gradient-to-b from-transparent to-slate-100/60 flex flex-col gap-3">
        <div class="p-3.5 rounded-xl bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-slate-100 border border-emerald-500/30 flex items-center gap-3">
            <div class="relative flex items-center justify-center">
                <div class="w-3 h-3 rounded-full bg-emerald-600"></div>
                <div class="w-3 h-3 rounded-full bg-emerald-400 animate-ping absolute"></div>
            </div>
            <div class="flex flex-col min-w-0">
                <span class="text-xs font-bold text-slate-900 flex items-center gap-1">QMS Online</span>
                <span class="text-[11px] font-medium text-emerald-800 truncate">System Operational</span>
            </div>
        </div>
        <div class="flex items-center justify-between px-2 pt-1 text-slate-400 text-xs">
            <a href="../logout.php" class="flex items-center gap-1.5 text-slate-500 hover:text-red-600"><span class="material-symbols-outlined text-sm text-red-500">logout</span> Logout</a>
            <span class="font-mono text-[10px] bg-slate-200/80 text-slate-700 px-1.5 py-0.5 rounded font-semibold border border-slate-300">v1.0</span>
        </div>
    </div>
</aside>
<div class="pl-64">
    <header class="fixed top-0 left-64 right-0 h-16 bg-white/95 backdrop-blur-xl border-b border-slate-200/90 z-40 flex items-center justify-between px-8 shadow-xs">
        <div class="flex items-center flex-1 max-w-xl">
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">search</span>
                <input class="w-full h-10 pl-10 pr-4 rounded-xl bg-slate-100/90 border border-slate-200/60 font-medium text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 transition-all shadow-inner" placeholder="Search quotations, clients..." type="text"/>
            </div>
        </div>
        <div class="flex items-center gap-3.5 shrink-0 ml-6">
            <span class="text-xs font-semibold text-slate-600"><?= e($currentUser['name']) ?> (<?= e($currentUser['role']) ?>)</span>
            <?php if($comp['logo']): ?>
            <img alt="Logo" class="w-9 h-9 rounded-xl object-contain" src="<?= BASE_URL ?>assets/uploads/<?= e($comp['logo']) ?>"/>
            <?php endif; ?>
        </div>
    </header>
    <main class="w-full pt-16 bg-slate-50 min-h-screen">
        <div class="p-8 flex flex-col gap-6 max-w-[1680px] mx-auto w-full">
            <!-- Header -->
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex flex-col">
                    <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Quotation Dashboard</h1>
                    <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Overview of your quotation management system.</p>
                </div>
                <a href="quotations/create.php" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                    <span class="material-symbols-outlined text-base">add_circle</span><span>Create New Quotation</span>
                </a>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
                <div class="bg-gradient-to-br from-emerald-800 via-emerald-700 to-green-600 p-6 rounded-2xl shadow-lg shadow-emerald-900/20 text-white relative overflow-hidden group hover:scale-[1.01] transition-transform duration-200 border border-emerald-600/40">
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Total Quotations</span>
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner border border-white/20">
                            <span class="material-symbols-outlined text-xl">request_quote</span>
                        </div>
                    </div>
                    <div class="mt-3 relative z-10">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold tracking-tight text-white drop-shadow-xs"><?= $stats['total'] ?></span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-semibold text-white bg-white/20 px-2 py-0.5 rounded-md"><?= $stats['draft'] ?> Drafts</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-[#b91c1c] via-[#dc2626] to-[#ea580c] p-6 rounded-2xl shadow-lg shadow-red-600/20 text-white relative overflow-hidden group hover:scale-[1.01] transition-transform duration-200 border border-red-400/30">
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-100">Pending (Sent)</span>
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner border border-white/20">
                            <span class="material-symbols-outlined text-xl">pending_actions</span>
                        </div>
                    </div>
                    <div class="mt-3 relative z-10">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold tracking-tight text-white drop-shadow-xs"><?= $stats['sent'] ?></span>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-700 p-6 rounded-2xl shadow-lg shadow-slate-900/20 text-white relative overflow-hidden group hover:scale-[1.01] transition-transform duration-200 border border-slate-600/50">
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Accepted</span>
                        <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-md flex items-center justify-center text-slate-200 shadow-inner border border-white/15">
                            <span class="material-symbols-outlined text-xl">verified</span>
                        </div>
                    </div>
                    <div class="mt-3 relative z-10">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-extrabold tracking-tight text-white drop-shadow-xs"><?= $stats['accepted'] ?></span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-bold text-emerald-300 bg-emerald-950/60 backdrop-blur-sm px-2.5 py-0.5 rounded-full border border-emerald-500/40">Win Rate: <?= $winRate ?>%</span>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-green-800 via-emerald-800 to-teal-800 p-6 rounded-2xl shadow-lg shadow-green-900/20 text-white relative overflow-hidden group hover:scale-[1.01] transition-transform duration-200 border border-emerald-500/40">
                    <div class="flex items-center justify-between relative z-10">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Total Value</span>
                        <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner border border-white/20">
                            <span class="material-symbols-outlined text-xl">payments</span>
                        </div>
                    </div>
                    <div class="mt-3 relative z-10">
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-extrabold tracking-tight text-white drop-shadow-xs"><?= formatCurrency($totalValue, $comp['currency']) ?></span>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs font-bold text-emerald-200">Avg: <?= formatCurrency($avgValue, $comp['currency']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Pipeline -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-700 to-green-600 flex items-center justify-center text-white shadow-md shadow-emerald-700/25">
                        <span class="material-symbols-outlined text-xl">filter_alt</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Quotation Status Overview</h2>
                        <p class="text-xs font-medium text-slate-500">Distribution of <?= $stats['total'] ?> quotations</p>
                    </div>
                </div>
                <?php
                $total = max($stats['total'], 1);
                $draftPct = round($stats['draft'] / $total * 100, 1);
                $sentPct = round($stats['sent'] / $total * 100, 1);
                $acceptedPct = round($stats['accepted'] / $total * 100, 1);
                $rejectedPct = round($stats['rejected'] / $total * 100, 1);
                ?>
                <div class="h-4 w-full rounded-full bg-slate-200/80 p-1 flex gap-1 shadow-inner overflow-hidden border border-slate-300">
                    <div class="bg-gradient-to-r from-slate-400 to-slate-500 rounded-l-full" style="width: <?= $draftPct ?>%;" title="Draft: <?= $stats['draft'] ?>"></div>
                    <div class="bg-gradient-to-r from-amber-400 to-orange-400" style="width: <?= $sentPct ?>%;" title="Sent: <?= $stats['sent'] ?>"></div>
                    <div class="bg-gradient-to-r from-emerald-600 to-green-500" style="width: <?= $acceptedPct ?>%;" title="Accepted: <?= $stats['accepted'] ?>"></div>
                    <div class="bg-gradient-to-r from-red-500 to-[#b91c1c] rounded-r-full" style="width: <?= $rejectedPct ?>%;" title="Rejected: <?= $stats['rejected'] ?>"></div>
                </div>
                <div class="flex flex-wrap items-center justify-between mt-3 text-xs font-semibold text-slate-600">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>Draft (<?= $stats['draft'] ?>)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>Sent (<?= $stats['sent'] ?>)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>Accepted (<?= $stats['accepted'] ?>)</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-[#dc2626]"></span>Rejected (<?= $stats['rejected'] ?>)</span>
                </div>
            </div>

            <!-- Recent Quotations Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
                <div class="p-5 flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 bg-gradient-to-r from-white via-slate-50/60 to-white">
                    <div>
                        <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-700">table_chart</span> Recent Quotations
                        </h2>
                        <p class="text-xs text-slate-500 font-medium">Latest quotation records</p>
                    </div>
                    <a href="quotations/index.php" class="text-xs font-bold text-emerald-700 hover:underline">View All →</a>
                </div>
                <div class="w-full overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                                <th class="py-3 px-4">Quotation #</th>
                                <th class="py-3 px-4">Client</th>
                                <th class="py-3 px-4">Subject</th>
                                <th class="py-3 px-4 text-right">Amount</th>
                                <th class="py-3 px-4 text-center">Status</th>
                                <th class="py-3 px-4">Date</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-xs divide-y divide-slate-100 font-medium">
                            <?php foreach($recent as $q): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <a href="quotations/view.php?id=<?= $q['id'] ?>" class="font-mono text-emerald-800 font-bold hover:underline bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"><?= e($q['quotation_number']) ?></a>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-900"><?= e($q['client_name']) ?></span>
                                        <span class="text-[11px] text-slate-500"><?= e($q['company']) ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700"><?= e($q['subject']) ?></td>
                                <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-900"><?= formatCurrency($q['grand_total'], $comp['currency']) ?></td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php
                                    $statusColor = 'bg-slate-100 text-slate-700 border-slate-200';
                                    if($q['status']=='draft') $statusColor = 'bg-slate-100 text-slate-700 border-slate-200';
                                    elseif($q['status']=='sent') $statusColor = 'bg-amber-100 text-amber-800 border-amber-200';
                                    elseif($q['status']=='accepted') $statusColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';
                                    elseif($q['status']=='rejected') $statusColor = 'bg-red-100 text-red-700 border-red-200';
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-extrabold px-2.5 py-1 rounded-full <?= $statusColor ?>"><?= ucfirst($q['status']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 font-medium"><?= date('d M Y', strtotime($q['quotation_date'])) ?></td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <a href="quotations/view.php?id=<?= $q['id'] ?>" class="p-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-800 transition-colors border border-emerald-200" title="View">
                                            <span class="material-symbols-outlined text-sm">visibility</span>
                                        </a>
                                        <a href="quotations/edit.php?id=<?= $q['id'] ?>" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors border border-slate-200" title="Edit">
                                            <span class="material-symbols-outlined text-sm">edit</span>
                                        </a>
                                        <a href="quotations/preview.php?id=<?= $q['id'] ?>" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors border border-slate-200" title="Preview">
                                            <span class="material-symbols-outlined text-sm">desktop_windows</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($recent)): ?>
                            <tr><td colspan="7" class="py-8 text-center text-slate-500">No quotations found. <a href="quotations/create.php" class="text-emerald-700 font-bold hover:underline">Create your first quotation</a></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-4 bg-white flex items-center justify-between border-t border-slate-200">
                    <span class="text-xs font-semibold text-slate-500">Showing <span class="font-bold text-slate-900"><?= count($recent) ?></span> of <?= $stats['total'] ?> total quotation records</span>
                    <a href="quotations/index.php" class="text-xs font-bold text-emerald-700 hover:underline">View All Quotations →</a>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
