<template>
  <div class="fullscreen-bg">
    <div class="upload-container">
      <div class="upload-card">
        <!-- Header -->
        <div class="card-header">
          <i class="fas fa-upload header-icon"></i>
          <h1 class="header-title">Upload Artikel dari Excel</h1>
        </div>

<!-- Token & Publish ID Input -->
<div class="token-section">
  <label for="token" class="token-label">Token Hashnode Anda:</label>
  <input type="text" id="token" v-model="token" class="token-input" placeholder="Masukkan token Hashnode Anda">

  <label for="publishId" class="token-label">Publish ID:</label>
  <input type="text" id="publishId" v-model="publishId" class="token-input" placeholder="Masukkan publish ID Anda">
</div>


        <!-- Upload Box -->
        <div class="upload-box">
          <div v-if="!selectedFile" class="upload-content">
            <div class="drop-zone" @dragover.prevent @drop.prevent="handleDrop" @click="triggerFileInput">
              <input type="file" ref="fileInput" @change="handleFile" accept=".xlsx,.xls" class="hidden-input" />
              <div class="drop-content">
                <i class="fas fa-file-excel file-icon"></i>
                <p class="main-instruction">Drag & drop file Excel di sini</p>
                <p class="sub-instruction">atau klik untuk memilih file</p>
              </div>
            </div>
          </div>

          <!-- File Preview -->
          <div v-else class="upload-content">
            <div class="file-preview-minimal">
              <i class="fas fa-file-excel file-icon-small"></i>
              <span class="file-name">{{ selectedFile.name }}</span>
              <button class="delete-file-btn" @click="removeFile">
                <i class="fas fa-times"></i> Hapus File
              </button>
            </div>
          </div>
        </div>

        <!-- Save as Draft Button -->
        <button class="upload-button" :disabled="!selectedFile || loading" @click="saveAsDraft">
          <i class="fas fa-save button-icon"></i>
          <span v-if="!loading">Save as Draft</span>
          <span v-else>Uploading...</span>
        </button>

        <!-- Footer -->
        <div class="card-footer">
          <p class="footer-text">
            <i class="fas fa-info-circle info-icon"></i>
            Format yang didukung: .xlsx, .xls (Maks. 10MB)
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      selectedFile: null,
      token: '',
      publishId: '',
      loading: false,
      statusMessage: '',
      isSuccess: false,
      isError: false
    };
  },
  methods: {
    handleFile(event) {
      this.selectedFile = event.target.files[0];
    },
    handleDrop(e) {
      const file = e.dataTransfer.files[0];
      if (file && (file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
        this.selectedFile = file;
      }
    },
    triggerFileInput() {
      this.$refs.fileInput.click();
    },
    removeFile() {
      this.selectedFile = null;
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = null;
      }
    },
async saveAsDraft() {
  if (!this.selectedFile || !this.token) {
    this.setStatus('Token dan file wajib diisi.', false);
    return;
  }

  const formData = new FormData();
  formData.append('xlsx_file', this.selectedFile);
  formData.append('token', this.token);
  formData.append('publish_id', this.publishId);

  this.loading = true;

  try {
    const response = await fetch('http://127.0.0.1:8000/api/upload-excel', {
      method: 'POST',
      body: formData,
    });

    const result = await response.json();
    console.log("HASIL:", result); // Tambahan debug

    if (response.ok) {
      this.setStatus(result.message || 'Berhasil diupload!', true);
      this.resetForm(); // Reset file dan input
    } else {
      this.setStatus(result.message || 'Gagal mengunggah.', false);
    }
  } catch (error) {
    console.error("Upload error:", error); // Untuk debug DevTools
    this.setStatus('Terjadi kesalahan saat mengunggah.', false);
  } finally {
    this.loading = false;
  }
},

    setStatus(message, success) {
      this.statusMessage = message;
      this.isSuccess = success;
      this.isError = !success;
    }
  }
};
</script>


<style scoped>
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

.save-token-button {
  align-self: flex-start;
  background: #3498db;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.2s ease;
}

.save-token-button:hover {
  background: #2980b9;
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

.instruction-text {
  text-align: center;
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

.remove-icon {
  color: #e74c3c;
  font-size: 1.2rem;
  cursor: pointer;
  margin-left: 10px;
  transition: color 0.2s ease;
}

.remove-icon:hover {
  color: #c0392b;
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
</style>