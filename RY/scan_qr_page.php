<?php
session_start();
include("conn.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: ../WS/login.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>

    <style>
        * { box-sizing: border-box; }

        :root {
            --eco-green: #2e7d32;
            --light-green: #4caf50;
            --accent-green: #81c784;
            --background: #f1f8e9;
            --card-bg: #ffffff;
            --text-dark: #1b5e20;
            --text-medium: #388e3c;
            --text-light: #666;
            --shadow: 0 2px 8px rgba(46, 125, 50, 0.1);
            --border-radius: 12px;
        }

        body {
            margin: 0;
            background: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
        }

        a { text-decoration: none; }

        .container { 
            display: flex; 
            min-height: 100vh; 
            position: relative; 
            overflow-x: hidden; 
        }

        .main { 
            margin-left: 0; 
            padding: 30px; 
            width: 100%; 
        }
        
        .header{
            display: flex;
            align-items: center;       
            justify-content: center;   
            gap: 20px;                 
            position: relative;
        }

        .backBtn{
            position:absolute;
            left:0%;
            border:none;
            font-size:30px;
            cursor:pointer;
            background: var(--background);
        }

        hr {
            height: 3px;
            background: var(--eco-green);
            border: none;
        }
        
        .scanner-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow);
            max-width: 600px;
            width: 100%; 
            margin: 0 auto;
            border-top: 5px solid var(--eco-green);
            text-align: center;
        }

        #reader {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: #f0f0f0;
            border: none !important;
        }

        #reader video {
            max-width: 100% !important;
            height: auto !important;
            border-radius: 12px;
            object-fit: cover;
        }
        
        #reader button {
            background-color: var(--eco-green) !important;
            color: white !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 20px !important;
            font-size: 14px !important;
            cursor: pointer !important;
            margin-top: 10px !important;
            font-family: inherit !important;
        }

        #reader select {
            padding: 8px;
            border-radius: 8px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            max-width: 100%;
        }

        .result-container {
            margin-top: 25px;
            padding: 20px;
            background-color: #e8f5e9;
            border-radius: 10px;
            border: 1px dashed var(--eco-green);
        }

        .result-label {
            font-size: 14px;
            color: var(--text-light);
            margin-bottom: 5px;
        }

        #result {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--text-dark);
            word-break: break-all; 
        }

        @media (max-width: 600px) {
            .main { padding: 15px; }
            
            .page-title h2 { font-size: 18px; line-height: 1.2; }
            .page-title p { display: none; }

            .scanner-card { padding: 20px 15px; }
        }
    </style>
</head>

<body>

    <div class="container">
        <main class="main">
            <div class='header'>
                <button type='button' class='backBtn' onclick="window.location.href='../Aston/profile.php'">←</button>
                <h2>QR Scanner</h2>
            </div>
            <hr><br><br>

            <div class="scanner-card">
                
                <div id="reader"></div>
                
                <div class="result-container">
                    <div class="result-label">Scanned Result:</div>
                    <div id="result">Waiting for scan...</div>
                </div>

            </div>

        </main>
    </div>

    <script>
        function onScanSuccess(decodedText, decodedResult) {
            document.getElementById('result').innerText = decodedText;
            document.getElementById('result').style.color = '#2e7d32'; 
        }

        function onScanFailure(error) {
            console.warn(`Code scan error = ${error}`);
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader", 
            { fps: 10, qrbox: 250 }
        );

        html5QrcodeScanner.render(onScanSuccess, onScanFailure);
    </script>

</body>
</html>