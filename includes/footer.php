    </div>
    <footer class="bg-white mt-10 py-6 border-t">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-600">
            <p>&copy; <?= date('Y') ?> <?= e($comp['company_name'] ?? 'Quotation Management System') ?>. All rights reserved.</p>
        </div>
    </footer>
    <div id="toast" class="fixed bottom-4 right-4 hidden"></div>
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
