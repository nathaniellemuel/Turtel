<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../../Connection/Connection.php';
require_once __DIR__ . '/../../../Config/Language.php';

session_start();

// Access control: only allow staff
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL . '/View/pages/auth/index.php' : '/Turtel/View/pages/auth/index.php'));
    exit;
}

$returnTo = $_GET['return'] ?? 'egg';
$returnUrl = ($returnTo === 'egg')
    ? (defined('BASE_URL') ? BASE_URL . '/View/pages/Staff/egg.php' : '/Turtel/View/pages/Staff/egg.php')
    : (defined('BASE_URL') ? BASE_URL . '/View/pages/Staff/egg.php' : '/Turtel/View/pages/Staff/egg.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Egg Counter</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- TensorFlow.js -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.22.0/dist/tf.min.js"></script>

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px;
            margin: 0;
            min-height: 100vh;
        }

        .top-bar {
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            padding: 14px 16px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .top-bar .btn-back {
            background: rgba(255,255,255,0.15);
            border: none;
            color: white;
            font-weight: 800;
            padding: 10px 12px;
            border-radius: 12px;
            cursor: pointer;
        }

        .container-main {
            padding: 16px;
        }

        .card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .preview {
            background: #111;
            position: relative;
            aspect-ratio: 4 / 3;
            width: 100%;
        }

        .preview > video,
        .preview > .img-preview,
        .preview > #overlayCanvas {
            position: absolute;
            inset: 0;
        }

        video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-preview {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
            background: #111;
        }

        #overlayCanvas {
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        canvas {
            display: none;
        }

        .overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            padding: 12px;
            pointer-events: none;
        }

        .badge-result {
            background: rgba(0,0,0,0.55);
            color: white;
            font-weight: 800;
            font-size: 0.95rem;
            border-radius: 14px;
            padding: 10px 12px;
            backdrop-filter: blur(10px);
        }

        .controls {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 14px;
        }

        .controls button {
            border: none;
            border-radius: 14px;
            padding: 12px 12px;
            font-weight: 800;
        }

        .btn-primary-egg {
            background: #6B2C2C;
            color: white;
        }

        .btn-accent {
            background: #F39C12;
            color: white;
        }

        .btn-outline-soft {
            background: rgba(107, 44, 44, 0.08);
            color: #6B2C2C;
        }

        .status {
            padding: 12px 14px;
            color: #444;
            font-size: 0.9rem;
        }

        .upload-row {
            padding: 0 14px 14px 14px;
        }

        .upload-card {
            background: white;
            border-radius: 14px;
            padding: 12px;
            border: 1px solid rgba(0,0,0,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .upload-card .upload-info {
            font-size: 0.85rem;
            color: #444;
        }

        .upload-card .upload-info b {
            display: block;
            font-size: 0.9rem;
            color: #222;
            margin-bottom: 2px;
        }

        .btn-upload {
            background: #F39C12;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 10px 12px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
        }

        .btn-upload:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .file-input-hidden {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .note {
            margin-top: 12px;
            font-size: 0.8rem;
            color: #666;
            line-height: 1.4;
        }

        @media (min-width: 768px) {
            body { padding-bottom: 20px; }
            .container-main { max-width: 720px; margin: 0 auto; }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../Components/sidebar-staff.php'; ?>

    <div class="top-bar">
        <button class="btn-back" type="button" onclick="goBack()">← Back</button>
        <div>AI Egg Counter</div>
        <div style="width: 70px"></div>
    </div>

    <div class="container-main">
        <div class="card">
            <div class="preview">
                <video id="video" playsinline autoplay muted></video>
                <img id="imgPreview" class="img-preview" alt="Uploaded" />
                <canvas id="overlayCanvas"></canvas>
                <canvas id="canvas"></canvas>
                <div class="overlay">
                    <div class="badge-result" id="resultBadge">Detected: -</div>
                </div>
            </div>

            <div class="controls">
                <button type="button" class="btn-primary-egg" id="btnStart">Start Camera</button>
                <button type="button" class="btn-outline-soft" id="btnStop" disabled>Stop</button>
                <button type="button" class="btn-accent" id="btnCapture" disabled>Capture & Count</button>
                <button type="button" class="btn-outline-soft" id="btnAuto" disabled>Auto: Off</button>
                <button type="button" class="btn-primary-egg" id="btnUse" disabled>Use Result</button>
                <button type="button" class="btn-outline-soft" id="btnSwitch" disabled>Switch Camera</button>
            </div>

            <div class="upload-row">
                <div class="upload-card">
                    <div class="upload-info">
                        <b>Upload Foto (Mobile)</b>
                        Bisa dari galeri atau ambil foto langsung.
                    </div>
                    <input id="fileInput" class="file-input-hidden" type="file" accept="image/*" capture="environment" />
                    <label class="btn-upload" id="btnPick" for="fileInput">Choose Photo</label>
                </div>
            </div>

            <div class="status" id="statusText">Ready. Tap “Start Camera”.</div>
        </div>

        <div class="note">
            Catatan: Deteksi ini adalah <b>estimasi dari gambar</b> 
            Kamera biasanya hanya bisa berjalan di <b>https</b> atau <b>http://localhost</b>.
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>

    <script>
        const RETURN_URL = <?= json_encode($returnUrl) ?>;

        const video = document.getElementById('video');
        const imgPreview = document.getElementById('imgPreview');
        const overlayCanvas = document.getElementById('overlayCanvas');
        const canvas = document.getElementById('canvas');
        const resultBadge = document.getElementById('resultBadge');
        const statusText = document.getElementById('statusText');

        const previewEl = document.querySelector('.preview');
        const overlayCtx = overlayCanvas.getContext('2d');

        const btnStart = document.getElementById('btnStart');
        const btnStop = document.getElementById('btnStop');
        const btnCapture = document.getElementById('btnCapture');
        const btnAuto = document.getElementById('btnAuto');
        const btnUse = document.getElementById('btnUse');
        const btnSwitch = document.getElementById('btnSwitch');
        const fileInput = document.getElementById('fileInput');
        const btnPick = document.getElementById('btnPick');

        let stream = null;
        let autoTimer = null;
        let lastCount = null;
        let currentFacingMode = 'environment';
        let currentPreviewUrl = null;
        let lastDetections = null;

        function isLikelyInsecureOrigin() {
            try {
                const host = window.location.hostname;
                const isLocal = host === 'localhost' || host === '127.0.0.1' || host === '::1';
                return window.location.protocol !== 'https:' && !isLocal;
            } catch (_) {
                return true;
            }
        }

        function getUserMediaCompat(constraints) {
            // Modern API
            if (navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function') {
                return navigator.mediaDevices.getUserMedia(constraints);
            }

            // Legacy iOS / older Android WebView fallbacks
            const legacy = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
            if (legacy) {
                return new Promise((resolve, reject) => {
                    legacy.call(navigator, constraints, resolve, reject);
                });
            }

            return Promise.reject(new Error('getUserMedia is not available in this browser/origin'));
        }

        function setStatus(text) {
            statusText.textContent = text;
        }

        function setResult(count) {
            lastCount = count;
            resultBadge.textContent = `Detected: ${count}`;
            btnUse.disabled = !(Number.isFinite(count) && count >= 0);
        }

        function resizeOverlayCanvas() {
            const dpr = window.devicePixelRatio || 1;
            const w = Math.max(1, Math.floor(previewEl.clientWidth * dpr));
            const h = Math.max(1, Math.floor(previewEl.clientHeight * dpr));
            if (overlayCanvas.width !== w || overlayCanvas.height !== h) {
                overlayCanvas.width = w;
                overlayCanvas.height = h;
            }
            overlayCtx.setTransform(dpr, 0, 0, dpr, 0, 0);
        }

        function clearOverlay() {
            resizeOverlayCanvas();
            overlayCtx.clearRect(0, 0, previewEl.clientWidth, previewEl.clientHeight);
        }

        function drawDetections(detections, sourceW, sourceH) {
            clearOverlay();
            if (!detections || !detections.length) return;

            const pw = previewEl.clientWidth;
            const ph = previewEl.clientHeight;
            const scale = Math.min(pw / sourceW, ph / sourceH);
            const drawW = sourceW * scale;
            const drawH = sourceH * scale;
            const offX = (pw - drawW) / 2;
            const offY = (ph - drawH) / 2;

            overlayCtx.lineWidth = 3;
            overlayCtx.strokeStyle = '#F39C12';
            overlayCtx.fillStyle = 'rgba(243, 156, 18, 0.10)';

            for (const c of detections) {
                const x = offX + c.x * scale;
                const y = offY + c.y * scale;
                const r = c.r * scale;
                overlayCtx.beginPath();
                overlayCtx.arc(x, y, r, 0, Math.PI * 2);
                overlayCtx.fill();
                overlayCtx.stroke();
            }
        }

        function showVideoMode() {
            imgPreview.style.display = 'none';
            video.style.display = 'block';
            clearOverlay();
            if (currentPreviewUrl) {
                try { URL.revokeObjectURL(currentPreviewUrl); } catch (_) {}
                currentPreviewUrl = null;
            }
        }

        function showImageMode(objectUrl) {
            video.style.display = 'none';
            imgPreview.style.display = 'block';
            imgPreview.src = objectUrl;
            clearOverlay();
        }

        function goBack() {
            window.location.href = RETURN_URL;
        }

        function showImagePreview() {
            // Show the canvas as a quick preview by swapping into video element via poster-like trick
            // We keep <video> but it's OK because image is drawn into canvas for analysis.
            // For UX: just update the badge.
        }

        async function startCamera() {
            try {
                if (isLikelyInsecureOrigin()) {
                    setStatus('Camera blocked: buka via HTTPS atau http://localhost (bukan IP LAN).');
                    return;
                }

                if (!window.isSecureContext) {
                    // Some browsers still expose mediaDevices in non-secure contexts but will fail.
                    setStatus('Camera membutuhkan secure context (HTTPS/localhost).');
                    return;
                }

                setStatus('Requesting camera permission...');

                showVideoMode();

                if (stream) {
                    stopCamera();
                }

                const constraints = {
                    audio: false,
                    video: {
                        facingMode: { ideal: currentFacingMode },
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                };

                stream = await getUserMediaCompat(constraints);
                video.srcObject = stream;

                btnStop.disabled = false;
                btnCapture.disabled = false;
                btnAuto.disabled = false;
                btnSwitch.disabled = false;

                setStatus('Camera started. You can capture or enable auto mode.');
            } catch (err) {
                console.error(err);

                const msg = (err?.message ?? String(err));
                // Extra hints for common mobile failures
                if (!navigator.mediaDevices) {
                    setStatus('Camera tidak tersedia. Pastikan buka via HTTPS/localhost dan pakai Chrome/Safari terbaru.');
                } else if (String(msg).toLowerCase().includes('permission')) {
                    setStatus('Izin kamera ditolak. Aktifkan permission kamera di browser.');
                } else {
                    setStatus('Failed to start camera: ' + msg);
                }
            }
        }

        function stopCamera() {
            if (autoTimer) {
                clearInterval(autoTimer);
                autoTimer = null;
                btnAuto.textContent = 'Auto: Off';
            }

            if (stream) {
                for (const track of stream.getTracks()) {
                    track.stop();
                }
                stream = null;
            }

            video.srcObject = null;
            // keep whatever preview mode is currently active
            btnStop.disabled = true;
            btnCapture.disabled = true;
            btnAuto.disabled = true;
            btnSwitch.disabled = true;
            btnUse.disabled = true;
            setResult(NaN);
            clearOverlay();
            setStatus('Camera stopped.');
        }

        function switchCamera() {
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            startCamera();
        }

        function captureToCanvas() {
            const width = video.videoWidth;
            const height = video.videoHeight;
            if (!width || !height) {
                return false;
            }
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(video, 0, 0, width, height);
            return true;
        }

        async function loadImageToCanvas(file) {
            const url = URL.createObjectURL(file);
            // keep the URL alive for preview until replaced
            if (currentPreviewUrl) {
                try { URL.revokeObjectURL(currentPreviewUrl); } catch (_) {}
            }
            currentPreviewUrl = url;

            const img = new Image();
            img.decoding = 'async';
            img.src = url;
            await new Promise((resolve, reject) => {
                img.onload = resolve;
                img.onerror = reject;
            });

            showImageMode(url);

            // Fit image into canvas
            const maxW = 1280;
            const scale = Math.min(1, maxW / img.width);
            const w = Math.max(1, Math.round(img.width * scale));
            const h = Math.max(1, Math.round(img.height * scale));

            canvas.width = w;
            canvas.height = h;
            const ctx = canvas.getContext('2d', { willReadFrequently: true });
            ctx.drawImage(img, 0, 0, w, h);
            return true;
        }

        function percentileFromHistogram(hist, total, p) {
            // p in [0,1]
            const target = Math.floor(total * p);
            let acc = 0;
            for (let i = 0; i < 256; i++) {
                acc += hist[i];
                if (acc >= target) return i;
            }
            return 255;
        }

        function otsuThresholdFromHistogram(hist, total) {
            let sum = 0;
            for (let i = 0; i < 256; i++) sum += i * hist[i];

            let sumB = 0;
            let wB = 0;
            let maxVar = 0;
            let threshold = 127;

            for (let i = 0; i < 256; i++) {
                wB += hist[i];
                if (wB === 0) continue;
                const wF = total - wB;
                if (wF === 0) break;

                sumB += i * hist[i];
                const mB = sumB / wB;
                const mF = (sum - sumB) / wF;

                const between = wB * wF * (mB - mF) * (mB - mF);
                if (between > maxVar) {
                    maxVar = between;
                    threshold = i;
                }
            }

            return threshold;
        }

        function dilate(bin, w, h) {
            const out = new Uint8Array(bin.length);
            for (let y = 1; y < h - 1; y++) {
                for (let x = 1; x < w - 1; x++) {
                    const idx = y * w + x;
                    let on = 0;
                    for (let dy = -1; dy <= 1; dy++) {
                        for (let dx = -1; dx <= 1; dx++) {
                            if (bin[idx + dy * w + dx]) { on = 1; break; }
                        }
                        if (on) break;
                    }
                    out[idx] = on;
                }
            }
            return out;
        }

        function erode(bin, w, h) {
            const out = new Uint8Array(bin.length);
            for (let y = 1; y < h - 1; y++) {
                for (let x = 1; x < w - 1; x++) {
                    const idx = y * w + x;
                    let on = 1;
                    for (let dy = -1; dy <= 1; dy++) {
                        for (let dx = -1; dx <= 1; dx++) {
                            if (!bin[idx + dy * w + dx]) { on = 0; break; }
                        }
                        if (!on) break;
                    }
                    out[idx] = on;
                }
            }
            return out;
        }

        function clearBorderForeground(bin, w, h, margin = 1) {
            // Removes any foreground components touching the image border.
            // Useful for white background photos where border shadows/noise become foreground.
            const out = new Uint8Array(bin);
            const visited = new Uint8Array(out.length);
            const stack = [];

            function push(idx) {
                if (!out[idx] || visited[idx]) return;
                visited[idx] = 1;
                stack.push(idx);
            }

            // top & bottom rows
            for (let x = 0; x < w; x++) {
                for (let m = 0; m <= margin; m++) {
                    push(m * w + x);
                    push((h - 1 - m) * w + x);
                }
            }
            // left & right cols
            for (let y = 0; y < h; y++) {
                for (let m = 0; m <= margin; m++) {
                    push(y * w + m);
                    push(y * w + (w - 1 - m));
                }
            }

            while (stack.length) {
                const idx = stack.pop();
                out[idx] = 0;
                const x = idx % w;
                const y = (idx - x) / w;
                for (let dy = -1; dy <= 1; dy++) {
                    for (let dx = -1; dx <= 1; dx++) {
                        if (dx === 0 && dy === 0) continue;
                        const nx = x + dx;
                        const ny = y + dy;
                        if (nx < 0 || ny < 0 || nx >= w || ny >= h) continue;
                        const nidx = ny * w + nx;
                        push(nidx);
                    }
                }
            }

            return out;
        }

        function countConnectedComponents(bin, w, h, opts = {}) {
            const visited = new Uint8Array(bin.length);
            const components = [];

            const imgArea = w * h;
            // More strict base min area to avoid salt noise on white backgrounds
            const minAreaFactor = Number.isFinite(opts.minAreaFactor) ? opts.minAreaFactor : 1.0;
            const fracMinOpt = Number.isFinite(opts.fracMin) ? opts.fracMin : 0.22;
            const minArea = Math.max(120, Math.floor(imgArea * 0.0008 * minAreaFactor));
            const maxArea = Math.floor(imgArea * 0.20);

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const start = y * w + x;
                    if (!bin[start] || visited[start]) continue;

                    let area = 0;
                    let minX = x, maxX = x, minY = y, maxY = y;
                    const queue = [start];
                    visited[start] = 1;

                    while (queue.length) {
                        const idx = queue.pop();
                        area++;
                        const cx = idx % w;
                        const cy = (idx - cx) / w;

                        if (cx < minX) minX = cx;
                        if (cx > maxX) maxX = cx;
                        if (cy < minY) minY = cy;
                        if (cy > maxY) maxY = cy;

                        for (let dy = -1; dy <= 1; dy++) {
                            for (let dx = -1; dx <= 1; dx++) {
                                if (dx === 0 && dy === 0) continue;
                                const nx = cx + dx;
                                const ny = cy + dy;
                                if (nx < 0 || ny < 0 || nx >= w || ny >= h) continue;
                                const nidx = ny * w + nx;
                                if (!bin[nidx] || visited[nidx]) continue;
                                visited[nidx] = 1;
                                queue.push(nidx);
                            }
                        }
                    }

                    const bw = (maxX - minX + 1);
                    const bh = (maxY - minY + 1);
                    const bboxArea = bw * bh;
                    const aspect = bh > 0 ? (bw / bh) : 0;
                    const solidity = bboxArea > 0 ? (area / bboxArea) : 0;

                    const touchesBorder = (minX <= 1 || minY <= 1 || maxX >= w - 2 || maxY >= h - 2);

                    // Basic shape filtering (eggs are compact-ish)
                    const shapeOk = aspect >= 0.40 && aspect <= 2.6 && solidity >= 0.22;
                    const sizeOk = area >= minArea && area <= maxArea;

                    components.push({ area, bw, bh, bboxArea, aspect, solidity, shapeOk, sizeOk, touchesBorder });
                }
            }

            // Candidate components: ignore border-touching + require shape/size
            let candidates = components.filter(c => c.sizeOk && c.shapeOk && !c.touchesBorder);
            if (!candidates.length) {
                // fallback: allow border-touching if nothing else
                candidates = components.filter(c => c.sizeOk && c.shapeOk);
            }
            if (!candidates.length) return 0;

            candidates.sort((a, b) => b.area - a.area);
            const largest = candidates[0].area;

            // Keep only blobs that are a meaningful fraction of the largest blob.
            // This kills the "always 11" issue where many tiny specks are counted.
            const minKeep = Math.max(minArea, Math.floor(largest * fracMinOpt));
            const kept = candidates.filter(c => c.area >= minKeep);

            // If we keep too many, raise threshold further.
            let finalKept = kept;
            if (finalKept.length > 6) {
                const minKeep2 = Math.max(minKeep, Math.floor(largest * 0.30));
                finalKept = candidates.filter(c => c.area >= minKeep2);
            }

            // If eggs are touching, they may become one blob.
            // Estimate split by area using the median of top blobs.
            const topAreas = finalKept.slice(0, 6).map(c => c.area).sort((a,b) => a-b);
            const medianArea = topAreas[Math.floor(topAreas.length / 2)] || largest;

            let count = 0;
            for (const c of finalKept) {
                const ratio = c.area / Math.max(1, medianArea);
                if (ratio < 1.4) count += 1;
                else if (ratio < 2.3) count += 2;
                else count += Math.min(6, Math.round(ratio));
            }

            return count;
        }

        async function estimateEggCountFromCanvas() {
            await tf.ready();
            // Downscale for speed
            const targetW = 320;
            const targetH = 240;

            const tmp = document.createElement('canvas');
            tmp.width = targetW;
            tmp.height = targetH;
            const tctx = tmp.getContext('2d', { willReadFrequently: true });
            tctx.drawImage(canvas, 0, 0, targetW, targetH);

            const imageData = tctx.getImageData(0, 0, targetW, targetH);

            // Original approach: grayscale + blur + contrast stretch + Otsu + morphology + CC
            const gray = tf.tidy(() => {
                const t = tf.browser.fromPixels(imageData).toFloat();
                const g = tf.image.rgbToGrayscale(t).reshape([targetH, targetW]);
                const k = tf.tensor4d([
                    1, 4, 6, 4, 1,
                    4,16,24,16, 4,
                    6,24,36,24, 6,
                    4,16,24,16, 4,
                    1, 4, 6, 4, 1
                ], [5,5,1,1], 'float32').div(256);
                const g4 = g.expandDims(0).expandDims(-1);
                return tf.conv2d(g4, k, 1, 'same').squeeze();
            });

            const grayData = await gray.data();
            gray.dispose();

            const hist0 = new Uint32Array(256);
            for (let i = 0; i < grayData.length; i++) {
                const v0 = Math.max(0, Math.min(255, Math.round(grayData[i])));
                hist0[v0]++;
            }

            const p2 = percentileFromHistogram(hist0, grayData.length, 0.02);
            const p98 = percentileFromHistogram(hist0, grayData.length, 0.98);
            const denom = Math.max(1, (p98 - p2));

            const scaled = new Float32Array(grayData.length);
            const hist = new Uint32Array(256);
            for (let i = 0; i < grayData.length; i++) {
                const v = (grayData[i] - p2) * (255 / denom);
                const vv = Math.max(0, Math.min(255, Math.round(v)));
                scaled[i] = vv;
                hist[vv]++;
            }

            const thr = otsuThresholdFromHistogram(hist, scaled.length);

            let bright = 0;
            for (let i = 0; i < scaled.length; i++) {
                if (scaled[i] >= thr) bright++;
            }
            const invert = bright > scaled.length * 0.6;

            const bin = new Uint8Array(scaled.length);
            for (let i = 0; i < scaled.length; i++) {
                const on = invert ? (scaled[i] < thr) : (scaled[i] >= thr);
                bin[i] = on ? 1 : 0;
            }

            // Morphology: close then stronger open (helps white background speckle)
            let m = dilate(bin, targetW, targetH);   // close: dilate
            m = erode(m, targetW, targetH);          // close: erode

            // remove border-connected junk (shadows/edges)
            m = clearBorderForeground(m, targetW, targetH, 1);

            // open twice: erode->dilate, erode->dilate
            m = erode(m, targetW, targetH);
            m = dilate(m, targetW, targetH);
            m = erode(m, targetW, targetH);
            m = dilate(m, targetW, targetH);

            // Pass 1 (normal)
            let raw = countConnectedComponents(m, targetW, targetH);

            // Pass 2 (stricter) if we over-detect (common on white background)
            if (raw > 3) {
                raw = countConnectedComponents(m, targetW, targetH, { minAreaFactor: 1.35, fracMin: 0.32 });
            }

            // Final clamp as requested: 1–3 only
            if (!Number.isFinite(raw) || raw < 1) raw = 1;
            if (raw > 3) raw = 3;
            return raw;
        }

        function extractComponents(bin, w, h) {
            const visited = new Uint8Array(bin.length);
            const comps = [];

            const imgArea = w * h;
            const minArea = Math.max(60, Math.floor(imgArea * 0.00025));

            for (let y = 0; y < h; y++) {
                for (let x = 0; x < w; x++) {
                    const start = y * w + x;
                    if (!bin[start] || visited[start]) continue;

                    let area = 0;
                    let minX = x, maxX = x, minY = y, maxY = y;
                    const queue = [start];
                    visited[start] = 1;

                    while (queue.length) {
                        const idx = queue.pop();
                        area++;
                        const cx = idx % w;
                        const cy = (idx - cx) / w;

                        if (cx < minX) minX = cx;
                        if (cx > maxX) maxX = cx;
                        if (cy < minY) minY = cy;
                        if (cy > maxY) maxY = cy;

                        for (let dy = -1; dy <= 1; dy++) {
                            for (let dx = -1; dx <= 1; dx++) {
                                if (dx === 0 && dy === 0) continue;
                                const nx = cx + dx;
                                const ny = cy + dy;
                                if (nx < 0 || ny < 0 || nx >= w || ny >= h) continue;
                                const nidx = ny * w + nx;
                                if (!bin[nidx] || visited[nidx]) continue;
                                visited[nidx] = 1;
                                queue.push(nidx);
                            }
                        }
                    }

                    if (area >= minArea) {
                        comps.push({ minX, maxX, minY, maxY, area });
                    }
                }
            }
            return comps;
        }

        function isEdgePixel(bin, w, idx) {
            if (!bin[idx]) return false;
            const x = idx % w;
            if (x === 0 || x === w - 1) return false;
            const up = idx - w;
            const down = idx + w;
            // edge if any 4-neighbor is background
            return (!bin[idx - 1] || !bin[idx + 1] || !bin[up] || !bin[down]);
        }

        function scoreCircleOnMask(bin, w, h, cx, cy, r) {
            // returns { perimeterSupport, insideSupport }
            const steps = 48;
            let periHits = 0;
            let insideHits = 0;

            for (let i = 0; i < steps; i++) {
                const a = (i / steps) * Math.PI * 2;
                const px = Math.round(cx + r * Math.cos(a));
                const py = Math.round(cy + r * Math.sin(a));
                if (px < 1 || py < 1 || px >= w - 1 || py >= h - 1) continue;
                const pidx = py * w + px;
                if (isEdgePixel(bin, w, pidx)) periHits++;

                const ix = Math.round(cx + (r * 0.55) * Math.cos(a));
                const iy = Math.round(cy + (r * 0.55) * Math.sin(a));
                if (ix < 0 || iy < 0 || ix >= w || iy >= h) continue;
                const iidx = iy * w + ix;
                if (bin[iidx]) insideHits++;
            }

            return {
                perimeterSupport: periHits / steps,
                insideSupport: insideHits / steps
            };
        }

        function houghCirclesForComponent(bin, w, h, bbox) {
            const { minX, maxX, minY, maxY } = bbox;
            const bw = maxX - minX + 1;
            const bh = maxY - minY + 1;
            const minDim = Math.min(bw, bh);
            if (minDim < 12) return [];

            // Edge points inside bbox
            const edges = [];
            for (let y = minY + 1; y < maxY; y++) {
                for (let x = minX + 1; x < maxX; x++) {
                    const idx = y * w + x;
                    if (!bin[idx]) continue;
                    const n = bin[idx - 1] & bin[idx + 1] & bin[idx - w] & bin[idx + w];
                    if (!n) {
                        edges.push([x, y]);
                    }
                }
            }
            if (edges.length < 20) return [];

            // Global-ish radius limits (avoid tiny noise circles)
            const globalRMin = 12;
            const globalRMax = 70;
            const rMin = Math.max(globalRMin, Math.floor(minDim * 0.22));
            const rMax = Math.min(globalRMax, Math.max(rMin + 2, Math.floor(minDim * 0.60)));
            if (rMax <= rMin) return [];

            // Precompute angles
            const steps = 24;
            const sin = new Float32Array(steps);
            const cos = new Float32Array(steps);
            for (let i = 0; i < steps; i++) {
                const a = (i / steps) * Math.PI * 2;
                sin[i] = Math.sin(a);
                cos[i] = Math.cos(a);
            }

            const circles = [];

            for (let r = rMin; r <= rMax; r += 2) {
                const acc = new Uint16Array(bw * bh);

                for (let i = 0; i < edges.length; i++) {
                    const ex = edges[i][0];
                    const ey = edges[i][1];
                    for (let t = 0; t < steps; t++) {
                        const cx = Math.round(ex - r * cos[t]);
                        const cy = Math.round(ey - r * sin[t]);
                        if (cx < minX || cx > maxX || cy < minY || cy > maxY) continue;
                        const ax = cx - minX;
                        const ay = cy - minY;
                        acc[ay * bw + ax]++;
                    }
                }

                // Find peaks
                let maxVote = 0;
                for (let i = 0; i < acc.length; i++) {
                    if (acc[i] > maxVote) maxVote = acc[i];
                }
                if (maxVote < 18) continue;

                // Stricter threshold to avoid many false circles
                const threshold = Math.max(24, Math.floor(maxVote * 0.65));

                // Collect candidates
                const candidates = [];
                for (let ay = 0; ay < bh; ay++) {
                    for (let ax = 0; ax < bw; ax++) {
                        const v = acc[ay * bw + ax];
                        if (v < threshold) continue;
                        candidates.push({ x: ax + minX, y: ay + minY, r, v });
                    }
                }

                candidates.sort((a, b) => b.v - a.v);

                // Non-max suppression + verification on mask
                for (const c of candidates) {
                    if (circles.length >= 6) break;

                    // Verify circle looks like an egg on the mask
                    const sc = scoreCircleOnMask(bin, w, h, c.x, c.y, c.r);
                    if (sc.perimeterSupport < 0.30) continue;
                    if (sc.insideSupport < 0.50) continue;

                    let ok = true;
                    for (const ex of circles) {
                        const dx = c.x - ex.x;
                        const dy = c.y - ex.y;
                        const dist2 = dx * dx + dy * dy;
                        const minDist = Math.max(12, Math.min(c.r, ex.r) * 0.80);
                        if (dist2 < minDist * minDist) {
                            ok = false;
                            break;
                        }
                    }
                    if (!ok) continue;

                    circles.push({ ...c, score: c.v + Math.round(sc.perimeterSupport * 80) + Math.round(sc.insideSupport * 40) });
                }
            }

            // Keep only best circles per component
            circles.sort((a, b) => (b.score ?? b.v) - (a.score ?? a.v));
            return circles.slice(0, 4);
        }

        function estimateCountByCircles(bin, w, h) {
            let comps = extractComponents(bin, w, h);
            if (!comps.length) return { count: 0, circles: [] };

            // Sort by area and ignore tiny noisy components.
            comps.sort((a, b) => b.area - a.area);
            const top = comps.slice(0, 14);
            const areas = top.map(c => c.area).sort((a, b) => a - b);
            const medianArea = areas[Math.floor(areas.length / 2)] || 0;
            const minKeep = Math.max(80, Math.floor(medianArea * 0.35));
            comps = top.filter(c => c.area >= minKeep);
            if (!comps.length) comps = top;

            let total = 0;
            const allCircles = [];
            for (const comp of comps) {
                const bw = comp.maxX - comp.minX + 1;
                const bh = comp.maxY - comp.minY + 1;

                // Quick reject very elongated blobs
                const aspect = bw / Math.max(1, bh);
                if (aspect < 0.25 || aspect > 4.0) continue;

                const rEst = Math.max(6, Math.min(bw, bh) * 0.28);
                const oneArea = Math.PI * rEst * rEst;
                const areaRatio = comp.area / Math.max(1, oneArea);

                const circles = houghCirclesForComponent(bin, w, h, comp);
                if (circles.length) {
                    // If eggs touch, Hough sometimes returns 1 circle for a 2-egg blob.
                    // Use area as a tie-breaker.
                    if (circles.length === 1 && areaRatio > 1.65) {
                        total += 2;
                        const c = circles[0];
                        allCircles.push({ x: c.x, y: c.y, r: c.r });
                        // Synthesize a second circle (best-effort) so user sees 2 eggs.
                        const shift = Math.max(6, Math.round(c.r * 0.60));
                        if (bw >= bh) {
                            allCircles.push({ x: Math.min(comp.maxX, c.x + shift), y: c.y, r: c.r });
                        } else {
                            allCircles.push({ x: c.x, y: Math.min(comp.maxY, c.y + shift), r: c.r });
                        }
                    } else {
                        total += circles.length;
                        for (const c of circles) allCircles.push({ x: c.x, y: c.y, r: c.r });
                    }
                } else {
                    total += Math.max(1, Math.round(areaRatio));
                }
            }
            return { count: total, circles: allCircles };
        }

        async function captureAndCount() {
            if (!captureToCanvas()) {
                setStatus('Camera not ready yet.');
                return;
            }

            setStatus('Analyzing image...');
            try {
                const count = await estimateEggCountFromCanvas();
                lastDetections = null;
                setResult(count);
                clearOverlay();
                setStatus('Done. You can “Use Result” to show popup in Egg page.');
            } catch (err) {
                console.error(err);
                setStatus('Analysis failed: ' + (err?.message ?? String(err)));
            }
        }

        async function countFromUpload(file) {
            if (!file) return;
            if (!file.type || !String(file.type).startsWith('image/')) {
                setStatus('File yang dipilih bukan gambar.');
                return;
            }

            const type = String(file.type).toLowerCase();
            if (type.includes('heic') || type.includes('heif')) {
                setStatus('Format foto HEIC/HEIF terdeteksi. Jika gagal diproses, ubah setting kamera ke JPEG (Most Compatible).');
            }

            setStatus('Loading image...');
            try {
                const ok = await loadImageToCanvas(file);
                if (!ok) {
                    setStatus('Failed to load image.');
                    return;
                }
                setStatus('Analyzing image...');
                const count = await estimateEggCountFromCanvas();
                lastDetections = null;
                setResult(count);
                clearOverlay();
                setStatus('Done from uploaded photo. You can “Use Result”.');
            } catch (err) {
                console.error(err);
                const msg = (err?.message ?? String(err));
                setStatus('Upload gagal diproses. Coba foto lain (JPEG/PNG). ' + msg);
            }
        }

        function toggleAuto() {
            if (autoTimer) {
                clearInterval(autoTimer);
                autoTimer = null;
                btnAuto.textContent = 'Auto: Off';
                setStatus('Auto mode off.');
                return;
            }

            btnAuto.textContent = 'Auto: On';
            setStatus('Auto mode on (updates every 1.2s).');
            autoTimer = setInterval(async () => {
                if (!stream) return;
                if (!captureToCanvas()) return;
                try {
                    const count = await estimateEggCountFromCanvas();
                    lastDetections = null;
                    setResult(count);
                    clearOverlay();
                } catch (_) {
                    // ignore
                }
            }, 1200);
        }

        function useResult() {
            if (!Number.isFinite(lastCount)) return;
            try {
                sessionStorage.setItem('turtel_ai_egg_count', String(lastCount));
            } catch (_) {
                // ignore
            }
            // Also pass as query param for compatibility
            const url = new URL(RETURN_URL, window.location.origin);
            url.searchParams.set('ai_count', String(lastCount));
            window.location.href = url.toString();
        }

        btnStart.addEventListener('click', startCamera);
        btnStop.addEventListener('click', stopCamera);
        btnCapture.addEventListener('click', captureAndCount);
        btnAuto.addEventListener('click', toggleAuto);
        btnUse.addEventListener('click', useResult);
        btnSwitch.addEventListener('click', switchCamera);

        fileInput.addEventListener('change', async (e) => {
            const file = e.target.files && e.target.files[0];
            if (!file) return;
            setStatus(`Selected: ${file.name}`);
            await countFromUpload(file);
            // Allow selecting the same file again to retrigger change
            e.target.value = '';
        });

        // Init badge
        setResult(NaN);

        // Keep overlay aligned
        window.addEventListener('resize', () => {
            clearOverlay();
        });

        // Initial overlay sizing
        clearOverlay();

        // Helpful hint for LAN HTTP usage (upload still works)
        if (isLikelyInsecureOrigin()) {
            setStatus('LAN HTTP terdeteksi. Kamera browser diblok, tapi Upload Foto tetap bisa dipakai.');
        }
    </script>
</body>
</html>
