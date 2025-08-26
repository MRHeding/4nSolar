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
            
            // Handle inventory table specifically
            if (tableId === 'inventory-table') {
                // Add custom headers for inventory
                csv = 'Brand,Model,Category,Size/Specification,Base Price,Selling Price,Discount %,Stock Quantity,Minimum Stock,Supplier\n';
                
                // Process data rows only (skip header row)
                const dataRows = table.querySelectorAll('tbody tr');
                dataRows.forEach(function(row) {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        // Extract clean data from each cell
                        const brand = cells[0].querySelector('.text-sm.font-medium')?.textContent.trim() || '';
                        const model = cells[0].querySelector('.text-sm.text-gray-500')?.textContent.trim() || '';
                        const category = cells[1]?.textContent.trim() || '';
                        const size = cells[2]?.textContent.trim() || '';
                        
                        // Extract pricing info (remove "Base:" and "Sell:" prefixes)
                        const pricingCell = cells[3];
                        const basePrice = pricingCell.querySelector('div:first-child')?.textContent.replace('Base: ', '').trim() || '';
                        const sellPrice = pricingCell.querySelector('div:nth-child(2)')?.textContent.replace('Sell: ', '').trim() || '';
                        const discountElement = pricingCell.querySelector('.text-green-600');
                        const discount = discountElement ? discountElement.textContent.replace('-', '').replace('%', '').trim() : '0';
                        
                        // Extract stock info
                        const stockCell = cells[4];
                        const stockQty = stockCell.querySelector('.text-sm.text-gray-900')?.textContent.trim() || '0';
                        
                        const supplier = cells[5]?.textContent.trim() || '';
                        
                        // Build CSV row with clean data
                        const rowData = [
                            `"${brand}"`,
                            `"${model}"`,
                            `"${category}"`,
                            `"${size}"`,
                            `"${basePrice}"`,
                            `"${sellPrice}"`,
                            `"${discount}"`,
                            `"${stockQty}"`,
                            `"10"`, // Default minimum stock (not displayed in table)
                            `"${supplier}"`
                        ];
                        csv += rowData.join(',') + '\n';
                    }
                });
            } else {
                // Generic table export for other tables
                rows.forEach(function(row, index) {
                    const cols = row.querySelectorAll('td, th');
                    const rowData = [];
                    cols.forEach(function(col, colIndex) {
                        // Skip action columns (usually last column)
                        if (colIndex < cols.length - 1 || !col.textContent.includes('fas fa-')) {
                            // Clean up the text content
                            let cellText = col.textContent.trim();
                            // Remove extra whitespace and newlines
                            cellText = cellText.replace(/\s+/g, ' ');
                            rowData.push('"' + cellText.replace(/"/g, '""') + '"');
                        }
                    });
                    csv += rowData.join(',') + '\n';
                });
            }
            
            // Create and download the CSV file
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>
</body>
</html>
