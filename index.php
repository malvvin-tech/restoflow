<?php
session_start();

// Jika tidak ada sesi role, redirect ke halaman login
if (!isset($_SESSION['user_role'])) {
    header('Location: login.html');
    exit();
}

$user_role = $_SESSION['user_role'];
$username = htmlspecialchars($_SESSION['username']);

// Tentukan halaman default berdasarkan role
$default_page = 'page-order';
if ($user_role === 'koki') {
    $default_page = 'page-dapur';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RestoFlow - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        /* Style untuk radio button kustom di popup */
        label:has(input[type="radio"]:checked) {
            border-color: #10B981;
            /* emerald-500 */
            background-color: #ECFDF5;
            /* emerald-50 */
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="flex h-screen bg-gray-100">
        <div class="flex flex-col w-64 bg-white shadow-lg">
            <div class="flex items-center justify-center p-4 shadow-md">
                <h1 class="text-2xl font-bold text-gray-700 ml-2">RestoFlow</h1>
            </div>
            <div class="flex-grow p-4">
                <nav class="flex flex-col space-y-2">
                    <?php if ($user_role === 'admin' || $user_role === 'kasir') : ?>
                        <a href="#" id="nav-order" class="nav-link flex items-center p-3" onclick="showPage('page-order')">
                            <span class="ml-3">Meja & Pemesanan</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user_role === 'admin' || $user_role === 'koki') : ?>
                        <a href="#" id="nav-dapur" class="nav-link flex items-center p-3" onclick="showPage('page-dapur')">
                            <span class="ml-3">Dapur</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user_role === 'admin' || $user_role === 'kasir') : ?>
                        <a href="#" id="nav-payment" class="nav-link flex items-center p-3" onclick="showPage('page-payment')">
                            <span class="ml-3">Pembayaran</span>
                        </a>
                    <?php endif; ?>

                    <?php if ($user_role === 'admin') : ?>
                        <a href="#" id="nav-menu" class="nav-link flex items-center p-3" onclick="showPage('page-menu')">
                            <span class="ml-3">Manajemen Menu</span>
                        </a>
                        <a href="#" id="nav-stok" class="nav-link flex items-center p-3" onclick="showPage('page-stok')">
                            <span class="ml-3">Manajemen Stok</span>
                        </a>
                        <a href="laporan.html" target="_blank" class="nav-link flex items-center p-3 text-gray-600 hover:bg-gray-200 rounded-lg">
                            <span class="ml-3">Laporan Penjualan</span>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="flex justify-between items-center p-4 bg-white border-b-2">
                <h2 id="page-title" class="text-xl font-semibold text-gray-700 ml-4"></h2>
                <div class="flex items-center">
                    <span class="text-gray-600">Halo, <strong class="capitalize"><?php echo $username; ?></strong>!</span>
                    <a href="logout.php" class="ml-4 text-sm text-red-500 hover:text-red-700 font-semibold">Logout</a>
                </div>
            </header>
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-4 md:p-6">
                <div id="page-order" class="page-content">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pilih Meja & Menu</h3>
                            <div class="mb-4">
                                <label for="table-select" class="block text-sm font-medium text-gray-700">Pilih Meja</label>
                                <select id="table-select" class="mt-1 block w-full p-2 border border-gray-300 rounded-md"></select>
                            </div>
                            <input type="text" id="menu-search" class="w-full p-3 pl-4 border border-gray-300 rounded-lg" placeholder="Ketik untuk mencari menu...">
                            <div class="overflow-y-auto h-80 border rounded-lg mt-4">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="p-3 text-left text-xs font-medium text-gray-500">Nama Menu</th>
                                            <th class="p-3 text-left text-xs font-medium text-gray-500">Harga</th>
                                            <th class="p-3 text-middle text-xs font-medium text-gray-500">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="menu-list" class="bg-white divide-y divide-gray-200"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Detail Pesanan</h3>
                            <div id="order-details" class="space-y-3 mb-4 h-96 overflow-y-auto">
                                <p id="order-empty-msg" class="text-gray-500 text-center pt-16">Belum ada item yang dipilih.</p>
                            </div>
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="text-lg font-medium text-gray-700">Total</span>
                                    <span id="order-total" class="text-2xl font-bold text-emerald-600">Rp 0</span>
                                </div>
                                <button id="process-order-btn" class="w-full bg-emerald-500 text-white font-bold py-3 rounded-lg hover:bg-emerald-600 disabled:bg-gray-400" disabled>
                                    Buat Pesanan
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="page-dapur" class="page-content hidden">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 h-full">
                        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                            <h3 class="font-bold text-lg mb-4 text-center text-blue-600">Masuk</h3>
                            <div id="dapur-masuk" class="space-y-4 overflow-y-auto flex-grow"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                            <h3 class="font-bold text-lg mb-4 text-center text-yellow-600">Diproses</h3>
                            <div id="dapur-diproses" class="space-y-4 overflow-y-auto flex-grow"></div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                            <h3 class="font-bold text-lg mb-4 text-center text-green-600">Selesai</h3>
                            <div id="dapur-selesai" class="space-y-4 overflow-y-auto flex-grow"></div>
                        </div>
                    </div>
                </div>

                <div id="page-payment" class="page-content hidden">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Transaksi Terakhir</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500">ID Pesanan</th>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500">No. Meja</th>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500">Total</th>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500">Tanggal</th>
                                        <th class="p-3 text-center text-xs font-medium text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="unpaid-orders-table" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="page-menu" class="page-content hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Tambah Menu Baru</h3>
                            <form id="add-menu-form" class="space-y-4">
                                <div>
                                    <label for="menu-name" class="block text-sm font-medium text-gray-700">Nama Menu</label>
                                    <input type="text" id="menu-name" name="name" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                                </div>
                                <div>
                                    <label for="menu-price" class="block text-sm font-medium text-gray-700">Harga</label>
                                    <input type="number" id="menu-price" name="price" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required placeholder="Contoh: 25000">
                                </div>
                                <div>
                                    <label for="menu-category" class="block text-sm font-medium text-gray-700">Kategori</label>
                                    <select id="menu-category" name="category" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm" required>
                                        <option value="Makanan">Makanan</option>
                                        <option value="Minuman">Minuman</option>
                                        <option value="Camilan">Camilan</option>
                                    </select>
                                </div>
                                <button type="submit" class="w-full bg-emerald-500 text-white font-bold py-2 rounded-lg hover:bg-emerald-600 transition-colors">Tambah Menu</button>
                            </form>
                        </div>
                        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Daftar Menu</h3>
                            <div class="overflow-y-auto max-h-[75vh]">
                                <table class="min-w-full">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="p-3 text-left text-xs font-medium text-gray-500">Nama Menu</th>
                                            <th class="p-3 text-left text-xs font-medium text-gray-500">Kategori</th>
                                            <th class="p-3 text-left text-xs font-medium text-gray-500">Harga</th>
                                        </tr>
                                    </thead>
                                    <tbody id="manajemen-menu-list" class="bg-white divide-y divide-gray-200"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="page-stok" class="page-content hidden">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-xl font-bold text-gray-800 mb-4">Manajemen Stok Menu</h3>
                        <div class="overflow-y-auto max-h-[80vh]">
                            <table class="min-w-full">
                                <thead class="bg-gray-50 sticky top-0">
                                    <tr>
                                        <th class="p-3 text-left text-xs font-medium text-gray-500">Nama Menu</th>
                                        <th class="p-3 text-center text-xs font-medium text-gray-500">Stok Saat Ini</th>
                                        <th class="p-3 text-center text-xs font-medium text-gray-500">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="stok-menu-list" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="payment-modal" class="fixed inset-0 bg-gray-900 bg-opacity-60 flex items-center justify-center p-4 hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 id="modal-title" class="text-xl font-semibold text-gray-800">Konfirmasi Pembayaran</h3>
                <button id="close-modal-btn" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
            </div>
            <div class="p-6">
                <div id="modal-confirm-view">
                    <div id="modal-loader" class="text-center py-10">
                        <p>Memuat detail pesanan...</p>
                    </div>
                    <div id="modal-content" class="hidden">
                        <p class="text-sm text-gray-600 mb-2">Item yang dipesan:</p>
                        <ul id="modal-item-list" class="space-y-1 bg-gray-50 p-3 rounded-md max-h-48 overflow-y-auto mb-4"></ul>
                        <div class="flex justify-between items-center border-t pt-4 mb-4">
                            <span class="text-lg font-medium text-gray-700">Total Pembayaran</span>
                            <span id="modal-total" class="text-2xl font-bold text-emerald-600">Rp 0</span>
                        </div>
                        <p class="text-sm font-medium text-gray-700 mb-2">Pilih Metode Pembayaran</p>
                        <div class="flex space-x-4 mb-6">
                            <label class="flex items-center p-3 border rounded-lg flex-1 cursor-pointer">
                                <input type="radio" name="payment-method" value="Tunai" class="mr-2" checked><span>Tunai</span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg flex-1 cursor-pointer">
                                <input type="radio" name="payment-method" value="QRIS" class="mr-2"><span>QRIS</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div id="modal-success-view" class="hidden text-center p-4">
                    <div class="text-green-500 mb-4"><i class="fas fa-check-circle fa-4x"></i></div>
                    <h3 class="text-xl font-bold text-gray-800">Pembayaran Berhasil!</h3>
                    <p class="text-gray-600 mt-2">Terima kasih telah melakukan pembayaran.</p>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-t rounded-b-lg flex justify-end space-x-3">
                <div id="modal-footer-confirm">
                    <button id="cancel-payment-btn" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Batal</button>
                    <button id="confirm-payment-btn" class="px-4 py-2 bg-emerald-500 text-white font-bold rounded-lg hover:bg-emerald-600">Konfirmasi Pembayaran</button>
                </div>
                <div id="modal-footer-success" class="hidden w-full flex space-x-3">
                    <button id="close-success-btn" class="flex-1 px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Tutup</button>
                    <button id="print-receipt-btn" class="flex-1 px-4 py-2 bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-600">
                        <i class="fas fa-print mr-2"></i>Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-5 right-5 bg-emerald-500 text-white py-2 px-4 rounded-lg shadow-lg opacity-0 transition-all duration-300"></div>

    <script>
        const API_URL = 'api.php';
        let menus = [];
        let currentOrder = [];
        let activePaymentButton = null;
        let lastOrderForReceipt = null;

        // --- FUNGSI UTILITAS ---
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `fixed bottom-5 right-5 text-white py-2 px-4 rounded-lg shadow-lg transition-all duration-300 ${isSuccess ? 'bg-emerald-500' : 'bg-red-500'}`;
            toast.style.opacity = '1';
            setTimeout(() => {
                toast.style.opacity = '0';
            }, 3000);
        }

        function formatCurrency(number) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(number);
        }

        // --- NAVIGASI & PEMUATAN DATA UTAMA ---
        function showPage(pageId) {
            document.querySelectorAll('.page-content').forEach(page => page.classList.add('hidden'));
            document.getElementById(pageId).classList.remove('hidden');
            const pageTitles = {
                'page-order': 'Meja & Pemesanan',
                'page-dapur': 'Papan Pesanan Dapur',
                'page-payment': 'Pembayaran',
                'page-menu': 'Manajemen Menu',
                'page-stok': 'Manajemen Stok'
            };
            document.getElementById('page-title').textContent = pageTitles[pageId];
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-emerald-100', 'font-semibold');
                link.classList.add('text-gray-600', 'hover:bg-gray-200');
            });
            document.querySelector(`.nav-link[onclick="showPage('${pageId}')"]`).classList.add('bg-emerald-100', 'font-semibold');

            if (pageId === 'page-menu') renderManajemenMenu();
            if (pageId === 'page-dapur') loadKitchenOrders();
            if (pageId === 'page-stok') renderManajemenStok();
        }

        async function loadData() {
            // Memeriksa peran sebelum memuat data
            const userRole = '<?php echo $user_role; ?>';
            if (userRole === 'admin' || userRole === 'kasir') {
                await loadMenuData();
                await loadTableData();
                await loadUnpaidOrders();
            }
            if (userRole === 'koki') {
                await loadKitchenOrders();
            }
        }

        // --- LOGIKA HALAMAN PEMESANAN ---
        async function loadMenuData() {
            try {
                const response = await fetch(`${API_URL}?action=get_menus`);
                menus = await response.json();
                renderMenuList(menus);
            } catch (error) {
                showToast("Gagal memuat data menu.", false);
            }
        }

        function renderMenuList(menuData) {
            const menuList = document.getElementById('menu-list');
            const availableMenus = menuData.filter(m => m.stock > 0);

            if (availableMenus.length === 0) {
                menuList.innerHTML = `<tr><td colspan="3" class="text-center p-4 text-gray-500">Semua menu habis.</td></tr>`;
                return;
            }
            menuList.innerHTML = availableMenus.map(m => `
                <tr>
                    <td class="p-3 text-sm">
                        ${m.name}
                        <span class="text-xs text-gray-500">(Stok: ${m.stock})</span>
                    </td>
                    <td class="p-3 text-sm">${formatCurrency(m.price)}</td>
                    <td class="p-3 text-center">
                        <button onclick="addToOrder(${m.id})" class="bg-emerald-500 text-white px-3 py-1 rounded">+</button>
                    </td>
                </tr>`).join('');
        }

        async function loadTableData() {
            try {
                const response = await fetch(`${API_URL}?action=get_tables`);
                const tables = await response.json();
                const tableSelect = document.getElementById('table-select');
                const availableTables = tables.filter(t => t.status === 'Tersedia');
                tableSelect.innerHTML = availableTables.map(t => `<option value="${t.id}">${t.number}</option>`).join('');
                if (tableSelect.innerHTML === '') {
                    tableSelect.innerHTML = '<option value="" disabled>Semua meja terpakai</option>';
                }
            } catch (error) {
                showToast("Gagal memuat data meja.", false);
            }
        }

        function addToOrder(menuId) {
            const menuItem = menus.find(m => m.id == menuId);
            const orderItem = currentOrder.find(item => item.id === menuId);

            if (menuItem.stock <= (orderItem ? orderItem.quantity : 0)) {
                showToast(`Stok untuk ${menuItem.name} tidak mencukupi.`, false);
                return;
            }

            if (orderItem) {
                orderItem.quantity++;
            } else {
                currentOrder.push({ ...menuItem,
                    id: parseInt(menuId),
                    quantity: 1
                });
            }
            renderCurrentOrder();
        }

        function updateOrderQuantity(index, change) {
            const orderItem = currentOrder[index];
            const menuItem = menus.find(m => m.id == orderItem.id);

            if (change > 0 && menuItem.stock <= orderItem.quantity) {
                showToast(`Stok untuk ${menuItem.name} tidak mencukupi.`, false);
                return;
            }

            orderItem.quantity += change;
            if (orderItem.quantity <= 0) {
                currentOrder.splice(index, 1);
            }
            renderCurrentOrder();
        }

        function removeFromOrder(index) {
            currentOrder.splice(index, 1);
            renderCurrentOrder();
        }

        function renderCurrentOrder() {
            const orderDetailsDiv = document.getElementById('order-details');
            const processBtn = document.getElementById('process-order-btn');
            if (currentOrder.length === 0) {
                orderDetailsDiv.innerHTML = '<p id="order-empty-msg" class="text-gray-500 text-center pt-16">Belum ada item yang dipilih.</p>';
                processBtn.disabled = true;
            } else {
                orderDetailsDiv.innerHTML = currentOrder.map((item, index) => `<div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg"><div><p class="font-semibold text-sm text-gray-800">${item.name}</p><p class="text-xs text-gray-500">${formatCurrency(item.price)}</p></div><div class="flex items-center space-x-2"><button onclick="updateOrderQuantity(${index}, -1)" class="w-6 h-6 rounded-full bg-gray-200 text-gray-700">-</button><span>${item.quantity}</span><button onclick="updateOrderQuantity(${index}, 1)" class="w-6 h-6 rounded-full bg-gray-200 text-gray-700">+</button><button onclick="removeFromOrder(${index})" class="text-red-400 hover:text-red-600">×</button></div></div>`).join('');
                processBtn.disabled = false;
            }
            const total = currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            document.getElementById('order-total').textContent = formatCurrency(total);
        }

        // --- LOGIKA HALAMAN DAPUR ---
        async function loadKitchenOrders() {
            try {
                const response = await fetch(`${API_URL}?action=get_kitchen_orders`);
                const orders = await response.json();
                const colMasuk = document.getElementById('dapur-masuk');
                const colDiproses = document.getElementById('dapur-diproses');
                const colSelesai = document.getElementById('dapur-selesai');
                colMasuk.innerHTML = '';
                colDiproses.innerHTML = '';
                colSelesai.innerHTML = '';

                if (orders.length === 0) {
                    colMasuk.innerHTML = '<p class="text-center text-gray-500 mt-8">Tidak ada pesanan.</p>';
                    return;
                }

                orders.forEach(order => {
                    const itemsHtml = order.items.map(item => `<li class="flex justify-between"><span>${item.name}</span><span class="font-semibold">${item.quantity}x</span></li>`).join('');
                    let buttonHtml = '';
                    if (order.status === 'Masuk') {
                        buttonHtml = `<button onclick="changeOrderStatus(${order.id}, 'Diproses')" class="w-full mt-3 bg-blue-500 text-white font-bold py-2 rounded-lg hover:bg-blue-600">Proses Pesanan</button>`;
                    } else if (order.status === 'Diproses') {
                        buttonHtml = `<button onclick="changeOrderStatus(${order.id}, 'Selesai')" class="w-full mt-3 bg-yellow-500 text-white font-bold py-2 rounded-lg hover:bg-yellow-600">Selesaikan</button>`;
                    }
                    const cardHtml = `<div class="bg-gray-50 border rounded-lg p-4 shadow-sm"><div class="flex justify-between items-baseline mb-2"><h4 class="font-bold">Pesanan #${order.id}</h4><span class="text-sm font-semibold">${order.table_number}</span></div><ul class="text-sm space-y-1">${itemsHtml}</ul>${buttonHtml}</div>`;
                    if (order.status === 'Masuk') colMasuk.innerHTML += cardHtml;
                    else if (order.status === 'Diproses') colDiproses.innerHTML += cardHtml;
                    else if (order.status === 'Selesai') colSelesai.innerHTML += cardHtml;
                });
            } catch (error) {
                // Jangan tampilkan toast jika user tidak punya akses
                if (window.location.pathname.endsWith('index.php')) showToast("Gagal memuat pesanan dapur.", false);
            }
        }

        async function changeOrderStatus(orderId, newStatus) {
            try {
                const response = await fetch(`${API_URL}?action=update_order_status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: orderId,
                        status: newStatus
                    })
                });
                const result = await response.json();
                if (result.success) {
                    showToast(`Pesanan #${orderId} kini ${newStatus}.`);
                    loadKitchenOrders();
                } else {
                    showToast(result.message, false);
                }
            } catch (error) {
                showToast("Gagal mengubah status pesanan.", false);
            }
        }

        // --- LOGIKA HALAMAN PEMBAYARAN & CETAK STRUK ---
        async function loadUnpaidOrders() {
            try {
                const response = await fetch(`${API_URL}?action=get_unpaid_orders`);
                const orders = await response.json();
                const unpaidOrdersTable = document.getElementById('unpaid-orders-table');
                unpaidOrdersTable.innerHTML = orders.map(o => {
                    let buttonHtml = o.status === 'Lunas' ?
                        `<button class="bg-green-500 text-white px-4 py-1 rounded cursor-not-allowed" disabled>Lunas</button>` :
                        `<button id="pay-btn-${o.id}" onclick="showPaymentPopup(${o.id}, this)" class="bg-red-500 hover:bg-red-600 text-white px-4 py-1 rounded transition-colors duration-300">Bayar</button>`;
                    return `<tr><td class="p-3 text-sm font-semibold">#${o.id}</td><td class="p-3 text-sm">${o.table_number}</td><td class="p-3 text-sm">${formatCurrency(o.total)}</td><td class="p-3 text-sm">${new Date(o.date).toLocaleString('id-ID')}</td><td class="p-3 text-center">${buttonHtml}</td></tr>`;
                }).join('');
            } catch (error) {
                showToast("Gagal memuat data pesanan.", false);
            }
        }

        async function showPaymentPopup(orderId, buttonElement) {
            resetModalViews();
            const modal = document.getElementById('payment-modal');
            const modalContent = document.getElementById('modal-content');
            const modalLoader = document.getElementById('modal-loader');
            const itemList = document.getElementById('modal-item-list');
            const totalSpan = document.getElementById('modal-total');
            const confirmBtn = document.getElementById('confirm-payment-btn');
            activePaymentButton = buttonElement;
            confirmBtn.dataset.orderId = orderId;
            modal.classList.remove('hidden');
            modalContent.classList.add('hidden');
            modalLoader.classList.remove('hidden');
            itemList.innerHTML = '';
            try {
                const response = await fetch(`${API_URL}?action=get_order_details_for_payment&order_id=${orderId}`);
                const result = await response.json();
                if (!result.success) {
                    showToast(result.message, false);
                    closePaymentModal();
                    return;
                }
                lastOrderForReceipt = result;
                result.items.forEach(item => {
                    const li = document.createElement('li');
                    li.textContent = `${item.quantity}x ${item.name}`;
                    li.className = 'text-gray-700';
                    itemList.appendChild(li);
                });
                totalSpan.textContent = formatCurrency(result.order.total);
                modalLoader.classList.add('hidden');
                modalContent.classList.remove('hidden');
            } catch (error) {
                showToast("Gagal mengambil detail pesanan.", false);
                closePaymentModal();
            }
        }

        async function confirmAndPay() {
            const confirmBtn = document.getElementById('confirm-payment-btn');
            const orderId = confirmBtn.dataset.orderId;
            const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
            if (!orderId) return;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Memproses...';
            try {
                const response = await fetch(`${API_URL}?action=process_payment`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        order_id: parseInt(orderId),
                        method: paymentMethod
                    })
                });
                const result = await response.json();
                if (result.success) {
                    document.getElementById('modal-title').textContent = "Sukses";
                    document.getElementById('modal-confirm-view').classList.add('hidden');
                    document.getElementById('modal-footer-confirm').classList.add('hidden');
                    document.getElementById('modal-success-view').classList.remove('hidden');
                    document.getElementById('modal-footer-success').classList.remove('hidden');
                    showToast(result.message, true);
                    loadData();
                } else {
                    showToast(result.message, false);
                }
            } catch (error) {
                showToast("Gagal memproses pembayaran.", false);
            } finally {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Konfirmasi Pembayaran';
            }
        }

        function closePaymentModal() {
            document.getElementById('payment-modal').classList.add('hidden');
            resetModalViews();
        }

        function resetModalViews() {
            document.getElementById('modal-title').textContent = "Konfirmasi Pembayaran";
            document.getElementById('modal-confirm-view').classList.remove('hidden');
            document.getElementById('modal-footer-confirm').classList.remove('hidden');
            document.getElementById('modal-success-view').classList.add('hidden');
            document.getElementById('modal-footer-success').classList.add('hidden');
            lastOrderForReceipt = null;
        }

        function printReceipt() {
            if (!lastOrderForReceipt) {
                showToast("Data struk tidak ditemukan.", false);
                return;
            }
            const {
                order,
                items
            } = lastOrderForReceipt;
            const orderDate = new Date(order.date).toLocaleString('id-ID');
            const itemRows = items.map(item => `
                <tr>
                    <td>${item.name}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-right">${formatCurrency(item.price)}</td>
                    <td class="text-right">${formatCurrency(item.price * item.quantity)}</td>
                </tr>`).join('');
            const receiptHtml = `
                <html><head><title>Struk Pesanan #${order.id}</title><style>
                    body { font-family: 'Courier New', monospace; font-size: 12px; } .container { width: 280px; margin: 0 auto; } h2 { text-align: center; margin-bottom: 5px; } p { margin: 2px 0; } hr { border: none; border-top: 1px dashed #000; } table { width: 100%; border-collapse: collapse; margin: 10px 0; } th, td { padding: 2px 0; } .text-right { text-align: right; } .text-center { text-align: center; } .total { font-weight: bold; }
                </style></head><body><div class="container">
                    <h2>RestoFlow</h2><p>==============================</p><p>No. Pesanan: ${order.id}</p><p>Meja: ${order.table_number}</p><p>Tanggal: ${orderDate}</p><hr>
                    <table><thead><tr><th>Item</th><th class="text-center">Jml</th><th class="text-right">Harga</th><th class="text-right">Subtotal</th></tr></thead><tbody>${itemRows}</tbody></table><hr>
                    <table><tr><td class="total">TOTAL</td><td class="total text-right">${formatCurrency(order.total)}</td></tr></table><hr>
                    <p class="text-center" style="margin-top:10px;">Terima Kasih!</p>
                </div></body></html>`;
            const printWindow = window.open('', '', 'height=600,width=400');
            printWindow.document.write(receiptHtml);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        }

        // --- LOGIKA MANAJEMEN MENU & STOK ---
        async function renderManajemenMenu() {
            try {
                const response = await fetch(`${API_URL}?action=get_menus`);
                const allMenus = await response.json();
                const menuListBody = document.getElementById('manajemen-menu-list');
                if (allMenus.length === 0) {
                    menuListBody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-gray-500">Belum ada menu.</td></tr>`;
                    return;
                }
                menuListBody.innerHTML = allMenus.map(menu => `
                    <tr>
                        <td class="p-3 text-sm font-semibold">${menu.name}</td>
                        <td class="p-3 text-sm">${menu.category}</td>
                        <td class="p-3 text-sm">${formatCurrency(menu.price)}</td>
                    </tr>`).join('');
            } catch (error) {
                showToast("Gagal memuat daftar menu.", false);
            }
        }

        async function renderManajemenStok() {
            try {
                const response = await fetch(`${API_URL}?action=get_menus`);
                const allMenus = await response.json();
                const stockListBody = document.getElementById('stok-menu-list');
                if (allMenus.length === 0) {
                    stockListBody.innerHTML = `<tr><td colspan="3" class="text-center p-4 text-gray-500">Belum ada menu.</td></tr>`;
                    return;
                }
                stockListBody.innerHTML = allMenus.map(menu => `
                    <tr>
                        <td class="p-3 text-sm font-semibold">${menu.name}</td>
                        <td class="p-3 text-center text-lg font-bold">${menu.stock}</td>
                        <td class="p-3 text-center">
                            <div class="flex justify-center items-center space-x-2">
                                <button onclick="updateStock(${menu.id}, -1)" class="bg-red-500 text-white w-8 h-8 rounded-full font-bold text-lg">-</button>
                                <button onclick="updateStock(${menu.id}, 1)" class="bg-green-500 text-white w-8 h-8 rounded-full font-bold text-lg">+</button>
                                <button onclick="updateStock(${menu.id}, 10)" class="bg-blue-500 text-white px-3 h-8 rounded-md text-sm font-semibold">+10</button>
                            </div>
                        </td>
                    </tr>`).join('');
            } catch (error) {
                showToast("Gagal memuat daftar stok.", false);
            }
        }

        async function updateStock(menuId, changeAmount) {
            try {
                const response = await fetch(`${API_URL}?action=update_stock`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: menuId,
                        change: changeAmount
                    })
                });
                const result = await response.json();
                if (result.success) {
                    renderManajemenStok();
                    loadMenuData();
                } else {
                    showToast(result.message, false);
                }
            } catch (error) {
                showToast("Gagal memperbarui stok.", false);
            }
        }

        // --- EVENT LISTENERS ---
        document.addEventListener('DOMContentLoaded', () => {
            const defaultPage = '<?php echo $default_page; ?>';
            showPage(defaultPage);
            loadData();
        });

        document.getElementById('process-order-btn').addEventListener('click', async () => {
            const tableId = document.getElementById('table-select').value;
            if (!tableId) {
                showToast("Pilih meja terlebih dahulu!", false);
                return;
            }
            const total = currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const orderData = {
                table_id: parseInt(tableId),
                items: currentOrder,
                total: total
            };
            const response = await fetch(`${API_URL}?action=create_order`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });
            const result = await response.json();
            showToast(result.message, result.success);
            if (result.success) {
                currentOrder = [];
                renderCurrentOrder();
                loadData();
            }
        });

        document.getElementById('confirm-payment-btn').addEventListener('click', confirmAndPay);
        document.getElementById('close-modal-btn').addEventListener('click', closePaymentModal);
        document.getElementById('cancel-payment-btn').addEventListener('click', closePaymentModal);
        document.getElementById('close-success-btn').addEventListener('click', closePaymentModal);
        document.getElementById('print-receipt-btn').addEventListener('click', printReceipt);

        document.getElementById('menu-search').addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const filteredMenus = menus.filter(menu => menu.name.toLowerCase().includes(searchTerm));
            renderMenuList(filteredMenus);
        });

        document.getElementById('add-menu-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const form = e.target;
            const menuData = {
                name: form.name.value,
                price: parseFloat(form.price.value),
                category: form.category.value
            };
            try {
                const response = await fetch(`${API_URL}?action=add_menu`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(menuData)
                });
                const result = await response.json();
                showToast(result.message, result.success);
                if (result.success) {
                    form.reset();
                    renderManajemenMenu();
                    renderManajemenStok();
                    loadMenuData();
                }
            } catch (error) {
                showToast("Gagal menambahkan menu.", false);
            }
        });
    </script>

</body>
</html>