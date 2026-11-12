<?php
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
date_default_timezone_set('Asia/Makassar');
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sync.php';

$rootDir = dirname(__DIR__);
$certFile = $rootDir . '/data/certificates.json';
$projFile = $rootDir . '/data/projects.json';
$msgFile = $rootDir . '/data/messages.json';

// Helper: Read JSON
function readJsonData($file) {
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

// Helper: Save JSON & Sync
function saveJsonData($file, $data) {
    $ok = file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok !== false) {
        syncIndexHtml();
        return true;
    }
    return false;
}

$action = $_GET['action'] ?? '';

// Public action: login
if ($action === 'login') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (verifyAdminLogin($user, $pass)) {
        echo json_encode(['success' => true, 'message' => 'Login berhasil!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Username atau password yang Anda masukkan salah.']);
    }
    exit;
}

// Public action: send_message (dikirim dari form kontak index.html)
if ($action === 'send_message') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name) || empty($email) || empty($whatsapp) || empty($purpose) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Mohon lengkapi semua kolom: Nama, Email, Nomor WhatsApp, Tujuan, dan Pesan.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format alamat email tidak valid.']);
        exit;
    }

    $messages = readJsonData($msgFile);
    $newMessage = [
        'id' => 'msg_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8),
        'name' => htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($email, ENT_QUOTES, 'UTF-8'),
        'whatsapp' => htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8'),
        'purpose' => htmlspecialchars($purpose, ENT_QUOTES, 'UTF-8'),
        'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'created_at' => date('Y-m-d H:i:s'),
        'is_read' => false
    ];

    array_unshift($messages, $newMessage);

    if (file_put_contents($msgFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) {
        echo json_encode([
            'success' => true,
            'message' => 'Pesan Anda berhasil dikirim ke dashboard admin!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menyimpan pesan ke database. Silakan coba lagi.'
        ]);
    }
    exit;
}

if (!isAdminLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sesi login telah habis atau halaman telah di-reload. Silakan login kembali.']);
    exit;
}

// Helper: Handle File Upload
function handleFileUpload($fileInputName, $targetDir, $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'webp']) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fileInputName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts)) {
        throw new Exception("Ekstensi file .{$ext} tidak diizinkan. Format yang didukung: " . implode(', ', $allowedExts));
    }

    // Maksimal 25MB
    if ($file['size'] > 25 * 1024 * 1024) {
        throw new Exception("Ukuran file terlalu besar (maksimal 25MB).");
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Nama file bersih & unik
    $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
    $cleanBase = substr($cleanBase, 0, 30);
    $filename = time() . '_' . $cleanBase . '.' . $ext;
    $targetPath = $targetDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    }

    throw new Exception("Gagal memindahkan file yang diunggah ke folder tujuan.");
}

try {
    switch ($action) {
        // ---------------- CERTIFICATES ----------------
        case 'list_certs':
            $certs = readJsonData($certFile);
            echo json_encode(['success' => true, 'data' => $certs]);
            break;

        case 'save_cert':
            $certs = readJsonData($certFile);
            $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;

            // Upload Preview Image
            $previewUploadDir = $rootDir . '/sertif/preview';
            $previewFileName = handleFileUpload('preview_file', $previewUploadDir, ['jpg', 'jpeg', 'png', 'webp']);

            // Upload Original Doc (PDF/JPG/PNG)
            $docUploadDir = $rootDir . '/sertif';
            $docFileName = handleFileUpload('doc_file', $docUploadDir, ['pdf', 'jpg', 'jpeg', 'png', 'webp']);

            $existing = null;
            $existingIndex = -1;
            if ($id !== null) {
                foreach ($certs as $idx => $c) {
                    if (isset($c['id']) && intval($c['id']) === $id) {
                        $existing = $c;
                        $existingIndex = $idx;
                        break;
                    }
                }
            }

            // Preview Path
            $previewPath = $existing ? ($existing['preview'] ?? '') : '';
            if ($previewFileName) {
                $previewPath = 'sertif/preview/' . $previewFileName;
            } elseif (!empty($_POST['preview_url'])) {
                $previewPath = trim($_POST['preview_url']);
            }

            // Original File Path
            $filePath = $existing ? ($existing['file'] ?? '') : '';
            if ($docFileName) {
                $filePath = 'sertif/' . $docFileName;
            } elseif (!empty($_POST['file_url'])) {
                $filePath = trim($_POST['file_url']);
            }

            // Menentukan tipe dokumen
            $filetype = trim($_POST['filetype'] ?? '');
            if (empty($filetype)) {
                $fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $filetype = ($fileExt === 'pdf') ? 'PDF Resmi' : strtoupper($fileExt);
            }

            $certData = [
                'id' => ($id !== null) ? $id : (time() + rand(1, 999)),
                'title' => trim($_POST['title'] ?? ''),
                'short_name' => trim($_POST['short_name'] ?? $_POST['title'] ?? ''),
                'category' => trim($_POST['category'] ?? 'Sertifikasi'),
                'badge_label' => trim($_POST['badge_label'] ?? ''),
                'issuer' => trim($_POST['issuer'] ?? ''),
                'date' => trim($_POST['date'] ?? ''),
                'credential' => trim($_POST['credential'] ?? ''),
                'tag' => trim($_POST['tag'] ?? $_POST['credential'] ?? ''),
                'icon' => trim($_POST['icon'] ?? 'fas fa-award'),
                'preview' => $previewPath,
                'file' => $filePath,
                'filetype' => $filetype,
                'verify_url' => trim($_POST['verify_url'] ?? ''),
                'quote' => trim($_POST['quote'] ?? ''),
                'description' => trim($_POST['description'] ?? '')
            ];

            if (empty($certData['title'])) {
                throw new Exception('Judul sertifikat wajib diisi.');
            }

            if ($existingIndex >= 0) {
                $certs[$existingIndex] = $certData;
            } else {
                $certs[] = $certData;
            }

            saveJsonData($certFile, $certs);
            echo json_encode(['success' => true, 'message' => 'Sertifikat berhasil disimpan & web disinkronkan!', 'data' => $certData]);
            break;

        case 'delete_cert':
            $id = intval($_POST['id'] ?? 0);
            $certs = readJsonData($certFile);
            $newCerts = [];
            $deleted = false;
            foreach ($certs as $c) {
                if (isset($c['id']) && intval($c['id']) === $id) {
                    $deleted = true;
                    continue; // Skip (hapus)
                }
                $newCerts[] = $c;
            }

            if (!$deleted) {
                throw new Exception('Sertifikat tidak ditemukan.');
            }

            saveJsonData($certFile, $newCerts);
            echo json_encode(['success' => true, 'message' => 'Sertifikat berhasil dihapus & web disinkronkan!']);
            break;

        // ---------------- PROJECTS ----------------
        case 'list_projects':
            $projects = readJsonData($projFile);
            echo json_encode(['success' => true, 'data' => $projects]);
            break;

        case 'save_project':
            $projects = readJsonData($projFile);
            $id = isset($_POST['id']) && $_POST['id'] !== '' ? intval($_POST['id']) : null;

            // Upload Cover Image
            $imgUploadDir = $rootDir . '/img/project';
            $coverFileName = handleFileUpload('image_file', $imgUploadDir, ['jpg', 'jpeg', 'png', 'webp']);

            $existing = null;
            $existingIndex = -1;
            if ($id !== null) {
                foreach ($projects as $idx => $p) {
                    if (isset($p['id']) && intval($p['id']) === $id) {
                        $existing = $p;
                        $existingIndex = $idx;
                        break;
                    }
                }
            }

            $imgPath = $existing ? ($existing['image'] ?? '') : '';
            if (!empty($_POST['remove_cover']) && $_POST['remove_cover'] === '1') {
                $imgPath = '';
            }
            if ($coverFileName) {
                $imgPath = 'img/project/' . $coverFileName;
            } elseif (!empty($_POST['image_url'])) {
                $imgPath = trim($_POST['image_url']);
            }

            // Handle Gallery Images (Existing + Newly Uploaded)
            $galleryImages = [];
            if (isset($_POST['existing_gallery']) && $_POST['existing_gallery'] !== '') {
                $decoded = json_decode($_POST['existing_gallery'], true);
                if (is_array($decoded)) {
                    $galleryImages = $decoded;
                }
            } elseif ($existing && isset($existing['gallery_images']) && is_array($existing['gallery_images'])) {
                $galleryImages = $existing['gallery_images'];
            }

            // Upload multiple gallery images
            if (isset($_FILES['gallery_files']) && is_array($_FILES['gallery_files']['name'])) {
                if (!is_dir($imgUploadDir)) {
                    mkdir($imgUploadDir, 0777, true);
                }
                $gCount = count($_FILES['gallery_files']['name']);
                for ($k = 0; $k < $gCount; $k++) {
                    $gErr = $_FILES['gallery_files']['error'][$k];
                    $gTmp = $_FILES['gallery_files']['tmp_name'][$k];
                    $gName = $_FILES['gallery_files']['name'][$k];

                    if ($gErr === UPLOAD_ERR_OK && is_uploaded_file($gTmp)) {
                        $ext = strtolower(pathinfo($gName, PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                            $cleanTitle = slugifyProject(trim($_POST['title'] ?? 'proto'));
                            $uniqueGName = $cleanTitle . '_' . time() . '_' . substr(md5(uniqid()), 0, 6) . '.' . $ext;
                            $destPath = $imgUploadDir . '/' . $uniqueGName;
                            if (move_uploaded_file($gTmp, $destPath)) {
                                $galleryImages[] = 'img/project/' . $uniqueGName;
                            }
                        }
                    }
                }
            }

            $isDownload = (!empty($_POST['is_download']) && $_POST['is_download'] === '1');
            $autoPreview = (!empty($_POST['auto_preview']) && $_POST['auto_preview'] === '1') || !isset($_POST['auto_preview']);

            $projData = [
                'id' => ($id !== null) ? $id : (time() + rand(1, 999)),
                'title' => trim($_POST['title'] ?? ''),
                'subtitle' => trim($_POST['subtitle'] ?? ''),
                'badge' => trim($_POST['badge'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'full_description' => trim($_POST['full_description'] ?? ''),
                'tech_stack' => trim($_POST['tech_stack'] ?? ''),
                'video_url' => trim($_POST['video_url'] ?? ''),
                'demo_url' => trim($_POST['demo_url'] ?? ''),
                'github_url' => trim($_POST['github_url'] ?? ''),
                'image' => $imgPath,
                'gallery_images' => $galleryImages,
                'link' => trim($_POST['link'] ?? '#'),
                'btn_text' => trim($_POST['btn_text'] ?? 'lihat demo'),
                'is_download' => $isDownload,
                'auto_preview' => $autoPreview
            ];

            if (empty($projData['title'])) {
                throw new Exception('Nama/Judul proyek wajib diisi.');
            }

            // Otomatis generate file preview jika auto_preview aktif
            if (!$isDownload && $autoPreview) {
                $genFile = generateProjectPreviewPage($projData, $rootDir);
                if ($genFile) {
                    $projData['link'] = $genFile;
                }
            }

            if ($existingIndex >= 0) {
                $projects[$existingIndex] = $projData;
            } else {
                $projects[] = $projData;
            }

            saveJsonData($projFile, $projects);
            echo json_encode(['success' => true, 'message' => 'Proyek & halaman preview berhasil disimpan dan dipublish!', 'data' => $projData]);
            break;

        case 'delete_project':
            $id = intval($_POST['id'] ?? 0);
            $projects = readJsonData($projFile);
            $newProjects = [];
            $deleted = false;
            $deletedProject = null;

            foreach ($projects as $p) {
                if (isset($p['id']) && intval($p['id']) === $id) {
                    $deleted = true;
                    $deletedProject = $p;
                    continue; // Skip (hapus)
                }
                $newProjects[] = $p;
            }

            if (!$deleted) {
                throw new Exception('Proyek tidak ditemukan.');
            }

            // Jika proyek memiliki file preview otomatis yang dibuat (project-*.html), hapus file HTML-nya
            if ($deletedProject && !empty($deletedProject['link'])) {
                $link = $deletedProject['link'];
                if (preg_match('/^project-[a-z0-9\-]+\.html$/i', $link)) {
                    $fileToDelete = $rootDir . '/' . $link;
                    if (file_exists($fileToDelete)) {
                        @unlink($fileToDelete);
                    }
                }
            }

            saveJsonData($projFile, $newProjects);
            echo json_encode(['success' => true, 'message' => 'Proyek dan halaman preview berhasil dihapus dari web!']);
            break;

        // ---------------- MESSAGES (PESAN MASUK) ----------------
        case 'list_messages':
            $messages = readJsonData($msgFile);
            $unread = 0;
            foreach ($messages as $m) {
                if (empty($m['is_read'])) $unread++;
            }
            echo json_encode([
                'success' => true,
                'data' => $messages,
                'total' => count($messages),
                'unread' => $unread
            ]);
            break;

        case 'mark_message_read':
            $id = $_POST['id'] ?? '';
            $isRead = isset($_POST['is_read']) ? (bool)$_POST['is_read'] : true;
            $messages = readJsonData($msgFile);
            $found = false;
            $unread = 0;
            foreach ($messages as &$m) {
                if ($m['id'] === $id) {
                    $m['is_read'] = $isRead;
                    $found = true;
                }
                if (empty($m['is_read'])) $unread++;
            }
            unset($m);
            if ($found) {
                file_put_contents($msgFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            echo json_encode([
                'success' => true,
                'unread' => $unread
            ]);
            break;

        case 'mark_all_messages_read':
            $messages = readJsonData($msgFile);
            foreach ($messages as &$m) {
                $m['is_read'] = true;
            }
            unset($m);
            file_put_contents($msgFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            echo json_encode([
                'success' => true,
                'unread' => 0
            ]);
            break;

        case 'delete_message':
            $id = $_POST['id'] ?? '';
            $messages = readJsonData($msgFile);
            $newMessages = [];
            $deleted = false;
            $unread = 0;
            foreach ($messages as $m) {
                if ($m['id'] === $id) {
                    $deleted = true;
                    continue;
                }
                $newMessages[] = $m;
                if (empty($m['is_read'])) $unread++;
            }
            if ($deleted) {
                file_put_contents($msgFile, json_encode($newMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            echo json_encode([
                'success' => true,
                'message' => 'Pesan berhasil dihapus.',
                'unread' => $unread
            ]);
            break;

        // ---------------- SYNC MANUAL ----------------
        case 'sync_all':
            $res = syncIndexHtml();
            echo json_encode($res);
            break;

        // ---------------- ACCOUNT SETTINGS ----------------
        case 'change_password':
            $oldPass = $_POST['old_password'] ?? '';
            $newPass = trim($_POST['new_password'] ?? '');
            $newUsername = trim($_POST['username'] ?? '');

            $cfg = getAuthConfig();
            if (!password_verify($oldPass, $cfg['password_hash'])) {
                throw new Exception('Password saat ini salah. Jika Anda belum pernah mengubahnya, ketik password default: admin123');
            }

            if (empty($newUsername)) {
                throw new Exception('Username tidak boleh kosong.');
            }

            // Jika password baru diisi, perbarui password. Jika kosong, pertahankan password lama
            if (!empty($newPass)) {
                if (strlen($newPass) < 4) {
                    throw new Exception('Password baru minimal 4 karakter.');
                }
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
            } else {
                $hash = $cfg['password_hash'];
            }

            saveAuthConfig($newUsername, $hash);
            $_SESSION['fdrtech_admin_user'] = $newUsername;

            echo json_encode([
                'success' => true,
                'message' => 'Akun berhasil diperbarui! Username Anda sekarang: ' . $newUsername,
                'username' => $newUsername
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

