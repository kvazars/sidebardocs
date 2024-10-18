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
      const toDataURL = url => fetch(url)
  .then(response => response.blob())
  .then(blob => new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onloadend = () => resolve(reader.result)
    reader.onerror = reject
    reader.readAsDataURL(blob)
  }))


toDataURL('http://localhost:8000/contentImages/dEHD3n2GHNH6JPSYeqNp7Tfivs3deq1xEF264vFi.jpg')
  .then(dataUrl => {
    console.log('RESULT:', dataUrl)
  })

    },



  };
  </script>