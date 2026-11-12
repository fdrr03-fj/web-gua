/**
 * FDRTECH MINI ADMIN DASHBOARD JAVASCRIPT
 */

// Global State
let certificatesData = [];
let projectsData = [];
let messagesData = [];
let currentMsgFilter = 'all';
let lastUnreadMsgCount = 0;
let msgPollInterval = null;

// Helper: Switch Tab Programmatically
function switchTab(tabId) {
    const btn = document.querySelector(`.tab-btn[data-tab="${tabId}"]`);
    if (btn) {
        btn.click();
        try {
            btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        } catch (e) {}
    }
}

// Toast Notification Helper
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation';
    toast.innerHTML = `<i class="fas ${icon}"></i><span>${message}</span>`;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(30px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// Tab Switching
function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-tab');
            tabButtons.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetPane = document.getElementById(targetId);
            if (targetPane) targetPane.classList.add('active');

            // Auto-center tab in scrollable nav on touch/mobile
            try {
                btn.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } catch (e) {}
        });
    });
}

// Modal Helpers
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('open');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('open');
}

// ================= IMAGE PREVIEW LIGHTBOX FUNCTIONS =================
function previewImageModal(src, title = 'Pratinjau Gambar', filename = '') {
    if (!src) return;
    const modal = document.getElementById('image-preview-modal');
    const targetImg = document.getElementById('image-preview-target');
    const titleEl = document.getElementById('image-preview-title');
    const filenameEl = document.getElementById('image-preview-filename');
    const openLink = document.getElementById('image-preview-open-link');

    if (targetImg) targetImg.src = src;
    if (titleEl) titleEl.textContent = title;
    if (filenameEl) filenameEl.textContent = filename || src.split('/').pop() || 'Gambar';
    if (openLink) openLink.href = src;

    if (modal) {
        modal.classList.add('open');
    }
}

function previewCoverImage() {
    const thumb = document.getElementById('project-cover-thumb');
    const filenameEl = document.getElementById('project-cover-filename');
    const titleInput = document.getElementById('project-title');
    const projectTitle = titleInput ? titleInput.value.trim() : 'Proyek';

    if (thumb && thumb.src && thumb.style.display !== 'none') {
        const fname = filenameEl ? filenameEl.textContent : 'cover.jpg';
        previewImageModal(thumb.src, `Cover: ${projectTitle || 'Proyek'}`, fname);
    }
}

function handleImagePreviewBackdropClick(event) {
    if (event && event.target && event.target.id === 'image-preview-modal') {
        closeImagePreviewModal();
    }
}

function closeImagePreviewModal() {
    const modal = document.getElementById('image-preview-modal');
    if (modal) {
        modal.classList.remove('open');
    }
}

// Setup File Input Preview
function setupFileInputPreview(inputId, previewWrapId, previewThumbId, fileNameId) {
    const input = document.getElementById(inputId);
    const wrap = document.getElementById(previewWrapId);
    const thumb = document.getElementById(previewThumbId);
    const nameEl = document.getElementById(fileNameId);

    if (!input) return;

    input.addEventListener('change', () => {
        const file = input.files[0];
        if (!file) return;

        if (nameEl) nameEl.textContent = file.name;
        if (wrap) wrap.style.display = 'flex';

        if (inputId === 'proj_image_file') {
            const removeInput = document.getElementById('project-remove-cover');
            if (removeInput) removeInput.value = '0';
        }

        if (file.type.startsWith('image/') && thumb) {
            const reader = new FileReader();
            reader.onload = (e) => {
                thumb.src = e.target.result;
                thumb.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else if (thumb) {
            thumb.style.display = 'none';
        }
    });
}

// ---------------- CERTIFICATES LOGIC ----------------
async function loadCertificates() {
    try {
        const res = await fetch(`api.php?action=list_certs&_t=${Date.now()}`, { cache: 'no-store' });
        const json = await res.json();
        if (json.success) {
            certificatesData = json.data;
            renderCertificatesTable(certificatesData);
            updateStatCounters();
        }
    } catch (err) {
        showToast('Gagal memuat daftar sertifikat: ' + err.message, 'error');
    }
}

function renderCertificatesTable(certs) {
    const tbody = document.getElementById('certs-table-body');
    if (!tbody) return;

    if (!certs || certs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="empty-state">
                        <i class="fas fa-award"></i>
                        <h4>Belum ada sertifikat</h4>
                        <p>Klik tombol "+ Tambah Sertifikat Baru" untuk mengunggah sertifikat pertama kamu.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = certs.map((c, idx) => {
        const previewImg = c.preview ? `../${c.preview}` : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="60" height="44"%3E%3Crect width="100%25" height="100%25" fill="%23222"/%3E%3C/svg%3E';
        const fileBadge = c.filetype && c.filetype.toLowerCase().includes('pdf') ? 'blue' : 'green';
        return `
            <tr>
                <td style="width: 70px;">
                    <img src="${previewImg}" alt="${c.title}" class="cell-thumb">
                </td>
                <td>
                    <div class="cell-title-wrap">
                        <span class="cell-title-main">${c.short_name || c.title}</span>
                        <span class="cell-title-sub">${c.title}</span>
                    </div>
                </td>
                <td>
                    <span class="badge-pill blue">${c.category || 'General'}</span>
                    ${c.badge_label ? `<span class="badge-pill gray">${c.badge_label}</span>` : ''}
                </td>
                <td>
                    <div class="cell-title-wrap">
                        <span class="cell-title-main" style="font-size: 13px;">${c.issuer || '-'}</span>
                        <span class="cell-title-sub">${c.date || '-'}</span>
                    </div>
                </td>
                <td>
                    <span class="badge-pill ${fileBadge}">${c.filetype || 'File'}</span>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <button type="button" class="btn-icon-action edit" onclick="editCertificate(${c.id})" title="Edit Sertifikat">
                        <i class="fas fa-pencil"></i>
                    </button>
                    <button type="button" class="btn-icon-action delete" onclick="deleteCertificate(${c.id}, '${encodeURIComponent(c.short_name || c.title)}')" title="Hapus Sertifikat">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

// ---------------- SMART CERTIFICATE ANALYZER LOGIC ----------------
let currentAnalyzedFile = null;
let currentPreviewBlob = null;
let currentDocZoom = 1.0;

// Curated Icon Collection for Certificate & Tech Badges
const AVAILABLE_ICONS = [
    { class: 'fas fa-award', label: 'Award & Prestasi' },
    { class: 'fas fa-trophy', label: 'Juara & Trophy' },
    { class: 'fas fa-medal', label: 'Medali Sains' },
    { class: 'fas fa-certificate', label: 'Sertifikat Resmi' },
    { class: 'fas fa-graduation-cap', label: 'Akademik & Kuliah' },
    { class: 'fas fa-shield-halved', label: 'Keamanan Siber' },
    { class: 'fas fa-network-wired', label: 'Jaringan & Cisco' },
    { class: 'fab fa-linux', label: 'Linux & Red Hat' },
    { class: 'fas fa-server', label: 'Server & Cloud' },
    { class: 'fas fa-microchip', label: 'Hardware & IoT' },
    { class: 'fas fa-laptop-code', label: 'Software Eng.' },
    { class: 'fas fa-code', label: 'Pemrograman / Web' },
    { class: 'fab fa-python', label: 'Python Developer' },
    { class: 'fas fa-database', label: 'Database & SQL' },
    { class: 'fas fa-newspaper', label: 'Media & Jurnalistik' },
    { class: 'fas fa-microphone-lines', label: 'Public Speaking' },
    { class: 'fas fa-users', label: 'Organisasi & Leader' },
    { class: 'fas fa-robot', label: 'AI & Robotika' },
    { class: 'fas fa-pen-nib', label: 'Desain Grafis & UI' },
    { class: 'fas fa-lightbulb', label: 'Inovasi & Riset' },
    { class: 'fas fa-book-bookmark', label: 'Kursus & Workshop' },
    { class: 'fas fa-star', label: 'Apresiasi & Bintang' }
];

function renderIconPicker() {
    const container = document.getElementById('cert-icon-options');
    if (!container) return;

    container.innerHTML = AVAILABLE_ICONS.map(item => `
        <button type="button" class="icon-chip-btn" data-class="${item.class}" onclick="selectCertIcon('${item.class}', '${item.label}')">
            <i class="${item.class}"></i>
            <span>${item.label}</span>
        </button>
    `).join('');
}

function selectCertIcon(iconClass, customLabel = null) {
    if (!iconClass) iconClass = 'fas fa-award';

    const hiddenInput = document.getElementById('cert-icon');
    if (hiddenInput) hiddenInput.value = iconClass;

    const previewEl = document.getElementById('cert-icon-preview');
    if (previewEl) previewEl.innerHTML = `<i class="${iconClass}"></i>`;

    const found = AVAILABLE_ICONS.find(i => i.class === iconClass);
    const finalLabel = customLabel || (found ? found.label : 'Ikon FontAwesome');

    const labelEl = document.getElementById('cert-icon-label');
    if (labelEl) labelEl.textContent = finalLabel;

    const codeEl = document.getElementById('cert-icon-code');
    if (codeEl) codeEl.textContent = iconClass;

    // Highlight matching chip
    const allChips = document.querySelectorAll('.icon-chip-btn');
    allChips.forEach(chip => {
        if (chip.getAttribute('data-class') === iconClass) {
            chip.classList.add('active');
        } else {
            chip.classList.remove('active');
        }
    });
}

function toggleCustomIconInput() {
    const box = document.getElementById('custom-icon-box');
    if (!box) return;
    const isHidden = box.style.display === 'none';
    box.style.display = isHidden ? 'flex' : 'none';
    if (isHidden) {
        const input = document.getElementById('cert-custom-icon-input');
        if (input) {
            const current = document.getElementById('cert-icon').value;
            input.value = current;
            input.focus();
        }
    }
}

function applyCustomIcon() {
    const input = document.getElementById('cert-custom-icon-input');
    if (!input) return;
    const val = input.value.trim();
    if (!val) {
        showToast('Harap masukkan nama class ikon FontAwesome', 'error');
        return;
    }
    selectCertIcon(val, 'Ikon Kustom');
    showToast(`Ikon berhasil diterapkan: ${val}`);
}

// Initialize PDF.js worker
if (window.pdfjsLib) {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

function updateIconPreview(iconClass) {
    selectCertIcon(iconClass);
}

function setCertModalStep(step) {
    const stepUpload = document.getElementById('cert-step-upload');
    const stepReview = document.getElementById('cert-step-review');
    const btnBack = document.getElementById('btn-cert-back');
    const btnPublish = document.getElementById('btn-publish-cert');

    if (step === 1) {
        if (stepUpload) stepUpload.classList.add('active');
        if (stepReview) stepReview.classList.remove('active');
        if (btnBack) btnBack.style.display = 'none';
        if (btnPublish) btnPublish.style.display = 'none';
    } else {
        if (stepUpload) stepUpload.classList.remove('active');
        if (stepReview) stepReview.classList.add('active');
        if (btnBack) btnBack.style.display = 'inline-flex';
        if (btnPublish) btnPublish.style.display = 'inline-flex';
    }
}

function clearDocPreview() {
    const imgEl = document.getElementById('cert-rendered-preview-img');
    const canvasEl = document.getElementById('cert-pdf-canvas');
    if (imgEl) {
        imgEl.src = '';
        imgEl.style.display = 'none';
    }
    if (canvasEl) {
        const ctx = canvasEl.getContext('2d');
        if (ctx) ctx.clearRect(0, 0, canvasEl.width, canvasEl.height);
        canvasEl.width = 0;
        canvasEl.height = 0;
        canvasEl.style.display = 'none';
    }
    resetDocZoom();
}

function zoomDocPreview(delta) {
    currentDocZoom = Math.min(Math.max(0.4, currentDocZoom + delta), 2.5);
    const stage = document.getElementById('doc-stage');
    const zoomText = document.getElementById('doc-zoom-level-text');
    if (stage) stage.style.transform = `scale(${currentDocZoom})`;
    if (zoomText) zoomText.textContent = `${Math.round(currentDocZoom * 100)}%`;
}

function resetDocZoom() {
    currentDocZoom = 1.0;
    const stage = document.getElementById('doc-stage');
    const zoomText = document.getElementById('doc-zoom-level-text');
    if (stage) stage.style.transform = 'scale(1)';
    if (zoomText) zoomText.textContent = '100%';
}

function openAddCertModal() {
    const form = document.getElementById('cert-form');
    if (form) form.reset();
    document.getElementById('cert-modal-id').value = '';
    document.getElementById('cert-modal-filetype').value = '';
    document.getElementById('cert-modal-preview-url').value = '';
    document.getElementById('cert-modal-file-url').value = '';
    document.getElementById('cert-modal-title-text').textContent = 'Tambah Sertifikat Baru: Smart Analyzer';

    currentAnalyzedFile = null;
    currentPreviewBlob = null;
    clearDocPreview();

    // Render & select default icon
    renderIconPicker();
    selectCertIcon('fas fa-award');

    // Hide custom input box if open
    const customBox = document.getElementById('custom-icon-box');
    if (customBox) customBox.style.display = 'none';

    // Reset Dropzone view
    document.getElementById('dropzone-idle-state').style.display = 'block';
    document.getElementById('dropzone-scanning-state').style.display = 'none';

    // Reset file input
    const fileInput = document.getElementById('smart-cert-file-input');
    if (fileInput) fileInput.value = '';

    // Step 1 active
    setCertModalStep(1);

    openModal('cert-modal');
}

function backToUploadStep() {
    clearDocPreview();
    document.getElementById('dropzone-idle-state').style.display = 'block';
    document.getElementById('dropzone-scanning-state').style.display = 'none';
    const fileInput = document.getElementById('smart-cert-file-input');
    if (fileInput) fileInput.value = '';
    currentAnalyzedFile = null;
    currentPreviewBlob = null;
    setCertModalStep(1);
}

function switchToManualEntry() {
    clearDocPreview();
    document.getElementById('cert-view-filetype').textContent = 'Manual';
    document.getElementById('cert-view-filename').textContent = 'Input Manual';
    renderIconPicker();
    setCertModalStep(2);
}

function editCertificate(id) {
    const cert = certificatesData.find(c => c.id == id);
    if (!cert) return;

    document.getElementById('cert-modal-title-text').textContent = 'Edit Sertifikat';
    document.getElementById('cert-modal-id').value = cert.id;
    document.getElementById('cert-modal-filetype').value = cert.filetype || '';
    document.getElementById('cert-modal-preview-url').value = cert.preview || '';
    document.getElementById('cert-modal-file-url').value = cert.file || '';

    document.getElementById('cert-title').value = cert.title || '';
    document.getElementById('cert-short-name').value = cert.short_name || '';
    document.getElementById('cert-issuer').value = cert.issuer || '';
    document.getElementById('cert-category').value = cert.category || '';
    document.getElementById('cert-badge-label').value = cert.badge_label || '';
    document.getElementById('cert-date').value = cert.date || '';
    document.getElementById('cert-credential').value = cert.credential || '';
    document.getElementById('cert-tag').value = cert.tag || '';

    // Render & select icon
    renderIconPicker();
    selectCertIcon(cert.icon || 'fas fa-award');

    // Hide custom input box if open
    const customBox = document.getElementById('custom-icon-box');
    if (customBox) customBox.style.display = 'none';

    document.getElementById('cert-verify-url').value = cert.verify_url || '';
    document.getElementById('cert-quote').value = cert.quote || '';
    document.getElementById('cert-description').value = cert.description || '';

    // Clear old visual preview first
    clearDocPreview();

    // Set preview image or doc
    const imgEl = document.getElementById('cert-rendered-preview-img');
    const canvasEl = document.getElementById('cert-pdf-canvas');

    const previewSrc = cert.preview || cert.file;
    if (previewSrc) {
        imgEl.src = `../${previewSrc}`;
        imgEl.style.display = 'block';
        canvasEl.style.display = 'none';
    } else {
        imgEl.style.display = 'none';
        canvasEl.style.display = 'none';
    }

    document.getElementById('cert-view-filetype').textContent = cert.filetype || 'Dokumen';
    document.getElementById('cert-view-filename').textContent = cert.file || 'sertifikat';

    currentAnalyzedFile = null;
    currentPreviewBlob = null;

    // Show Step 2 directly
    setCertModalStep(2);

    openModal('cert-modal');
}

// Smart Heuristic Analyzer
function analyzeCertificateData(text, filename) {
    const fullText = (text + ' ' + filename).replace(/\s+/g, ' ');
    const lower = fullText.toLowerCase();

    let data = {
        title: '',
        short_name: '',
        issuer: '',
        category: 'Sertifikasi',
        badge_label: '',
        date: '',
        credential: '',
        tag: '',
        icon: 'fas fa-award',
        verify_url: '',
        quote: '',
        description: ''
    };

    // 1. CISCO / CCNA
    if (lower.includes('cisco') || lower.includes('ccna') || lower.includes('networking academy')) {
        data.title = 'CCNA: Introduction to Networks';
        data.short_name = 'Cisco CCNA: Intro to Networks';
        data.issuer = 'Cisco Networking Academy';
        data.category = 'Network Engineering';
        data.badge_label = '2026';
        data.icon = 'fas fa-network-wired';
        data.tag = 'Verified Cisco NetAcad Credential';
        data.quote = 'Sertifikasi konfigurasi switch & router Cisco IOS, protokol OSI/Ethernet, subnetting IPv4/IPv6, dan keamanan jaringan.';
        data.description = 'Sertifikasi resmi Cisco Networking Academy atas penguasaan arsitektur jaringan, konfigurasi switch & router Cisco IOS, model OSI/TCP-IP, pengalamatan IPv4/IPv6, serta keamanan jaringan dasar.';
    }
    // 2. GOOGLE CYBERSECURITY / COURSERA
    else if (lower.includes('google') && (lower.includes('cybersecurity') || lower.includes('coursera') || lower.includes('security'))) {
        data.title = 'Google Cybersecurity Professional Certificate';
        data.short_name = 'Google Cybersecurity';
        data.issuer = 'Google & Coursera Professional';
        data.category = 'Cybersecurity';
        data.badge_label = '9 Courses';
        data.icon = 'fas fa-shield-halved';
        data.verify_url = 'https://coursera.org/verify/professional-cert/T4PQFVWB7WAM';
        data.quote = 'Penyelesaian 9 kursus keamanan siber Google: Python, Linux, SQL, SIEM tools, Intrusion Detection (IDS), & mitigasi ancaman.';
        data.description = 'Sertifikat Profesional 9 kursus resmi dari Google melalui Coursera, mencakup keamanan jaringan, Linux, SQL, SIEM tools, Intrusion Detection (IDS), Python scripting untuk cybersecurity, serta manajemen risiko dan kepatuhan siber.';
    }
    // 3. RED HAT LINUX / RH104
    else if (lower.includes('red hat') || lower.includes('rh104') || lower.includes('rhel') || lower.includes('enterprise linux')) {
        data.title = 'Red Hat System Administration I (RH104)';
        data.short_name = 'Red Hat Linux (RH104)';
        data.issuer = 'Red Hat Training & Academy';
        data.category = 'Linux System Admin';
        data.badge_label = 'RHEL 9.1';
        data.icon = 'fab fa-linux';
        data.tag = 'RH104-RHA Ver. 9.1 • 16 Hours';
        data.quote = 'Sertifikasi Red Hat Enterprise Linux 9.1 mencakup penguasaan terminal shell, arsitektur sistem file, dan administrasi server dasar.';
        data.description = 'Pelatihan resmi Red Hat Academy mencakup penguasaan baris perintah (CLI), manajemen pengguna & grup, kontrol izin file, konfigurasi layanan systemd, serta manajemen penyimpanan Linux.';
    }
    // 4. PRESMANESIA / KS2N / OLIMPIADE MATEMATIKA
    else if (lower.includes('presmanesia') || lower.includes('ks2n') || (lower.includes('olimpiade') && lower.includes('matematika')) || lower.includes('medali perak')) {
        data.title = 'Medali Perak Bidang Matematika – KS2N 2024';
        data.short_name = 'Medali Perak Matematika';
        data.issuer = 'KS2N Nasional – PRESMANESIA';
        data.category = 'Olimpiade Nasional';
        data.badge_label = 'Predikat A';
        data.icon = 'fas fa-award';
        data.credential = '# 1492424/PRESMANESIA/KS2N-P/IX/2024';
        data.tag = '# 1492424/PRESMANESIA/KS2N-P/IX/2024';
        data.quote = 'Peraih Medali Perak (Predikat A) bidang Matematika tingkat nasional pada Kompetisi Sains Siswa Nasional (KS2N) 2024.';
        data.description = 'Piagam Penghargaan resmi atas pencapaian Medali Perak (Predikat A) Tingkat Mahasiswa pada Kompetisi Sains Siswa Nasional (KS2N) yang diselenggarakan oleh CV. Presmanesia.';
    }
    // 5. DEVFEST / HACKATHON
    else if (lower.includes('devfest') || lower.includes('hackathon')) {
        data.title = lower.includes('pens') ? 'Hackathon Rekayasa IoT & Sistem Tertanam PENS' : 'DevFest Hackathon 2026';
        data.short_name = lower.includes('pens') ? 'Hackathon PENS 2026' : 'DevFest Hackathon';
        data.issuer = lower.includes('pens') ? 'Politeknik Elektronika Negeri Surabaya (PENS)' : 'Google Developer Groups (GDG)';
        data.category = 'IoT & Hardware Prototype';
        data.badge_label = 'Finalis';
        data.icon = 'fas fa-microchip';
        data.quote = 'Peserta kompetisi hackathon inovasi rekayasa teknologi dan purwarupa perangkat keras terapan.';
        data.description = 'Sertifikat apresiasi keikutsertaan kompetisi hackathon pengembangan solusi perangkat keras cerdas dan rekayasa embedded modern.';
    }
    // 6. JURNALISTIK / KORAN KALTIM
    else if (lower.includes('jurnalistik') || lower.includes('koran kaltim') || lower.includes('pers')) {
        data.title = 'Pelatihan Jurnalistik Mahasiswa – HUT ke-20 Koran Kaltim';
        data.short_name = 'Jurnalistik Koran Kaltim';
        data.issuer = 'SKH Koran Kaltim';
        data.category = 'Media & Journalism';
        data.badge_label = 'Pelatihan Redaksi';
        data.icon = 'fas fa-newspaper';
        data.tag = 'HUT ke-20 Harian Koran Kaltim';
        data.quote = 'Pelatihan jurnalistik mahasiswa oleh redaksi Surat Kabar Harian Koran Kaltim meliputi teknik peliputan & penulisan berita.';
        data.description = 'Sertifikat resmi keikutsertaan Pelatihan Jurnalistik Mahasiswa dalam rangka HUT ke-20 Surat Kabar Harian (SKH) Koran Kaltim, mendalami kode etik pers, reportase investigatif lapangan, dan teknik penulisan feature media cetak & online.';
    }
    // 7. PUBLIC SPEAKING / BICARA INDONESIA
    else if (lower.includes('public speaking') || lower.includes('bicara') || lower.includes('zoominar')) {
        data.title = 'Sertifikat Kompetensi Public Speaking – Mega Zoominar';
        data.short_name = 'Public Speaking Mastery';
        data.issuer = 'PT. Bicara Official Indonesia';
        data.category = 'Communication & Leadership';
        data.badge_label = '8 JPL';
        data.icon = 'fas fa-microphone-lines';
        data.tag = '006/MZ/DPP/BOI/X/2024 • Berkompeten';
        data.quote = 'Predikat Berkompeten (Nilai Baik pada materi Prepare, Praktis, & Performe) dalam pelatihan intensif komunikasi publik.';
        data.description = 'Sertifikat kelulusan kompetensi dengan predikat BERKOMPETEN (Nilai Baik pada seluruh indikator Prepare, Praktis, dan Performe – 8 Jam Pelajaran) oleh Lembaga Pelatihan PT. Bicara Official Indonesia.';
    }
    // GENERAL PARSER
    else {
        let cleanName = filename.replace(/\.[^/.]+$/, '').replace(/[-_]/g, ' ');
        data.title = cleanName.charAt(0).toUpperCase() + cleanName.slice(1);
        data.short_name = data.title;
        data.category = 'Keahlian & Kompetensi';
        data.issuer = 'Lembaga Penerbit Sertifikasi';
        data.quote = `Sertifikat bukti keahlian dan kompetensi resmi di bidang ${data.title}.`;
        data.description = `Sertifikat resmi atas pencapaian dan penguasaan materi pelatihan ${data.title}.`;
    }

    // Heuristic Date extraction
    const dateMatch = fullText.match(/\b(\d{1,2}(?:\s*[-–&]\s*\d{1,2})?\s+(?:Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember|January|February|March|April|May|June|July|August|September|October|November|December)\s+\d{4})\b/i);
    if (dateMatch && !data.date) {
        data.date = dateMatch[1];
    } else if (!data.date) {
        const yearMatch = fullText.match(/\b(202[0-9])\b/);
        data.date = yearMatch ? yearMatch[1] : '2026';
    }

    // Heuristic Credential ID extraction
    const credMatch = fullText.match(/(?:ID|No|Nomor|Certificate No|Credential ID)[:\s]*([A-Za-z0-9\/\-_]{5,35})/i);
    if (credMatch && !data.credential) {
        data.credential = credMatch[1];
        if (!data.tag) data.tag = `No: ${credMatch[1]}`;
    }

    return data;
}

// Core Pipeline for Analyzing Certificate
async function processUploadedCertificate(file) {
    if (!file) return;

    const ext = file.name.split('.').pop().toLowerCase();
    const isPdf = ext === 'pdf';
    const isImg = ['jpg', 'jpeg', 'png', 'webp'].includes(ext);

    if (!isPdf && !isImg) {
        showToast('Format file tidak didukung. Harap pilih PDF atau Gambar (JPG/PNG/WebP).', 'error');
        return;
    }

    currentAnalyzedFile = file;

    // Switch Dropzone to scanning state
    document.getElementById('dropzone-idle-state').style.display = 'none';
    document.getElementById('dropzone-scanning-state').style.display = 'block';

    const statusText = document.getElementById('scan-status-text');
    const dot1 = document.getElementById('dot-step-1');
    const dot2 = document.getElementById('dot-step-2');
    const dot3 = document.getElementById('dot-step-3');
    const dot4 = document.getElementById('dot-step-4');

    const updateStep = (text, activeIndex) => {
        if (statusText) statusText.textContent = text;
        [dot1, dot2, dot3, dot4].forEach((dot, idx) => {
            if (!dot) return;
            if (idx + 1 === activeIndex) {
                dot.className = 'step-dot active';
                dot.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> ${dot.textContent.trim()}`;
            } else if (idx + 1 < activeIndex) {
                dot.className = 'step-dot active';
                dot.innerHTML = `<i class="fas fa-circle-check"></i> ${dot.textContent.trim()}`;
            } else {
                dot.className = 'step-dot';
                dot.innerHTML = `<i class="fas fa-circle"></i> ${dot.textContent.trim()}`;
            }
        });
    };

    try {
        updateStep('Membaca file sertifikat...', 1);
        await new Promise(r => setTimeout(r, 250));

        let extractedText = '';
        let filetypeLabel = isPdf ? 'PDF Resmi' : 'Gambar';

        const previewImg = document.getElementById('cert-rendered-preview-img');
        const pdfCanvas = document.getElementById('cert-pdf-canvas');

        clearDocPreview();

        if (isPdf) {
            updateStep('Mengekstrak teks & isi dokumen dari PDF...', 2);
            const arrayBuffer = await file.arrayBuffer();

            if (!window.pdfjsLib) {
                throw new Error('Library PDF.js belum selesai dimuat. Harap periksa koneksi internet.');
            }

            const loadingTask = pdfjsLib.getDocument({ data: arrayBuffer });
            const pdfDoc = await loadingTask.promise;
            const page = await pdfDoc.getPage(1);

            // Extract text tokens
            const textContent = await page.getTextContent();
            extractedText = textContent.items.map(item => item.str).join(' ');

            updateStep('Merender pratinjau resolusi tinggi...', 4);
            const viewport = page.getViewport({ scale: 1.5 });
            pdfCanvas.width = viewport.width;
            pdfCanvas.height = viewport.height;
            const ctx = pdfCanvas.getContext('2d');

            await page.render({ canvasContext: ctx, viewport: viewport }).promise;

            pdfCanvas.style.display = 'block';
            previewImg.style.display = 'none';

            // Generate JPEG Blob for preview image upload
            await new Promise((resolve) => {
                pdfCanvas.toBlob((blob) => {
                    currentPreviewBlob = blob;
                    resolve();
                }, 'image/jpeg', 0.92);
            });

        } else {
            // Gambar (JPG/PNG/WebP)
            updateStep('Menyiapkan pratinjau gambar...', 2);
            const dataUrl = await new Promise((res, rej) => {
                const reader = new FileReader();
                reader.onload = e => res(e.target.result);
                reader.onerror = rej;
                reader.readAsDataURL(file);
            });

            previewImg.src = dataUrl;
            previewImg.style.display = 'block';
            pdfCanvas.style.display = 'none';

            currentPreviewBlob = file;
            extractedText = file.name;
        }

        updateStep('Menganalisis penerbit, tanggal, dan nomor kredensial...', 3);
        await new Promise(r => setTimeout(r, 300));

        // Intelligent parsing
        const analyzed = analyzeCertificateData(extractedText, file.name);

        // Fill form
        document.getElementById('cert-title').value = analyzed.title;
        document.getElementById('cert-short-name').value = analyzed.short_name;
        document.getElementById('cert-issuer').value = analyzed.issuer;
        document.getElementById('cert-category').value = analyzed.category;
        document.getElementById('cert-badge-label').value = analyzed.badge_label;
        document.getElementById('cert-date').value = analyzed.date;
        document.getElementById('cert-credential').value = analyzed.credential;
        document.getElementById('cert-tag').value = analyzed.tag;

        // Render & select icon
        renderIconPicker();
        selectCertIcon(analyzed.icon);

        document.getElementById('cert-verify-url').value = analyzed.verify_url;
        document.getElementById('cert-quote').value = analyzed.quote;
        document.getElementById('cert-description').value = analyzed.description;

        document.getElementById('cert-modal-filetype').value = filetypeLabel;
        document.getElementById('cert-view-filetype').textContent = isPdf ? 'PDF Asli' : 'Gambar';
        document.getElementById('cert-view-filename').textContent = file.name;

        // Transition to Step 2
        setCertModalStep(2);
        resetDocZoom();

        showToast('Analisis selesai! Periksa dan edit data di formulir sebelum dipublish.');

    } catch (err) {
        console.error(err);
        showToast('Gagal menganalisis dokumen: ' + err.message, 'error');
        document.getElementById('dropzone-idle-state').style.display = 'block';
        document.getElementById('dropzone-scanning-state').style.display = 'none';
    }
}

// Publish Certificate to Web
async function handleCertFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = document.getElementById('btn-publish-cert');
    const origText = submitBtn ? submitBtn.innerHTML : 'Publish';

    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mempublikasikan...';
        submitBtn.disabled = true;
    }

    try {
        const formData = new FormData(form);

        // If analyzed file exists, attach it as doc_file
        if (currentAnalyzedFile) {
            formData.append('doc_file', currentAnalyzedFile, currentAnalyzedFile.name);
        }

        // If preview blob exists, attach it as preview_file
        if (currentPreviewBlob) {
            const previewName = currentAnalyzedFile 
                ? 'preview_' + currentAnalyzedFile.name.replace(/\.[^/.]+$/, '') + '.jpg'
                : 'preview_cert.jpg';
            formData.append('preview_file', currentPreviewBlob, previewName);
        }

        const res = await fetch('api.php?action=save_cert', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Sertifikat berhasil dipublish ke web!');
            closeModal('cert-modal');
            loadCertificates();
            currentAnalyzedFile = null;
            currentPreviewBlob = null;
        } else {
            showToast(json.message || 'Gagal mempublish sertifikat.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
        }
    }
}

async function deleteCertificate(id, title) {
    const decodedTitle = decodeURIComponent(title);
    if (!confirm(`Yakin ingin menghapus sertifikat "${decodedTitle}"?\nTindakan ini juga akan otomatis memperbarui tampilan web utama.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const res = await fetch('api.php?action=delete_cert', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Sertifikat berhasil dihapus.');
            loadCertificates();
        } else {
            showToast(json.message || 'Gagal menghapus.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

// ---------------- PROJECTS LOGIC ----------------
let currentProjectExistingGallery = [];
let currentProjectNewGalleryFiles = [];

function renderProjectGalleryThumbs() {
    const container = document.getElementById('project-gallery-thumbs');
    const hiddenInput = document.getElementById('project-existing-gallery');
    if (hiddenInput) {
        hiddenInput.value = JSON.stringify(currentProjectExistingGallery);
    }
    updateClearGalleryButtonState();
    if (!container) return;

    const totalCount = currentProjectExistingGallery.length + currentProjectNewGalleryFiles.length;
    if (totalCount === 0) {
        container.innerHTML = '<span style="font-size: 12px; color: var(--text-muted); font-style: italic;">Belum ada foto prototipe tambahan di galeri.</span>';
        return;
    }

    const titleInput = document.getElementById('project-title');
    const projectTitle = titleInput ? titleInput.value.trim() : 'Proyek';

    let html = '';

    // 1. Existing Saved Gallery Photos
    currentProjectExistingGallery.forEach((imgPath, idx) => {
        const fullSrc = imgPath.startsWith('http') || imgPath.startsWith('data:') ? imgPath : `../${imgPath}`;
        const fileName = imgPath.split('/').pop() || `Foto ${idx + 1}`;
        html += `
            <div class="gallery-thumb-item" title="Foto ${idx + 1}: ${escapeHtml(fileName)} (Klik untuk pratinjau)">
                <img src="${fullSrc}" alt="Prototipe ${idx + 1}" onclick="previewImageModal('${fullSrc}', 'Foto Prototipe ${idx + 1} (${escapeHtml(projectTitle)})', '${escapeHtml(fileName)}')" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'64\\' height=\\'64\\'%3E%3Crect width=\\'100%25\\' height=\\'100%25\\' fill=\\'%23222\\'/></svg>'">
                <div class="gallery-thumb-overlay" onclick="previewImageModal('${fullSrc}', 'Foto Prototipe ${idx + 1} (${escapeHtml(projectTitle)})', '${escapeHtml(fileName)}')">
                    <i class="fas fa-magnifying-glass-plus"></i>
                </div>
                <button type="button" class="gallery-thumb-remove" onclick="removeExistingGalleryItem(event, ${idx})" title="Hapus foto ini dari galeri">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
        `;
    });

    // 2. Newly Picked Gallery Files
    currentProjectNewGalleryFiles.forEach((f, i) => {
        const url = URL.createObjectURL(f);
        html += `
            <div class="gallery-thumb-item new-gallery-file-thumb" title="File Baru: ${escapeHtml(f.name)} (Klik untuk pratinjau)">
                <img src="${url}" alt="File Baru ${i + 1}" onclick="previewImageModal('${url}', 'File Baru (${i + 1}/${currentProjectNewGalleryFiles.length}): ${escapeHtml(projectTitle)}', '${escapeHtml(f.name)}')">
                <div class="gallery-thumb-overlay" onclick="previewImageModal('${url}', 'File Baru (${i + 1}/${currentProjectNewGalleryFiles.length}): ${escapeHtml(projectTitle)}', '${escapeHtml(f.name)}')">
                    <i class="fas fa-magnifying-glass-plus"></i>
                </div>
                <button type="button" class="gallery-thumb-remove" onclick="removeNewGalleryItem(event, ${i})" title="Batal upload file ini">
                    <i class="fas fa-xmark"></i>
                </button>
                <span class="gallery-new-pill">Baru</span>
            </div>
        `;
    });

    container.innerHTML = html;
}

function removeExistingGalleryItem(event, index) {
    if (event) event.stopPropagation();
    if (index >= 0 && index < currentProjectExistingGallery.length) {
        currentProjectExistingGallery.splice(index, 1);
        renderProjectGalleryThumbs();
        showToast('Foto galeri dihapus.', 'info');
    }
}

function removeNewGalleryItem(event, index) {
    if (event) event.stopPropagation();
    if (index >= 0 && index < currentProjectNewGalleryFiles.length) {
        currentProjectNewGalleryFiles.splice(index, 1);
        syncGalleryFileInput();
        renderProjectGalleryThumbs();
        showToast('File baru dibatalkan dari antrean upload.', 'info');
    }
}

function clearAllGalleryImages() {
    if (currentProjectExistingGallery.length === 0 && currentProjectNewGalleryFiles.length === 0) return;
    if (!confirm('Apakah Anda yakin ingin menghapus semua foto di galeri proyek ini?')) return;
    currentProjectExistingGallery = [];
    currentProjectNewGalleryFiles = [];
    const galleryInput = document.getElementById('proj_gallery_files');
    if (galleryInput) galleryInput.value = '';
    renderProjectGalleryThumbs();
    syncGalleryFileInput();
    showToast('Semua foto galeri telah dihapus.', 'info');
}

function updateClearGalleryButtonState() {
    const btn = document.getElementById('btn-clear-all-gallery');
    if (!btn) return;
    const count = (currentProjectExistingGallery ? currentProjectExistingGallery.length : 0) + 
                  (currentProjectNewGalleryFiles ? currentProjectNewGalleryFiles.length : 0);
    btn.style.display = count > 0 ? 'inline-flex' : 'none';
}

function syncGalleryFileInput() {
    try {
        const dt = new DataTransfer();
        currentProjectNewGalleryFiles.forEach(f => dt.items.add(f));
        const galleryInput = document.getElementById('proj_gallery_files');
        if (galleryInput) galleryInput.files = dt.files;
    } catch (e) {}
    updateClearGalleryButtonState();
}

function removeProjectCover() {
    const fileInput = document.getElementById('proj_image_file');
    const wrap = document.getElementById('project-cover-wrap');
    const thumb = document.getElementById('project-cover-thumb');
    const nameEl = document.getElementById('project-cover-filename');
    const removeInput = document.getElementById('project-remove-cover');

    if (fileInput) fileInput.value = '';
    if (wrap) wrap.style.display = 'none';
    if (thumb) thumb.src = '';
    if (nameEl) nameEl.textContent = '-';
    if (removeInput) removeInput.value = '1';

    showToast('Foto cover telah dihapus (akan disimpan saat formulir disubmit).', 'info');
}

function updateProjectModalState() {
    const isDownloadEl = document.getElementById('project-is-download');
    const autoPreviewEl = document.getElementById('project-auto-preview');
    const manualLinkGroup = document.getElementById('manual-link-group');
    const statusPill = document.getElementById('project-status-pill');

    const isDownload = isDownloadEl ? isDownloadEl.checked : false;
    const autoPreview = autoPreviewEl ? autoPreviewEl.checked : true;

    if (isDownload) {
        if (manualLinkGroup) manualLinkGroup.style.display = 'block';
        if (statusPill) {
            statusPill.className = 'badge-pill green';
            statusPill.innerHTML = '<i class="fas fa-download"></i> Mode Unduh Dokumen Aktif';
        }
    } else if (autoPreview) {
        if (manualLinkGroup) manualLinkGroup.style.display = 'none';
        if (statusPill) {
            statusPill.className = 'badge-pill blue';
            statusPill.innerHTML = '<i class="fas fa-bolt"></i> Auto-Generate Preview Aktif';
        }
    } else {
        if (manualLinkGroup) manualLinkGroup.style.display = 'block';
        if (statusPill) {
            statusPill.className = 'badge-pill gray';
            statusPill.innerHTML = '<i class="fas fa-link"></i> Mode Tautan Manual';
        }
    }
}

async function loadProjects() {
    try {
        const res = await fetch(`api.php?action=list_projects&_t=${Date.now()}`, { cache: 'no-store' });
        const json = await res.json();
        if (json.success) {
            projectsData = json.data;
            renderProjectsTable(projectsData);
            updateStatCounters();
        }
    } catch (err) {
        showToast('Gagal memuat proyek: ' + err.message, 'error');
    }
}

function renderProjectsTable(projects) {
    const tbody = document.getElementById('projects-table-body');
    if (!tbody) return;

    if (!projects || projects.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5">
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h4>Belum ada proyek</h4>
                        <p>Klik tombol "+ Tambah Proyek Baru" untuk memasukkan proyek pilihan kamu.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = projects.map((p, idx) => {
        const coverImg = p.image ? `../${p.image}` : 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="60" height="44"%3E%3Crect width="100%25" height="100%25" fill="%23222"/%3E%3C/svg%3E';
        
        let previewLinkBadge = '';
        if (p.link && (p.link === 'rumahsiap.html' || p.link.startsWith('project-'))) {
            previewLinkBadge = `<a href="../${p.link}" target="_blank" class="badge-pill blue" style="text-decoration: none; margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;" title="Buka Halaman Preview"><i class="fas fa-arrow-up-right-from-square"></i> Buka Preview (${p.link})</a>`;
        } else if (p.is_download) {
            previewLinkBadge = `<span class="badge-pill green" style="margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-download"></i> Direct Download (${p.link})</span>`;
        } else {
            previewLinkBadge = `<span class="badge-pill gray" style="margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;"><i class="fas fa-link"></i> ${p.link}</span>`;
        }

        return `
            <tr>
                <td style="width: 70px;">
                    <img src="${coverImg}" alt="${p.title}" class="cell-thumb" style="cursor: pointer;" onclick="previewImageModal('${coverImg}', 'Cover: ${escapeHtml(p.title)}', '${escapeHtml(p.image || '')}')" title="Klik untuk pratinjau gambar penuh" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'60\\' height=\\'44\\'%3E%3Crect width=\\'100%25\\' height=\\'100%25\\' fill=\\'%23333\\'/></svg>'">
                </td>
                <td>
                    <div class="cell-title-wrap">
                        <span class="cell-title-main">${p.title}</span>
                        <span class="cell-title-sub">${p.subtitle || '-'}</span>
                    </div>
                </td>
                <td>
                    <span style="font-size: 13px; color: var(--text-muted); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        ${p.description || '-'}
                    </span>
                </td>
                <td>
                    <div style="display: flex; flex-direction: column; align-items: flex-start;">
                        <span style="font-size: 13px; font-weight: 600; color: #ffffff;">Tombol: "${p.btn_text || 'lihat demo'}"</span>
                        ${previewLinkBadge}
                    </div>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <button type="button" class="btn-icon-action edit" onclick="editProject(${p.id})" title="Edit Proyek">
                        <i class="fas fa-pencil"></i>
                    </button>
                    <button type="button" class="btn-icon-action delete" onclick="deleteProject(${p.id}, '${encodeURIComponent(p.title)}')" title="Hapus Proyek">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function openAddProjectModal() {
    const form = document.getElementById('project-form');
    if (form) form.reset();
    document.getElementById('project-modal-id').value = '';
    document.getElementById('project-modal-title-text').textContent = 'Tambah Proyek Baru';
    document.getElementById('project-cover-wrap').style.display = 'none';
    const thumb = document.getElementById('project-cover-thumb');
    if (thumb) {
        thumb.src = '';
        thumb.style.display = 'none';
    }
    document.getElementById('project-btn-text').value = 'lihat detail proyek';
    
    const autoPrev = document.getElementById('project-auto-preview');
    if (autoPrev) autoPrev.checked = true;
    
    const isDown = document.getElementById('project-is-download');
    if (isDown) isDown.checked = false;

    currentProjectExistingGallery = [];
    currentProjectNewGalleryFiles = [];
    const galleryInput = document.getElementById('proj_gallery_files');
    if (galleryInput) galleryInput.value = '';
    const removeCoverInput = document.getElementById('project-remove-cover');
    if (removeCoverInput) removeCoverInput.value = '0';
    syncGalleryFileInput();
    renderProjectGalleryThumbs();
    updateProjectModalState();
    openModal('project-modal');
}

function editProject(id) {
    const p = projectsData.find(item => item.id == id);
    if (!p) return;

    document.getElementById('project-modal-title-text').textContent = 'Edit Proyek: ' + (p.title || '');
    document.getElementById('project-modal-id').value = p.id;
    document.getElementById('project-title').value = p.title || '';
    document.getElementById('project-subtitle').value = p.subtitle || '';
    document.getElementById('project-badge').value = p.badge || '';
    document.getElementById('project-tech-stack').value = Array.isArray(p.tech_stack) ? p.tech_stack.join(', ') : (p.tech_stack || '');
    document.getElementById('project-description').value = p.description || '';
    document.getElementById('project-full-description').value = p.full_description || p.description || '';
    document.getElementById('project-video-url').value = p.video_url || '';
    document.getElementById('project-demo-url').value = p.demo_url || '';
    document.getElementById('project-github-url').value = p.github_url || '';
    document.getElementById('project-btn-text').value = p.btn_text || 'lihat detail proyek';
    document.getElementById('project-link').value = p.link || '';
    
    const autoPrev = document.getElementById('project-auto-preview');
    if (autoPrev) autoPrev.checked = (p.auto_preview !== false);
    
    const isDown = document.getElementById('project-is-download');
    if (isDown) isDown.checked = !!p.is_download;

    // Gallery Thumbs
    currentProjectExistingGallery = Array.isArray(p.gallery_images) ? [...p.gallery_images] : [];
    currentProjectNewGalleryFiles = [];
    const galleryInput = document.getElementById('proj_gallery_files');
    if (galleryInput) galleryInput.value = '';
    const removeCoverInput = document.getElementById('project-remove-cover');
    if (removeCoverInput) removeCoverInput.value = '0';
    syncGalleryFileInput();
    renderProjectGalleryThumbs();

    const wrap = document.getElementById('project-cover-wrap');
    const thumb = document.getElementById('project-cover-thumb');
    const nameEl = document.getElementById('project-cover-filename');
    if (p.image) {
        wrap.style.display = 'flex';
        thumb.src = `../${p.image}`;
        thumb.style.display = 'block';
        const cleanName = p.image.split('/').pop() || p.image;
        nameEl.textContent = cleanName;
        nameEl.title = p.image;
    } else {
        wrap.style.display = 'none';
        if (thumb) thumb.src = '';
    }

    updateProjectModalState();
    openModal('project-modal');
}

async function handleProjectFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = document.getElementById('btn-submit-project') || form.querySelector('button[type="submit"]');
    const origText = submitBtn ? submitBtn.innerHTML : 'Simpan';

    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses & Merakit Preview...';
        submitBtn.disabled = true;
    }

    try {
        const formData = new FormData(form);
        // Pastikan array foto galeri yang tersimpan & flag hapus cover selalu tersinkron
        formData.set('existing_gallery', JSON.stringify(currentProjectExistingGallery || []));
        const removeCoverVal = document.getElementById('project-remove-cover')?.value || '0';
        formData.set('remove_cover', removeCoverVal);

        const res = await fetch('api.php?action=save_project', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Proyek & halaman preview berhasil disimpan!');
            closeModal('project-modal');
            loadProjects();
        } else {
            showToast(json.message || 'Gagal menyimpan proyek.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    } finally {
        if (submitBtn) {
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
        }
    }
}

async function deleteProject(id, title) {
    const decodedTitle = decodeURIComponent(title);
    if (!confirm(`Yakin ingin menghapus proyek "${decodedTitle}"?\nHalaman preview dan kartu proyek akan otomatis dihapus dari website.`)) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);

        const res = await fetch('api.php?action=delete_project', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Proyek berhasil dihapus.');
            loadProjects();
        } else {
            showToast(json.message || 'Gagal menghapus proyek.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

// ---------------- MANUAL SYNC ----------------
async function handleManualSync() {
    const btn = document.getElementById('btn-manual-sync');
    if (!btn) return;

    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-arrows-rotate fa-spin"></i> Sinkronisasi...';
    btn.disabled = true;

    try {
        const res = await fetch('api.php?action=sync_all', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            showToast('Website berhasil disinkronkan sepenuhnya!');
        } else {
            showToast(json.message || 'Gagal sinkronisasi.', 'error');
        }
    } catch (err) {
        showToast('Kesalahan jaringan: ' + err.message, 'error');
    } finally {
        btn.innerHTML = orig;
        btn.disabled = false;
    }
}

// ---------------- ACCOUNT SETTINGS ----------------
function togglePasswordVisibility(inputId, btnEl) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';
    const icon = btnEl.querySelector('i');
    if (icon) {
        icon.className = isPass ? 'far fa-eye-slash' : 'far fa-eye';
    }
}

async function handleAccountFormSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const origText = submitBtn.innerHTML;

    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memperbarui...';
    submitBtn.disabled = true;

    try {
        const formData = new FormData(form);
        const res = await fetch('api.php?action=change_password', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(json.message || 'Pengaturan akun berhasil disimpan!');
            // Kosongkan kolom password saja, pertahankan username terbaru
            const oldPassInput = document.getElementById('acc-old-password');
            const newPassInput = document.getElementById('acc-new-password');
            if (oldPassInput) oldPassInput.value = '';
            if (newPassInput) newPassInput.value = '';
            if (json.username) {
                const uInput = document.getElementById('acc-username');
                if (uInput) uInput.value = json.username;
            }
        } else {
            showToast(json.message || 'Gagal memperbarui akun.', 'error');
        }
    } catch (err) {
        showToast('Kesalahan: ' + err.message, 'error');
    } finally {
        submitBtn.innerHTML = origText;
        submitBtn.disabled = false;
    }
}

// ---------------- SEARCH FILTER ----------------
function initSearch() {
    const searchCert = document.getElementById('search-certs');
    if (searchCert) {
        searchCert.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const filtered = certificatesData.filter(c => {
                return (c.title && c.title.toLowerCase().includes(query)) ||
                       (c.short_name && c.short_name.toLowerCase().includes(query)) ||
                       (c.issuer && c.issuer.toLowerCase().includes(query)) ||
                       (c.category && c.category.toLowerCase().includes(query));
            });
            renderCertificatesTable(filtered);
        });
    }

    const searchProj = document.getElementById('search-projects');
    if (searchProj) {
        searchProj.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            const filtered = projectsData.filter(p => {
                return (p.title && p.title.toLowerCase().includes(query)) ||
                       (p.subtitle && p.subtitle.toLowerCase().includes(query)) ||
                       (p.description && p.description.toLowerCase().includes(query));
            });
            renderProjectsTable(filtered);
        });
    }
}

// ---------------- MESSAGES (PESAN MASUK) LOGIC ----------------
async function loadMessages(silent = false) {
    try {
        const res = await fetch(`api.php?action=list_messages&_t=${Date.now()}`, { cache: 'no-store' });
        const json = await res.json();
        if (json.success) {
            const prevUnread = lastUnreadMsgCount;
            messagesData = Array.isArray(json.data) ? json.data : [];
            const newUnread = typeof json.unread === 'number' ? json.unread : 0;

            renderMessagesList();
            updateStatCounters();

            // Notifikasi popup jika ada pesan baru yang masuk saat admin sedang aktif
            if (newUnread > prevUnread && prevUnread !== 0) {
                const diff = newUnread - prevUnread;
                showToast(`🔔 Ada ${diff} pesan baru masuk dari pengunjung website!`, 'success');
            }
            lastUnreadMsgCount = newUnread;
        }
    } catch (err) {
        if (!silent) {
            showToast('Gagal memuat pesan masuk: ' + err.message, 'error');
        }
    }
}

// Helper: Escape HTML to prevent XSS in rendering
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Helper: Clean WhatsApp Phone Number for wa.me link
function getCleanWaNumber(phone) {
    if (!phone) return '';
    let digits = String(phone).replace(/[^0-9]/g, '');
    if (digits.startsWith('0')) {
        digits = '62' + digits.substring(1);
    } else if (digits.startsWith('8')) {
        digits = '62' + digits;
    }
    return digits;
}

function renderMessagesList() {
    const container = document.getElementById('messages-list-wrap');
    if (!container) return;

    try {
        const searchInput = document.getElementById('search-messages');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        let filtered = messagesData.filter(msg => {
            // Filter Status Tab
            if (currentMsgFilter === 'unread' && msg.is_read) return false;
            if (currentMsgFilter === 'read' && !msg.is_read) return false;

            // Filter Search Query
            if (query) {
                const nameMatch = msg.name && msg.name.toLowerCase().includes(query);
                const emailMatch = msg.email && msg.email.toLowerCase().includes(query);
                const waMatch = msg.whatsapp && msg.whatsapp.toLowerCase().includes(query);
                const purposeMatch = msg.purpose && msg.purpose.toLowerCase().includes(query);
                const contentMatch = msg.message && msg.message.toLowerCase().includes(query);
                return nameMatch || emailMatch || waMatch || purposeMatch || contentMatch;
            }
            return true;
        });

        if (filtered.length === 0) {
            let emptyTitle = 'Belum Ada Pesan Masuk';
            let emptyDesc = 'Pesan yang dikirimkan pengunjung melalui formulir kontak website akan muncul di sini secara otomatis.';
            if (query) {
                emptyTitle = 'Pesan Tidak Ditemukan';
                emptyDesc = `Tidak ada pesan yang cocok dengan kata kunci "${query}".`;
            } else if (currentMsgFilter === 'unread') {
                emptyTitle = 'Semua Pesan Sudah Dibaca';
                emptyDesc = 'Tidak ada pesan baru yang belum dibaca saat ini.';
            } else if (currentMsgFilter === 'read') {
                emptyTitle = 'Belum Ada Pesan yang Telah Dibaca';
                emptyDesc = 'Pesan yang telah Anda tinjau akan tercatat di sini.';
            }

            container.innerHTML = `
                <div class="empty-state" style="padding: 45px 20px;">
                    <i class="fas fa-envelope-open" style="font-size: 42px; color: var(--text-muted); opacity: 0.6; margin-bottom: 12px;"></i>
                    <h3 style="font-size: 16px; margin: 0 0 6px 0; color: var(--text-main);">${emptyTitle}</h3>
                    <p style="font-size: 13px; color: var(--text-muted); max-width: 420px; margin: 0 auto;">${emptyDesc}</p>
                </div>
            `;
            return;
        }

        container.innerHTML = filtered.map(msg => {
            // Avatar Initials (e.g. "Budi Santoso" -> "BS")
            const initials = (msg.name || 'User')
                .split(' ')
                .filter(Boolean)
                .map(n => n[0])
                .slice(0, 2)
                .join('')
                .toUpperCase();

            const isUnread = !msg.is_read;
            const cardClass = isUnread ? 'message-card unread' : 'message-card';
            const statusBadge = isUnread
                ? '<span class="msg-badge-status unread"><span class="status-dot green"></span> Baru</span>'
                : '<span class="msg-badge-status read"><i class="fas fa-check"></i> Dibaca</span>';

            // Formatted Date
            let dateDisplay = msg.created_at || '';
            try {
                const d = new Date(msg.created_at.replace(' ', 'T'));
                if (!isNaN(d.getTime())) {
                    dateDisplay = d.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'short',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            } catch (e) {}

            const toggleTitle = isUnread ? 'Tandai sudah dibaca' : 'Tandai belum dibaca';
            const toggleIcon = isUnread ? 'fa-envelope-open' : 'fa-envelope';

            // WhatsApp and Purpose helpers
            const cleanWa = getCleanWaNumber(msg.whatsapp);
            const waLink = cleanWa ? `https://wa.me/${cleanWa}?text=Halo%20${encodeURIComponent(msg.name)},%20terima%20kasih%20telah%20menghubungi%20saya%20melalui%20website%20portofolio.` : '';
            const purposeBadge = msg.purpose ? `<span class="msg-purpose-pill"><i class="fas fa-tag"></i> ${escapeHtml(msg.purpose)}</span>` : '';
            const waBtn = waLink ? `<a href="${waLink}" target="_blank" class="btn-msg-action btn-wa" title="Buka percakapan WhatsApp"><i class="fab fa-whatsapp"></i> Chat WA</a>` : '';

            return `
                <div class="${cardClass}" id="msg-card-${msg.id}">
                    <div class="msg-card-top">
                        <div class="msg-sender-wrap">
                            <div class="msg-avatar">${initials}</div>
                            <div class="msg-sender-meta">
                                <h4>
                                    <span>${escapeHtml(msg.name)}</span>
                                    ${purposeBadge}
                                </h4>
                                <div class="msg-sender-contacts">
                                    <a href="mailto:${escapeHtml(msg.email)}" class="msg-sender-email" title="Kirim email ke ${escapeHtml(msg.email)}">
                                        <i class="fas fa-envelope"></i> ${escapeHtml(msg.email)}
                                    </a>
                                    ${msg.whatsapp ? `
                                        <span class="meta-dot">&bull;</span>
                                        <a href="${waLink || '#'}" target="_blank" class="msg-sender-wa" title="Buka WhatsApp (${escapeHtml(msg.whatsapp)})">
                                            <i class="fab fa-whatsapp"></i> ${escapeHtml(msg.whatsapp)}
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                        <div class="msg-status-right">
                            <span class="msg-time"><i class="far fa-clock"></i> ${dateDisplay}</span>
                            ${statusBadge}
                        </div>
                    </div>

                    <p class="msg-card-preview">${escapeHtml(msg.message)}</p>

                    <div class="msg-card-actions">
                        <button type="button" class="btn-msg-action" onclick="openMessageDetail('${msg.id}')">
                            <i class="fas fa-eye"></i> Detail
                        </button>
                        ${waBtn}
                        <a href="mailto:${escapeHtml(msg.email)}?subject=Re:%20[Portofolio]%20${encodeURIComponent(msg.purpose || 'Tanggapan')}&body=Halo%20${encodeURIComponent(msg.name)},%0D%0A%0D%0A" class="btn-msg-action btn-reply">
                            <i class="fas fa-reply"></i> Email
                        </a>
                        <button type="button" class="btn-msg-action" onclick="toggleMessageReadStatus('${msg.id}', ${!isUnread})" title="${toggleTitle}">
                            <i class="fas ${toggleIcon}"></i> ${isUnread ? 'Dibaca' : 'Belum'}
                        </button>
                        <button type="button" class="btn-msg-action btn-delete" onclick="deleteMessage('${msg.id}')" title="Hapus pesan">
                            <i class="fas fa-trash-can"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    } catch (err) {
        console.error('Error rendering messages:', err);
        container.innerHTML = `
            <div class="empty-state" style="padding: 40px 20px;">
                <i class="fas fa-triangle-exclamation" style="font-size: 36px; color: #f59e0b; margin-bottom: 10px;"></i>
                <h3 style="color: var(--text-main);">Gagal Merender Pesan</h3>
                <p style="color: var(--text-muted); font-size: 13px;">${err.message}</p>
            </div>
        `;
    }
}

function filterMessages(filterType, btnEl) {
    currentMsgFilter = filterType;
    document.querySelectorAll('.msg-filter-btn').forEach(btn => btn.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    renderMessagesList();
}

function openMessageDetail(id) {
    const msg = messagesData.find(m => m.id === id);
    if (!msg) return;

    const modal = document.getElementById('message-detail-modal');
    if (!modal) return;

    // Set Initials
    const initials = (msg.name || 'User')
        .split(' ')
        .filter(Boolean)
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

    document.getElementById('modal-msg-avatar').textContent = initials;
    document.getElementById('modal-msg-name').textContent = msg.name;
    document.getElementById('modal-msg-sender-name').textContent = `Pesan dari ${msg.name}`;

    // Purpose Badge
    const purposeEl = document.getElementById('modal-msg-purpose');
    if (purposeEl) {
        if (msg.purpose) {
            purposeEl.textContent = msg.purpose;
            purposeEl.style.display = 'inline-flex';
        } else {
            purposeEl.style.display = 'none';
        }
    }

    // Email
    const emailEl = document.getElementById('modal-msg-email');
    emailEl.textContent = msg.email;
    emailEl.href = `mailto:${msg.email}`;

    // WhatsApp
    const waEl = document.getElementById('modal-msg-wa');
    const waBtnModal = document.getElementById('modal-msg-btn-wa');
    const cleanWa = getCleanWaNumber(msg.whatsapp);
    if (cleanWa) {
        const waUrl = `https://wa.me/${cleanWa}?text=Halo%20${encodeURIComponent(msg.name)},%20terima%20kasih%20telah%20menghubungi%20saya%20melalui%20website%20portofolio.`;
        if (waEl) {
            waEl.href = waUrl;
            waEl.innerHTML = `<i class="fab fa-whatsapp"></i> ${escapeHtml(msg.whatsapp)}`;
            waEl.style.display = 'inline-flex';
        }
        if (waBtnModal) {
            waBtnModal.href = waUrl;
            waBtnModal.style.display = 'inline-flex';
        }
    } else {
        if (waEl) waEl.style.display = 'none';
        if (waBtnModal) waBtnModal.style.display = 'none';
    }

    // Date
    let dateDisplay = msg.created_at || '';
    try {
        const d = new Date(msg.created_at.replace(' ', 'T'));
        if (!isNaN(d.getTime())) {
            dateDisplay = d.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    } catch (e) {}
    document.getElementById('modal-msg-date').innerHTML = `<i class="far fa-clock"></i> ${dateDisplay}`;

    // Body
    document.getElementById('modal-msg-body').textContent = msg.message;

    // Status Badge
    const badgeWrap = document.getElementById('modal-msg-status-badge');
    badgeWrap.innerHTML = '<span class="badge-pill blue"><i class="fas fa-envelope-open"></i> Dibaca</span>';

    // Reply Button
    const replyBtn = document.getElementById('modal-msg-btn-reply');
    replyBtn.href = `mailto:${encodeURIComponent(msg.email)}?subject=Re:%20[Portofolio]%20${encodeURIComponent(msg.purpose || 'Tanggapan')}&body=Halo%20${encodeURIComponent(msg.name)},%0D%0A%0D%0A`;

    // Delete Button
    const deleteBtn = document.getElementById('modal-msg-btn-delete');
    deleteBtn.onclick = () => deleteMessage(msg.id, true);

    // Otomatis tandai sebagai sudah dibaca saat dibuka jika sebelumnya unread
    if (!msg.is_read) {
        msg.is_read = true;
        updateStatCounters();
        renderMessagesList();

        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_read', '1');
        fetch('api.php?action=mark_message_read', {
            method: 'POST',
            body: formData
        }).catch(() => {});
    }

    openModal('message-detail-modal');
}

async function toggleMessageReadStatus(id, newReadStatus) {
    const msg = messagesData.find(m => m.id === id);
    if (!msg) return;

    msg.is_read = newReadStatus;
    updateStatCounters();
    renderMessagesList();

    try {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('is_read', newReadStatus ? '1' : '0');
        const res = await fetch('api.php?action=mark_message_read', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            showToast(newReadStatus ? 'Pesan ditandai sudah dibaca.' : 'Pesan ditandai belum dibaca.');
        }
    } catch (err) {
        showToast('Gagal mengubah status pesan: ' + err.message, 'error');
    }
}

async function markAllMessagesAsRead() {
    if (messagesData.length === 0) {
        showToast('Tidak ada pesan masuk untuk ditandai.', 'error');
        return;
    }

    const hasUnread = messagesData.some(m => !m.is_read);
    if (!hasUnread) {
        showToast('Semua pesan sudah dalam status dibaca.');
        return;
    }

    try {
        const res = await fetch('api.php?action=mark_all_messages_read', { method: 'POST' });
        const json = await res.json();
        if (json.success) {
            messagesData.forEach(m => m.is_read = true);
            lastUnreadMsgCount = 0;
            updateStatCounters();
            renderMessagesList();
            showToast('Semua pesan berhasil ditandai sudah dibaca.');
        }
    } catch (err) {
        showToast('Gagal menandai pesan: ' + err.message, 'error');
    }
}

async function deleteMessage(id, fromModal = false) {
    if (!confirm('Apakah Anda yakin ingin menghapus pesan ini secara permanen?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('id', id);
        const res = await fetch('api.php?action=delete_message', {
            method: 'POST',
            body: formData
        });
        const json = await res.json();
        if (json.success) {
            messagesData = messagesData.filter(m => m.id !== id);
            lastUnreadMsgCount = typeof json.unread === 'number' ? json.unread : messagesData.filter(m => !m.is_read).length;
            if (fromModal) closeModal('message-detail-modal');
            updateStatCounters();
            renderMessagesList();
            showToast('Pesan berhasil dihapus.');
        } else {
            showToast(json.message || 'Gagal menghapus pesan.', 'error');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'error');
    }
}

// Counters Update
function updateStatCounters() {
    const cCount = certificatesData.length;
    const pCount = projectsData.length;
    const mCount = messagesData.length;
    const unreadCount = messagesData.filter(m => !m.is_read).length;
    const readCount = mCount - unreadCount;

    const elCert = document.getElementById('stat-cert-count');
    const elProj = document.getElementById('stat-proj-count');
    const elMsg = document.getElementById('stat-msg-count');
    const elUnreadSub = document.getElementById('stat-unread-sub');
    const badgeCert = document.getElementById('badge-cert-count');
    const badgeProj = document.getElementById('badge-proj-count');
    const badgeMsg = document.getElementById('badge-msg-count');
    const headerUnreadBadge = document.getElementById('header-unread-badge');

    const fAllCount = document.getElementById('msg-filter-all-count');
    const fUnreadCount = document.getElementById('msg-filter-unread-count');
    const fReadCount = document.getElementById('msg-filter-read-count');

    if (elCert) elCert.textContent = cCount;
    if (elProj) elProj.textContent = pCount;
    if (elMsg) elMsg.textContent = mCount;
    if (badgeCert) badgeCert.textContent = cCount;
    if (badgeProj) badgeProj.textContent = pCount;

    if (fAllCount) fAllCount.textContent = mCount;
    if (fUnreadCount) fUnreadCount.textContent = unreadCount;
    if (fReadCount) fReadCount.textContent = readCount;

    // Header notification badge
    if (headerUnreadBadge) {
        if (unreadCount > 0) {
            headerUnreadBadge.textContent = unreadCount;
            headerUnreadBadge.style.display = 'inline-flex';
        } else {
            headerUnreadBadge.style.display = 'none';
        }
    }

    // Tab notification badge
    if (badgeMsg) {
        if (unreadCount > 0) {
            badgeMsg.textContent = unreadCount;
            badgeMsg.style.display = 'inline-block';
        } else {
            badgeMsg.style.display = 'none';
        }
    }

    // Stat card unread sub-label
    if (elUnreadSub) {
        if (unreadCount > 0) {
            elUnreadSub.textContent = `${unreadCount} Baru`;
            elUnreadSub.style.display = 'inline-block';
        } else {
            elUnreadSub.style.display = 'none';
        }
    }
}

// ---------------- LOGIN FORM & SESSION AUTO-LOCK ----------------
function initLoginForm() {
    const loginForm = document.getElementById('login-form');
    if (!loginForm) return;

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const origText = submitBtn.innerHTML;
        const errorBox = document.getElementById('login-error-box');
        const errorText = document.getElementById('login-error-text');
        if (errorBox) errorBox.style.display = 'none';

        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
        submitBtn.disabled = true;

        try {
            const formData = new FormData(loginForm);
            const res = await fetch('api.php?action=login', {
                method: 'POST',
                body: formData
            });
            const json = await res.json();
            if (json.success) {
                // Buka dashboard dan sembunyikan layar login
                document.getElementById('login-screen').style.display = 'none';
                document.getElementById('dashboard-screen').style.display = 'block';

                // Isi username pada form pengaturan akun
                const usernameVal = loginForm.querySelector('#username').value.trim();
                const accUser = document.getElementById('acc-username');
                if (accUser && usernameVal) accUser.value = usernameVal;

                showToast('Login berhasil! Selamat datang di dashboard.');
                loadCertificates();
                loadProjects();
                loadMessages(true);

                // Polling pesan masuk setiap 25 detik
                if (msgPollInterval) clearInterval(msgPollInterval);
                msgPollInterval = setInterval(() => loadMessages(true), 25000);
            } else {
                if (errorBox && errorText) {
                    errorText.textContent = json.message || 'Username atau password yang Anda masukkan salah.';
                    errorBox.style.display = 'flex';
                } else {
                    showToast(json.message || 'Username atau password salah.', 'error');
                }
            }
        } catch (err) {
            showToast('Terjadi kesalahan koneksi: ' + err.message, 'error');
        } finally {
            submitBtn.innerHTML = origText;
            submitBtn.disabled = false;
        }
    });
}

// Keluar / Logout
function handleLogout() {
    try {
        navigator.sendBeacon('logout.php');
    } catch (e) {}
    window.location.reload();
}

// Kunci otomatis saat halaman ditutup atau di-reload (F5 / Refresh)
window.addEventListener('beforeunload', () => {
    try {
        navigator.sendBeacon('logout.php');
    } catch (e) {}
});

// Dom Ready
document.addEventListener('DOMContentLoaded', () => {
    initTabs();
    initSearch();
    initLoginForm();
    renderIconPicker();

    // Smart Dropzone Setup
    const dropzone = document.getElementById('smart-dropzone');
    const smartInput = document.getElementById('smart-cert-file-input');

    if (dropzone && smartInput) {
        // Drag over / leave
        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('dragover');
        });
        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('dragover');
        });
        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('dragover');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                processUploadedCertificate(e.dataTransfer.files[0]);
            }
        });

        // Click on dropzone idle area triggers file browse
        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('#dropzone-idle-state')) {
                smartInput.click();
            }
        });

        // File input changed
        smartInput.addEventListener('change', () => {
            if (smartInput.files && smartInput.files[0]) {
                processUploadedCertificate(smartInput.files[0]);
            }
        });
    }

    // Icon Live Preview
    const iconInput = document.getElementById('cert-icon');
    if (iconInput) {
        iconInput.addEventListener('input', (e) => {
            updateIconPreview(e.target.value.trim());
        });
    }

    // Project Cover Preview binding
    setupFileInputPreview('proj_image_file', 'project-cover-wrap', 'project-cover-thumb', 'project-cover-filename');

    // Project Modal State Toggles
    const autoPrevCheck = document.getElementById('project-auto-preview');
    if (autoPrevCheck) {
        autoPrevCheck.addEventListener('change', updateProjectModalState);
    }

    const isDownCheck = document.getElementById('project-is-download');
    if (isDownCheck) {
        isDownCheck.addEventListener('change', updateProjectModalState);
    }

    // Project Gallery Multiple Files Live Preview
    const galleryInput = document.getElementById('proj_gallery_files');
    if (galleryInput) {
        galleryInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            if (files.length === 0) return;
            files.forEach(file => {
                const exists = currentProjectNewGalleryFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
                if (!exists) {
                    currentProjectNewGalleryFiles.push(file);
                }
            });
            syncGalleryFileInput();
            renderProjectGalleryThumbs();
        });
    }

    // Global ESC listener to close Image Preview Lightbox
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const imgModal = document.getElementById('image-preview-modal');
            if (imgModal && imgModal.classList.contains('open')) {
                closeImagePreviewModal();
                e.stopPropagation();
            }
        }
    });

    // Messages Search
    const searchMsgInput = document.getElementById('search-messages');
    if (searchMsgInput) {
        searchMsgInput.addEventListener('input', () => {
            renderMessagesList();
        });
    }

    // Forms
    const certForm = document.getElementById('cert-form');
    if (certForm) certForm.addEventListener('submit', handleCertFormSubmit);

    const projectForm = document.getElementById('project-form');
    if (projectForm) projectForm.addEventListener('submit', handleProjectFormSubmit);

    const accountForm = document.getElementById('account-form');
    if (accountForm) accountForm.addEventListener('submit', handleAccountFormSubmit);
});

