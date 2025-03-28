<?php
// 随机图片API - 完全修复版本
// 图片链接文本文件路径
$linksFile = 'image_links.txt';
$pageTitle = "随机图片API";

// 处理图片请求
if (isset($_GET['imageonly']) && $_GET['imageonly'] == '1') {
    // 检查文件是否存在
    if (!file_exists($linksFile)) {
        header('HTTP/1.1 404 Not Found');
        die('Image links file not found.');
    }

    // 读取并过滤有效的图片URL
    $imageUrls = file($linksFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $validUrls = [];
    
    foreach ($imageUrls as $url) {
        $url = trim($url);
        if (!empty($url) && strpos($url, '#') !== 0 && filter_var($url, FILTER_VALIDATE_URL)) {
            $validUrls[] = $url;
        }
    }
    
    if (empty($validUrls)) {
        header('HTTP/1.1 404 Not Found');
        die('No valid image URLs found in the file.');
    }

    // 随机选择一个URL
    $randomImageUrl = $validUrls[array_rand($validUrls)];
    
    // 设置超时和用户代理
    $contextOptions = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n",
            'timeout' => 10  // 10秒超时
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ];
    
    $context = stream_context_create($contextOptions);
    
    // 尝试获取图片
    $imageData = @file_get_contents($randomImageUrl, false, $context);
    
    if ($imageData === false) {
        // 记录错误日志
        error_log("Failed to fetch image: $randomImageUrl");
        
        // 返回一个默认错误图片
        header('Content-Type: image/png');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        exit;
    }

    // 检测真实的MIME类型
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->buffer($imageData);
    
    // 设置正确的Content-Type
    header('Content-Type: ' . $mimeType);
    echo $imageData;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        #imageContainer {
            min-height: 300px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 15px 0;
        }
        #randomImage {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .loading {
            display: none;
            color: #666;
            margin: 20px 0;
        }
        .error {
            color: #d9534f;
            padding: 15px;
            background-color: #f8d7da;
            border-radius: 4px;
            margin: 15px 0;
        }
        .refresh-btn {
            padding: 10px 20px;
            background-color: #5cb85c;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .refresh-btn:hover {
            background-color: #4cae4c;
        }
        .refresh-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <p>每次刷新都会显示不同的随机图片</p>
        
        <div id="imageContainer">
            <img src="?imageonly=1&t=<?= time() ?>" alt="随机图片" id="randomImage">
        </div>
        
        <div id="loadingIndicator" class="loading">
            <p>图片加载中，请稍候...</p>
        </div>
        
        <div id="errorDisplay" style="display:none;" class="error">
            <p id="errorMessage"></p>
        </div>
        
        <button id="refreshBtn" class="refresh-btn">换一张</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const refreshBtn = document.getElementById('refreshBtn');
            const imageContainer = document.getElementById('imageContainer');
            const loadingIndicator = document.getElementById('loadingIndicator');
            const errorDisplay = document.getElementById('errorDisplay');
            const errorMessage = document.getElementById('errorMessage');
            const randomImage = document.getElementById('randomImage');
            
            // 初始加载图片
            loadNewImage();
            
            // 点击按钮事件
            refreshBtn.addEventListener('click', loadNewImage);
            
            // 图片加载错误处理
            randomImage.addEventListener('error', function() {
                loadingIndicator.style.display = 'none';
                errorMessage.textContent = '图片加载失败，请重试或检查图片链接';
                errorDisplay.style.display = 'block';
                refreshBtn.disabled = false;
            });
            
            function loadNewImage() {
                refreshBtn.disabled = true;
                loadingIndicator.style.display = 'block';
                errorDisplay.style.display = 'none';
                
                // 添加时间戳防止缓存
                const newUrl = `?imageonly=1&t=${Date.now()}`;
                
                // 预加载图片
                const img = new Image();
                img.onload = function() {
                    randomImage.src = newUrl;
                    loadingIndicator.style.display = 'none';
                    refreshBtn.disabled = false;
                };
                img.onerror = function() {
                    loadingIndicator.style.display = 'none';
                    errorMessage.textContent = '无法加载图片，请检查网络连接或图片URL';
                    errorDisplay.style.display = 'block';
                    refreshBtn.disabled = false;
                };
                img.src = newUrl;
            }
        });
    </script>
</body>
</html>
