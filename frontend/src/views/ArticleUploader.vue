<template>
  <div class="fullscreen-bg">
    <div class="upload-container">
      <div class="upload-card">
        <!-- Header -->
        <div class="card-header">
          <i class="fas fa-upload header-icon"></i>
          <h1 class="header-title">Upload Artikel dari Excel</h1>
        </div>

        <!-- Token Input -->
        <div class="token-section">
          <label for="token" class="token-label">Token Hashnode Anda:</label>
          <input type="text" id="token" v-model="token" class="token-input" placeholder="Masukkan token Hashnode Anda">

          <button class="save-token-button" @click="saveToken">
            <i class="fas fa-save"></i>
            Simpan Token
          </button>

          <label for="publishId" class="token-label">Publish ID (Opsional):</label>
          <input type="text" id="publishId" v-model="publishId" class="token-input" placeholder="Masukkan publish ID jika ada">

          <button class="save-token-button" @click="savePublishId">
            <i class="fas fa-save"></i>
            Simpan Publish ID
          </button>
        </div>

        <!-- Drop Zone -->
        <div class="drop-zone" @dragover.prevent @drop.prevent="handleDrop" @click="triggerFileInput">
          <input type="file" ref="fileInput" @change="handleFile" accept=".xlsx,.xls" class="hidden-input" />
          <div class="drop-content">
            <i class="fas fa-file-excel file-icon"></i>
            <div class="instruction-text">
              <p class="main-instruction">Drag & drop file Excel di sini</p>
              <p class="sub-instruction">atau klik untuk memilih file</p>
            </div>
          </div>
          <div v-if="selectedFile" class="file-preview">
            <i class="fas fa-check-circle check-icon"></i>
            <span class="file-name">{{ selectedFile.name }}</span>
          </div>
        </div>

        <!-- Save as Draft Button -->
        <button class="upload-button" :class="{ disabled: !selectedFile }" @click="saveAsDraft">
          <i class="fas fa-save button-icon"></i>
          <span>Save as Draft</span>
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
      token: localStorage.getItem("hashnode_token") || "",
      publishId: localStorage.getItem("hashnode_publish_id") || ""
    };
  },
  methods: {
    triggerFileInput() {
      this.$refs.fileInput.click();
    },
    handleFile(e) {
      this.selectedFile = e.target.files[0];
    },
    handleDrop(e) {
      const files = e.dataTransfer.files;
      if (files.length) {
        this.selectedFile = files[0];
      }
    },
    saveAsDraft() {
      if (!this.selectedFile || !this.token) {
        alert("Silakan pilih file dan isi token terlebih dahulu.");
        return;
      }

      const formData = new FormData();
      formData.append("file", this.selectedFile);
      formData.append("token", this.token);
      formData.append("publish_id", this.publishId);

      fetch("http://localhost:8000/api/upload-excel", {
        method: "POST",
        headers: {
          Accept: "application/json"
        },
        body: formData
      })
        .then(async (res) => {
          const contentType = res.headers.get("content-type") || "";

          if (res.ok) {
            alert("✅ Artikel disimpan sebagai draft!");
          } else if (contentType.includes("application/json")) {
            const err = await res.json();
            alert("❌ Gagal menyimpan draft: " + (err.message || JSON.stringify(err)));
          } else {
            const errText = await res.text();
            alert("❌ Gagal menyimpan draft (non-JSON): " + errText);
          }
        })
        .catch((err) => {
          alert("❌ Terjadi kesalahan: " + err.message);
        });
    },
    saveToken() {
      if (this.token) {
        localStorage.setItem("hashnode_token", this.token);
        alert("Token berhasil disimpan!");
      } else {
        alert("Token tidak boleh kosong.");
      }
    },
    savePublishId() {
      if (this.publishId) {
        localStorage.setItem("hashnode_publish_id", this.publishId);
        alert("Publish ID berhasil disimpan!");
      } else {
        alert("Publish ID tidak boleh kosong.");
      }
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

.file-preview {
  margin-top: 20px;
  padding: 10px 15px;
  background: #e8f4fd;
  border-radius: 6px;
  color: #3498db;
  display: flex;
  align-items: center;
  justify-content: center;
}

.check-icon {
  color: #27ae60;
  margin-right: 8px;
}

.file-name {
  font-size: 0.95rem;
  word-break: break-word;
  text-align: center;
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
