<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escola LMS Database Setup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
        }
        .setup-btn {
            background: #3498db;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            display: block;
            margin: 20px auto;
        }
        .setup-btn:hover {
            background: #2980b9;
        }
        .setup-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        .output {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 400px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .loading {
            text-align: center;
            color: #3498db;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Escola LMS Database Setup</h1>
        
        <p>This will set up your database with all required tables and initial data.</p>
        
        <button id="setupBtn" class="setup-btn" onclick="setupDatabase()">
            Start Database Setup
        </button>
        
        <div id="output" class="output" style="display: none;"></div>
    </div>

    <script>
        async function setupDatabase() {
            const btn = document.getElementById('setupBtn');
            const output = document.getElementById('output');
            
            btn.disabled = true;
            btn.textContent = 'Setting up database...';
            output.style.display = 'block';
            output.textContent = '🔄 Starting database setup...\n';
            
            try {
                const response = await fetch('/setup-database');
                const data = await response.json();
                
                if (data.success) {
                    output.innerHTML = '<span class="success">✅ Setup completed successfully!</span>\n\n';
                    output.innerHTML += data.output.join('\n');
                    btn.textContent = 'Setup Complete!';
                    btn.style.background = '#27ae60';
                } else {
                    output.innerHTML = '<span class="error">❌ Setup failed!</span>\n\n';
                    output.innerHTML += 'Output:\n' + data.output.join('\n');
                    if (data.errors.length > 0) {
                        output.innerHTML += '\n\nErrors:\n' + data.errors.join('\n');
                    }
                    btn.textContent = 'Setup Failed - Try Again';
                    btn.style.background = '#e74c3c';
                    btn.disabled = false;
                }
            } catch (error) {
                output.innerHTML = '<span class="error">❌ Network error!</span>\n\n';
                output.innerHTML += 'Error: ' + error.message;
                btn.textContent = 'Network Error - Try Again';
                btn.style.background = '#e74c3c';
                btn.disabled = false;
            }
        }
    </script>
</body>
</html>