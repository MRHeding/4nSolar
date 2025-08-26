            <?php if (isset($content_start) && $content_start): ?>
            </div>
            <?php endif; ?>
            
        <?php if (isLoggedIn()): ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Scripts -->
    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-auto-hide');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 5000);

        // Confirm delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // Format currency
        function formatCurrency(amount) {
            return '₱' + new Intl.NumberFormat('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        }

        // Calculate totals in forms
        function calculateTotal() {
            const quantity = parseFloat(document.getElementById('quantity')?.value || 0);
            const price = parseFloat(document.getElementById('selling_price')?.value || 0);
            const discount = parseFloat(document.getElementById('discount_percentage')?.value || 0);
            
            const subtotal = quantity * price;
            const discountAmount = subtotal * (discount / 100);
            const total = subtotal - discountAmount;
            
            const totalField = document.getElementById('total_amount');
            if (totalField) {
                totalField.value = total.toFixed(2);
            }
        }

        // Print functionality
        function printPage() {
            window.print();
        }

        // Export to CSV
        function exportToCSV(tableId, filename) {
            const table = document.getElementById(tableId);
            if (!table) return;
            
            let csv = '';
            const rows = table.querySelectorAll('tr');
            
            rows.forEach(function(row) {
                const cols = row.querySelectorAll('td, th');
                const rowData = [];
                cols.forEach(function(col) {
                    rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
                });
                csv += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
