<!-- footer.php -->
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script>
        // Fungsi untuk menambahkan item order
        function addOrderItem() {
            let itemCount = document.querySelectorAll('.order-item').length;
            let template = document.getElementById('order-item-template');
            let newItem = template.cloneNode(true);
            newItem.classList.remove('d-none');
            newItem.classList.add('order-item');
            newItem.id = 'order-item-' + (itemCount + 1);
            
            // Update ID dan name pada elemen di dalam item baru
            let newIndex = itemCount + 1;
            
            // Update semua select, input, dan label
            let elements = newItem.querySelectorAll('select, input, label, div[id^="reject-div-"], span[id^="biaya-klik-"]');
            elements.forEach(function(el) {
                if (el.id) {
                    let parts = el.id.split('-');
                    if (parts.length > 1) {
                        parts[parts.length - 1] = newIndex;
                        el.id = parts.join('-');
                    }
                }
                
                if (el.name) {
                    el.name = el.name.replace('[0]', '[' + newIndex + ']');
                }
                
                if (el.hasAttribute('for')) {
                    let forAttr = el.getAttribute('for');
                    let parts = forAttr.split('-');
                    if (parts.length > 1) {
                        parts[parts.length - 1] = newIndex;
                        el.setAttribute('for', parts.join('-'));
                    }
                }
                
                // Khusus untuk select mesin, perbarui onchange attribute
                if (el.id && el.id.startsWith('mesin-')) {
                    el.setAttribute('onchange', 'toggleReject(this, ' + newIndex + ')');
                }
                
                // Khusus untuk select jenis cetak, perbarui onchange attribute
                if (el.id && el.id.startsWith('jenis_cetak-')) {
                    el.setAttribute('onchange', 'updateHarga(this, ' + newIndex + ')');
                }
                
                // Khusus untuk checkbox tanpa biaya klik
                if (el.id && el.id.startsWith('tanpa_biaya_klik-')) {
                    el.setAttribute('onchange', 'toggleTanpaBiaya(this, ' + newIndex + ')');
                }
            });
            
            // Reset semua select ke opsi default
            newItem.querySelectorAll('select').forEach(function(select) {
                select.selectedIndex = 0;
            });
            
            // Reset semua input
            newItem.querySelectorAll('input[type="number"]').forEach(function(input) {
                if (input.id && input.id.startsWith('diskon_persen-')) {
                    input.value = '50'; // Default diskon 50%
                } else {
                    input.value = '';
                }
            });
            
            newItem.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                checkbox.checked = false;
            });
            
            // Pastikan reject div tersembunyi pada item baru
            let rejectDiv = newItem.querySelector('[id^="reject-div-"]');
            if (rejectDiv) {
                rejectDiv.classList.add('d-none');
            }
            
            // Pastikan biaya klik direset
            let biayaKlikSpan = newItem.querySelector('[id^="biaya-klik-"]');
            if (biayaKlikSpan) {
                biayaKlikSpan.textContent = 'Rp 0';
            }
            
            document.getElementById('order-items').appendChild(newItem);
        }

        // Fungsi untuk menghapus item order
        function removeOrderItem(btn) {
            let item = btn.closest('.order-item');
            if (document.querySelectorAll('.order-item').length > 1) {
                item.remove();
            } else {
                alert('Minimal harus ada satu item order!');
            }
        }

        // Fungsi untuk menampilkan/menyembunyikan reject mesin
        function toggleReject(select, index) {
            let rejectDiv = document.getElementById('reject-div-' + index);
            if (rejectDiv) {
                if (select.value !== '') {
                    rejectDiv.classList.remove('d-none');
                } else {
                    rejectDiv.classList.add('d-none');
                    
                    // Reset reject select dan checkbox saat mesin dikosongkan
                    let rejectSelect = document.getElementById('reject-' + index);
                    if (rejectSelect) {
                        rejectSelect.selectedIndex = 0;
                    }
                    
                    let diskonCheckbox = document.getElementById('diskon_klik-' + index);
                    if (diskonCheckbox) {
                        diskonCheckbox.checked = false;
                    }
                    
                    let tanpaBiayaCheckbox = document.getElementById('tanpa_biaya_klik-' + index);
                    if (tanpaBiayaCheckbox) {
                        tanpaBiayaCheckbox.checked = false;
                    }
                    
                    let diskonPersenInput = document.getElementById('diskon_persen-' + index);
                    if (diskonPersenInput) {
                        diskonPersenInput.value = '50';
                    }
                }
            }
        }
        
        // Fungsi untuk menangani opsi tanpa biaya klik
        function toggleTanpaBiaya(checkbox, index) {
            let diskonCheckbox = document.getElementById('diskon_klik-' + index);
            let diskonPersenInput = document.getElementById('diskon_persen-' + index);
            
            if (checkbox.checked) {
                // Jika tanpa biaya klik dicentang, nonaktifkan opsi diskon
                if (diskonCheckbox) {
                    diskonCheckbox.checked = false;
                    diskonCheckbox.disabled = true;
                }
                if (diskonPersenInput) {
                    diskonPersenInput.disabled = true;
                }
            } else {
                // Jika tanpa biaya klik tidak dicentang, aktifkan kembali opsi diskon
                if (diskonCheckbox) {
                    diskonCheckbox.disabled = false;
                }
                if (diskonPersenInput) {
                    diskonPersenInput.disabled = false;
                }
            }
        }

        // Fungsi untuk update harga berdasarkan jenis cetak yang dipilih
        function updateHarga(select, index) {
            let biayaKlikElement = document.getElementById('biaya-klik-' + index);
            if (biayaKlikElement) {
                if (select.selectedIndex > 0) {
                    let jenisCetak = select.options[select.selectedIndex];
                    let biayaKlik = jenisCetak.getAttribute('data-biaya');
                    biayaKlikElement.textContent = formatRupiah(biayaKlik);
                } else {
                    biayaKlikElement.textContent = 'Rp 0';
                }
            }
        }

        // Format angka ke rupiah
        function formatRupiah(angka) {
            return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
        }
        
        // Inisialisasi halaman setelah DOM dimuat
        document.addEventListener('DOMContentLoaded', function() {
            // Untuk halaman order_add, tambahkan item order pertama secara otomatis
            if (document.getElementById('order-items') && 
                document.querySelectorAll('.order-item').length === 0 && 
                document.getElementById('order-item-template')) {
                addOrderItem();
            }
            
            // Pastikan semua event handler sudah terpasang untuk item yang sudah ada
            document.querySelectorAll('[id^="mesin-"]').forEach(function(select) {
                let index = select.id.split('-')[1];
                
                // Periksa jika mesin sudah dipilih, tampilkan reject div
                if (select.value !== '') {
                    let rejectDiv = document.getElementById('reject-div-' + index);
                    if (rejectDiv) {
                        rejectDiv.classList.remove('d-none');
                    }
                }
            });
            
            document.querySelectorAll('[id^="jenis_cetak-"]').forEach(function(select) {
                let index = select.id.split('-')[1];
                
                // Update tampilan biaya klik
                if (select.selectedIndex > 0) {
                    updateHarga(select, index);
                }
            });
            
            // Cek status tanpa biaya klik pada item yang sudah ada
            document.querySelectorAll('[id^="tanpa_biaya_klik-"]').forEach(function(checkbox) {
                if (checkbox.checked) {
                    let index = checkbox.id.split('-')[1];
                    toggleTanpaBiaya(checkbox, index);
                }
            });
        });
    </script>
</body>
</html>
