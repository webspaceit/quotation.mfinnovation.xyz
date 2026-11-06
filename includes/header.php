<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
$company = new Company();
$comp = $company->getSettings();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
<style>@layer base{html,body{margin:0;padding:0;font-family:'Inter',sans-serif;}body{overscroll-behavior:none;}main>:first-child{margin-top:0!important;}main>:last-child{margin-bottom:0!important;}}::-webkit-scrollbar{width:6px;height:6px;}::-webkit-scrollbar-track{background:transparent;}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:3px;}</style>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">tailwind.config={darkMode:"class",theme:{extend:{colors:{"primary":"#00652c","primary-container":"#15803d","secondary":"#bb0112","secondary-container":"#e02928","surface":"#faf8ff","surface-container":"#eaedff","surface-container-high":"#e2e7ff","on-surface":"#131b2e","outline":"#6f7a6e","outline-variant":"#becabc"}}}}</script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen">
<!-- Sidebar -->
<aside class="fixed left-0 top-0 h-full w-64 bg-white/95 backdrop-blur-xl border-r border-slate-200 z-50 flex flex-col justify-between shadow-[4px_0_24px_rgba(0,0,0,0.04)]">
    <div class="flex flex-col flex-1 overflow-y-auto">
        <!-- Brand -->
        <div class="h-20 px-4 flex items-center gap-3 border-b border-slate-200/80 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/40">
            <div class="flex items-center gap-2.5 min-w-0">
                <?php if(!empty($comp['logo'])): ?>
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
        <!-- Main Menu -->
        <div class="px-4 py-5">
            <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Main Menu</p>
            <nav class="flex flex-col gap-1.5">
                <a href="<?= BASE_URL ?>admin/dashboard.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 <?= ($currentPage === 'dashboard' && $currentDir === 'admin') ? 'bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25' : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px]">dashboard</span><span>Dashboard</span></div>
                    <?php if($currentPage === 'dashboard' && $currentDir === 'admin'): ?><span class="w-2 h-2 rounded-full bg-emerald-200 animate-pulse"></span><?php endif; ?>
                </a>
                <?php $qStats = ''; try { $qStats = (new Quotation())->getStats()['total']; } catch(Exception $e) {} ?>
                <a href="<?= BASE_URL ?>admin/quotations/index.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 <?= ($currentPage === 'index' && $currentDir === 'quotations') ? 'bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25' : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">request_quote</span><span>Quotations</span></div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 font-bold text-xs border border-emerald-200 group-hover:bg-emerald-700 group-hover:text-white transition-colors"><?= $qStats ?></span>
                </a>
                <a href="<?= BASE_URL ?>admin/clients/index.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 <?= ($currentPage === 'index' && $currentDir === 'clients') ? 'bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25' : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">corporate_fare</span><span>Clients</span></div>
                </a>
                <a href="<?= BASE_URL ?>admin/users/index.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 <?= ($currentPage === 'index' && $currentDir === 'users') ? 'bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25' : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-emerald-700 transition-colors">group</span><span>Users</span></div>
                </a>
                <a href="<?= BASE_URL ?>admin/settings/index.php" class="flex items-center justify-between px-3.5 py-2.5 rounded-xl font-medium text-sm transition-all duration-200 <?= ($currentPage === 'index' && $currentDir === 'settings') ? 'bg-gradient-to-r from-emerald-700 to-green-600 text-white shadow-md shadow-emerald-700/25' : 'font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100/80' ?>">
                    <div class="flex items-center gap-3"><span class="material-symbols-outlined text-[20px] text-slate-400 group-hover:text-slate-800 transition-colors">settings</span><span>Settings</span></div>
                    <span class="material-symbols-outlined text-sm text-slate-400">chevron_right</span>
                </a>
            </nav>
        </div>
    </div>
    <!-- Footer -->
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
            <a href="<?= BASE_URL ?>logout.php" class="flex items-center gap-1.5 text-slate-500 hover:text-red-600"><span class="material-symbols-outlined text-sm text-red-500">logout</span> Logout</a>
            <span class="font-mono text-[10px] bg-slate-200/80 text-slate-700 px-1.5 py-0.5 rounded font-semibold border border-slate-300">v1.0</span>
        </div>
    </div>
</aside>

<!-- Top Header -->
<div class="pl-64">
<header class="fixed top-0 left-64 right-0 h-16 bg-white/95 backdrop-blur-xl border-b border-slate-200/90 z-40 flex items-center justify-between px-8 shadow-xs">
    <div class="flex items-center flex-1 max-w-xl">
        <div class="relative w-full">
            <span class="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-lg pointer-events-none">search</span>
            <input class="w-full h-10 pl-10 pr-4 rounded-xl bg-slate-100/90 border border-slate-200/60 font-medium text-sm text-slate-800 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/15 transition-all shadow-inner" placeholder="Search quotations, clients..." type="text"/>
        </div>
    </div>
    <div class="flex items-center gap-3.5 shrink-0 ml-6">
        <span class="text-xs font-semibold text-slate-600"><?= e($currentUser['name'] ?? 'Admin') ?> (<?= e($currentUser['role'] ?? 'admin') ?>)</span>
        <?php if(!empty($comp['logo'])): ?>
        <img alt="Logo" class="w-9 h-9 rounded-xl object-contain" src="<?= BASE_URL ?>assets/uploads/<?= e($comp['logo']) ?>"/>
        <?php endif; ?>
    </div>
</header>
