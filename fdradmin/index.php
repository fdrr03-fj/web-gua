<?php
require_once __DIR__ . '/auth.php';

// Pastikan sesi selalu bersih saat halaman pertama kali dibuka atau di-reload (F5)
logoutAdmin();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fdrtech Portfolio CMS — Admin Dashboard</title>
    
    <!-- Favicon / Tab Icon -->
    <link rel="icon" type="image/png" href="../img/logo/icon.png">
    <link rel="apple-touch-icon" href="../img/logo/icon.png">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Mozilla PDF.js for Client-Side Text Extraction & Canvas Rendering -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <link rel="stylesheet" href="admin.css?v=20261106_delimg02">
</head>
<body>

    <!-- ======================================================================
         LOGIN SCREEN (Tampil Setiap Kali Halaman Dibuka / Di-reload)
         ====================================================================== -->
    <div class="login-wrapper" id="login-screen">
        <div class="login-card">
            <a href="../" class="login-logo" title="Kembali ke Portofolio">
                <img src="../img/logo/logo-dark.png" alt="Fdrtech Logo">
            </a>

            <h1 class="login-title">Portfolio Manager</h1>
            <p class="login-subtitle">Masukkan username &amp; password untuk membuka dashboard.</p>

            <div id="login-error-box" style="display: none; background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; text-align: left; align-items: center; gap: 8px;">
                <i class="fas fa-circle-exclamation"></i>
                <span id="login-error-text">Username atau password salah.</span>
            </div>

            <form id="login-form" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" style="padding-right: 42px;" placeholder="••••••••" required>
                        <button type="button" onclick="togglePasswordVisibility('password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; font-size: 14px;" title="Lihat password">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <span class="form-hint" style="margin-top: 4px;">Akun bawaan default: <code>admin</code> / <code>admin123</code></span>
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
                    <i class="fas fa-right-to-bracket"></i> Masuk ke Dashboard
                </button>
            </form>

            <div style="margin-top: 28px; border-top: 1px solid var(--border-color); padding-top: 18px; font-size: 12px; color: var(--text-muted);">
                <a href="../" style="color: var(--text-muted); text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Kembali ke Website Utama
                </a>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         ADMIN DASHBOARD LAYOUT (Tersembunyi sampai Login Berhasil)
         ====================================================================== -->
    <div class="admin-layout" id="dashboard-screen" style="display: none;">
        <!-- Header Nav -->
        <header class="admin-header">
            <div class="header-inner">
                <div class="brand-wrap">
                    <img src="../img/logo/logo-dark.png" alt="Fdrtech Logo">
                    <span class="brand-badge">Admin CMS</span>
                </div>

                <div class="header-actions">
                    <button type="button" class="btn-notification" id="btn-notifications" onclick="switchTab('tab-messages')" title="Notifikasi Pesan Masuk">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="header-unread-badge" style="display: none;">0</span>
                    </button>
                    <button type="button" class="btn-header-link" id="btn-manual-sync" onclick="handleManualSync()" title="Sinkronkan data ke index.html">
                        <i class="fas fa-arrows-rotate"></i> <span>Sync Web</span>
                    </button>
                    <a href="../" target="_blank" class="btn-header-link" title="Buka website di tab baru">
                        <i class="fas fa-arrow-up-right-from-square"></i> <span>Lihat Website</span>
                    </a>
                    <button type="button" class="btn-logout" onclick="handleLogout()" title="Keluar dari sesi admin">
                        <i class="fas fa-power-off"></i> <span>Keluar</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="fas fa-award"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Total Sertifikat</h4>
                        <div class="stat-number" id="stat-cert-count">0</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Proyek Unggulan</h4>
                        <div class="stat-number" id="stat-proj-count">0</div>
                    </div>
                </div>

                <div class="stat-card stat-clickable" onclick="switchTab('tab-messages')" style="cursor: pointer;" title="Buka Pesan Masuk">
                    <div class="stat-icon orange">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Pesan Masuk</h4>
                        <div class="stat-number" id="stat-msg-count">0</div>
                        <span class="stat-sub-badge" id="stat-unread-sub" style="display: none;">0 Baru</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="stat-info">
                        <h4>Auto-Lock Security</h4>
                        <div class="stat-number" style="font-size: 15px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-lock"></i> Kunci Otomatis
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="tabs-nav">
                <button type="button" class="tab-btn active" data-tab="tab-certificates">
                    <i class="fas fa-award"></i>
                    <span>Kelola Sertifikat</span>
                    <span class="tab-badge" id="badge-cert-count">0</span>
                </button>
                <button type="button" class="tab-btn" data-tab="tab-projects">
                    <i class="fas fa-folder-open"></i>
                    <span>Kelola Proyek</span>
                    <span class="tab-badge" id="badge-proj-count">0</span>
                </button>
                <button type="button" class="tab-btn" data-tab="tab-messages" id="tab-btn-messages">
                    <i class="fas fa-envelope"></i>
                    <span>Pesan Masuk</span>
                    <span class="tab-badge badge-unread-pill" id="badge-msg-count" style="display: none;">0</span>
                </button>
                <button type="button" class="tab-btn" data-tab="tab-settings">
                    <i class="fas fa-shield-halved"></i>
                    <span>Pengaturan Akun</span>
                </button>
            </div>

            <!-- TAB 1: SERTIFIKAT -->
            <div class="tab-pane active" id="tab-certificates">
                <div class="section-toolbar">
                    <div class="toolbar-title">
                        <h3>Daftar Sertifikat &amp; Penghargaan</h3>
                        <p>Kelola dokumen sertifikasi resmi yang tampil pada carousel dan modal preview keaslian.</p>
                    </div>
                    <div class="toolbar-actions">
                        <div class="search-input-wrap">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="text" id="search-certs" class="search-input" placeholder="Cari sertifikat...">
                        </div>
                        <button type="button" class="btn-primary" onclick="openAddCertModal()">
                            <i class="fas fa-plus"></i> Tambah Sertifikat Baru
                        </button>
                    </div>
                </div>

                <div class="data-table-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Pratinjau</th>
                                    <th>Nama Sertifikat</th>
                                    <th>Kategori &amp; Badge</th>
                                    <th>Penerbit &amp; Tanggal</th>
                                    <th>Format File</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="certs-table-body">
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data sertifikat...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: PROYEK -->
            <div class="tab-pane" id="tab-projects">
                <div class="section-toolbar">
                    <div class="toolbar-title">
                        <h3>Daftar Karya &amp; Proyek Pilihan</h3>
                        <p>Atur kartu proyek unggulan yang ditampilkan pada bagian Featured Projects.</p>
                    </div>
                    <div class="toolbar-actions">
                        <div class="search-input-wrap">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="text" id="search-projects" class="search-input" placeholder="Cari proyek...">
                        </div>
                        <button type="button" class="btn-primary" onclick="openAddProjectModal()">
                            <i class="fas fa-plus"></i> Tambah Proyek Baru
                        </button>
                    </div>
                </div>

                <div class="data-table-card">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>Gambar Cover</th>
                                    <th>Nama Proyek</th>
                                    <th>Deskripsi Singkat</th>
                                    <th>Tautan &amp; Tombol</th>
                                    <th style="text-align: right;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="projects-table-body">
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        <i class="fas fa-spinner fa-spin"></i> Memuat data proyek...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PESAN MASUK (INBOX) -->
            <div class="tab-pane" id="tab-messages">
                <div class="section-toolbar">
                    <div class="toolbar-title">
                        <h3>Pesan Masuk dari Formulir Kontak</h3>
                        <p>Kelola pesan dan ajakan diskusi/kolaborasi yang dikirimkan pengunjung website portofolio.</p>
                    </div>
                    <div class="toolbar-actions">
                        <div class="search-input-wrap">
                            <i class="fas fa-magnifying-glass"></i>
                            <input type="text" id="search-messages" class="search-input" placeholder="Cari pesan atau nama pengirim...">
                        </div>
                        <div class="toolbar-btn-row">
                            <button type="button" class="btn-secondary" onclick="markAllMessagesAsRead()" id="btn-mark-all-read" title="Tandai semua pesan sebagai sudah dibaca">
                                <i class="fas fa-check-double"></i> <span>Tandai Semua Dibaca</span>
                            </button>
                            <button type="button" class="btn-secondary" onclick="loadMessages(true)" title="Muat ulang pesan masuk">
                                <i class="fas fa-rotate"></i> <span>Refresh</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filter Tabs: Semua, Belum Dibaca, Sudah Dibaca -->
                <div class="message-filter-bar">
                    <button type="button" class="msg-filter-btn active" data-filter="all" onclick="filterMessages('all', this)">
                        Semua Pesan (<span id="msg-filter-all-count">0</span>)
                    </button>
                    <button type="button" class="msg-filter-btn" data-filter="unread" onclick="filterMessages('unread', this)">
                        <span class="status-dot green"></span> Belum Dibaca (<span id="msg-filter-unread-count">0</span>)
                    </button>
                    <button type="button" class="msg-filter-btn" data-filter="read" onclick="filterMessages('read', this)">
                        Sudah Dibaca (<span id="msg-filter-read-count">0</span>)
                    </button>
                </div>

                <!-- Messages Container -->
                <div class="messages-container" id="messages-list-wrap">
                    <div class="empty-state">
                        <i class="fas fa-spinner fa-spin"></i>
                        <h3>Memuat pesan masuk...</h3>
                    </div>
                </div>
            </div>

            <!-- TAB 4: PENGATURAN AKUN -->
            <div class="tab-pane" id="tab-settings">
                <div class="section-toolbar">
                    <div class="toolbar-title">
                        <h3>Keamanan &amp; Pengaturan Akun</h3>
                        <p>Ubah username dan password untuk melindungi akses ke Mini Admin CMS.</p>
                    </div>
                </div>

                <div class="data-table-card" style="max-width: 560px; padding: 28px;">
                    <div style="background: rgba(77, 97, 255, 0.1); border: 1px solid rgba(77, 97, 255, 0.3); color: #9bb0ff; padding: 14px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 22px; line-height: 1.6;">
                        <strong style="color: #ffffff;"><i class="fas fa-circle-info" style="margin-right: 6px;"></i>Petunjuk:</strong><br>
                        &bull; Kolom <strong>Password Saat Ini</strong> wajib diisi untuk verifikasi keamanan. Ketik <code>admin123</code> jika belum pernah diubah.<br>
                        &bull; Kolom <strong>Password Baru</strong> opsional. Biarkan kosong jika hanya ingin mengganti username.
                    </div>

                    <form id="account-form">
                        <div class="form-group" style="margin-bottom: 18px;">
                            <label class="form-label" for="acc-username">Username</label>
                            <input type="text" id="acc-username" name="username" class="form-control" value="admin" required>
                            <span class="form-hint">Nama pengguna untuk login dashboard.</span>
                        </div>

                        <div class="form-group" style="margin-bottom: 18px;">
                            <label class="form-label" for="acc-old-password">Password Saat Ini (*)</label>
                            <div style="position: relative;">
                                <input type="password" id="acc-old-password" name="old_password" class="form-control" style="padding-right: 42px;" placeholder="Ketik admin123 (jika belum diubah)" required>
                                <button type="button" onclick="togglePasswordVisibility('acc-old-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; font-size: 14px;" title="Lihat password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 24px;">
                            <label class="form-label" for="acc-new-password">Password Baru (Opsional)</label>
                            <div style="position: relative;">
                                <input type="password" id="acc-new-password" name="new_password" class="form-control" style="padding-right: 42px;" placeholder="Kosongkan jika hanya ingin ganti username">
                                <button type="button" onclick="togglePasswordVisibility('acc-new-password', this)" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: transparent; border: none; color: var(--text-muted); cursor: pointer; padding: 6px; font-size: 14px;" title="Lihat password">
                                    <i class="far fa-eye"></i>
                                </button>
                            </div>
                            <span class="form-hint">Minimal 4 karakter jika ingin mengganti password.</span>
                        </div>

                        <button type="submit" class="btn-primary">
                            <i class="fas fa-key"></i> Simpan Perubahan Akun
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- ======================================================================
         MODAL: SMART ANALYZER & VERIFIKASI SERTIFIKAT (2-STEP WORKFLOW)
         ====================================================================== -->
    <div class="modal-backdrop" id="cert-modal">
        <div class="modal-dialog modal-wide">
            <div class="modal-header">
                <div class="modal-header-brand">
                    <span class="analyzer-pill-badge"><i class="fas fa-wand-magic-sparkles"></i> AI Analyzer</span>
                    <h3 id="cert-modal-title-text">Tambah Sertifikat Baru</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('cert-modal')">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <!-- MODAL BODY CONTAINER (Scrollable / Constrained Flex) -->
            <div class="modal-body-container">
                <!-- STEP 1: DROPZONE & SCANNING SCREEN -->
                <div id="cert-step-upload" class="cert-step-view active">
                    <div class="analyzer-intro">
                        <h4>Unggah Dokumen Sertifikat</h4>
                        <p>Cukup unggah file PDF atau Foto Sertifikat (JPG/PNG). Sistem cerdas akan otomatis membaca isi sertifikat, mengekstrak judul, penerbit, tanggal, nomor kredensial, dan membuat pratinjau dokumen sebelum dipublish.</p>
                    </div>

                    <!-- Dropzone Area -->
                    <div class="smart-upload-container" id="smart-dropzone">
                        <input type="file" id="smart-cert-file-input" accept=".pdf,image/png,image/jpeg,image/webp" class="smart-file-hidden">
                        
                        <div class="dropzone-idle" id="dropzone-idle-state">
                            <div class="dropzone-icon-orbit">
                                <i class="fas fa-cloud-arrow-up main-icon"></i>
                                <i class="fas fa-file-pdf orbit-sub sub-pdf"></i>
                                <i class="fas fa-file-image orbit-sub sub-img"></i>
                            </div>
                            <h4 class="dropzone-title">Seret &amp; Lepaskan File Sertifikat ke Sini</h4>
                            <p class="dropzone-subtitle">atau <button type="button" class="btn-browse-trigger" onclick="document.getElementById('smart-cert-file-input').click()">Pilih File dari Komputer</button></p>
                            <div class="dropzone-formats">
                                <span class="fmt-badge"><i class="fas fa-file-pdf"></i> PDF Asli</span>
                                <span class="fmt-badge"><i class="fas fa-file-image"></i> JPG / PNG / WebP</span>
                                <span class="fmt-badge"><i class="fas fa-bolt"></i> Auto Scan &amp; Preview</span>
                            </div>
                        </div>

                        <!-- Scanning / Parsing State -->
                        <div class="dropzone-scanning" id="dropzone-scanning-state" style="display: none;">
                            <div class="scanner-anim-box">
                                <div class="scanner-doc-sheet">
                                    <div class="sheet-line line-1"></div>
                                    <div class="sheet-line line-2"></div>
                                    <div class="sheet-line line-3"></div>
                                    <div class="sheet-badge"></div>
                                </div>
                                <div class="laser-scanner-line"></div>
                            </div>
                            <h4 class="scan-status-title" id="scan-status-text">Membaca dokumen sertifikat...</h4>
                            <div class="scan-steps-progress">
                                <span class="step-dot active" id="dot-step-1"><i class="fas fa-circle-check"></i> Load File</span>
                                <span class="step-dot" id="dot-step-2"><i class="fas fa-circle-notch fa-spin"></i> Baca Teks</span>
                                <span class="step-dot" id="dot-step-3"><i class="fas fa-circle-notch"></i> Ekstrak Metadata</span>
                                <span class="step-dot" id="dot-step-4"><i class="fas fa-circle-notch"></i> Generate Preview</span>
                            </div>
                        </div>
                    </div>

                    <div class="analyzer-footer-options">
                        <button type="button" class="btn-text-manual" onclick="switchToManualEntry()">
                            <i class="fas fa-keyboard"></i> Atau isi formulir secara manual tanpa deteksi otomatis &rarr;
                        </button>
                    </div>
                </div>

                <!-- STEP 2: PREVIEW & VERIFICATION SPLIT SCREEN -->
                <div id="cert-step-review" class="cert-step-view">
                    <div class="analysis-status-banner">
                        <div class="status-banner-icon">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <div class="status-banner-content">
                            <strong>Analisis Berhasil Diekstrak!</strong>
                            <span>Periksa pratinjau dokumen di sebelah kiri dan tinjau kolom data di sebelah kanan. Anda dapat mengedit teks sebelum dipublish ke website live.</span>
                        </div>
                    </div>

                    <form id="cert-form" enctype="multipart/form-data">
                        <input type="hidden" id="cert-modal-id" name="id" value="">
                        <input type="hidden" id="cert-modal-filetype" name="filetype" value="">
                        <input type="hidden" id="cert-modal-preview-url" name="preview_url" value="">
                        <input type="hidden" id="cert-modal-file-url" name="file_url" value="">

                        <!-- Split Grid: Left Document Preview, Right Form Fields -->
                        <div class="analyzer-split-layout">
                            <!-- LEFT: VISUAL DOCUMENT PREVIEW -->
                            <div class="analyzer-doc-col">
                                <div class="doc-panel-header">
                                    <div class="doc-panel-title">
                                        <i class="fas fa-file-invoice"></i> Pratinjau Dokumen Asli
                                    </div>
                                    <div class="doc-panel-meta">
                                        <span class="badge-pill blue" id="cert-view-filetype">PDF</span>
                                        <span class="badge-pill gray" id="cert-view-filename">dokumen.pdf</span>
                                    </div>
                                </div>

                                <div class="doc-visual-viewport" id="doc-viewport">
                                    <div class="doc-zoom-toolbar">
                                        <button type="button" class="zoom-btn" onclick="zoomDocPreview(-0.15)" title="Perkecil (-)"><i class="fas fa-magnifying-glass-minus"></i></button>
                                        <button type="button" class="zoom-btn" id="doc-zoom-level-text" onclick="resetDocZoom()" title="Reset Ukuran (100%)">100%</button>
                                        <button type="button" class="zoom-btn" onclick="zoomDocPreview(0.15)" title="Perbesar (+)"><i class="fas fa-magnifying-glass-plus"></i></button>
                                    </div>

                                    <div class="doc-canvas-stage" id="doc-stage">
                                        <img id="cert-rendered-preview-img" src="" alt="Pratinjau Sertifikat" style="display: none;">
                                        <canvas id="cert-pdf-canvas" style="display: none;"></canvas>
                                    </div>
                                </div>

                                <div class="doc-panel-bottom-bar">
                                    <span class="doc-tip"><i class="fas fa-info-circle"></i> Gunakan tombol zoom untuk memeriksa teks detail / kode QR.</span>
                                    <button type="button" class="btn-change-doc" onclick="backToUploadStep()">
                                        <i class="fas fa-rotate-left"></i> Ganti File
                                    </button>
                                </div>
                            </div>

                            <!-- RIGHT: EXTRACTED FORM FIELDS -->
                            <div class="analyzer-form-col">
                                <div class="form-panel-header">
                                    <div class="form-panel-title">
                                        <i class="fas fa-pen-to-square"></i> Verifikasi Data &amp; Edit
                                    </div>
                                    <span class="badge-pill green"><i class="fas fa-wand-magic-sparkles"></i> Auto-Filled</span>
                                </div>

                                <div class="form-scrollable-body">
                                    <div class="form-grid">
                                        <div class="form-group col-full">
                                            <label class="form-label" for="cert-title">Judul Lengkap Sertifikat (*)</label>
                                            <input type="text" id="cert-title" name="title" class="form-control" placeholder="Contoh: CCNA: Introduction to Networks" required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-short-name">Nama Singkat di Kartu</label>
                                            <input type="text" id="cert-short-name" name="short_name" class="form-control" placeholder="Contoh: Cisco CCNA">
                                            <span class="form-hint">Teks ringkas kartu carousel.</span>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-issuer">Penerbit Resmi (*)</label>
                                            <input type="text" id="cert-issuer" name="issuer" class="form-control" placeholder="Contoh: Cisco Networking Academy" required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-category">Kategori Bidang</label>
                                            <input type="text" id="cert-category" name="category" class="form-control" placeholder="Contoh: Network Engineering">
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-badge-label">Label Badge</label>
                                            <input type="text" id="cert-badge-label" name="badge_label" class="form-control" placeholder="Contoh: 2026, Predikat A, 9 Courses">
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-date">Tanggal Terbit</label>
                                            <input type="text" id="cert-date" name="date" class="form-control" placeholder="Contoh: 19 Juni 2026">
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-credential">Nomor Kredensial / ID</label>
                                            <input type="text" id="cert-credential" name="credential" class="form-control" placeholder="Contoh: ID: T4PQFVWB7WAM">
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="cert-tag">Tag Ringkas Kredensial</label>
                                            <input type="text" id="cert-tag" name="tag" class="form-control" placeholder="Contoh: Verified Cisco NetAcad Credential">
                                        </div>

                                        <div class="form-group col-full">
                                            <label class="form-label">Pilihan Ikon Kartu (*)</label>
                                            <div class="icon-picker-widget">
                                                <!-- Selected Icon Bar -->
                                                <div class="icon-selected-bar">
                                                    <span class="icon-preview-box" id="cert-icon-preview"><i class="fas fa-award"></i></span>
                                                    <div class="icon-selected-info">
                                                        <strong class="icon-selected-title" id="cert-icon-label">Award &amp; Prestasi</strong>
                                                        <span class="icon-selected-code" id="cert-icon-code">fas fa-award</span>
                                                    </div>
                                                    <button type="button" class="btn-toggle-custom" onclick="toggleCustomIconInput()" title="Ketik manual class FontAwesome">
                                                        <i class="fas fa-keyboard"></i> Ketik Manual
                                                    </button>
                                                    <input type="hidden" id="cert-icon" name="icon" value="fas fa-award">
                                                </div>

                                                <!-- Manual input toggleable box -->
                                                <div class="custom-icon-box" id="custom-icon-box" style="display: none;">
                                                    <div class="custom-icon-input-wrap">
                                                        <input type="text" id="cert-custom-icon-input" class="form-control" placeholder="Contoh: fas fa-certificate, fab fa-python, dll">
                                                        <button type="button" class="btn-secondary btn-sm" onclick="applyCustomIcon()">Terapkan</button>
                                                    </div>
                                                    <span class="form-hint">Ketik nama class ikon FontAwesome 6 jika tidak ada pada daftar pilihan di bawah.</span>
                                                </div>

                                                <!-- Visual Icon Chips Grid -->
                                                <div class="icon-options-label">
                                                    <span><i class="fas fa-hand-pointer"></i> Klik salah satu ikon di bawah untuk memilih:</span>
                                                </div>
                                                <div class="icon-grid-selector" id="cert-icon-options">
                                                    <!-- Icon chips loaded dynamically by renderIconPicker() -->
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group col-full">
                                            <label class="form-label" for="cert-verify-url">Link Verifikasi Online (Opsional)</label>
                                            <input type="url" id="cert-verify-url" name="verify_url" class="form-control" placeholder="https://coursera.org/verify/...">
                                            <span class="form-hint">Tautan verifikasi resmi jika tersedia (Coursera, Credly, dll).</span>
                                        </div>

                                        <div class="form-group col-full">
                                            <label class="form-label" for="cert-quote">Kutipan Ringkas di Kartu Carousel</label>
                                            <textarea id="cert-quote" name="quote" class="form-control" rows="2" placeholder="Teks ringkas 1-2 kalimat yang tampil pada kartu sertifikat..."></textarea>
                                        </div>

                                        <div class="form-group col-full">
                                            <label class="form-label" for="cert-description">Deskripsi Lengkap di Mode Preview</label>
                                            <textarea id="cert-description" name="description" class="form-control" rows="3" placeholder="Rincian lengkap kompetensi yang tampil saat modal pratinjau dibuka di web..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PERMANENT MODAL ACTIONS FOOTER (Always at bottom, never clipped or pushed) -->
            <div class="modal-footer split-footer" id="cert-modal-footer">
                <div class="footer-left">
                    <button type="button" class="btn-secondary" id="btn-cert-back" onclick="backToUploadStep()" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Ganti File Sertifikat
                    </button>
                </div>
                <div class="footer-right">
                    <button type="button" class="btn-secondary" onclick="closeModal('cert-modal')">Batal</button>
                    <button type="submit" form="cert-form" class="btn-primary btn-publish-cert" id="btn-publish-cert" style="display: none;">
                        <i class="fas fa-cloud-arrow-up"></i> Publish ke Web
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         MODAL: TAMBAH / EDIT PROYEK (DENGAN AUTO-PREVIEW GENERATOR)
         ====================================================================== -->
    <div class="modal-backdrop" id="project-modal">
        <div class="modal-dialog modal-wide">
            <div class="modal-header">
                <div class="modal-header-brand">
                    <span class="analyzer-pill-badge" style="background: rgba(77, 97, 255, 0.15); color: #7f95ff;"><i class="fas fa-layer-group"></i> Project CMS</span>
                    <h3 id="project-modal-title-text">Tambah Proyek Baru</h3>
                </div>
                <button type="button" class="modal-close" onclick="closeModal('project-modal')">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <form id="project-form" enctype="multipart/form-data" class="modal-dialog-form">
                <input type="hidden" id="project-modal-id" name="id" value="">
                <input type="hidden" id="project-existing-gallery" name="existing_gallery" value="[]">
                <input type="hidden" id="project-remove-cover" name="remove_cover" value="0">

                <div class="modal-body-container" style="padding: 24px;">
                    <!-- Auto-Preview Banner Info -->
                    <div class="project-preview-info-banner">
                        <div class="info-banner-icon">
                            <i class="fas fa-wand-magic-sparkles"></i>
                        </div>
                        <div class="info-banner-text">
                            <strong>Otomatis Buat Halaman Preview Khusus</strong>
                            <span>Saat proyek disimpan, sistem secara otomatis merakit halaman preview mandiri lengkap dengan deskripsi mendalam, tech stack, pemutar video demo, dan galeri prototipe interaktif berfitur lightbox zoom.</span>
                        </div>
                    </div>

                    <div class="project-modal-grid">
                        <!-- KOLOM KIRI: INFORMASI & DESKRIPSI -->
                        <div class="project-form-col">
                            <h4 class="form-section-title"><i class="fas fa-circle-info"></i> Informasi &amp; Deskripsi Proyek</h4>

                            <div class="form-group" style="margin-bottom: 16px;">
                                <label class="form-label" for="project-title">Nama / Judul Proyek (*)</label>
                                <input type="text" id="project-title" name="title" class="form-control" placeholder="Contoh: Rumah Siap, Smart Infrared, Pekan Jurnalistik" required>
                            </div>

                            <div class="form-row-2" style="margin-bottom: 16px;">
                                <div class="form-group">
                                    <label class="form-label" for="project-subtitle">Sub-Judul / Kategori (*)</label>
                                    <input type="text" id="project-subtitle" name="subtitle" class="form-control" placeholder="Contoh: Health-Tech Web App, Hardware IoT">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="project-badge">Label Badge Halaman</label>
                                    <input type="text" id="project-badge" name="badge" class="form-control" placeholder="Contoh: Web App / Health-Tech">
                                    <span class="form-hint">Kosongkan untuk otomatis memakai Sub-Judul.</span>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 16px;">
                                <label class="form-label" for="project-tech-stack">Teknologi yang Digunakan (Tech Stack)</label>
                                <input type="text" id="project-tech-stack" name="tech_stack" class="form-control" placeholder="Contoh: Arduino, Solenoid, C++, PHP, MySQL, Bootstrap 5">
                                <span class="form-hint">Pisahkan tiap teknologi dengan tanda koma (,).</span>
                            </div>

                            <div class="form-group" style="margin-bottom: 16px;">
                                <label class="form-label" for="project-description">Deskripsi Singkat (Tampil di Kartu Beranda)</label>
                                <textarea id="project-description" name="description" class="form-control" rows="2" placeholder="Ringkasan 1-2 kalimat untuk kartu depan portofolio..."></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="project-full-description">Deskripsi Lengkap (Tampil di Halaman Preview)</label>
                                <textarea id="project-full-description" name="full_description" class="form-control" rows="4" placeholder="Penjelasan lengkap mengenai proyek, latar belakang, fitur utama, dan solusi yang dihadirkan..."></textarea>
                            </div>
                        </div>

                        <!-- KOLOM KANAN: MEDIA, VIDEO, GALERI & TAUTAN -->
                        <div class="project-form-col">
                            <h4 class="form-section-title"><i class="fas fa-photo-film"></i> Media, Galeri Prototipe &amp; Video</h4>

                            <!-- Cover Image -->
                            <div class="form-group" style="margin-bottom: 16px;">
                                <label class="form-label">Foto Cover Kartu Portofolio (JPG / PNG)</label>
                                <div class="file-upload-box compact-upload">
                                    <input type="file" id="proj_image_file" name="image_file" accept="image/*">
                                    <div class="upload-prompt">
                                        <i class="fas fa-image"></i>
                                        <span>Pilih atau seret foto cover utama</span>
                                    </div>
                                </div>
                                <div class="project-cover-card" id="project-cover-wrap" style="display: none;">
                                    <div class="cover-card-thumb-wrap" onclick="previewCoverImage()" title="Klik untuk pratinjau gambar cover">
                                        <img src="" alt="Cover" class="cover-card-img" id="project-cover-thumb">
                                        <div class="cover-card-zoom-overlay">
                                            <i class="fas fa-magnifying-glass-plus"></i>
                                            <span>Perbesar</span>
                                        </div>
                                    </div>
                                    <div class="cover-card-meta">
                                        <div class="cover-card-status">
                                            <span class="badge-pill blue" style="font-size: 11px; padding: 2px 8px;"><i class="fas fa-image"></i> Cover Proyek</span>
                                            <span class="upload-file-name" id="project-cover-filename" title="Nama File Gambar">-</span>
                                        </div>
                                        <p class="cover-card-hint">Foto cover utama kartu beranda &amp; header.</p>
                                        <div class="cover-card-actions">
                                            <button type="button" class="btn-preview-action" onclick="previewCoverImage()">
                                                <i class="fas fa-eye"></i> Pratinjau Gambar Penuh
                                            </button>
                                            <button type="button" class="btn-preview-action btn-danger-action" onclick="removeProjectCover()" title="Hapus foto cover ini">
                                                <i class="fas fa-trash-can"></i> Hapus Cover
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Multiple Prototype Gallery Upload -->
                            <div class="form-group" style="margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <label class="form-label" style="margin-bottom: 0;">Galeri Foto Prototipe &amp; Layar (Multiple Upload)</label>
                                    <button type="button" class="btn-mini-clear" id="btn-clear-all-gallery" onclick="clearAllGalleryImages()" style="display: none; background: none; border: none; color: #ef4444; font-size: 11.5px; font-weight: 600; cursor: pointer; padding: 0;">
                                        <i class="fas fa-trash-can"></i> Hapus Semua Galeri
                                    </button>
                                </div>
                                <div class="file-upload-box compact-upload">
                                    <input type="file" id="proj_gallery_files" name="gallery_files[]" accept="image/*" multiple>
                                    <div class="upload-prompt">
                                        <i class="fas fa-images"></i>
                                        <span>Pilih beberapa foto prototipe sekaligus (JPG / PNG / WebP)</span>
                                    </div>
                                </div>
                                <!-- Gallery Thumbs Strip -->
                                <div class="project-gallery-thumbs-container" id="project-gallery-thumbs"></div>
                                <span class="form-hint">Arahkan kursor atau klik tanda silang (X) pada foto untuk menghapus foto yang salah upload. Klik foto untuk pratinjau.</span>
                            </div>

                            <!-- Video Demo (MP4 or YouTube) -->
                            <div class="form-group" style="margin-bottom: 18px;">
                                <label class="form-label" for="project-video-url">Video Demo / Simulasi (Opsional)</label>
                                <input type="text" id="project-video-url" name="video_url" class="form-control" placeholder="Contoh: demo.mp4 atau https://youtube.com/watch?v=...">
                                <span class="form-hint">Mendukung file video lokal (.mp4) atau link video YouTube.</span>
                            </div>

                            <!-- Tautan & Aksi -->
                            <h4 class="form-section-title"><i class="fas fa-link"></i> Pengaturan Preview &amp; Tombol Aksi</h4>

                            <!-- Checkbox Auto Preview -->
                            <div class="form-group" style="background: rgba(77, 97, 255, 0.08); border: 1px solid rgba(77, 97, 255, 0.22); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 0;">
                                    <input type="checkbox" id="project-auto-preview" name="auto_preview" value="1" checked style="width: 18px; height: 18px; accent-color: #4d61ff;">
                                    <div>
                                        <strong style="color: #ffffff; font-size: 13.5px;">Otomatis Buat Halaman Preview</strong>
                                        <div style="font-size: 12px; color: var(--text-muted);">Menghasilkan file mandiri <code>project-[nama].html</code> dengan galeri &amp; deskripsi lengkap</div>
                                    </div>
                                </label>
                            </div>

                            <div class="form-row-2" style="margin-bottom: 14px;">
                                <div class="form-group">
                                    <label class="form-label" for="project-demo-url">Link Demo Live (Opsional)</label>
                                    <input type="url" id="project-demo-url" name="demo_url" class="form-control" placeholder="https://myapp.com">
                                </div>
                                <div class="form-group">
                                    <label class="form-label" for="project-github-url">Link GitHub (Opsional)</label>
                                    <input type="url" id="project-github-url" name="github_url" class="form-control" placeholder="https://github.com/...">
                                </div>
                            </div>

                            <div class="form-row-2" style="margin-bottom: 14px;">
                                <div class="form-group">
                                    <label class="form-label" for="project-btn-text">Teks Tombol Aksi di Kartu</label>
                                    <input type="text" id="project-btn-text" name="btn_text" class="form-control" placeholder="lihat detail proyek" value="lihat detail proyek">
                                </div>
                                <div class="form-group" id="manual-link-group" style="display: none;">
                                    <label class="form-label" for="project-link">Tautan Manual</label>
                                    <input type="text" id="project-link" name="link" class="form-control" placeholder="project-nama.html / #proyek" value="#">
                                </div>
                            </div>

                            <div class="form-group" style="flex-direction: row; align-items: center; gap: 10px;">
                                <input type="checkbox" id="project-is-download" name="is_download" value="1" style="width: 18px; height: 18px;">
                                <label class="form-label" for="project-is-download" style="cursor: pointer; margin-bottom: 0;">Jadikan tombol unduh file (Download File)?</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer split-footer">
                    <div class="footer-left">
                        <span class="badge-pill blue" id="project-status-pill"><i class="fas fa-bolt"></i> Auto-Generate Preview Aktif</span>
                    </div>
                    <div class="footer-right">
                        <button type="button" class="btn-secondary" onclick="closeModal('project-modal')">Batal</button>
                        <button type="submit" class="btn-primary" id="btn-submit-project">
                            <i class="fas fa-cloud-arrow-up"></i> Simpan &amp; Publikasikan Proyek
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL: DETAIL PESAN MASUK -->
    <div class="modal-backdrop" id="message-detail-modal">
        <div class="modal-dialog" style="max-width: 620px;">
            <div class="modal-header">
                <div class="modal-title-wrap">
                    <span class="badge-pill blue"><i class="fas fa-envelope-open-text"></i> Pesan Masuk</span>
                    <h3 id="modal-msg-sender-name">Detail Pesan</h3>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeModal('message-detail-modal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body" style="padding: 24px;">
                <div class="msg-detail-meta-box">
                    <div class="msg-detail-avatar" id="modal-msg-avatar">BP</div>
                    <div class="msg-detail-info">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 4px;">
                            <h4 id="modal-msg-name" style="margin: 0;">Nama Pengirim</h4>
                            <span id="modal-msg-purpose" class="msg-purpose-pill" style="display: none;">Kerjasama</span>
                        </div>
                        <div class="msg-detail-meta-row">
                            <a id="modal-msg-email" href="#" class="msg-email-link" title="Kirim email"><i class="fas fa-envelope"></i> email@example.com</a>
                            <span class="meta-separator">&bull;</span>
                            <a id="modal-msg-wa" href="#" target="_blank" class="msg-wa-link" style="display: none;" title="Buka WhatsApp"><i class="fab fa-whatsapp"></i> +628...</a>
                            <span class="meta-separator">&bull;</span>
                            <span id="modal-msg-date" class="msg-date-text"><i class="far fa-clock"></i> Waktu</span>
                        </div>
                    </div>
                    <div id="modal-msg-status-badge"></div>
                </div>

                <div class="msg-detail-content-wrap">
                    <label class="msg-content-label">Isi Pesan:</label>
                    <div class="msg-detail-body" id="modal-msg-body">
                        <!-- Pesan lengkap -->
                    </div>
                </div>
            </div>

            <div class="modal-footer split-footer">
                <div class="footer-left">
                    <button type="button" class="btn-danger-outline" id="modal-msg-btn-delete">
                        <i class="fas fa-trash-can"></i> Hapus Pesan
                    </button>
                </div>
                <div class="footer-right">
                    <button type="button" class="btn-secondary" onclick="closeModal('message-detail-modal')">Tutup</button>
                    <a id="modal-msg-btn-wa" href="#" target="_blank" class="btn-wa-direct" style="display: none;">
                        <i class="fab fa-whatsapp"></i> Chat WhatsApp
                    </a>
                    <a id="modal-msg-btn-reply" href="#" class="btn-primary" target="_blank">
                        <i class="fas fa-reply"></i> Balas Email
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================================================================
         MODAL: LIGHTBOX PRATINJAU GAMBAR (IMAGE PREVIEW MODAL)
         ====================================================================== -->
    <div class="modal-backdrop image-preview-backdrop" id="image-preview-modal" onclick="handleImagePreviewBackdropClick(event)">
        <div class="image-preview-dialog" onclick="event.stopPropagation()">
            <div class="image-preview-header">
                <div class="image-preview-title-wrap">
                    <span class="preview-badge-icon"><i class="fas fa-image"></i></span>
                    <div class="preview-title-texts">
                        <h4 id="image-preview-title">Pratinjau Gambar</h4>
                        <span id="image-preview-filename" class="preview-sub-filename">foto.jpg</span>
                    </div>
                </div>
                <div class="image-preview-header-actions">
                    <a id="image-preview-open-link" href="#" target="_blank" class="btn-preview-header-btn" title="Buka gambar di tab baru">
                        <i class="fas fa-arrow-up-right-from-square"></i> <span>Tab Baru</span>
                    </a>
                    <button type="button" class="btn-preview-header-btn btn-preview-close" onclick="closeImagePreviewModal()" title="Tutup pratinjau (Esc)">
                        <i class="fas fa-xmark"></i>
                    </button>
                </div>
            </div>

            <div class="image-preview-stage">
                <div class="image-preview-img-container">
                    <img id="image-preview-target" src="" alt="Pratinjau Gambar Penuh">
                </div>
            </div>

            <div class="image-preview-footer">
                <span class="image-preview-hint"><i class="fas fa-lightbulb"></i> Tekan <strong>Esc</strong> atau klik di luar untuk menutup pratinjau dan kembali ke form edit.</span>
                <button type="button" class="btn-secondary btn-sm" onclick="closeImagePreviewModal()">
                    <i class="fas fa-arrow-left"></i> Tutup Pratinjau
                </button>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div class="toast-container" id="toast-container"></div>

    <script src="admin.js?v=20261106_delimg02"></script>

</body>
</html>
