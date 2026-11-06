let itemIndex = 0;

function duplicateItemRow(btn) {
    const row = btn.closest('.item-row');
    const tbody = document.getElementById('itemsTableBody');
    const clone = row.cloneNode(true);
    tbody.appendChild(clone);
    updateRowNumbers();
    calculateTotal();
}

function addItemRow() {
    const tbody = document.getElementById('itemsTableBody');
    const row = document.createElement('tr');
    row.className = 'item-row hover:bg-slate-50 transition-colors';
    row.innerHTML = `
        <td class="px-3 py-3 border-b border-slate-100 text-center">${tbody.children.length + 1}</td>
        <td class="px-3 py-3 border-b border-slate-100"><textarea name="items[description][]" rows="2" class="w-full px-2 py-1 border border-slate-200 rounded text-sm" placeholder="Item description..."></textarea></td>
        <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[quantity][]" class="w-full px-2 py-1 border border-slate-200 rounded qty text-sm" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="1"></td>
        <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[unit_price][]" class="w-full px-2 py-1 border border-slate-200 rounded price text-sm" onchange="calculateRow(this)" onkeyup="calculateRow(this)" value="0"></td>
        <td class="px-3 py-3 border-b border-slate-100"><input type="number" step="0.01" name="items[amount][]" class="w-full px-2 py-1 border border-slate-200 rounded amount text-sm" readonly value="0"></td>
        <td class="px-3 py-3 border-b border-slate-100"><input type="text" name="items[remarks][]" class="w-full px-2 py-1 border border-slate-200 rounded text-sm" placeholder="Remarks"></td>
        <td class="px-3 py-3 border-b border-slate-100 text-center">
            <button type="button" onclick="duplicateItemRow(this)" class="text-blue-600 hover:text-blue-800 mr-1" title="Duplicate row"><span class="material-symbols-outlined text-sm">content_copy</span></button>
            <button type="button" onclick="removeItemRow(this)" class="text-red-600 hover:text-red-800" title="Remove row"><span class="material-symbols-outlined text-sm">delete</span></button>
        </td>
    `;
    tbody.appendChild(row);
    updateRowNumbers();
    calculateTotal();
}

function removeItemRow(btn) {
    if(document.querySelectorAll('.item-row').length <= 1) {
        alert('At least one item is required');
        return;
    }
    btn.closest('.item-row').remove();
    updateRowNumbers();
    calculateTotal();
}

function updateRowNumbers() {
    document.querySelectorAll('#itemsTableBody tr').forEach((tr, idx) => {
        tr.cells[0].textContent = idx + 1;
    });
}

function calculateRow(input) {
    const row = input.closest('.item-row');
    const qty = parseFloat(row.querySelector('.qty').value) || 0;
    const price = parseFloat(row.querySelector('.price').value) || 0;
    const amount = qty * price;
    row.querySelector('.amount').value = amount.toFixed(2);
    calculateTotal();
}

function calculateTotal() {
    let subtotal = 0;
    document.querySelectorAll('.amount').forEach(inp => {
        subtotal += parseFloat(inp.value) || 0;
    });
    document.getElementById('subtotal').value = subtotal.toFixed(2);
    const taxPercent = parseFloat(document.getElementById('tax_percent').value) || 0;
    const taxAmount = subtotal * taxPercent / 100;
    document.getElementById('tax_amount').value = taxAmount.toFixed(2);
    const grandTotal = subtotal + taxAmount;
    document.getElementById('grand_total').value = grandTotal.toFixed(2);
    if(document.getElementById('grand_total_display')) {
        document.getElementById('grand_total_display').textContent = grandTotal.toFixed(2);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const taxPercentInput = document.getElementById('tax_percent');
    if(taxPercentInput) {
        taxPercentInput.addEventListener('input', calculateTotal);
    }
});