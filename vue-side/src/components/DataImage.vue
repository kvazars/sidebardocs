<template>
  <img :src="imageAsBase64" />
</template>

<script>
export default {
  props: ["src", "datasend"],
  data() {
    return {
      imageAsBase64: ""
    };
  },
  mounted() {
    let form = new FormData();
    form.append('image', this.src);
    this.datasend('getImage', 'POST', form).then(res => {
      console.log(res);
this.imageAsBase64 = res.image;
    }).catch(error => {
      console.log(error);
    });
    // const myHeaders = new Headers();
    // // if (this.store.token) {
    // //   myHeaders.append("Authorization", `Bearer ${this.store.token}`);
    // // }
    // myHeaders.append("Access-Control-Allow-Origin", "*");
    // const requestOptions = {
    //   // method: method,
    //   // mode: 'no-cors',
    //   headers: myHeaders,
    // };
    // const toDataURL = url => fetch(url, {method:'POST'}, form)
    //   .then(response => response.json())
    //   .then(blob => new Promise((resolve, reject) => {
    //     const reader = new FileReader()
    //     reader.onloadend = () => resolve(reader.result)
    //     reader.onerror = reject
    //     reader.readAsDataURL(blob)
    //   }))


    // toDataURL('http://localhost:8000/api/getImage')
    //   .then(dataUrl => {
    //     console.log('RESULT:', dataUrl)
    //     this.imageAsBase64 = dataUrl
    //   })

    // this.downloadImage(this.src);
  },
  methods: {
    // async fetchImage(url) {
    //   const response = await fetch(url)
    //   const blob = await response.blob()

    //   return blob
    // },
    // async downloadImage(url) {
    //   const imageBlob = await this.fetchImage(url)
    //   const imageBase64 = URL.createObjectURL(imageBlob)

    //   console.log({ imageBase64 })

    //   const a = document.createElement('a')
    //   a.style.setProperty('display', 'none')
    //   document.body.appendChild(a)
    //   a.download = url.replace(/^.*[\\\/]/, '')
    //   a.href = imageBase64
    //   a.click()
    //   a.remove()
    // }
  },


};
</script>