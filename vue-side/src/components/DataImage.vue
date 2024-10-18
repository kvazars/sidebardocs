<template>
    <img :src="'data:image/png;'+ imageAsBase64" />
  </template>
  
  <script>

  
  export default {
    props: ["src"],
    data() {
      return {
        imageAsBase64: ""
      };
    },
    mounted() {
        const toDataURL = url => fetch(url, { mode: 'no-cors'})
  .then(response => response.blob())
  .then(blob => new Promise((resolve, reject) => {

    
    const reader = new FileReader()
    reader.onloadend = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(blob)
  }))


toDataURL(this.src)
  .then(dataUrl => {
    this.imageAsBase64 = dataUrl;
  })
    //   this.imageAsBase64 = fs.readFileSync("", "base64");
    }
  };
  </script>