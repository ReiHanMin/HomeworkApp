<template>
  <div class="app-container">
    <!-- Header -->
    <header class="header">
      <h1>Homework Assistant</h1>
      <p>Use an image or enter text to get help with your homework questions.</p>
    </header>

    <!-- Upload Section -->
    <div class="card">
      <h2>Upload an Image</h2>
      <div class="upload-section">
        <label for="file-upload" class="custom-file-upload">
          Choose File
        </label>
        <input id="file-upload" type="file" accept="image/*" @change="onFileChange" />
      </div>
    </div>

    <!-- Image Preview and Crop Option -->
    <div class="card" v-if="image && !croppedImage && !isCropping">
      <h2>Image Preview</h2>
      <img :src="image" alt="Uploaded Image Preview" class="image-preview" />
      <div class="button-group">
        <button @click="cropImage" class="crop-button">Crop Image</button>
        <button @click="uploadImage" class="upload-button">Upload Without Cropping</button>
      </div>
    </div>

    <!-- Image Cropper -->
    <div class="cropper-container" v-if="image && !croppedImage && isCropping">
      <img ref="image" :src="image" alt="Captured Image" class="image-to-crop" />
      <div class="button-group">
        <button @click="confirmCrop" class="confirm-crop-button">Confirm Crop</button>
        <button @click="cancelCrop" class="cancel-crop-button">Cancel Crop</button>
      </div>
    </div>

    <!-- Cropped Image Preview -->
    <div class="card" v-if="croppedImage">
      <h2>Cropped Image Preview</h2>
      <img :src="croppedImage" alt="Cropped Image Preview" class="image-preview" />
      <div class="button-group">
        <button @click="recropImage" class="recrop-button">Recrop Image</button>
        <button @click="uploadImage" class="upload-button">Upload Cropped Image</button>
        <button @click="resetImage" class="reset-button">Reset and Start Over</button>
      </div>
    </div>

    <!-- User Input Section -->
    <div class="card">
      <h2>Or Enter Your Question Manually</h2>
      <textarea v-model="manualInput" placeholder="Enter your question here..." rows="4"></textarea>
      <button @click="submitText" class="submit-button">Submit Text</button>
    </div>

    <!-- Results Section -->
    <div v-if="ocrText" class="card">
      <h2>Extracted Text</h2>
      <p>{{ ocrText }}</p>
    </div>

    <div v-if="gptResponse" class="card">
      <h2>Generated Answer</h2>
      <div v-html="formattedAnswer"></div>
    </div>
  </div>
</template>

<script>
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

export default {
  data() {
    return {
      image: null,
      croppedImage: null,
      ocrText: '',
      gptResponse: '',
      manualInput: '',
      cropper: null,
      isCropping: false,
    };
  },
  computed: {
    formattedAnswer() {
      return this.gptResponse
        .split("\n")
        .map(line => `<p>${line.replace(/"(.*?)"/g, '<strong>"$1"</strong>')}</p>`)
        .join("");
    },
  },
  methods: {
    onFileChange(event) {
      this.image = URL.createObjectURL(event.target.files[0]);
      this.croppedImage = null;  // Reset cropped image when a new file is selected
      this.isCropping = false;   // Reset cropping state
    },
    cropImage() {
      this.isCropping = true; // Enable cropping mode

      // Use nextTick to ensure the DOM is updated before initializing the Cropper
      this.$nextTick(() => {
        const imageElement = this.$refs.image;
        if (imageElement) {
          this.cropper = new Cropper(imageElement, {
            aspectRatio: NaN, // Free aspect ratio
            viewMode: 1,
            autoCropArea: 1,
          });
        } else {
          console.error("Image element not found");
        }
      });
    },
    confirmCrop() {
      if (this.cropper) {
        this.croppedImage = this.cropper.getCroppedCanvas().toDataURL('image/png');
        this.cropper.destroy();
        this.cropper = null;
        this.isCropping = false; // Disable cropping mode
      }
    },
    cancelCrop() {
      if (this.cropper) {
        this.cropper.destroy();
        this.cropper = null;
        this.isCropping = false; // Disable cropping mode
      }
    },
    recropImage() {
      this.croppedImage = null;  // Remove the cropped image
      this.isCropping = true;    // Re-enable cropping mode
      this.cropImage();          // Restart the cropping process
    },
    resetImage() {
      this.image = null;        // Remove the original image
      this.croppedImage = null; // Remove the cropped image
      this.isCropping = false;  // Reset cropping state
    },
    async uploadImage() {
      const formData = new FormData();
      const blob = await fetch(this.croppedImage || this.image).then((r) => r.blob());
      formData.append('image', blob, 'cropped-image.png');

      try {
        const response = await fetch('/process-image', {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: formData,
        });

        const data = await response.json();
        this.ocrText = data.text;
        this.gptResponse = data.gptResponse;
      } catch (error) {
        console.error('Error uploading image:', error);
      }
    },
    async submitText() {
      try {
        const response = await fetch('/process-text', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
          },
          body: JSON.stringify({ text: this.manualInput }),
        });

        const data = await response.json();
        this.ocrText = this.manualInput;
        this.gptResponse = data.gptResponse;
      } catch (error) {
        console.error('Error processing text:', error);
      }
    },
  },
};
</script>


<style scoped>
/* Global Styles */
body {
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: #f4f4f9;
  color: #333;
  line-height: 1.6;
}

/* App Container */
.app-container {
  max-width: 900px;
  margin: 40px auto;
  padding: 0 20px;
}

/* Header */
.header {
  text-align: center;
  margin-bottom: 30px;
}

.header h1 {
  font-size: 36px;
  color: #007bff;
  margin-bottom: 10px;
}

.header p {
  font-size: 18px;
  color: #666;
}

/* Card Layout */
.card {
  background-color: white;
  padding: 20px;
  border-radius: 10px;
  margin-bottom: 20px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  align-items: center;
}

/* Button Styles */
.custom-file-upload,
.submit-button,
.upload-button,
.crop-button,
.confirm-crop-button,
.cancel-crop-button,
.recrop-button,
.reset-button {
  padding: 12px 25px;
  color: white;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
  font-size: 16px;
  transition: background-color 0.3s ease;
  border: none;
  margin-top: 10px;
  width: calc(100% - 20px);
  max-width: 300px;
}

/* Primary Action Buttons */
.custom-file-upload,
.upload-button {
  background-color: #007bff; /* Blue for primary actions */
}

.custom-file-upload:hover,
.upload-button:hover {
  background-color: #0056b3;
}

/* Secondary Action Button */
.crop-button {
  background-color: #28a745; /* Green for cropping action */
}

.crop-button:hover {
  background-color: #218838;
}

/* Alternative Action Button */
.confirm-crop-button {
  background-color: #ffc107; /* Yellow for confirming crop */
  margin-right: 10px; /* Add some space between confirm and cancel */
}

.confirm-crop-button:hover {
  background-color: #e0a800;
}

/* Cancel Action Button */
.cancel-crop-button {
  background-color: #dc3545; /* Red for cancel action */
}

.cancel-crop-button:hover {
  background-color: #c82333;
}

/* Recrop and Reset Buttons */
.recrop-button {
  background-color: #6c757d; /* Gray for recrop */
  margin-right: 10px; /* Add some space between recrop and reset */
}

.recrop-button:hover {
  background-color: #5a6268;
}

.reset-button {
  background-color: #343a40; /* Darker gray for reset */
}

.reset-button:hover {
  background-color: #23272b;
}
.submit-button {
  background-color: #007bff; /* Blue for active state */
}

.submit-button:hover {
  background-color: #0056b3; /* Darker blue on hover */
}

/* If there is a disabled state */
.submit-button:disabled {
  background-color: #e9ecef; /* Light gray for disabled state */
  color: #6c757d; /* Text color for disabled state */
  cursor: not-allowed; /* Cursor style for disabled button */
}


#file-upload {
  display: none;
}

textarea {
  width: calc(100% - 20px);
  max-width: 300px;
  padding: 10px;
  margin-bottom: 20px;
  border-radius: 5px;
  border: 1px solid #ddd;
  font-size: 16px;
  font-family: 'Arial', sans-serif;
  resize: none;
}

/* Image Preview */
.image-preview {
  max-width: 100%;
  height: auto;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

/* Media Queries for Mobile Responsiveness */
@media (max-width: 768px) {
  .app-container {
    max-width: 100%;
    margin: 20px auto;
    padding: 0 10px;
  }

  .header h1 {
    font-size: 28px;
  }

  .header p {
    font-size: 16px;
  }

  .card {
    padding: 15px;
    margin-bottom: 15px;
  }

  .card h2 {
    font-size: 20px;
  }

  .upload-section {
    flex-direction: column;
    gap: 10px;
  }

  .custom-file-upload,
  .submit-button,
  .upload-button,
  .crop-button,
  .confirm-crop-button,
  .cancel-crop-button,
  .recrop-button,
  .reset-button {
    width: 100%;
    padding: 12px;
    font-size: 14px;
    margin-top: 5px;
  }
}
</style>
