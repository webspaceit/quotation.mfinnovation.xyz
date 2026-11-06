<?php
require_once '../../includes/auth.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';
$client = new Client();
$clients = $client->getAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if(isset($_POST['add'])) {
        $client->create($_POST);
        header('Location: ' . BASE_URL . 'admin/clients/index.php?added=1');
        exit;
    } elseif(isset($_POST['edit'])) {
        $client->update($_POST['id'], $_POST);
        header('Location: ' . BASE_URL . 'admin/clients/index.php?updated=1');
        exit;
    }
}

$pageTitle = 'Clients';
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
                        <span class="tracking-wide">Client Management</span>
                    </div>
                </div>
                <h1 class="text-2xl lg:text-3xl font-extrabold text-slate-900 tracking-tight">Clients</h1>
                <p class="text-xs lg:text-sm font-medium text-slate-500 mt-0.5">Manage your client directory and company details.</p>
            </div>
            <button onclick="document.getElementById('addModal').classList.remove('hidden');document.getElementById('addModal').classList.add('flex')" class="inline-flex items-center gap-2 h-10 px-4 rounded-xl bg-gradient-to-r from-emerald-700 via-emerald-600 to-green-600 text-white transition-all text-xs font-bold shadow-md shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98]">
                <span class="material-symbols-outlined text-base">add_circle</span><span>Add Client</span>
            </button>
        </div>

        <!-- Stats Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Total Clients</h2>
                    <p class="text-xs font-medium text-slate-500">Active clients in your directory</p>
                </div>
                <div class="text-3xl font-extrabold text-emerald-700"><?= count($clients) ?></div>
            </div>
        </div>

        <!-- Clients Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col">
            <div class="p-5 border-b border-slate-200 bg-white">
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-emerald-700">people</span> Client Directory
                </h2>
            </div>
            <div class="w-full overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6">Name</th>
                            <th class="py-4 px-6">Company</th>
                            <th class="py-4 px-6">Email</th>
                            <th class="py-4 px-6">Phone</th>
                            <th class="py-4 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100 font-medium">
                        <?php foreach($clients as $c): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 px-6 text-slate-900"><?= e($c['name']) ?></td>
                            <td class="py-4 px-6 text-slate-700"><?= e($c['company']) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= e($c['email']) ?></td>
                            <td class="py-4 px-6 text-slate-600"><?= e($c['phone']) ?></td>
                            <td class="py-4 px-6 text-right space-x-2">
                                <button onclick='editClient(<?= json_encode($c) ?>)' class="text-emerald-600 hover:text-emerald-900" title="Edit"><span class="material-symbols-outlined text-base">edit</span></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($clients)): ?>
                        <tr><td colspan="5" class="py-12 text-center text-slate-500">No clients found. <button onclick="document.getElementById('addModal').classList.remove('hidden');document.getElementById('addModal').classList.add('flex')" class="text-emerald-700 font-bold hover:underline">Add your first client</button></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Add Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900">Add New Client</h2>
            <button onclick="document.getElementById('addModal').classList.add('hidden');document.getElementById('addModal').classList.remove('flex')" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?php csrf_field(); ?>
            <input type="hidden" name="add" value="1">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Name *</label>
                <input type="text" name="name" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Company</label>
                <input type="text" name="company" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email</label>
                <input type="email" name="email" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Phone</label>
                <input type="text" name="phone" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Address</label>
                <textarea name="address" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('addModal').classList.add('hidden');document.getElementById('addModal').classList.remove('flex')" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-gradient-to-r from-emerald-700 to-green-600 text-white font-medium shadow-sm shadow-emerald-700/25 hover:shadow-lg active:scale-[0.98] transition-all">Save Client</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full mx-4">
        <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
            <h2 class="text-lg font-bold text-slate-900">Edit Client</h2>
            <button onclick="document.getElementById('editModal').classList.add('hidden');document.getElementById('editModal').classList.remove('flex')" class="text-slate-500 hover:text-slate-900 transition-colors"><span class="material-symbols-outlined">close</span></button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?php csrf_field(); ?>
            <input type="hidden" name="edit" value="1">
            <input type="hidden" name="id" id="edit_id">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Name *</label>
                <input type="text" name="name" id="edit_name" required class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Company</label>
                <input type="text" name="company" id="edit_company" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Email</label>
                <input type="email" name="email" id="edit_email" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Phone</label>
                <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Address</label>
                <textarea name="address" id="edit_address" rows="3" class="w-full px-3 py-2.5 border border-slate-300 rounded-lg focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/20 transition-all"></textarea>
            </div>
            <div class="flex justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden');document.getElementById('editModal').classList.remove('flex')" class="px-4 py-2.5 rounded-lg bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition-colors border border-slate-200">Cancel</button>
                <button type="submit" class="px-4 py-2.5 rounded-lg bg-blue-600 text-white font-medium shadow-sm shadow-blue-600/25 hover:shadow-lg active:scale-[0.98] transition-all">Update Client</button>
            </div>
        </form>
    </div>
</div>

<script>
function editClient(c) {
    document.getElementById('edit_id').value = c.id;
    document.getElementById('edit_name').value = c.name;
    document.getElementById('edit_company').value = c.company;
    document.getElementById('edit_email').value = c.email;
    document.getElementById('edit_phone').value = c.phone;
    document.getElementById('edit_address').value = c.address;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}
</script>
<?php require_once '../../includes/footer.php'; ?>
