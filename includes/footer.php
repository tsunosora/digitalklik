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
            let newItem = document.getElementById('order-item-template').cloneNode(true);
            newItem.id = 'order-item-' + (itemCount + 1);
            newItem.classList.remove('d-none');
            newItem.classList.add('order-item');
            
            // Update ID dan name pada elemen di dalam item baru
            let inputs = newItem.querySelectorAll('select, input');
            inputs.forEach(function(input) {
                input.name = input.name.replace('[0]', '[' + (itemCount + 1) + ']');
                input.id = input.id.replace('-0', '-' + (itemCount + 1));
            });
            
            // Hapus isi input pada item baru
            newItem.querySelectorAll('select').forEach(function(select) {
                select.selectedIndex = 0;
            });
            
            document.getElementById('order-items').appendChild(newItem);
        }

        // Fungsi untuk menghapus item order
        function removeOrderItem(btn) {
            let item = btn.closest('.order-item');
            item.remove();
        }

        // Fungsi untuk menampilkan/menyembunyikan reject mesin
        function toggleReject(select, index) {
            let rejectDiv = document.getElementById('reject-div-' + index);
            if (select.value !== '') {
                rejectDiv.classList.remove('d-none');
            } else {
                rejectDiv.classList.add('d-none');
            }
        }

        // Fungsi untuk update harga berdasarkan jenis cetak yang dipilih
        function updateHarga(select, index) {
            let jenisCetak = select.options[select.selectedIndex];
            let biayaKlik = jenisCetak.getAttribute('data-biaya');
            document.getElementById('biaya-klik-' + index).textContent = formatRupiah(biayaKlik);
        }

        // Format angka ke rupiah
        function formatRupiah(angka) {
            return 'Rp ' + parseFloat(angka).toLocaleString('id-ID');
        }
    </script>
</body>
</html>

