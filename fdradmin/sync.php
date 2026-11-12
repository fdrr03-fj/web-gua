<?php
/**
 * Synchronizer: Menyinkronkan file data/certificates.json dan data/projects.json ke index.html
 * Serta otomatis meng-generate halaman preview proyek individual (seperti rumahsiap.html)
 */

function slugifyProject($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    if (function_exists('iconv')) {
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    }
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'proyek' : $text;
}

function getYoutubeEmbedUrl($url) {
    if (empty($url)) return null;
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i', $url, $matches)) {
        return 'https://www.youtube-nocookie.com/embed/' . $matches[1];
    }
    return null;
}

/**
 * Otomatis generate file HTML preview mandiri untuk proyek (seperti rumahsiap.html)
 */
function generateProjectPreviewPage($project, $rootDir) {
    $titleRaw = trim($project['title'] ?? 'Proyek');
    $subtitle = trim($project['subtitle'] ?? '');
    $badge = !empty($project['badge']) ? trim($project['badge']) : ($subtitle ?: 'Karya Unggulan');

    // Title formatting (memberi tag <em> pada kata kedua jika ada dua kata)
    $words = explode(' ', $titleRaw, 2);
    if (count($words) === 2) {
        $titleDisplay = htmlspecialchars($words[0]) . ' <em>' . htmlspecialchars($words[1]) . '</em>';
    } else {
        $titleDisplay = htmlspecialchars($titleRaw);
    }

    // Penentuan nama file target
    if (!empty($project['link']) && preg_match('/^project-[a-z0-9\-]+\.html$/i', $project['link'])) {
        $filename = $project['link'];
    } else {
        $slug = slugifyProject($titleRaw);
        $filename = 'project-' . $slug . '.html';
    }

    $pageTitle = htmlspecialchars($titleRaw) . ' | Muh. Fadnur Fajrin';
    $shortDesc = htmlspecialchars($project['description'] ?? '');
    $fullDescRaw = !empty($project['full_description']) ? $project['full_description'] : ($project['description'] ?? '');

    // Paragraf deskripsi
    $descParagraphs = '';
    $paragraphs = preg_split('/\r\n|\r|\n/', $fullDescRaw);
    foreach ($paragraphs as $p) {
        $p = trim($p);
        if ($p !== '') {
            $descParagraphs .= "            <p style=\"color: var(--text-light); margin-bottom: 18px; line-height: 1.85; font-size: clamp(0.96rem, 1.2vw, 1.05rem);\">" . htmlspecialchars($p) . "</p>\n";
        }
    }
    if (empty($descParagraphs)) {
        $descParagraphs = "            <p style=\"color: var(--text-light); margin-bottom: 18px; line-height: 1.85;\">" . htmlspecialchars($shortDesc) . "</p>\n";
    }

    // Tech Stack Chips
    $techStackRaw = $project['tech_stack'] ?? '';
    $techBadgesHtml = '';
    if (!empty($techStackRaw)) {
        $techItems = is_array($techStackRaw) ? $techStackRaw : explode(',', $techStackRaw);
        $techBadgesHtml .= "            <div class=\"tech-stack-wrap\" style=\"display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 24px;\">\n";
        foreach ($techItems as $item) {
            $t = trim($item);
            if ($t !== '') {
                $techBadgesHtml .= "                <span class=\"tech-pill\"><i class=\"fas fa-layer-group\"></i> " . htmlspecialchars($t) . "</span>\n";
            }
        }
        $techBadgesHtml .= "            </div>\n";
    }

    // Action buttons (demo_url, github_url)
    $actionButtonsHtml = '';
    $demoUrl = trim($project['demo_url'] ?? '');
    $githubUrl = trim($project['github_url'] ?? '');
    if (!empty($demoUrl) || !empty($githubUrl)) {
        $actionButtonsHtml .= "            <div class=\"project-action-buttons\" style=\"display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 28px;\">\n";
        if (!empty($demoUrl)) {
            $actionButtonsHtml .= "                <a href=\"" . htmlspecialchars($demoUrl) . "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"btn-action-primary\"><i class=\"fas fa-arrow-up-right-from-square\"></i> Kunjungi Live Demo</a>\n";
        }
        if (!empty($githubUrl)) {
            $actionButtonsHtml .= "                <a href=\"" . htmlspecialchars($githubUrl) . "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"btn-action-secondary\"><i class=\"fab fa-github\"></i> Source Code GitHub</a>\n";
        }
        $actionButtonsHtml .= "            </div>\n";
    }

    // Video Section (YouTube or MP4)
    $videoUrl = trim($project['video_url'] ?? '');
    $videoHtml = '';
    if (!empty($videoUrl)) {
        $ytEmbed = getYoutubeEmbedUrl($videoUrl);
        $videoHtml .= "            <h3 style=\"margin: 28px 0 14px; color: var(--text-main); font-weight: 700; font-size: 1.25rem;\"><i class=\"fas fa-play-circle\" style=\"color: #4d61ff; margin-right: 8px;\"></i>Video Demo &amp; Simulasi</h3>\n";
        $videoHtml .= "            <div class=\"video-wrapper\">\n";
        if ($ytEmbed) {
            $videoHtml .= "                <iframe src=\"" . htmlspecialchars($ytEmbed) . "\" title=\"Video Demo " . htmlspecialchars($titleRaw) . "\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>\n";
        } else {
            $poster = !empty($project['image']) ? htmlspecialchars($project['image']) : '';
            $posterAttr = $poster ? " poster=\"{$poster}\"" : '';
            $videoHtml .= "                <video controls{$posterAttr}>\n";
            $videoHtml .= "                    <source src=\"" . htmlspecialchars($videoUrl) . "\" type=\"video/mp4\">\n";
            $videoHtml .= "                    Browser Anda tidak mendukung tag video HTML5.\n";
            $videoHtml .= "                </video>\n";
        }
        $videoHtml .= "            </div>\n";
    }

    // Prototype Gallery Images
    $gallery = $project['gallery_images'] ?? [];
    if (!is_array($gallery)) $gallery = [];
    if (empty($gallery) && !empty($project['image'])) {
        $gallery = [$project['image']];
    }

    $galleryHtml = '';
    $galleryJsonArray = [];
    if (!empty($gallery)) {
        $galleryHtml .= "            <h3 style=\"margin: 32px 0 16px; color: var(--text-main); font-weight: 700; font-size: 1.25rem;\"><i class=\"fas fa-images\" style=\"color: #4d61ff; margin-right: 8px;\"></i>Galeri Prototipe &amp; Tampilan Layar</h3>\n";
        $galleryHtml .= "            <div class=\"prototype-grid\">\n";
        foreach ($gallery as $gIdx => $gImg) {
            $gPath = trim($gImg);
            if ($gPath === '') continue;
            $galleryJsonArray[] = $gPath;
            $escapedGPath = htmlspecialchars($gPath);
            $galleryHtml .= "                <div class=\"prototype-card\" onclick=\"openLightbox({$gIdx})\" role=\"button\" tabindex=\"0\" aria-label=\"Lihat foto " . ($gIdx + 1) . "\">\n";
            $galleryHtml .= "                    <img src=\"{$escapedGPath}\" alt=\"Prototipe " . htmlspecialchars($titleRaw) . " " . ($gIdx + 1) . "\" loading=\"lazy\" onerror=\"this.src='https://placehold.co/600x400/1e293b/94a3b8?text=Foto+Prototipe'\">\n";
            $galleryHtml .= "                    <div class=\"prototype-overlay\">\n";
            $galleryHtml .= "                        <span class=\"prototype-zoom-pill\"><i class=\"fas fa-expand\"></i> Perbesar Foto</span>\n";
            $galleryHtml .= "                    </div>\n";
            $galleryHtml .= "                </div>\n";
        }
        $galleryHtml .= "            </div>\n";
    }

    $galleryJsonEscaped = json_encode($galleryJsonArray, JSON_UNESCAPED_SLASHES);
    $coverImg = !empty($project['image']) ? htmlspecialchars($project['image']) : 'img/gue.png';

    $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="id" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$pageTitle}</title>
    
    <!-- Favicon / Tab Icon -->
    <link rel="icon" type="image/png" href="img/logo/icon.png">
    <link rel="apple-touch-icon" href="img/logo/icon.png">
    
    <!-- Meta Tags SEO & Social Media -->
    <meta name="description" content="{$shortDesc}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{$pageTitle}">
    <meta property="og:description" content="{$shortDesc}">
    <meta property="og:image" content="{$coverImg}">
    
    <!-- Google Fonts: Plus Jakarta Sans, Poppins & Playfair Display -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400;1,600;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="style.css?v=20261105_xtra21">
    <style>
        .demo-page-wrapper { padding: clamp(30px, 5vw, 60px) 0; }
        .demo-container { 
            max-width: 960px; 
            margin: 0 auto; 
            padding: clamp(24px, 4vw, 42px); 
            background: var(--bg-card); 
            border-radius: clamp(18px, 2.5vw, 24px); 
            border: 1px solid var(--border-card); 
            border-bottom: 2px solid transparent;
            backdrop-filter: blur(14px); 
            box-shadow: var(--shadow-card); 
            transition: border-bottom-color 0.3s ease, box-shadow 0.3s ease;
        }
        .demo-container:hover {
            border-bottom-color: #4d61ff;
            box-shadow: var(--shadow-hover);
        }
        .demo-title { 
            font-size: clamp(1.8rem, 4vw, 2.5rem); 
            font-weight: 800; 
            margin-bottom: 12px; 
            color: var(--text-white); 
            letter-spacing: -0.025em; 
        }
        .demo-title em {
            color: #4d61ff;
            font-style: normal;
        }
        .badge-demo { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            padding: 6px 16px; 
            background: rgba(77, 97, 255, 0.12); 
            border: 1px solid rgba(77, 97, 255, 0.28); 
            color: #4d61ff; 
            border-radius: 20px; 
            font-size: 0.82rem; 
            font-weight: 600; 
            margin-bottom: 20px; 
        }
        .tech-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.25s ease;
        }
        .tech-pill:hover {
            border-color: #4d61ff;
            color: #4d61ff;
            background: rgba(77, 97, 255, 0.08);
        }
        .btn-action-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #4d61ff;
            color: #ffffff !important;
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 18px rgba(77, 97, 255, 0.35);
        }
        .btn-action-primary:hover {
            background: #394de6;
            transform: translateY(-2px);
            box-shadow: 0 6px 22px rgba(77, 97, 255, 0.45);
        }
        .btn-action-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--pill-bg);
            color: var(--text-white) !important;
            border: 1px solid var(--pill-border);
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-action-secondary:hover {
            background: rgba(77, 97, 255, 0.15);
            border-color: #4d61ff;
            color: #4d61ff !important;
            transform: translateY(-2px);
        }
        .video-wrapper { 
            position: relative; 
            padding-bottom: 56.25%; 
            height: 0; 
            overflow: hidden; 
            border-radius: 16px; 
            margin-bottom: 32px; 
            background: #000; 
            border: 1px solid var(--border-card); 
            box-shadow: 0 12px 30px rgba(0,0,0,0.3); 
        }
        .video-wrapper video, .video-wrapper iframe { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            border: 0;
        }
        .prototype-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
            gap: clamp(14px, 2.5vw, 22px); 
            margin-top: 18px; 
        }
        .prototype-card {
            position: relative;
            border-radius: 14px;
            overflow: hidden;
            aspect-ratio: 16 / 11;
            background: #0c1017;
            border: 1px solid var(--border-card);
            cursor: pointer;
            transition: transform 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .prototype-card img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            display: block; 
            transition: transform 0.4s ease; 
        }
        .prototype-card:hover { 
            transform: translateY(-4px); 
            border-color: #4d61ff; 
            box-shadow: 0 10px 24px rgba(77, 97, 255, 0.2); 
        }
        .prototype-card:hover img { 
            transform: scale(1.06); 
        }
        .prototype-overlay {
            position: absolute;
            inset: 0;
            background: rgba(10, 14, 25, 0.55);
            backdrop-filter: blur(2px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .prototype-card:hover .prototype-overlay {
            opacity: 1;
        }
        .prototype-zoom-pill {
            background: rgba(77, 97, 255, 0.9);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            transform: translateY(6px);
            transition: transform 0.3s ease;
        }
        .prototype-card:hover .prototype-zoom-pill {
            transform: translateY(0);
        }
        .btn-back-pill { 
            display: inline-flex; 
            align-items: center; 
            gap: 8px; 
            color: var(--text-muted); 
            background: var(--pill-bg); 
            border: 1px solid var(--pill-border); 
            padding: 10px 20px; 
            border-radius: 50px; 
            font-weight: 600; 
            font-size: 0.9rem; 
            margin-bottom: 24px; 
            text-decoration: none; 
            transition: all 0.3s ease; 
        }
        .btn-back-pill:hover { 
            color: #ffffff; 
            background: #4d61ff; 
            border-color: #4d61ff; 
            transform: translateX(-4px); 
            box-shadow: 0 4px 16px rgba(77, 97, 255, 0.35); 
        }

        /* LIGHTBOX MODAL */
        .lightbox-modal {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .lightbox-modal.active {
            display: flex;
            opacity: 1;
        }
        .lightbox-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(6, 9, 18, 0.9);
            backdrop-filter: blur(10px);
        }
        .lightbox-dialog {
            position: relative;
            z-index: 2;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .lightbox-img-wrap {
            position: relative;
            max-width: 88vw;
            max-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox-img {
            max-width: 100%;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 12px;
            box-shadow: 0 16px 40px rgba(0,0,0,0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .lightbox-close {
            position: absolute;
            top: -46px;
            right: 0;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #ffffff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
        }
        .lightbox-close:hover {
            background: #ef4444;
            border-color: #ef4444;
            transform: scale(1.1);
        }
        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
            backdrop-filter: blur(6px);
        }
        .lightbox-nav:hover {
            background: #4d61ff;
            border-color: #4d61ff;
            transform: translateY(-50%) scale(1.1);
        }
        .lightbox-nav.prev { left: -56px; }
        .lightbox-nav.next { right: -56px; }
        .lightbox-bar {
            margin-top: 14px;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 600;
        }
        .lightbox-counter {
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 12px;
            border-radius: 20px;
            color: #ffffff;
        }

        @media (max-width: 768px) {
            .lightbox-nav.prev { left: 8px; }
            .lightbox-nav.next { right: 8px; }
            .lightbox-close { top: 10px; right: 10px; }
            .prototype-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Saklar Tema: Bulan & Matahari (Floating) -->
    <button class="theme-switch floating-switch" id="theme-btn" aria-label="Ganti Tema (Matahari / Bulan)" title="Ganti Tema">
        <span class="switch-track">
            <span class="switch-icon icon-sun"><i class="fas fa-sun"></i></span>
            <span class="switch-icon icon-moon"><i class="fas fa-moon"></i></span>
            <span class="switch-thumb">
                <i class="fas fa-moon thumb-icon" id="theme-icon"></i>
            </span>
        </span>
    </button>

    <div class="container demo-page-wrapper">
        <a href="index.html#projects" class="btn-back-pill"><i class="fas fa-arrow-left"></i> Kembali ke Beranda</a>
        
        <div class="demo-container">
            <h1 class="demo-title">{$titleDisplay}</h1>
            <span class="badge-demo"><i class="fas fa-folder-open"></i> {$badge}</span>

{$techBadgesHtml}{$actionButtonsHtml}{$descParagraphs}{$videoHtml}{$galleryHtml}        </div>
    </div>

    <!-- LIGHTBOX MODAL -->
    <div class="lightbox-modal" id="lightbox-modal">
        <div class="lightbox-backdrop" onclick="closeLightbox()"></div>
        <div class="lightbox-dialog">
            <button type="button" class="lightbox-close" onclick="closeLightbox()" title="Tutup (Esc)"><i class="fas fa-xmark"></i></button>
            <div class="lightbox-img-wrap">
                <button type="button" class="lightbox-nav prev" onclick="changeLightboxSlide(-1)" title="Foto Sebelumnya (&larr;)"><i class="fas fa-chevron-left"></i></button>
                <img class="lightbox-img" id="lightbox-img" src="" alt="Pratinjau Prototipe">
                <button type="button" class="lightbox-nav next" onclick="changeLightboxSlide(1)" title="Foto Selanjutnya (&rarr;)"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="lightbox-bar">
                <span class="lightbox-counter" id="lightbox-counter">1 / 1</span>
                <span>Gunakan panah keyboard (&larr; / &rarr;) atau Esc untuk menutup</span>
            </div>
        </div>
    </div>

    <!-- Script Tema & Lightbox Gallery -->
    <script>
        // TEMA MODE GELAP / TERANG (Default: Gelap) - SAKLAR MATAHARI & BULAN
        const themeBtn = document.getElementById('theme-btn');
        const themeIcon = document.getElementById('theme-icon');
        const currentTheme = localStorage.getItem('theme');
        
        function applyTheme(theme) {
            if (theme === 'light') {
                document.documentElement.removeAttribute('data-theme');
                if (themeIcon) themeIcon.className = 'fas fa-sun thumb-icon';
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                if (themeIcon) themeIcon.className = 'fas fa-moon thumb-icon';
                localStorage.setItem('theme', 'dark');
            }
        }

        if (currentTheme === 'light') {
            applyTheme('light');
        } else {
            applyTheme('dark');
        }

        if (themeBtn) {
            themeBtn.addEventListener('click', () => {
                const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                const nextTheme = isDark ? 'light' : 'dark';

                const rect = themeBtn.getBoundingClientRect();
                const x = rect.left + rect.width / 2;
                const y = rect.top + rect.height / 2;
                const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

                if (document.startViewTransition) {
                    document.documentElement.style.setProperty('--ripple-x', `\${x}px`);
                    document.documentElement.style.setProperty('--ripple-y', `\${y}px`);
                    document.documentElement.style.setProperty('--ripple-r', `\${endRadius}px`);

                    const transition = document.startViewTransition(() => {
                        applyTheme(nextTheme);
                    });

                    transition.ready.then(() => {
                        document.documentElement.animate(
                            {
                                clipPath: [
                                    `circle(0px at \${x}px \${y}px)`,
                                    `circle(\${endRadius}px at \${x}px \${y}px)`
                                ]
                            },
                            {
                                duration: 650,
                                easing: 'cubic-bezier(0.16, 1, 0.3, 1)',
                                pseudoElement: '::view-transition-new(root)'
                            }
                        );
                    }).catch(() => {});
                } else {
                    const fallbackRipple = document.createElement('div');
                    fallbackRipple.className = 'theme-ripple-fallback';
                    fallbackRipple.style.left = `\${x}px`;
                    fallbackRipple.style.top = `\${y}px`;
                    fallbackRipple.style.width = `\${endRadius * 2}px`;
                    fallbackRipple.style.height = `\${endRadius * 2}px`;
                    fallbackRipple.style.marginLeft = `\${-endRadius}px`;
                    fallbackRipple.style.marginTop = `\${-endRadius}px`;
                    fallbackRipple.style.background = nextTheme === 'dark' ? '#080c14' : '#f8fafc';
                    document.body.appendChild(fallbackRipple);

                    applyTheme(nextTheme);

                    requestAnimationFrame(() => {
                        fallbackRipple.classList.add('expanding');
                    });

                    setTimeout(() => {
                        fallbackRipple.style.opacity = '0';
                        setTimeout(() => fallbackRipple.remove(), 400);
                    }, 550);
                }
            });
        }

        // LIGHTBOX LOGIC
        const galleryImages = {$galleryJsonEscaped};
        let currentSlide = 0;
        const lightboxModal = document.getElementById('lightbox-modal');
        const lightboxImg = document.getElementById('lightbox-img');
        const lightboxCounter = document.getElementById('lightbox-counter');

        function openLightbox(idx) {
            if (!galleryImages || galleryImages.length === 0) return;
            currentSlide = (idx >= 0 && idx < galleryImages.length) ? idx : 0;
            updateLightboxView();
            lightboxModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            lightboxModal.classList.remove('active');
            document.body.style.overflow = '';
        }

        function changeLightboxSlide(delta) {
            if (!galleryImages || galleryImages.length === 0) return;
            currentSlide = (currentSlide + delta + galleryImages.length) % galleryImages.length;
            updateLightboxView();
        }

        function updateLightboxView() {
            if (!galleryImages || galleryImages.length === 0) return;
            lightboxImg.src = galleryImages[currentSlide];
            lightboxCounter.textContent = `\${currentSlide + 1} / \${galleryImages.length}`;
        }

        document.addEventListener('keydown', (e) => {
            if (!lightboxModal.classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') changeLightboxSlide(-1);
            if (e.key === 'ArrowRight') changeLightboxSlide(1);
        });

        // Anti-Inspect Protection
        document.addEventListener('contextmenu', function(e) {
            e.preventDefault();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'F12' || e.keyCode === 123) {
                e.preventDefault();
            }
            if (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c')) {
                e.preventDefault();
            }
            if (e.ctrlKey && (e.key === 'U' || e.key === 'u')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
HTML;

    file_put_contents($rootDir . '/' . $filename, $htmlContent);
    return $filename;
}

function syncIndexHtml() {
    $rootDir = dirname(__DIR__);
    $certFile = $rootDir . '/data/certificates.json';
    $projFile = $rootDir . '/data/projects.json';
    $htmlFile = $rootDir . '/index.html';

    if (!file_exists($htmlFile)) {
        return ['success' => false, 'message' => 'index.html tidak ditemukan'];
    }

    $html = file_get_contents($htmlFile);

    // 1. Sync Certificates
    if (file_exists($certFile)) {
        $certs = json_decode(file_get_contents($certFile), true);
        if (is_array($certs)) {
            $totalCerts = count($certs);

            // Generate Slides HTML
            $slidesHtml = "\n";
            foreach ($certs as $index => $cert) {
                $isCenter = ($index === 0);
                $activeClass = $isCenter ? 'is-center' : '';
                $title = htmlspecialchars($cert['title'] ?? '');
                $shortName = htmlspecialchars($cert['short_name'] ?? $cert['title'] ?? '');
                $issuer = htmlspecialchars($cert['issuer'] ?? '');
                $date = htmlspecialchars($cert['date'] ?? '');
                $category = htmlspecialchars($cert['category'] ?? '');
                $badge = htmlspecialchars($cert['badge_label'] ?? '');
                $credential = htmlspecialchars($cert['credential'] ?? '');
                $tag = htmlspecialchars($cert['tag'] ?? $credential);
                $icon = htmlspecialchars($cert['icon'] ?? 'fas fa-award');
                $preview = htmlspecialchars($cert['preview'] ?? '');
                $file = htmlspecialchars($cert['file'] ?? '');
                $filetype = htmlspecialchars($cert['filetype'] ?? 'PDF Resmi');
                $verifyUrl = htmlspecialchars($cert['verify_url'] ?? '');
                $quote = htmlspecialchars($cert['quote'] ?? '');
                $desc = htmlspecialchars($cert['description'] ?? '');

                $fileBadgeIcon = (stripos($filetype, 'PDF') !== false) ? 'fa-file-pdf' : 'fa-image';
                $fileBadgeText = (stripos($filetype, 'PDF') !== false) ? 'PDF' : 'Gambar';

                $badgeSnippet = $badge ? " • {$badge}" : "";

                $slidesHtml .= "                            <!-- Slide {$index}: {$shortName} -->\n";
                $slidesHtml .= "                            <article class=\"cz_carousel_slide {$activeClass}\" data-index=\"{$index}\"\n";
                $slidesHtml .= "                                data-title=\"{$title}\"\n";
                $slidesHtml .= "                                data-issuer=\"{$issuer}\"\n";
                $slidesHtml .= "                                data-date=\"{$date}\"\n";
                $slidesHtml .= "                                data-category=\"{$category}\"\n";
                $slidesHtml .= "                                data-credential=\"{$credential}\"\n";
                $slidesHtml .= "                                data-preview=\"{$preview}\"\n";
                $slidesHtml .= "                                data-file=\"{$file}\"\n";
                $slidesHtml .= "                                data-filetype=\"{$filetype}\"\n";
                $slidesHtml .= "                                data-verify-url=\"{$verifyUrl}\"\n";
                $slidesHtml .= "                                data-desc=\"{$desc}\">\n";
                $slidesHtml .= "                                <div class=\"cert_preview_frame\" role=\"button\" tabindex=\"0\" aria-label=\"Buka mode preview sertifikat {$title}\">\n";
                $slidesHtml .= "                                    <img src=\"{$preview}\" alt=\"Sertifikat {$title} - Muh. Fadnur Fajrin\" loading=\"lazy\" draggable=\"false\">\n";
                $slidesHtml .= "                                    <span class=\"cert_verified_badge\"><i class=\"fas fa-circle-check\"></i> Bukti Asli</span>\n";
                $slidesHtml .= "                                    <span class=\"cert_format_badge\"><i class=\"fas {$fileBadgeIcon}\"></i> {$fileBadgeText}</span>\n";
                $slidesHtml .= "                                    <div class=\"cert_preview_overlay\">\n";
                $slidesHtml .= "                                        <span class=\"cert_zoom_pill\"><i class=\"fas fa-expand\"></i> Buka Mode Preview</span>\n";
                $slidesHtml .= "                                    </div>\n";
                $slidesHtml .= "                                </div>\n";
                $slidesHtml .= "                                <div class=\"cert_card_body\">\n";
                $slidesHtml .= "                                    <div class=\"cz_testimonials_meta\">\n";
                $slidesHtml .= "                                        <div class=\"cz_testimonials_avatar\"><i class=\"{$icon}\"></i></div>\n";
                $slidesHtml .= "                                        <div class=\"cz_testimonials_headings\">\n";
                $slidesHtml .= "                                            <span class=\"cert_category_label\">{$category}{$badgeSnippet}</span>\n";
                $slidesHtml .= "                                            <div class=\"cz_testimonials_name\">{$shortName}</div>\n";
                $slidesHtml .= "                                            <div class=\"cz_testimonials_subname\">{$issuer}</div>\n";
                $slidesHtml .= "                                        </div>\n";
                $slidesHtml .= "                                    </div>\n";
                $slidesHtml .= "                                    <div class=\"cert_credential_tag\" title=\"Status Kredensial\">\n";
                $slidesHtml .= "                                        <i class=\"fas fa-qrcode\"></i>\n";
                $slidesHtml .= "                                        <span>{$tag}</span>\n";
                $slidesHtml .= "                                    </div>\n";
                $slidesHtml .= "                                    <p class=\"cz_carousel_quote\">\n";
                $slidesHtml .= "                                        {$quote}\n";
                $slidesHtml .= "                                    </p>\n";
                $slidesHtml .= "                                    <div class=\"cert_card_actions\">\n";
                $slidesHtml .= "                                        <button type=\"button\" class=\"cert_btn_preview\" data-open-cert=\"{$index}\">\n";
                $slidesHtml .= "                                            <i class=\"fas fa-expand\"></i> Preview Bukti\n";
                $slidesHtml .= "                                        </button>\n";
                $slidesHtml .= "                                    </div>\n";
                $slidesHtml .= "                                </div>\n";
                $slidesHtml .= "                            </article>\n\n";
            }
            $slidesHtml .= "                        ";

            // Replace stage slides
            $patternStage = '/(<div class="cz_carousel_stage" id="cert-carousel-stage">)(.*?)(<\/div>\s*<\/div>\s*<!-- Optional Floating Side Navigation Arrow Next -->)/s';
            if (preg_match($patternStage, $html)) {
                $html = preg_replace($patternStage, '$1' . $slidesHtml . '$3', $html);
            }

            // Generate Dots HTML
            $dotsHtml = "\n";
            foreach ($certs as $index => $cert) {
                $active = ($index === 0) ? ' active' : '';
                $shortName = htmlspecialchars($cert['short_name'] ?? $cert['title'] ?? '');
                $dotsHtml .= "                        <span class=\"cz_slick_dot{$active}\" data-index=\"{$index}\" title=\"{$shortName}\"></span>\n";
            }
            $dotsHtml .= "                    ";

            // Replace dots
            $patternDots = '/(<div class="cz_slick_dots" id="cert-dots">)(.*?)(<\/div>)/s';
            if (preg_match($patternDots, $html)) {
                $html = preg_replace($patternDots, '$1' . $dotsHtml . '$3', $html);
            }

            // Update counter pill (1 / X)
            $html = preg_replace(
                '/(<span class="cert_counter_pill" id="cert-counter-pill">)(.*?)(<\/span>)/',
                '${1}1 / ' . $totalCerts . '$3',
                $html
            );

            // Update modal counter (1 / X)
            $html = preg_replace(
                '/(<span class="cert_modal_counter" id="cert-modal-counter">)(.*?)(<\/span>)/',
                '${1}1 / ' . $totalCerts . '$3',
                $html
            );

            // Update animated counter data-target for certificates
            $html = preg_replace(
                '/(<span class="cz_counter_val" data-target=")[0-9]+(">[0-9]*<\/span><i>\+<\/i>\s*<\/div>\s*<span class="cz_counter_after">Sertifikasi &amp; Penghargaan)/',
                '${1}' . $totalCerts . '$2',
                $html
            );
        }
    }

    // 2. Sync Projects & Auto-Generate Individual Project Pages
    if (file_exists($projFile)) {
        $projects = json_decode(file_get_contents($projFile), true);
        if (is_array($projects)) {
            $updatedProjects = false;

            // Generate preview pages for projects where appropriate
            foreach ($projects as $i => &$proj) {
                $isDownload = !empty($proj['is_download']);
                $autoPreview = !isset($proj['auto_preview']) || $proj['auto_preview'] === true || $proj['auto_preview'] === '1' || $proj['auto_preview'] === 1;

                if (!$isDownload && $autoPreview) {
                    $previewFile = generateProjectPreviewPage($proj, $rootDir);
                    if ($previewFile && ($proj['link'] ?? '') !== $previewFile) {
                        $proj['link'] = $previewFile;
                        $updatedProjects = true;
                    }
                }
            }
            unset($proj);

            if ($updatedProjects) {
                file_put_contents($projFile, json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $totalProj = count($projects);
            $projHtml = "\n";
            foreach ($projects as $i => $proj) {
                $num = $i + 1;
                $titleRaw = $proj['title'] ?? '';
                $subtitle = htmlspecialchars($proj['subtitle'] ?? '');
                $desc = htmlspecialchars($proj['description'] ?? '');
                $img = htmlspecialchars($proj['image'] ?? '');
                $link = htmlspecialchars($proj['link'] ?? '#');
                $btnText = htmlspecialchars($proj['btn_text'] ?? 'lihat detail');
                $btnUpper = strtoupper($btnText);
                $downloadAttr = (!empty($proj['is_download'])) ? ' download' : '';

                // Format title (give <em> to second word if multiword)
                $words = explode(' ', $titleRaw, 2);
                if (count($words) === 2) {
                    $formattedTitle = htmlspecialchars($words[0]) . ' <em>' . htmlspecialchars($words[1]) . '</em>';
                } else {
                    $formattedTitle = htmlspecialchars($titleRaw);
                }

                $projHtml .= "                <!-- Card {$num}: " . htmlspecialchars($titleRaw) . " -->\n";
                $projHtml .= "                <div class=\"cz_overlay_card fade-on-scroll\" style=\"background-image: url('{$img}');\">\n";
                $projHtml .= "                    <div class=\"cz_overlay_card_content\">\n";
                $projHtml .= "                        <h3>{$formattedTitle}<br>{$subtitle}</h3>\n";
                $projHtml .= "                        <p>{$desc}</p>\n";
                $projHtml .= "                        <a href=\"{$link}\"{$downloadAttr} class=\"cz_btn_txt_move_up\">\n";
                $projHtml .= "                            <span class=\"cz_btn_txt\"><i class=\"fas fa-angle-right\"></i> <strong>{$btnText}</strong></span>\n";
                $projHtml .= "                            <b class=\"cz_btn_onhover\"><i class=\"fas fa-angle-right\"></i> <strong>{$btnUpper}</strong></b>\n";
                $projHtml .= "                        </a>\n";
                $projHtml .= "                    </div>\n";
                $projHtml .= "                </div>\n\n";
            }
            $projHtml .= "            ";

            // Replace project grid in index.html
            $patternProj = '/(<div class="cz_overlay_cards_grid">)(.*?)(<\/div>\s*<\/div>\s*<\/section>)/s';
            if (preg_match($patternProj, $html)) {
                $html = preg_replace($patternProj, '$1' . "\n" . $projHtml . '</div>' . "\n        </div>\n    </section>", $html);
            }

            // Update animated counter data-target for projects
            $html = preg_replace(
                '/(<span class="cz_counter_val" data-target=")[0-9]+(">[0-9]*<\/span>\s*<\/div>\s*<span class="cz_counter_after">Proyek &amp; Karya Terapan)/',
                '${1}' . $totalProj . '$2',
                $html
            );
        }
    }

    file_put_contents($htmlFile, $html);
    return ['success' => true, 'message' => 'index.html dan halaman proyek berhasil disinkronkan!'];
}


