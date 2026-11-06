function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.innerHTML = `<div class="toast toast-${type}">${message}</div>`;
    toast.classList.remove('hidden');
    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}
function formatNumber(num) {
    return parseFloat(num || 0).toFixed(2);
}
async function postData(url, data) {
    try {
        const formData = new FormData();
        for(let key in data) {
            if(Array.isArray(data[key])) {
                data[key].forEach((v, i) => formData.append(key + '[' + i + ']', v));
            } else {
                formData.append(key, data[key]);
            }
        }
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch(e) {
        console.error(e);
        return {success: false, message: 'Error occurred'};
    }
}
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.auto-hide').forEach(el => {
            el.style.display = 'none';
        });
    }, 4000);
});
