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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Egg Detection</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/View/Assets/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            padding-bottom: 90px;
            margin: 0;
            min-height: 100vh;
        }
        
        @media (min-width: 768px) {
            body {
                padding-bottom: 20px;
            }
        }
        
        .top-bar {
            background: linear-gradient(135deg, #FF9F1C 0%, #FF8C00 100%);
            color: white;
            padding: 15px 20px;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .back-button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .back-button img {
            width: 25px;
            height: 25px;
            filter: brightness(0) invert(1);
        }
        
        .main-container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .ai-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .camera-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
        }
        
        #video, #canvas {
            width: 100%;
            height: auto;
            display: block;
        }
        
        #canvas {
            display: none;
        }
        
        .controls {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-control {
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(107, 44, 44, 0.3);
        }
        
        .btn-control:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(107, 44, 44, 0.4);
        }
        
        .btn-control:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .btn-capture {
            background: linear-gradient(135deg, #F39C12 0%, #D68910 100%);
        }
        
        .btn-upload {
            background: linear-gradient(135deg, #27AE60 0%, #1E8449 100%);
        }
        
        .result-container {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #6B2C2C 0%, #4A1F1F 100%);
            border-radius: 15px;
            color: white;
            text-align: center;
            display: none;
        }
        
        .result-container.show {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .egg-count {
            font-size: 3rem;
            font-weight: 700;
            margin: 20px 0;
            color: #F39C12;
        }
        
        .egg-icon {
            font-size: 4rem;
            margin-bottom: 10px;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
            display: none;
        }
        
        .loading.show {
            display: block;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #F39C12;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #fileInput {
            display: none;
        }
        
        .mode-toggle {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }
        
        .mode-btn {
            padding: 10px 20px;
            border: 2px solid #6B2C2C;
            background: white;
            color: #6B2C2C;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .mode-btn.active {
            background: #6B2C2C;
            color: white;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../Components/sidebar-staff.php'; ?>
    
    <div class="top-bar">
        <button class="back-button" onclick="window.location.href='egg.php'">
            <img src="<?= BASE_URL ?>/View/Assets/icons/back.png" alt="Back">
        </button>
        <span>🥚 AI Egg Detection</span>
        <div style="width: 30px;"></div>
    </div>

    <div class="main-container">
        <div class="ai-card">
            <h5 style="text-align: center; margin-bottom: 20px; color: #6B2C2C; font-weight: 700;">
                Detect Egg Count Automatically
            </h5>
            
            <div class="mode-toggle">
                <button class="mode-btn active" onclick="switchMode('camera')" id="cameraModeBtn">
                    📷 Camera
                </button>
                <button class="mode-btn" onclick="switchMode('upload')" id="uploadModeBtn">
                    📁 Upload
                </button>
            </div>
            
            <div class="camera-container">
                <video id="video" autoplay playsinline></video>
                <canvas id="canvas"></canvas>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p>Processing image...</p>
            </div>
            
            <div class="result-container" id="result">
                <div class="egg-icon">🥚</div>
                <h6 style="margin: 0 0 10px 0; font-size: 1.2rem;">Detected Eggs</h6>
                <div class="egg-count" id="eggCount">0</div>
                <p style="margin: 0; opacity: 0.9; font-size: 0.9rem;">eggs detected in the image</p>
            </div>
            
            <div class="controls">
                <input type="file" id="fileInput" accept="image/*" capture="environment">
                <button class="btn-control" id="startBtn" onclick="startCamera()">Start Camera</button>
                <button class="btn-control btn-capture" id="captureBtn" onclick="captureAndDetect()" style="display: none;">Capture & Detect</button>
                <button class="btn-control btn-upload" id="uploadBtn" onclick="document.getElementById('fileInput').click()" style="display: none;">Choose Image</button>
                <button class="btn-control" id="stopBtn" onclick="stopCamera()" style="display: none;">Stop Camera</button>
            </div>
        </div>
        
        <div class="ai-card" style="background: linear-gradient(135deg, rgba(107, 44, 44, 0.1), rgba(74, 31, 31, 0.1));">
            <h6 style="font-weight: 700; color: #6B2C2C; margin-bottom: 15px;">📖 How to Use</h6>
            <ul style="color: #666; line-height: 1.8;">
                <li><strong>Camera Mode:</strong> Start camera, position eggs in frame, then capture to detect</li>
                <li><strong>Upload Mode:</strong> Choose an image from your gallery to detect eggs</li>
                <li>Make sure eggs are clearly visible and well-lit for best results</li>
                <li>The AI will count round/oval objects that look like eggs</li>
                <li><strong>Note:</strong> Camera access requires secure connection (HTTPS) or localhost. If camera doesn't work, please use Upload mode.</li>
            </ul>
        </div>
    </div>

    <?php include __DIR__ . '/../../Components/bottom-nav-staff.php'; ?>

    <!-- TensorFlow.js for image processing -->
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0"></script>

    <script>
        let video, canvas, ctx, stream = null, currentMode = 'camera';
        
        // Initialize after DOM loads
        window.addEventListener('DOMContentLoaded', function() {
            console.log('AI Detection Page Loaded');
            
            video = document.getElementById('video');
            canvas = document.getElementById('canvas');
            ctx = canvas.getContext('2d');
            
            // Test if elements exist
            if (!video || !canvas) {
                console.error('Required elements not found!');
                return;
            }
            
            console.log('Elements ready');
            
            // Setup file input listener
            document.getElementById('fileInput').addEventListener('change', handleFileUpload);
        });
        
        // Make functions globally accessible
        window.startCamera = startCamera;
        window.stopCamera = stopCamera;
        window.captureAndDetect = captureAndDetect;
        window.switchMode = switchMode;

        // Egg detection using custom circle and color detection
        async function detectEggs(imageElement) {
            try {
                // Create temporary canvas for processing
                const tempCanvas = document.createElement('canvas');
                const tempCtx = tempCanvas.getContext('2d');
                
                // Set canvas size
                tempCanvas.width = imageElement.width;
                tempCanvas.height = imageElement.height;
                tempCtx.drawImage(imageElement, 0, 0);
                
                const imageData = tempCtx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
                const data = imageData.data;
                
                // Convert to grayscale and detect edges
                const grayData = new Uint8Array(tempCanvas.width * tempCanvas.height);
                for (let i = 0; i < data.length; i += 4) {
                    const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                    grayData[i / 4] = gray;
                }
                
                // Detect circles using custom algorithm
                const detectedEggs = detectCircularObjects(
                    grayData, 
                    data,
                    tempCanvas.width, 
                    tempCanvas.height
                );
                
                // Draw bounding boxes on main canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.drawImage(imageElement, 0, 0, canvas.width, canvas.height);
                
                detectedEggs.forEach((egg, index) => {
                    // Draw bounding box
                    ctx.strokeStyle = '#F39C12';
                    ctx.lineWidth = 3;
                    ctx.strokeRect(egg.x, egg.y, egg.width, egg.height);
                    
                    // Draw label
                    ctx.fillStyle = '#F39C12';
                    ctx.font = 'bold 16px Montserrat';
                    ctx.fillText(`🥚 ${index + 1}`, egg.x, egg.y > 20 ? egg.y - 5 : egg.y + 20);
                    
                    // Draw confidence circle
                    ctx.beginPath();
                    ctx.arc(egg.centerX, egg.centerY, 5, 0, 2 * Math.PI);
                    ctx.fillStyle = '#F39C12';
                    ctx.fill();
                });
                
                return detectedEggs.length;
                
            } catch (error) {
                console.error('Error detecting eggs:', error);
                throw error;
            }
        }

        // Custom circle detection optimized for eggs
        function detectCircularObjects(grayData, colorData, width, height) {
            const detectedObjects = [];
            const minRadius = Math.min(width, height) * 0.05; // Min 5% of image size
            const maxRadius = Math.min(width, height) * 0.20; // Max 20% of image size
            const stepSize = 30; // Larger step for less false positives
            
            // Create edge map using Sobel operator
            const edges = detectEdges(grayData, width, height);
            
            // Scan image for circular objects
            for (let y = Math.floor(maxRadius * 1.5); y < height - maxRadius * 1.5; y += stepSize) {
                for (let x = Math.floor(maxRadius * 1.5); x < width - maxRadius * 1.5; x += stepSize) {
                    const idx = y * width + x;
                    
                    // Get color at this point
                    const colorIdx = idx * 4;
                    const r = colorData[colorIdx];
                    const g = colorData[colorIdx + 1];
                    const b = colorData[colorIdx + 2];
                    
                    // Check if color matches egg colors (white, cream, brown)
                    if (!isEggColor(r, g, b)) continue;
                    
                    // Try to find circular region around this point
                    let bestRadius = 0;
                    let bestScore = 0;
                    
                    for (let radius = minRadius; radius <= maxRadius; radius += 8) {
                        const score = calculateCircularityScore(
                            edges, colorData, x, y, radius, width, height
                        );
                        
                        if (score > bestScore) {
                            bestScore = score;
                            bestRadius = radius;
                        }
                    }
                    
                    // Only accept high confidence detections
                    if (bestScore > 0.75) {
                        // Check if this is not already detected
                        const overlap = detectedObjects.some(obj => {
                            const dist = Math.sqrt(
                                Math.pow(obj.centerX - x, 2) + 
                                Math.pow(obj.centerY - y, 2)
                            );
                            return dist < (obj.radius + bestRadius) * 0.7;
                        });
                        
                        if (!overlap) {
                            detectedObjects.push({
                                centerX: x,
                                centerY: y,
                                radius: bestRadius,
                                x: x - bestRadius,
                                y: y - bestRadius,
                                width: bestRadius * 2,
                                height: bestRadius * 2,
                                score: bestScore
                            });
                        }
                    }
                }
            }
            
            // Filter and refine detections
            return refineDetections(detectedObjects, width, height);
        }

        // Sobel edge detection
        function detectEdges(grayData, width, height) {
            const edges = new Uint8Array(width * height);
            
            for (let y = 1; y < height - 1; y++) {
                for (let x = 1; x < width - 1; x++) {
                    const idx = y * width + x;
                    
                    // Sobel X
                    const gx = 
                        -1 * grayData[(y-1)*width + (x-1)] + 
                        1 * grayData[(y-1)*width + (x+1)] +
                        -2 * grayData[y*width + (x-1)] + 
                        2 * grayData[y*width + (x+1)] +
                        -1 * grayData[(y+1)*width + (x-1)] + 
                        1 * grayData[(y+1)*width + (x+1)];
                    
                    // Sobel Y
                    const gy = 
                        -1 * grayData[(y-1)*width + (x-1)] + 
                        -2 * grayData[(y-1)*width + x] +
                        -1 * grayData[(y-1)*width + (x+1)] +
                        1 * grayData[(y+1)*width + (x-1)] + 
                        2 * grayData[(y+1)*width + x] +
                        1 * grayData[(y+1)*width + (x+1)];
                    
                    edges[idx] = Math.sqrt(gx * gx + gy * gy);
                }
            }
            
            return edges;
        }

        // Check if color matches typical egg colors
        function isEggColor(r, g, b) {
            // White eggs: high RGB values, relatively equal
            if (r > 200 && g > 200 && b > 200 && 
                Math.abs(r - g) < 30 && Math.abs(g - b) < 30) return true;
            
            // Cream/beige eggs - warmer whites
            if (r > 210 && g > 190 && b > 160 && 
                r > g && g > b && (r - b) < 60) return true;
            
            // Brown eggs - must have proper brown tone
            if (r > 140 && r < 200 && 
                g > 90 && g < 160 && 
                b > 60 && b < 130 && 
                r > g && g > b && 
                (r - g) > 20 && (g - b) > 20) return true;
            
            // Light brown/tan eggs
            if (r > 180 && r < 220 && 
                g > 150 && g < 200 && 
                b > 120 && b < 180 && 
                r > g && g > b) return true;
            let interiorConsistency = 0;
            const samples = 48; // Sample 48 points around circle
            
            // Sample reference color at center
            const centerIdx = (cy * width + cx) * 4;
            const refR = colorData[centerIdx];
            const refG = colorData[centerIdx + 1];
            const refB = colorData[centerIdx + 2];
            
            // Check if center color is egg-like
            if (!isEggColor(refR, refG, refB)) {
                return 0;
            }
            
            // Check edge of circle
            for (let i = 0; i < samples; i++) {
                const angle = (i / samples) * 2 * Math.PI;
                const x = Math.round(cx + radius * Math.cos(angle));
                const y = Math.round(cy + radius * Math.sin(angle));
                
                if (x >= 0 && x < width && y >= 0 && y < height) {
                    totalPoints++;
                    const idx = y * width + x;
                    
                    // Check edge strength at perimeter
                    if (edges[idx] > 40) {
                        edgePoints++;
                    }
                    
                    // Check color consistency at perimeter
                    const colorIdx = idx * 4;
                    const colorDiff = Math.abs(colorData[colorIdx] - refR) +
                                     Math.abs(colorData[colorIdx + 1] - refG) +
                                     Math.abs(colorData[colorIdx + 2] - refB);
                    
                    if (colorDiff < 80) {
                        colorConsistency++;
                    }
                }
            }
            
            // Check interior uniformity
            const interiorSa (highest first)
            detections.sort((a, b) => b.score - a.score);
            
            const refined = [];
            const minSize = Math.min(width, height) * 0.05;
            
            for (const det of detections) {
                // Reject if too close to border
                const border = det.radius * 0.5;
                if (det.x < border || det.y < border || 
                    det.x + det.width > width - border || 
                    det.y + det.height > height - border) {
                    continue;
                }
                
                // Reject if too small or too large
                if (det.radius < minSize || det.radius > Math.min(width, height) * 0.2) {
                    continue;
                }
                
                // Check overlap with existing detections (non-maximum suppression)
                let hasOverlap = false;
                for (const existing of refined) {
                    const dist = Math.sqrt(
                        Math.pow(existing.centerX - det.centerX, 2) + 
                        Math.pow(existing.centerY - det.centerY, 2)
                    );
                    const maxDist = Math.max(existing.radius, det.radius) * 1.2;
                    
                    if (dist < maxDist) {
                        hasOverlap = true;
                        break;
                    }
                }
                
                // Only keep high-confidence, non-overlapping detections
                if (!hasOverlap && det.score > 0.75 && refined.length < 20) {
            return (edgeScore * 0.3 + perimeterColorScore * 0.35 + interiorScore * 0.35
                        colorConsistency++;
                    }
                }
            }
            
            const edgeScore = edgePoints / totalPoints;
            const colorScore = colorConsistency / totalPoints;
            
            return (edgeScore * 0.6 + colorScore * 0.4);
        }

        // Refine and remove duplicate detections
        function refineDetections(detections, width, height) {
            // Sort by score
            detections.sort((a, b) => b.score - a.score);
            
            const refined = [];
            
            for (const det of detections) {
                // Check if too close to border
                if (det.x < 10 || det.y < 10 || 
                    det.x + det.width > width - 10 || 
                    det.y + det.height > height - 10) {
                    continue;
                }
                
                // Check overlap with existing detections
                let hasOverlap = false;
                for (const existing of refined) {
                    const dist = Math.sqrt(
                        Math.pow(existing.centerX - det.centerX, 2) + 
                        Math.pow(existing.centerY - det.centerY, 2)
                    );
                    const minDist = (existing.radius + det.radius) * 0.5;
                    
                    if (dist < minDist) {
                        hasOverlap = true;
                        break;
                    }
                }
                
                if (!hasOverlap && refined.length < 50) { // Max 50 eggs
                    refined.push(det);
                }
            }
            
            return refined;
        }

        function switchMode(mode) {
            console.log('Switching to mode:', mode);
            currentMode = mode;
            
            if (mode === 'camera') {
                document.getElementById('cameraModeBtn').classList.add('active');
                document.getElementById('uploadModeBtn').classList.remove('active');
                document.getElementById('startBtn').style.display = 'inline-block';
                document.getElementById('uploadBtn').style.display = 'none';
                if (video) video.style.display = 'block';
                if (canvas) canvas.style.display = 'none';
            } else {
                document.getElementById('uploadModeBtn').classList.add('active');
                document.getElementById('cameraModeBtn').classList.remove('active');
                document.getElementById('startBtn').style.display = 'none';
                document.getElementById('captureBtn').style.display = 'none';
                document.getElementById('stopBtn').style.display = 'none';
                document.getElementById('uploadBtn').style.display = 'inline-block';
                stopCamera();
            }
            
            document.getElementById('result').classList.remove('show');
        }

        async function startCamera() {
            console.log('Start camera clicked');
            
            // Check if getUserMedia is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Your browser does not support camera access. Please use the Upload mode instead or try a different browser.');
                switchMode('upload');
                return;
            }
            
            try {
                // Request camera with better mobile support
                const constraints = {
                    video: { 
                        facingMode: 'environment',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                };
                
                stream = await navigator.mediaDevices.getUserMedia(constraints);
                video.srcObject = stream;
                
                // Wait for video to be ready
                video.onloadedmetadata = () => {
                    video.play();
                };
                
                video.style.display = 'block';
                canvas.style.display = 'none';
                
                document.getElementById('startBtn').style.display = 'none';
                document.getElementById('captureBtn').style.display = 'inline-block';
                document.getElementById('stopBtn').style.display = 'inline-block';
            } catch (error) {
                console.error('Error accessing camera:', error);
                let errorMessage = 'Unable to access camera.\n\n';
                
                if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                    errorMessage += 'Camera permission was denied. Please:\n1. Allow camera access when prompted\n2. Check browser settings\n3. Use Upload mode instead';
                } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                    errorMessage += 'No camera found. Please use Upload mode instead.';
                } else if (error.name === 'NotReadableError' || error.name === 'TrackStartError') {
                    errorMessage += 'Camera is already in use. Please close other apps using the camera.';
                } else if (error.name === 'NotSupportedError') {
                    errorMessage += 'Camera access requires HTTPS or localhost.\n\nPlease access via:\n- http://localhost/Turtel\n- http://127.0.0.1/Turtel\n\nOr use Upload mode instead.';
                } else {
                    errorMessage += error.message + '\n\nPlease use Upload mode instead.';
                }
                
                alert(errorMessage);
                
                // Auto switch to upload mode
                switchMode('upload');
            }
        }

        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
            video.srcObject = null;
            
            document.getElementById('startBtn').style.display = 'inline-block';
            document.getElementById('captureBtn').style.display = 'none';
            document.getElementById('stopBtn').style.display = 'none';
        }

        async function captureAndDetect() {
            // Capture image from video
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Show canvas, hide video
            video.style.display = 'none';
            canvas.style.display = 'block';
            
            // Show loading
            document.getElementById('loading').classList.add('show');
            document.getElementById('result').classList.remove('show');
            
            // Detect eggs
            try {
                const eggCount = await detectEggs(canvas);
                
                // Show result
                document.getElementById('loading').classList.remove('show');
                document.getElementById('result').classList.add('show');
                document.getElementById('eggCount').textContent = eggCount;
            } catch (error) {
                document.getElementById('loading').classList.remove('show');
                alert('Error detecting eggs. Please try again.');
            }
        }

        // Handle file upload
        async function handleFileUpload(event) {
            console.log('File upload triggered');
            const file = event.target.files[0];
            if (!file) return;

            const img = new Image();
            const reader = new FileReader();
            
            reader.onload = async (e) => {
                img.src = e.target.result;
                img.onload = async () => {
                    canvas.width = img.width;
                    canvas.height = img.height;
                    ctx.drawImage(img, 0, 0);
                    
                    canvas.style.display = 'block';
                    video.style.display = 'none';
                    
                    document.getElementById('loading').classList.add('show');
                    document.getElementById('result').classList.remove('show');
                    
                    try {
                        const eggCount = await detectEggs(canvas);
                        
                        document.getElementById('loading').classList.remove('show');
                        document.getElementById('result').classList.add('show');
                        document.getElementById('eggCount').textContent = eggCount;
                    } catch (error) {
                        console.error('Error detecting eggs:', error);
                        document.getElementById('loading').classList.remove('show');
                        alert('Error detecting eggs. Please try again.');
                    }
                };
            };
            
            reader.readAsDataURL(file);
        }
    </script>
</body>
</html>
