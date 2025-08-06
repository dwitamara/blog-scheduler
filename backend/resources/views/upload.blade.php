<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Upload Artikel Excel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<div class="fullscreen-bg">
    <div class="upload-container">
        <div class="upload-card">

            <div class="card-header">
                <i class="fas fa-upload header-icon"></i>
                <h1 class="header-title">Upload Artikel dari Excel</h1>
            </div>

            <div class="token-section">
                <label for="token" class="token-label">Token Hashnode Anda:</label>
                <input type="text" id="token" class="token-input" placeholder="Masukkan token Hashnode Anda">

                <label for="publishId" class="token-label">Publish ID:</label>
                <input type="text" id="publishId" class="token-input" placeholder="Masukkan publish ID Anda">
            </div>

            <div class="upload-box">
                <div id="uploadContent" class="upload-content">
                    <div class="drop-zone" id="dropZone">
                        <input type="file" id="fileInput" accept=".xlsx,.xls" class="hidden-input" />
                        <div class="drop-content">
                            <i class="fas fa-file-excel file-icon"></i>
                            <p class="main-instruction">Drag & drop file Excel di sini</p>
                            <p class="sub-instruction">atau klik untuk memilih file</p>
                        </div>
                    </div>
                </div>
            </div>

            <div id="filePreview" class="upload-content" style="display: none;">
                <div class="file-preview-minimal">
                    <i class="fas fa-file-excel file-icon-small"></i>
                    <span id="fileName" class="file-name"></span>
                    <button class="delete-file-btn" onclick="removeFile()">
                        <i class="fas fa-times"></i> Hapus File
                    </button>
                </div>
            </div>

            <button class="upload-button" id="uploadBtn" onclick="saveAsDraft()">
                <i class="fas fa-save button-icon"></i>
                <span id="buttonText">Save as Draft</span>
            </button>

            <div id="statusMessage" class="status-box" style="display: none;"></div>

            <div class="card-footer">
                <p class="footer-text">
                    <i class="fas fa-info-circle info-icon"></i>
                    Format yang didukung: .xlsx, .xls (Maks. 10MB)
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    let selectedFile = null;

    const fileInput = document.getElementById('fileInput');
    const uploadContent = document.getElementById('uploadContent');
    const filePreview = document.getElementById('filePreview');
    const fileNameDisplay = document.getElementById('fileName');
    const statusBox = document.getElementById('statusMessage');
    const buttonText = document.getElementById('buttonText');

    document.getElementById('dropZone').addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', (e) => {
        selectedFile = e.target.files[0];
        if (selectedFile) {
            fileNameDisplay.textContent = selectedFile.name;
            uploadContent.style.display = 'none';
            filePreview.style.display = 'block';
        }
    });

    document.getElementById('dropZone').addEventListener('dragover', (e) => e.preventDefault());
    document.getElementById('dropZone').addEventListener('drop', (e) => {
        e.preventDefault();
        selectedFile = e.dataTransfer.files[0];
        if (selectedFile.name.endsWith('.xlsx') || selectedFile.name.endsWith('.xls')) {
            fileInput.files = e.dataTransfer.files;
            fileNameDisplay.textContent = selectedFile.name;
            uploadContent.style.display = 'none';
            filePreview.style.display = 'block';
        }
    });

    function removeFile() {
        selectedFile = null;
        fileInput.value = null;
        uploadContent.style.display = 'block';
        filePreview.style.display = 'none';
    }

    function setStatus(message, success) {
        statusBox.textContent = message;
        statusBox.style.display = 'block';
        statusBox.className = 'status-box ' + (success ? 'success' : 'error');
    }

    async function saveAsDraft() {
        const token = document.getElementById('token').value;
        const publishId = document.getElementById('publishId').value;

        if (!selectedFile || !token) {
            setStatus('Token dan file wajib diisi.', false);
            return;
        }

        const formData = new FormData();
        formData.append('xlsx_file', selectedFile);
        formData.append('token', token);
        formData.append('publish_id', publishId);

        buttonText.textContent = 'Uploading...';

        try {
            const response = await fetch("{{ url('/upload') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });

            const result = await response.json();

            if (response.ok) {
                setStatus(result.message || 'Berhasil diupload!', true);
                removeFile();
                document.getElementById('token').value = '';
                document.getElementById('publishId').value = '';
            } else {
                setStatus(result.message || 'Gagal mengunggah.', false);
            }
        } catch (error) {
            setStatus('Terjadi kesalahan saat mengunggah.', false);
        } finally {
            buttonText.textContent = 'Save as Draft';
        }
    }
</script>

<style scoped>
/* Semua gaya sebelumnya tetap */
.fullscreen-bg {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #f5f7fa;
  display: flex;
  justify-content: center;
  align-items: center;
  font-family: 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.upload-container {
  width: 100%;
  max-width: 600px;
  padding: 20px;
}

.upload-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.card-header {
  padding: 25px;
  text-align: center;
  background: linear-gradient(135deg, #3498db, #2980b9);
  color: white;
}

.header-icon {
  font-size: 2.8rem;
  margin-bottom: 15px;
}

.header-title {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
}

.token-section {
  padding: 20px 30px 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.token-label {
  font-weight: 600;
  color: #2c3e50;
}

.token-input {
  padding: 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.95rem;
}

.drop-zone {
  padding: 30px;
  cursor: pointer;
}

.hidden-input {
  display: none;
}

.drop-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 40px 20px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.drop-zone:hover .drop-content {
  border-color: #3498db;
  background-color: #f8fafc;
}

.file-icon {
  font-size: 3.5rem;
  color: #27ae60;
  margin-bottom: 20px;
}

.main-instruction {
  font-size: 1.2rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 5px;
}

.sub-instruction {
  font-size: 0.95rem;
  color: #7f8c8d;
}

.file-preview-minimal {
  margin: 20px 30px;
  padding: 10px 15px;
  background: #ecf0f1;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: #2c3e50;
  border: 1px solid #dcdde1;
}

.file-icon-small {
  font-size: 1.5rem;
  color: #27ae60;
  margin-right: 10px;
}

.file-preview-minimal .file-name {
  flex: 1;
  font-size: 0.95rem;
  word-break: break-word;
}

.upload-button {
  width: calc(100% - 60px);
  margin: 0 30px 30px;
  padding: 14px;
  background: linear-gradient(135deg, #27ae60, #219653);
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 1.05rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  transition: all 0.2s ease;
}

.upload-button:hover:not(.disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
}

.upload-button.disabled {
  background: #bdc3c7;
  cursor: not-allowed;
  opacity: 0.7;
}

.card-footer {
  padding: 15px;
  text-align: center;
  background: #f8f9fa;
  border-top: 1px solid #eee;
}

.footer-text {
  margin: 0;
  font-size: 0.85rem;
  color: #7f8c8d;
  display: flex;
  align-items: center;
  justify-content: center;
}

.info-icon {
  margin-right: 6px;
  color: #3498db;
}

.status-box {
  margin: 0 30px 20px;
  padding: 12px;
  border-radius: 6px;
  font-size: 0.95rem;
  text-align: center;
  font-weight: 500;
}

.status-box.success {
  background-color: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.status-box.error {
  background-color: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}
</style>
