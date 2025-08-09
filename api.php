<?php
// Memulai sesi di baris paling atas untuk mengelola login
session_start();

// Mengatur header respons sebagai JSON
header('Content-Type: application/json');
// Mengimpor koneksi database
require 'db.php';

// Mengambil aksi dari parameter GET
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Jika aksi bukan 'login', periksa apakah pengguna sudah login
if ($action !== 'login') {
    if (!isset($_SESSION['user_role'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Akses ditolak. Silakan login terlebih dahulu.']);
        exit();
    }
}

/**
 * Fungsi helper untuk memeriksa hak akses pengguna.
 * Menghentikan eksekusi jika peran pengguna tidak diizinkan.
 * @param array $allowed_roles Array berisi peran yang diizinkan (e.g., ['admin', 'kasir']).
 */
function authorize($allowed_roles) {
    if (!in_array($_SESSION['user_role'], $allowed_roles)) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki hak akses untuk aksi ini.']);
        exit();
    }
}

// Mengambil data JSON dari body request
$data = json_decode(file_get_contents('php://input'), true);

// Router untuk setiap aksi API
switch ($action) {
    case 'login':
        $username = isset($data['username']) ? mysqli_real_escape_string($conn, $data['username']) : '';
        $password = isset($data['password']) ? $data['password'] : '';

        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $username;
                $_SESSION['user_role'] = $user['role'];
                echo json_encode(['success' => true, 'role' => $user['role']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Username atau password salah.']);
        }
        $stmt->close();
        break;

    // --- Endpoint Manajemen Menu ---
    case 'get_menus':
        authorize(['admin', 'kasir', 'koki']); // Semua role bisa melihat menu
        $result = mysqli_query($conn, "SELECT id, name, category, price, stock FROM menu ORDER BY name ASC");
        $menus = mysqli_fetch_all($result, MYSQLI_ASSOC);
        echo json_encode($menus);
        break;

    case 'add_menu':
        authorize(['admin']);
        $name = isset($data['name']) ? mysqli_real_escape_string($conn, $data['name']) : '';
        $price = isset($data['price']) ? (float)$data['price'] : 0;
        $category = isset($data['category']) ? mysqli_real_escape_string($conn, $data['category']) : '';
        if (empty($name) || $price <= 0 || empty($category)) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
            break;
        }
        $sql = "INSERT INTO menu (name, price, category) VALUES ('$name', $price, '$category')";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Menu berhasil ditambahkan.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menambahkan menu.']);
        }
        break;

    case 'update_stock':
        authorize(['admin']);
        $id = isset($data['id']) ? (int)$data['id'] : 0;
        $change = isset($data['change']) ? (int)$data['change'] : 0;
        if ($id === 0 || $change === 0) {
            echo json_encode(['success' => false, 'message' => 'Data tidak valid.']);
            break;
        }
        $sql = "UPDATE menu SET stock = GREATEST(0, stock + ?) WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $change, $id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Stok berhasil diperbarui.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui stok.']);
        }
        $stmt->close();
        break;

    // --- Endpoint Manajemen Meja & Pesanan ---
    case 'get_tables':
        authorize(['admin', 'kasir']);
        $result = mysqli_query($conn, "SELECT id, number, status FROM tables ORDER BY number ASC");
        $tables = mysqli_fetch_all($result, MYSQLI_ASSOC);
        echo json_encode($tables);
        break;

    case 'create_order':
        authorize(['admin', 'kasir']);
        $table_id = isset($data['table_id']) ? (int)$data['table_id'] : 0;
        $items = isset($data['items']) ? $data['items'] : [];
        $total = isset($data['total']) ? (float)$data['total'] : 0;
        if (empty($table_id) || empty($items) || $total <= 0) {
            echo json_encode(['success' => false, 'message' => 'Data pesanan tidak lengkap.']);
            break;
        }
        mysqli_begin_transaction($conn);
        try {
            foreach ($items as $item) {
                $menu_id = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $sql_check_stock = "SELECT name, stock FROM menu WHERE id = ? FOR UPDATE";
                $stmt_check = $conn->prepare($sql_check_stock);
                $stmt_check->bind_param("i", $menu_id);
                $stmt_check->execute();
                $result_stock = $stmt_check->get_result();
                $menu_data = $result_stock->fetch_assoc();
                $stmt_check->close();
                if (!$menu_data || $menu_data['stock'] < $quantity) {
                    throw new Exception("Stok untuk menu '{$menu_data['name']}' tidak mencukupi.");
                }
            }
            $sql_order = "INSERT INTO orders (table_id, total, status) VALUES (?, ?, 'Masuk')";
            $stmt_order = $conn->prepare($sql_order);
            $stmt_order->bind_param("id", $table_id, $total);
            if (!$stmt_order->execute()) throw new Exception("Gagal membuat pesanan.");
            $order_id = $stmt_order->insert_id;
            $stmt_order->close();
            foreach ($items as $item) {
                $menu_id = (int)$item['id'];
                $quantity = (int)$item['quantity'];
                $sql_detail = "INSERT INTO order_details (order_id, menu_id, quantity) VALUES (?, ?, ?)";
                $stmt_detail = $conn->prepare($sql_detail);
                $stmt_detail->bind_param("iii", $order_id, $menu_id, $quantity);
                if (!$stmt_detail->execute()) throw new Exception("Gagal menyimpan detail pesanan.");
                $stmt_detail->close();
                $sql_update_stock = "UPDATE menu SET stock = stock - ? WHERE id = ?";
                $stmt_update = $conn->prepare($sql_update_stock);
                $stmt_update->bind_param("ii", $quantity, $menu_id);
                if (!$stmt_update->execute()) throw new Exception("Gagal mengurangi stok.");
                $stmt_update->close();
            }
            $sql_table = "UPDATE tables SET status = 'Terpakai' WHERE id = ?";
            $stmt_table = $conn->prepare($sql_table);
            $stmt_table->bind_param("i", $table_id);
            if (!$stmt_table->execute()) throw new Exception("Gagal update status meja.");
            $stmt_table->close();
            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Pesanan berhasil dibuat!']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Endpoint Dapur ---
    case 'get_kitchen_orders':
        authorize(['admin', 'koki']);
        $sql_orders = "SELECT o.id, o.status, t.number AS table_number, o.date FROM orders o JOIN tables t ON o.table_id = t.id WHERE o.status IN ('Masuk', 'Diproses', 'Selesai') ORDER BY o.date ASC";
        $result_orders = mysqli_query($conn, $sql_orders);
        $orders = mysqli_fetch_all($result_orders, MYSQLI_ASSOC);
        $order_ids = array_map(fn($order) => $order['id'], $orders);
        if (!empty($order_ids)) {
            $ids_string = implode(',', $order_ids);
            $sql_details = "SELECT od.order_id, od.quantity, m.name FROM order_details od JOIN menu m ON od.menu_id = m.id WHERE od.order_id IN ($ids_string)";
            $result_details = mysqli_query($conn, $sql_details);
            $details = mysqli_fetch_all($result_details, MYSQLI_ASSOC);
            foreach ($orders as $i => $order) {
                $orders[$i]['items'] = [];
                foreach ($details as $detail) {
                    if ($detail['order_id'] == $order['id']) {
                        $orders[$i]['items'][] = $detail;
                    }
                }
            }
        }
        echo json_encode($orders);
        break;

    case 'update_order_status':
        authorize(['admin', 'koki']);
        $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
        $new_status = isset($data['status']) ? mysqli_real_escape_string($conn, $data['status']) : '';
        if (!in_array($new_status, ['Diproses', 'Selesai'])) {
            echo json_encode(['success' => false, 'message' => 'Status tidak valid.']);
            break;
        }
        $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Status pesanan berhasil diubah.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengubah status pesanan.']);
        }
        break;

    // --- Endpoint Pembayaran ---
    case 'get_unpaid_orders':
        authorize(['admin', 'kasir']);
        $sql = "SELECT o.id, t.number as table_number, o.total, o.date, o.status FROM orders o JOIN tables t ON o.table_id = t.id ORDER BY o.date DESC LIMIT 50";
        $result = mysqli_query($conn, $sql);
        $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
        echo json_encode($orders);
        break;

    case 'get_order_details_for_payment':
        authorize(['admin', 'kasir']);
        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($order_id === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID Pesanan tidak valid.']);
            break;
        }
        try {
            $sql_order = "SELECT o.id, o.total, o.date, t.number AS table_number FROM orders o JOIN tables t ON o.table_id = t.id WHERE o.id = $order_id";
            $result_order = mysqli_query($conn, $sql_order);
            if (mysqli_num_rows($result_order) == 0) throw new Exception("Pesanan tidak ditemukan.");
            $order_data = mysqli_fetch_assoc($result_order);
            $sql_details = "SELECT m.name, od.quantity, m.price FROM order_details od JOIN menu m ON od.menu_id = m.id WHERE od.order_id = $order_id";
            $result_details = mysqli_query($conn, $sql_details);
            $items = mysqli_fetch_all($result_details, MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'order' => $order_data, 'items' => $items]);
        } catch (Exception $e) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'process_payment':
        authorize(['admin', 'kasir']);
        $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
        $payment_method = isset($data['method']) ? mysqli_real_escape_string($conn, $data['method']) : 'Tunai';
        mysqli_begin_transaction($conn);
        try {
            $sql_get_table = "SELECT table_id FROM orders WHERE id = $order_id";
            $result_table = mysqli_query($conn, $sql_get_table);
            if(mysqli_num_rows($result_table) == 0) throw new Exception("Pesanan tidak ditemukan.");
            $order_data = mysqli_fetch_assoc($result_table);
            $table_id = $order_data['table_id'];
            $sql_payment = "INSERT INTO payments (order_id, method, status) VALUES ($order_id, '$payment_method', 'Berhasil')";
            if (!mysqli_query($conn, $sql_payment)) throw new Exception("Gagal mencatat pembayaran.");
            $sql_order = "UPDATE orders SET status = 'Lunas' WHERE id = $order_id";
            if (!mysqli_query($conn, $sql_order)) throw new Exception("Gagal update status pesanan.");
            $sql_table = "UPDATE tables SET status = 'Tersedia' WHERE id = $table_id";
            if (!mysqli_query($conn, $sql_table)) throw new Exception("Gagal update status meja.");
            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Pembayaran berhasil diproses.']);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;
        
    // --- Endpoint Laporan ---
    case 'get_sales_report':
        authorize(['admin']);
        $base_sql = "SELECT p.id as payment_id, p.payment_date, p.method, o.id as order_id, o.total, t.number as table_number FROM payments p JOIN orders o ON p.order_id = o.id JOIN tables t ON o.table_id = t.id WHERE p.status = 'Berhasil'";
        $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
        $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
        if (!empty($start_date) && !empty($end_date)) {
            $end_date_obj = new DateTime($end_date);
            $end_date_obj->modify('+1 day');
            $end_date_plus_one = $end_date_obj->format('Y-m-d');
            $stmt = $conn->prepare($base_sql . " AND p.payment_date >= ? AND p.payment_date < ? ORDER BY p.payment_date DESC");
            $stmt->bind_param("ss", $start_date, $end_date_plus_one);
        } else {
            $stmt = $conn->prepare($base_sql . " ORDER BY p.payment_date DESC");
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $data = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal mengambil data laporan.']);
        }
        $stmt->close();
        break;

    default:
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
        break;
}

mysqli_close($conn);
?>