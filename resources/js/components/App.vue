<template>
  <div id="app">
    <!-- Include the CaptureImage component -->
    <CaptureImage />
  </div>
</template>

<script>
// Import the CaptureImage component
import CaptureImage from '../components/CaptureImage.vue';

export default {
  name: 'App',
  components: {
    CaptureImage, // Register the component
  },
};
</script>

<style scoped>
/* Add some basic styling for the app */
#app {
  font-family: Avenir, Helvetica, Arial, sans-serif;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
