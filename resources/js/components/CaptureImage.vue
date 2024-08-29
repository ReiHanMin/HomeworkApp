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
        <button @click="cropImage" class="crop-button" v-if="image">Crop Image</button>
      </div>
    </div>

    <!-- Image Cropper -->
    <div class="cropper-container" v-if="image && !croppedImage">
      <img ref="image" :src="image" alt="Captured Image" class="image-to-crop" />
      <button @click="confirmCrop" class="confirm-crop-button">Confirm Crop</button>
    </div>

    <!-- Cropped Image Preview -->
    <div class="card" v-if="croppedImage">
      <h2>Cropped Image Preview</h2>
      <img :src="croppedImage" alt="Cropped Image Preview" class="image-preview" />
      <button @click="uploadImage" class="upload-button">Upload Cropped Image</button>
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
    },
    cropImage() {
      if (this.cropper) {
        this.cropper.destroy();
      }

      const imageElement = this.$refs.image;
      this.cropper = new Cropper(imageElement, {
        aspectRatio: NaN, // Free aspect ratio
        viewMode: 1,
        autoCropArea: 1,
      });
    },
    confirmCrop() {
      if (this.cropper) {
        this.croppedImage = this.cropper.getCroppedCanvas().toDataURL('image/png');
        this.cropper.destroy();
        this.cropper = null;
      }
    },
    async uploadImage() {
      const formData = new FormData();
      const blob = await fetch(this.croppedImage).then((r) => r.blob());
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
}

.card h2 {
  font-size: 24px;
  color: #333;
  margin-bottom: 15px;
}

/* Upload Section */
.upload-section {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
  margin-bottom: 20px;
  flex-wrap: wrap; /* Makes items wrap on small screens */
}

.custom-file-upload,
.submit-button,
.upload-button {
  padding: 12px 25px;
  background-color: #007bff;
  color: white;
  border-radius: 5px;
  cursor: pointer;
  text-align: center;
  font-size: 16px;
  transition: background-color 0.3s ease;
  border: none;
  margin-top: 10px; /* Added margin for mobile spacing */
}

.custom-file-upload:hover,
.submit-button:hover,
.upload-button:hover {
  background-color: #0056b3;
}

#file-upload {
  display: none;
}

textarea {
  width: 100%;
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

/* Results Section */
.results-card {
  background-color: #f8f9fa;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.3s ease;
}

.results-card:hover {
  box-shadow: 0px 6px 12px rgba(0, 0, 0, 0.2);
}

.results-card h2 {
  font-size: 20px;
  margin-bottom: 10px;
  color: #333;
}

/* Answer Card Styles */
.answer-card {
  background-color: #ffffff;
  padding: 15px;
  border-radius: 8px;
  margin-bottom: 15px;
  box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.answer-card:hover {
  transform: translateY(-5px);
  box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.1);
}

.answer-card p {
  font-size: 16px;
  line-height: 1.6;
  color: #555;
  text-align: justify;
  margin-bottom: 10px;
}

.answer-card strong {
  color: #333;
  font-weight: bold;
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
    flex-direction: column; /* Stack items vertically on mobile */
    gap: 10px;
  }

  .custom-file-upload,
  .submit-button,
  .upload-button {
    width: 100%;
    padding: 10px;
    font-size: 14px;
  }

  .answer-card {
    padding: 10px;
    margin-bottom: 10px;
  }

  .answer-card p {
    font-size: 14px;
  }
}
.image-to-crop {
  max-width: 100%;
  height: auto;
  border: 1px solid #ddd;
  border-radius: 8px;
  box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.confirm-crop-button {
  padding: 12px 25px;
  background-color: #28a745;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s ease;
  margin-top: 10px;
}

.confirm-crop-button:hover {
  background-color: #218838;
}


/* Cropper container styles */
.cropper-container {
  text-align: center;
  margin-bottom: 20px;
}



/* Button Styles */
.custom-file-upload,
.submit-button,
.upload-button,
.crop-button,  /* Added crop-button styling */
.confirm-crop-button {
  padding: 12px 25px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 16px;
  transition: background-color 0.3s ease;
  margin-top: 10px;
  margin-right: 5px; /* Adds slight spacing between buttons */
}

.custom-file-upload:hover,
.submit-button:hover,
.upload-button:hover,
.crop-button:hover, /* Hover styling for crop button */
.confirm-crop-button:hover {
  background-color: #0056b3;
}

#file-upload {
  display: none;
}

textarea {
  width: 100%;
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

</style>
